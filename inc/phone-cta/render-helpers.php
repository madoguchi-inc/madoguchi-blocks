<?php
/**
 * 電話CTA 2 ブロックの render.php が共有する小さなヘルパー。
 */

/**
 * アイコン SVG。phone は cta-button / recommend-card と同じ図形（Figma phone-filled）。
 * icons.js 側にも同じ図形があるので、変更時は両方を同期する（block-design.md）。
 */
function madoguchi_blocks_phone_cta_icon( $key ) {
	$paths = array(
		'phone' => '<g transform="scale(1.5)" fill="currentColor" stroke="none"><path d="M10.3708 9.69851L10.0671 10.0181C10.0671 10.0181 9.34539 10.778 7.37539 8.70392C5.40541 6.6299 6.12713 5.87006 6.12713 5.87006L6.31833 5.66875C6.78939 5.17283 6.83379 4.37665 6.42279 3.7954L5.58217 2.60641C5.07352 1.887 4.09064 1.79197 3.50763 2.40576L2.46123 3.50743C2.17215 3.81178 1.97843 4.2063 2.00193 4.64397C2.06203 5.76365 2.54047 8.17274 5.21024 10.9835C8.04139 13.9642 10.6979 14.0826 11.7842 13.9754C12.1278 13.9415 12.4266 13.7562 12.6674 13.5027L13.6145 12.5057C14.2537 11.8326 14.0735 10.6788 13.2555 10.208L11.9819 9.47489C11.4448 9.16578 10.7905 9.25656 10.3708 9.69851Z"/></g>',
		// 指でタップ（WEB 査定ボタン用）
		'touch' => '<path d="M9 11V5a2 2 0 1 1 4 0v6"/><path d="M13 10a2 2 0 1 1 4 0v1"/><path d="M17 11a2 2 0 1 1 4 0v4a7 7 0 0 1-7 7h-1.5a7 7 0 0 1-5.7-2.9L3.6 15.2a1.8 1.8 0 0 1 2.9-2.1L9 15.5"/>',
	);
	$key = isset( $paths[ $key ] ) ? $key : 'phone';
	return '<span class="phone-cta__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" focusable="false">' . $paths[ $key ] . '</svg></span>';
}

/**
 * 1 リクエスト中に 1 回だけ true を返す（記事内で最初のブロックだけ id を付ける、フッターは 1 つだけ等）。
 * render.php はブロックごとに include されるため、ファイル内の static 変数ではなく関数の static に持つ。
 */
function madoguchi_blocks_phone_cta_once( $key ) {
	static $done = array();
	if ( isset( $done[ $key ] ) ) {
		return false;
	}
	$done[ $key ] = true;
	return true;
}

/**
 * 表示テキスト用の許可リスト（RichText のインライン書式を活かす）。
 */
function madoguchi_blocks_phone_cta_kses( $html ) {
	return wp_kses( (string) $html, array(
		'br'     => array(),
		'strong' => array(),
		'em'     => array(),
		'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
	) );
}
