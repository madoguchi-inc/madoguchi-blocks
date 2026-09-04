<?php
/**
 * 色値のサニタイズ。
 *
 * ブロックエディタのカラーピッカーは hex だけを返すとは限らない。
 * 透明度スライダーを動かした場合や、テーマの theme.json が半透明色を持つ場合は
 * rgba()／8桁 hex が返る。これらを sanitize_hex_color() は一律 false にするため、
 * 「エディタでは選べた色が、フロントでは既定色に戻る」という食い違いが起きていた。
 * ここでは CSS の色値として安全に出力できる形式だけを許可リスト方式で通す。
 *
 * @package madoguchi-blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * インライン style に出して安全な色値だけを通す。
 *
 * 許可する形式:
 *   - #RGB / #RGBA / #RRGGBB / #RRGGBBAA
 *   - rgb() / rgba() / hsl() / hsla()
 *
 * 括弧・引用符・セミコロン等を値に含めないため、var() や url() は通らず、
 * インライン style へ別宣言を注入されることもない。
 *
 * @param mixed $value 検証する色値。
 * @return string 安全な色値。許可されない場合は空文字。
 */
function madoguchi_blocks_sanitize_color( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$color = trim( $value );
	if ( '' === $color ) {
		return '';
	}

	// #RGB / #RGBA / #RRGGBB / #RRGGBBAA
	if ( preg_match( '/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $color ) ) {
		return $color;
	}

	// rgb()/rgba()/hsl()/hsla()。中身は数値・単位・区切りだけに限定する
	// （括弧を含められないため入れ子の関数呼び出しは成立しない）
	if ( preg_match( '#^(?:rgba?|hsla?)\(\s*[0-9a-z.%,/\s+-]{1,64}\)$#i', $color ) ) {
		return $color;
	}

	return '';
}
