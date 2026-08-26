<?php
/**
 * CTAボタンブロック（動的レンダリング）
 *
 * 背景色・文字色・角丸・整列・全方向margin/paddingを設定でき、
 * ボタン内テキストの一部をバッジ風に表示できる。
 *
 * @var array    $attributes ブロック属性。
 * @var string   $content    内部コンテンツ（未使用）。
 * @var WP_Block $block      ブロックインスタンス。
 */

$text          = isset( $attributes['text'] ) ? $attributes['text'] : '';
$badge         = isset( $attributes['badgeText'] ) ? $attributes['badgeText'] : '';
$badge_pos     = isset( $attributes['badgePosition'] ) ? $attributes['badgePosition'] : 'before';
$url           = isset( $attributes['url'] ) ? $attributes['url'] : '';
$show_badge    = ( 'none' !== $badge_pos ) && ( '' !== trim( wp_strip_all_tags( $badge ) ) );

// テキストもバッジも空なら描画しない
if ( '' === trim( wp_strip_all_tags( $text ) ) && ! $show_badge ) {
	return '';
}

$align  = in_array( ( isset( $attributes['align'] ) ? $attributes['align'] : 'center' ), array( 'left', 'center', 'right' ), true ) ? $attributes['align'] : 'center';
$radius = isset( $attributes['borderRadius'] ) ? max( 0, (int) $attributes['borderRadius'] ) : 10;
$bg     = isset( $attributes['backgroundColor'] ) ? sanitize_hex_color( $attributes['backgroundColor'] ) : '';
$color  = isset( $attributes['textColor'] ) ? sanitize_hex_color( $attributes['textColor'] ) : '';

// CSS長さ値のサニタイズ（数値のみなら px を付与、単位付きは許可単位のみ通す）
$len = function( $v ) {
	$v = trim( (string) $v );
	if ( '' === $v ) {
		return '';
	}
	if ( preg_match( '/^\d+(\.\d+)?$/', $v ) ) {
		return $v . 'px';
	}
	return preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vw|vh)$/', $v ) ? $v : '';
};
$box_css = function( $box, $prop ) use ( $len ) {
	if ( ! is_array( $box ) ) {
		return '';
	}
	$out = '';
	foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
		if ( ! empty( $box[ $side ] ) ) {
			$val = $len( $box[ $side ] );
			if ( '' !== $val ) {
				$out .= $prop . '-' . $side . ':' . $val . ';';
			}
		}
	}
	return $out;
};

// ラッパー: 整列 + margin
$wrap_style   = 'text-align:' . $align . ';' . $box_css( isset( $attributes['margin'] ) ? $attributes['margin'] : array(), 'margin' );
$wrapper      = get_block_wrapper_attributes( array( 'class' => 'cta-button-wrap', 'style' => $wrap_style ) );

// ボタン: 背景（単色 or グラデーション）・文字色・角丸・padding
$btn_style = '';
$bg_type   = isset( $attributes['bgType'] ) ? $attributes['bgType'] : 'solid';
$g_from    = isset( $attributes['gradientFrom'] ) ? sanitize_hex_color( $attributes['gradientFrom'] ) : '';
$g_to      = isset( $attributes['gradientTo'] ) ? sanitize_hex_color( $attributes['gradientTo'] ) : '';
$g_angle   = isset( $attributes['gradientAngle'] ) ? (int) $attributes['gradientAngle'] : 90;
if ( 'gradient' === $bg_type && $g_from && $g_to ) {
	$btn_style .= 'background:linear-gradient(' . $g_angle . 'deg,' . $g_from . ',' . $g_to . ');';
} elseif ( $bg ) {
	$btn_style .= 'background:' . $bg . ';';
}
if ( $color ) {
	$btn_style .= 'color:' . $color . ';';
}
$btn_style .= 'border-radius:' . $radius . 'px;';
$gap        = isset( $attributes['badgeGap'] ) ? max( 0, (int) $attributes['badgeGap'] ) : 10;
$btn_style .= 'gap:' . $gap . 'px;';
$font_size  = isset( $attributes['fontSize'] ) ? (int) $attributes['fontSize'] : 0;
if ( $font_size > 0 ) {
	$btn_style .= 'font-size:' . $font_size . 'px;';
}
$btn_style .= $box_css( isset( $attributes['padding'] ) ? $attributes['padding'] : array(), 'padding' );
// 各値は sanitize_hex_color / (int) / 長さバリデーション済みのため、esc_attr で出力する
// （safecss_filter_attr は linear-gradient を除去する場合があるため使用しない）

$badge_fs     = isset( $attributes['badgeFontSize'] ) ? (int) $attributes['badgeFontSize'] : 0;
$badge_color  = isset( $attributes['badgeTextColor'] ) ? sanitize_hex_color( $attributes['badgeTextColor'] ) : '';
$badge_styles = array();
if ( $badge_fs > 0 ) {
	$badge_styles[] = 'font-size:' . $badge_fs . 'px';
}
if ( $badge_color ) {
	$badge_styles[] = 'color:' . $badge_color;
}
$badge_style = $badge_styles ? ' style="' . esc_attr( implode( ';', $badge_styles ) ) . '"' : '';
$badge_html  = $show_badge ? '<span class="cta-button__badge"' . $badge_style . '>' . esc_html( wp_strip_all_tags( $badge ) ) . '</span>' : '';
$label_html = '<span class="cta-button__label">' . esc_html( wp_strip_all_tags( $text ) ) . '</span>';

// ラベル前のアイコン（任意）。図形は src/condition-card/icons.js と同期（追加・変更時は両方を更新）。
$icon_paths = array(
	// 電話（塗り）: Figma phone-filled（16px 基準）を 24px 基準に拡大
	'phone' => '<g transform="scale(1.5)" fill="currentColor" stroke="none"><path d="M10.3708 9.69851L10.0671 10.0181C10.0671 10.0181 9.34539 10.778 7.37539 8.70392C5.40541 6.6299 6.12713 5.87006 6.12713 5.87006L6.31833 5.66875C6.78939 5.17283 6.83379 4.37665 6.42279 3.7954L5.58217 2.60641C5.07352 1.887 4.09064 1.79197 3.50763 2.40576L2.46123 3.50743C2.17215 3.81178 1.97843 4.2063 2.00193 4.64397C2.06203 5.76365 2.54047 8.17274 5.21024 10.9835C8.04139 13.9642 10.6979 14.0826 11.7842 13.9754C12.1278 13.9415 12.4266 13.7562 12.6674 13.5027L13.6145 12.5057C14.2537 11.8326 14.0735 10.6788 13.2555 10.208L11.9819 9.47489C11.4448 9.16578 10.7905 9.25656 10.3708 9.69851Z"/></g>',
);
$icon_key   = isset( $attributes['icon'] ) ? (string) $attributes['icon'] : '';
$icon_html  = '';
if ( isset( $icon_paths[ $icon_key ] ) ) {
	$icon_html = '<span class="cta-button__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" focusable="false">' . $icon_paths[ $icon_key ] . '</svg></span>';
}
?>
<div <?php echo $wrapper; ?>>
	<a class="cta-button" href="<?php echo esc_url( $url ? $url : '#' ); ?>" style="<?php echo esc_attr( $btn_style ); ?>">
		<?php
		if ( 'after' === $badge_pos ) {
			echo $icon_html . $label_html . $badge_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo $badge_html . $icon_html . $label_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</a>
</div>
