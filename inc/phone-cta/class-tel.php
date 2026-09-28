<?php
/**
 * 表示用の電話番号（0120-000-000 など）を tel: リンク用の E.164 に変換する。
 */
class Madoguchi_Blocks_Phone_Cta_Tel {

	public static function to_href( string $display ): string {
		$half   = function_exists( 'mb_convert_kana' ) ? mb_convert_kana( $display, 'n', 'UTF-8' ) : $display;
		$plus   = 0 === strpos( ltrim( $half ), '+' );
		$digits = preg_replace( '/\D+/', '', $half );
		if ( '' === $digits ) {
			return '';
		}
		if ( $plus ) {
			return 'tel:+' . $digits;
		}
		if ( '0' === $digits[0] ) {
			$digits = '81' . substr( $digits, 1 );
		}
		return 'tel:+' . $digits;
	}
}
