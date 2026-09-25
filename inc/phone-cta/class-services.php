<?php
/**
 * 電話CTAで扱うサービス（事業）キーの定義。
 * キーは固定。サイトごとに設定画面で API URL だけを入れる。
 */
class Madoguchi_Blocks_Phone_Cta_Services {

	/** @var array<string,string> キー => 表示名 */
	const KEYS = array(
		'kaitori'  => '買取',
		'fuyouhin' => '回収',
		'osouji'   => '清掃',
	);

	public static function is_valid( string $key ): bool {
		return isset( self::KEYS[ $key ] );
	}

	public static function label( string $key ): string {
		return isset( self::KEYS[ $key ] ) ? self::KEYS[ $key ] : $key;
	}

	/**
	 * 属性から来たサービスキーを解決する。空・不正な値は $fallback にする。
	 * ブロックの既定サービスと、カードごとの上書き（空なら「ブロックに従う」）の両方で使う。
	 *
	 * @param mixed  $value    属性の値（文字列とは限らない）
	 * @param string $fallback 解決できなかったときに使うキー
	 */
	public static function resolve( $value, string $fallback = 'kaitori' ): string {
		return is_string( $value ) && self::is_valid( $value ) ? $value : $fallback;
	}
}
