<?php
/**
 * PHPUnit ブートストラップ。WordPress は読み込まず、純粋ロジックのクラスだけを読む。
 */
date_default_timezone_set( 'UTC' );

$inc = dirname( __DIR__ ) . '/inc/phone-cta/';
foreach ( array(
	'class-services.php',
	'class-reception.php',
	'class-tel.php',
	'class-view.php',
	'class-store.php',
	'class-repository.php',
) as $file ) {
	if ( file_exists( $inc . $file ) ) {
		require_once $inc . $file;
	}
}

// バナー解決とヘルパーは WordPress の関数・定数を少し使うので、テスト用に最小限だけ用意する
if ( ! defined( 'MADOGUCHI_BLOCKS_DIR' ) ) {
	define( 'MADOGUCHI_BLOCKS_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'MADOGUCHI_BLOCKS_URL' ) ) {
	define( 'MADOGUCHI_BLOCKS_URL', 'https://example.test/wp-content/plugins/madoguchi-blocks/' );
}

$GLOBALS['madoguchi_blocks_test_options'] = array();

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default_value = false ) {
		return array_key_exists( $name, $GLOBALS['madoguchi_blocks_test_options'] )
			? $GLOBALS['madoguchi_blocks_test_options'][ $name ]
			: $default_value;
	}
}

require_once $inc . 'class-banners.php';
require_once $inc . 'render-helpers.php';
