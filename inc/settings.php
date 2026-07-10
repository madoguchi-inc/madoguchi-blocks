<?php
/**
 * ブランドカラー等の設定ページ（Settings API）
 *
 * メディアごとにブロックの主要色を切り替えられるようにする。
 * 保存した色は CSS カスタムプロパティ --md-brand として出力される。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 既定のブランドカラー（元テーマの $color_orange 相当）
if ( ! defined( 'MADOGUCHI_BLOCKS_DEFAULT_BRAND' ) ) {
	define( 'MADOGUCHI_BLOCKS_DEFAULT_BRAND', '#f56f00' );
}

/**
 * 保存済みブランドカラーを取得する（未設定・不正値は既定色にフォールバック）。
 *
 * @return string HEXカラー
 */
function madoguchi_blocks_brand_color() {
	$color = get_option( 'madoguchi_blocks_brand_color', MADOGUCHI_BLOCKS_DEFAULT_BRAND );
	$color = sanitize_hex_color( $color );

	return $color ? $color : MADOGUCHI_BLOCKS_DEFAULT_BRAND;
}

/**
 * 設定項目を登録する。
 */
function madoguchi_blocks_register_settings() {
	register_setting(
		'madoguchi_blocks',
		'madoguchi_blocks_brand_color',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_hex_color',
			'default'           => MADOGUCHI_BLOCKS_DEFAULT_BRAND,
		)
	);

	add_settings_section(
		'madoguchi_blocks_main',
		__( '基本設定', 'madoguchi-blocks' ),
		'__return_false',
		'madoguchi-blocks'
	);

	add_settings_field(
		'madoguchi_blocks_brand_color',
		__( 'ブランドカラー', 'madoguchi-blocks' ),
		'madoguchi_blocks_brand_color_field',
		'madoguchi-blocks',
		'madoguchi_blocks_main'
	);
}
add_action( 'admin_init', 'madoguchi_blocks_register_settings' );

/**
 * ブランドカラー入力フィールドを描画する。
 */
function madoguchi_blocks_brand_color_field() {
	$color = madoguchi_blocks_brand_color();
	printf(
		'<input type="color" name="madoguchi_blocks_brand_color" value="%1$s" />',
		esc_attr( $color )
	);
	echo '<p class="description">' . esc_html__( '各ブロックの主要色（CTAボタン・強調・リンクなど）に使用されます。', 'madoguchi-blocks' ) . '</p>';
}

/**
 * 設定ページを「設定」メニュー配下に追加する。
 */
function madoguchi_blocks_add_menu() {
	add_options_page(
		__( 'Madoguchi Blocks 設定', 'madoguchi-blocks' ),
		__( 'Madoguchi Blocks', 'madoguchi-blocks' ),
		'manage_options',
		'madoguchi-blocks',
		'madoguchi_blocks_settings_page'
	);
}
add_action( 'admin_menu', 'madoguchi_blocks_add_menu' );

/**
 * 設定ページ本体を描画する。
 */
function madoguchi_blocks_settings_page() {
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Madoguchi Blocks 設定', 'madoguchi-blocks' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'madoguchi_blocks' );
			do_settings_sections( 'madoguchi-blocks' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
