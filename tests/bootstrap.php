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
