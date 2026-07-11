<?php
/**
 * REST API 経由でブロックを取得した際に、CSS を出力HTMLへインライン同梱する。
 *
 * 通常のフロント表示では enqueue 済みの build/style.css が使われるが、
 * WordPress REST API が返す content.rendered には enqueue したスタイルは乗らない。
 * そのため REST リクエスト時のみ、madoguchi/* ブロックを含む投稿本文の先頭に
 * コンパイル済み CSS を <style> として差し込み、
 * headless / 他サイトへ配信しても同一デザインを再現できるようにする。
 *
 * the_content フィルタ（投稿ごとに実行）にフックすることで、
 * REST の複数投稿レスポンス（一覧・アーカイブ等）でも各投稿にCSSを同梱する。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Madoguchi_Blocks_Style_Inliner {

	/**
	 * 本プラグインが提供するブロック名。
	 *
	 * @var string[]
	 */
	private $block_names = array(
		'madoguchi/checklist-cta',
		'madoguchi/condition-cards',
		'madoguchi/condition-card',
		'madoguchi/author-box',
		'madoguchi/review-section',
		'madoguchi/comparison-table',
		'madoguchi/cta-button',
		'madoguchi/recommend-card',
	);

	/**
	 * 読み込み済み CSS のキャッシュ（1リクエスト内の複数投稿で再利用）。
	 *
	 * @var string|null
	 */
	private $style_cache = null;

	/**
	 * フィルタを登録する。
	 * do_blocks（優先度9）より前に差し込むため優先度5で登録する。
	 * <style> 内は wptexturize（優先度10）の非対象タグのため安全。
	 */
	public function init() {
		add_filter( 'the_content', array( $this, 'maybe_inline' ), 5 );
	}

	/**
	 * REST レスポンスの場合、madoguchi/* ブロックを含む本文の先頭へ CSS を差し込む。
	 *
	 * @param string $content 投稿本文。
	 * @return string
	 */
	public function maybe_inline( $content ) {
		// REST API（content.rendered）でのみインライン。通常フロントは enqueue 済みCSSを使う。
		if ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $content;
		}
		if ( ! $this->has_our_block( $content ) ) {
			return $content;
		}

		return $this->build_style_tag() . $content;
	}

	/**
	 * 本文に本プラグインのブロックが含まれるか判定する。
	 *
	 * @param string $content 投稿本文（ブロックコメントを含む生データ）。
	 * @return bool
	 */
	private function has_our_block( $content ) {
		foreach ( $this->block_names as $name ) {
			if ( has_block( $name, $content ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * インライン用の <style> 文字列を生成する。
	 *
	 * REST配信用に変換済みの build/style-rest.css（rem→px・!important化・
	 * スコープ付きリセット入り。配信先サイトのテーマCSSの影響を受けない）を優先し、
	 * 無ければ build/style.css にフォールバックする。
	 * 相対画像パスを絶対URLへ書き換え、先頭にブランドカラーのカスタムプロパティを付与する。
	 *
	 * @return string
	 */
	private function build_style_tag() {
		if ( null === $this->style_cache ) {
			$css_file = MADOGUCHI_BLOCKS_DIR . 'build/style-rest.css';
			if ( ! file_exists( $css_file ) ) {
				$css_file = MADOGUCHI_BLOCKS_DIR . 'build/style.css';
			}
			$css = file_exists( $css_file ) ? file_get_contents( $css_file ) : '';
			// インライン化するとページURL基準になるため、画像の相対パスを絶対URLへ変換する。
			$this->style_cache = str_replace( '../assets/img/', MADOGUCHI_BLOCKS_URL . 'assets/img/', $css );
		}

		$root = ':root{--md-brand:' . madoguchi_blocks_brand_color() . ';}';

		return '<style id="madoguchi-blocks-inline">' . $root . $this->style_cache . '</style>';
	}
}
