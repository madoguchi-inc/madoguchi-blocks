<?php
/**
 * 電話CTA 固定フッター。1 記事に 1 つだけ出力する。
 *
 * @var array $attributes
 */
if ( empty( $attributes['isVisible'] ) ) {
	return;
}
$service = isset( $attributes['service'] ) && Madoguchi_Blocks_Phone_Cta_Services::is_valid( $attributes['service'] ) ? $attributes['service'] : 'kaitori';
$texts   = Madoguchi_Blocks_Phone_Cta_View::default_texts( $service );
$now     = Madoguchi_Blocks_Phone_Cta_Reception::now_jst();

$shop_attr = isset( $attributes['shop'] ) && is_array( $attributes['shop'] ) ? $attributes['shop'] : array();
$shop      = null;
if ( ! empty( $shop_attr['uuid'] ) ) {
	$shop = Madoguchi_Blocks_Phone_Cta_Repository::default()->find( $service, (string) $shop_attr['uuid'] );
}
$state = Madoguchi_Blocks_Phone_Cta_View::footer_state( $shop, $shop_attr, $now, $service );

$show_web = ! isset( $attributes['showWebButton'] ) || $attributes['showWebButton'];
$web_url  = isset( $attributes['webButtonUrl'] ) && '' !== trim( $attributes['webButtonUrl'] ) ? $attributes['webButtonUrl'] : '/form';
$catch    = madoguchi_blocks_phone_cta_kses( isset( $attributes['catchText'] ) ? $attributes['catchText'] : $texts['footer_catch'] );

// 電話側ボタンを出すか（web_only は黒ボタンのみ）
$show_tel = 'web_only' !== $state['mode'];
if ( ! $show_tel && ! $show_web ) {
	return; // 出すボタンが無い
}
if ( ! madoguchi_blocks_phone_cta_once( 'footer' ) ) {
	return; // 1 記事に 1 つだけ
}

$classes = 'phone-cta-footer';
if ( ! $show_tel || ! $show_web ) {
	$classes .= ' phone-cta-footer--single';
}
$wrapper = get_block_wrapper_attributes( array(
	'class'                 => $classes,
	'data-phone-cta-footer' => '',
	'data-service'          => $service,
	'data-shop-uuid'        => $state['uuid'],
	'data-shop-name'        => $state['name'],
	'data-tel'              => $state['tel_href'],
	'data-open'             => $state['is_open'] ? '1' : '0',
) );

if ( 'web' === $state['mode'] ) {
	$tel_href = $state['fallback_url'];
} elseif ( 'generic' === $state['mode'] ) {
	$tel_href = '#phone-cta';
} else {
	$tel_href = $state['tel_href'];
}
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( '' !== trim( wp_strip_all_tags( $catch ) ) ) : ?>
		<p class="phone-cta-footer__catch"><?php echo $catch; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<?php endif; ?>
	<div class="phone-cta-footer__buttons">
		<?php if ( $show_web ) : ?>
			<a class="phone-cta-footer__web" href="<?php echo esc_url( $web_url ); ?>"><?php echo esc_html( $texts['web_button'] ); ?></a>
		<?php endif; ?>
		<?php if ( $show_tel ) : ?>
			<a class="phone-cta-footer__tel<?php echo 'web' === $state['mode'] ? ' phone-cta-footer__tel--web' : ''; ?>"
				href="<?php echo esc_url( $tel_href ); ?>"
				<?php echo 'web' === $state['mode'] ? 'target="_blank" rel="noopener"' : ''; ?>>
				<?php if ( 'tel' === $state['mode'] && '' !== $state['balloon'] ) : ?>
					<span class="phone-cta-footer__balloon"><?php echo esc_html( $state['balloon'] ); ?></span>
				<?php endif; ?>
				<?php echo madoguchi_blocks_phone_cta_icon( 'web' === $state['mode'] ? 'touch' : 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $state['label'] ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
