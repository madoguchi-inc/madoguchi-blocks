<?php
/**
 * Plugin Name:       Madoguchi Blocks（記事内コンテンツブロック集）
 * Description:        記事内に設置できるカスタムブロック集。チェックリスト型CTA・条件別カードリンク・買取業者比較テーブル・著者情報・口コミ。テーマ非依存で動作し、REST API 経由でもCSSを同梱して同一デザインを再現します。
 * Version:           1.4.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Madoguchi Inc.
 * Text Domain:       madoguchi-blocks
 * Update URI:        https://github.com/madoguchi-inc/madoguchi-blocks
 *
 * 使い方:
 *   1. この「madoguchi-blocks」フォルダごと wp-content/plugins/ に置く
 *   2. 管理画面 > プラグイン で「Madoguchi Blocks」を有効化
 *   3. 投稿/固定ページの編集画面、ブロック挿入メニューのカテゴリー「Madoguchi Blocks」に各ブロックが追加される
 *   4. 管理画面 > 設定 > Madoguchi Blocks でメディア別のブランドカラーを設定できる
 *
 * 備考: ブロックの名前空間は madoguchi/* です（旧 fuyouhin/* から変更）。
 *       テーマ側が提供する fuyouhin/* ブロックとは名前が衝突しないため、併用できます。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MADOGUCHI_BLOCKS_DIR', plugin_dir_path( __FILE__ ) );
define( 'MADOGUCHI_BLOCKS_URL', plugin_dir_url( __FILE__ ) );

require_once MADOGUCHI_BLOCKS_DIR . 'inc/settings.php';
require_once MADOGUCHI_BLOCKS_DIR . 'inc/class-style-inliner.php';
require_once MADOGUCHI_BLOCKS_DIR . 'inc/review-avatars.php';

/**
 * GitHub リリースを更新元とした自動更新を有効化する。
 *
 * 専用リポジトリ madoguchi-inc/madoguchi-blocks の最新リリースを監視し、
 * リリースに添付したビルド済み zip を更新パッケージとして配信する。
 * これにより各サイトの「プラグイン」画面に WordPress 標準の更新通知が出る
 * （「自動更新を有効化」を ON にすれば wp-cron で完全自動更新になる）。
 */
function madoguchi_blocks_boot_update_checker() {
	require_once MADOGUCHI_BLOCKS_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

	$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/madoguchi-inc/madoguchi-blocks/',
		__FILE__,
		'madoguchi-blocks'
	);

	// リリースのタグ名をバージョンとして扱い、添付したビルド済み zip を
	// 更新パッケージとしてダウンロードさせる（ソースzipは使わない）。
	$update_checker->getVcsApi()->enableReleaseAssets();

	// private リポジトリへ切り替える場合の任意トークン。
	// public 配布では不要。wp-config.php に定数を定義した時だけ認証する。
	if ( defined( 'MADOGUCHI_BLOCKS_GH_TOKEN' ) && MADOGUCHI_BLOCKS_GH_TOKEN ) {
		$update_checker->setAuthentication( MADOGUCHI_BLOCKS_GH_TOKEN );
	}
}
madoguchi_blocks_boot_update_checker();

/**
 * ブロック挿入メニューに専用カテゴリー「Madoguchi Blocks」を追加する。
 *
 * 各ブロックの block.json は "category": "madoguchi" を指定しているため、
 * このカテゴリーを登録しておくと全ブロックが1か所にまとまって表示される。
 * 先頭に差し込み、挿入メニューで見つけやすくする。
 *
 * @param array $categories 既存のカテゴリー配列。
 * @return array
 */
function madoguchi_blocks_register_category( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'madoguchi',
			'title' => __( 'Madoguchi Blocks', 'madoguchi-blocks' ),
			'icon'  => null,
		)
	);

	return $categories;
}
add_filter( 'block_categories_all', 'madoguchi_blocks_register_category' );

/**
 * 共有スクリプト/スタイルを登録し、各ブロックを block.json から登録する。
 */
function madoguchi_blocks_register() {

	$asset_file = MADOGUCHI_BLOCKS_DIR . 'build/index.asset.php';
	$asset      = file_exists( $asset_file )
		? include $asset_file
		: array( 'dependencies' => array(), 'version' => '1.1.0' );

	// エディタ用スクリプト（全ブロックをまとめて登録）
	wp_register_script(
		'madoguchi-blocks-editor',
		MADOGUCHI_BLOCKS_URL . 'build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	// 著者テンプレートをエディタへ供給する（著者情報ブロックのテンプレート選択用）。
	if ( function_exists( 'madoguchi_blocks_author_templates' ) ) {
		wp_localize_script(
			'madoguchi-blocks-editor',
			'madoguchiBlocksData',
			array(
				'authorTemplates' => array_values( madoguchi_blocks_author_templates() ),
			)
		);
	}

	// フロント＋エディタ共通スタイル
	// CSSのみ変更したときもキャッシュが切れるよう、style.css のファイル更新時刻をバージョンに使う
	$style_file = MADOGUCHI_BLOCKS_DIR . 'build/style.css';
	$style_ver  = file_exists( $style_file ) ? (string) filemtime( $style_file ) : $asset['version'];
	wp_register_style(
		'madoguchi-blocks-style',
		MADOGUCHI_BLOCKS_URL . 'build/style.css',
		array(),
		$style_ver
	);

	// ブランドカラーをカスタムプロパティとして通常フロント/エディタへ供給する。
	// （REST API 経由の場合は class-style-inliner.php 側でインライン注入する）
	wp_add_inline_style(
		'madoguchi-blocks-style',
		':root{--md-brand:' . madoguchi_blocks_brand_color() . ';}'
	);

	// フロント用スクリプト（チェックリストのチェックトグル）
	wp_register_script(
		'madoguchi-blocks-view',
		MADOGUCHI_BLOCKS_URL . 'build/view.js',
		array(),
		$asset['version'],
		true
	);

	// 静的ブロック（save 済みマークアップ）:
	//   子（condition-card）→ 親（condition-cards）→ checklist の順に登録
	// 動的ブロック（render.php でサーバー生成）:
	//   author-box / review-section / comparison-table
	$blocks = array(
		'condition-card',
		'condition-cards',
		'checklist-cta',
		'author-box',
		'review-section',
		'comparison-row',
		'comparison-table',
		'cta-button',
		'recommend-card',
	);
	foreach ( $blocks as $block ) {
		$dir = MADOGUCHI_BLOCKS_DIR . 'blocks/' . $block;
		// 未ビルドのブロックはスキップ（部分ビルド時の警告を防ぐ）
		if ( file_exists( $dir . '/block.json' ) ) {
			register_block_type( $dir );
		}
	}
}
add_action( 'init', 'madoguchi_blocks_register' );

/**
 * REST API 経由の取得時に CSS を出力へインライン同梱する。
 */
function madoguchi_blocks_boot_style_inliner() {
	$inliner = new Madoguchi_Blocks_Style_Inliner();
	$inliner->init();
}
add_action( 'init', 'madoguchi_blocks_boot_style_inliner' );
