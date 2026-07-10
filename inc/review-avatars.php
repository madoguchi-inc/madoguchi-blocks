<?php
/**
 * 口コミのデフォルトアバター（SVG）をサーバー側で生成する。
 * src/review-section/avatars.js と配色・形状を一致させること。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'madoguchi_blocks_review_avatar_svg' ) ) {
	/**
	 * 性別と番号からデフォルトアバターの SVG 文字列を返す。
	 *
	 * @param string $gender 'male' | 'female'
	 * @param int    $index  0起点の番号
	 * @return string SVGマークアップ
	 */
	function madoguchi_blocks_review_avatar_svg( $gender, $index ) {
		$clothes_set = array( '#4a90d9', '#e0733f', '#4faf6d', '#8e6fc4', '#d9a441', '#d76b98' );
		$hair_set    = array( '#3b2f2a', '#5a4632', '#222222', '#6b4f34', '#7a5a3a', '#1f2937' );
		$count       = 6;

		$i       = ( ( (int) $index % $count ) + $count ) % $count;
		$clothes = $clothes_set[ $i ];
		$hair    = $hair_set[ $i ];
		$skin    = '#f5cfa8';
		$bg      = '#eef1f4';

		$svg  = '<svg class="review-card__avatar-svg" viewBox="0 0 64 64" width="60" height="60" aria-hidden="true" focusable="false">';
		$svg .= '<circle cx="32" cy="32" r="32" fill="' . esc_attr( $bg ) . '"/>';
		$svg .= '<path d="M13 63c0-11 8.5-17 19-17s19 6 19 17z" fill="' . esc_attr( $clothes ) . '"/>';

		if ( 'female' === $gender ) {
			$svg .= '<path d="M18 44V27c0-9 6-14.5 14-14.5S46 18 46 27v17l-5-3V27c0-6-3.8-9.5-9-9.5S23 21 23 27v14z" fill="' . esc_attr( $hair ) . '"/>';
			$svg .= '<circle cx="32" cy="28.5" r="12" fill="' . esc_attr( $skin ) . '"/>';
			$svg .= '<path d="M20 28c0-7.5 5-12 12-12s12 4.5 12 12c-2-4.5-6-6.5-12-6.5S22 23.5 20 28z" fill="' . esc_attr( $hair ) . '"/>';
		} else {
			$svg .= '<circle cx="32" cy="28" r="12.5" fill="' . esc_attr( $skin ) . '"/>';
			$svg .= '<path d="M19.5 28c0-8 5-13.5 12.5-13.5S44.5 20 44.5 28c-2.2-5-6-7.5-12.5-7.5S21.7 23 19.5 28z" fill="' . esc_attr( $hair ) . '"/>';
		}

		$svg .= '</svg>';

		return $svg;
	}
}
