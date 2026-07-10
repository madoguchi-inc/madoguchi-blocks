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

$badge_fs    = isset( $attributes['badgeFontSize'] ) ? (int) $attributes['badgeFontSize'] : 0;
$badge_style = $badge_fs > 0 ? ' style="font-size:' . $badge_fs . 'px"' : '';
$badge_html  = $show_badge ? '<span class="cta-button__badge"' . $badge_style . '>' . esc_html( wp_strip_all_tags( $badge ) ) . '</span>' : '';
$label_html = '<span class="cta-button__label">' . esc_html( wp_strip_all_tags( $text ) ) . '</span>';
?>
<div <?php echo $wrapper; ?>>
	<a class="cta-button" href="<?php echo esc_url( $url ? $url : '#' ); ?>" style="<?php echo esc_attr( $btn_style ); ?>">
		<?php
		if ( 'after' === $badge_pos ) {
			echo $label_html . $badge_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo $badge_html . $label_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</a>
</div>
