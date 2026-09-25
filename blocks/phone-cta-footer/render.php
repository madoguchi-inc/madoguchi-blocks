<?php
/**
 * 電話CTA 固定フッター。1 記事に 1 つだけ出力する。
 * 見た目は Figma「みんなの買取」固定フッター（PC / SP）に合わせる。
 *
 * @var array $attributes
 */
if ( empty( $attributes['isVisible'] ) ) {
	return;
}
$service = Madoguchi_Blocks_Phone_Cta_Services::resolve( isset( $attributes['service'] ) ? $attributes['service'] : null );
$texts   = Madoguchi_Blocks_Phone_Cta_View::default_texts( $service );
$now     = Madoguchi_Blocks_Phone_Cta_Reception::now_jst();

$shop_attr = isset( $attributes['shop'] ) && is_array( $attributes['shop'] ) ? $attributes['shop'] : array();
$shop      = null;
if ( ! empty( $shop_attr['uuid'] ) ) {
	$shop = Madoguchi_Blocks_Phone_Cta_Repository::default()->find( $service, (string) $shop_attr['uuid'] );
}
$state = Madoguchi_Blocks_Phone_Cta_View::footer_state( $shop, $shop_attr, $now, $service );

$show_web    = ! isset( $attributes['showWebButton'] ) || $attributes['showWebButton'];
$web_url     = isset( $attributes['webButtonUrl'] ) && '' !== trim( $attributes['webButtonUrl'] ) ? $attributes['webButtonUrl'] : '/form';
$catch       = madoguchi_blocks_phone_cta_kses( isset( $attributes['catchText'] ) ? $attributes['catchText'] : $texts['footer_catch'] );
$catch_badge = isset( $attributes['catchBadge'] ) ? (string) $attributes['catchBadge'] : $texts['footer_catch_badge'];

// 電話側ボタンを出すか（web_only は黒ボタンのみ）
$show_tel = 'web_only' !== $state['mode'];
if ( ! $show_tel && ! $show_web ) {
	return; // 出すボタンが無い
}
if ( ! madoguchi_blocks_phone_cta_once( 'footer:' . (int) get_the_ID() ) ) {
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
	// 店舗未選択のときは記事内の電話CTAブロックへ飛ばす。そのブロックが記事に無ければ
	// アンカー先が存在せず押しても何も起きないので、WEB ボタンと同じ遷移先にする
	$tel_href = has_block( 'madoguchi/phone-cta', get_the_ID() ) ? '#phone-cta' : $web_url;
} else {
	$tel_href = $state['tel_href'];
}
$has_catch = '' !== trim( wp_strip_all_tags( $catch ) ) || '' !== trim( $catch_badge );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $has_catch ) : ?>
		<p class="phone-cta-footer__catch">
			<?php if ( '' !== trim( $catch_badge ) ) : ?>
				<span class="phone-cta-footer__catch-badge"><?php echo esc_html( $catch_badge ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== trim( wp_strip_all_tags( $catch ) ) ) : ?>
				<span class="phone-cta-footer__catch-text"><?php echo $catch; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php endif; ?>
		</p>
	<?php endif; ?>
	<div class="phone-cta-footer__buttons">
		<?php if ( $show_web ) : ?>
			<a class="phone-cta-footer__web" href="<?php echo esc_url( $web_url ); ?>">
				<?php echo madoguchi_blocks_phone_cta_icon( 'touch' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="phone-cta-footer__web-main phone-cta-footer__web-main--pc"><?php echo esc_html( $texts['web_button'] ); ?></span>
				<span class="phone-cta-footer__web-main phone-cta-footer__web-main--sp"><?php echo esc_html( $texts['web_button_sp'] ); ?></span>
				<span class="phone-cta-footer__web-sub"><?php echo esc_html( $texts['web_button_sub'] ); ?></span>
			</a>
		<?php endif; ?>
		<?php if ( $show_tel ) : ?>
			<a class="phone-cta-footer__tel<?php echo 'web' === $state['mode'] ? ' phone-cta-footer__tel--web' : ''; ?>"
				href="<?php echo esc_url( $tel_href ); ?>"
				<?php echo 'web' === $state['mode'] ? 'target="_blank" rel="noopener"' : ''; ?>>
				<?php if ( '' !== $state['balloon'] ) : ?>
					<span class="phone-cta-footer__balloon"><?php echo esc_html( $state['balloon'] ); ?></span>
				<?php endif; ?>
				<span class="phone-cta-footer__tel-main">
					<?php echo madoguchi_blocks_phone_cta_icon( 'web' === $state['mode'] ? 'touch' : 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php // 2 片の間に空白文字が入らないよう 1 行で出す ?>
					<span class="phone-cta-footer__tel-label"><?php if ( '' !== $state['label_parts'][0] ) : ?><span class="phone-cta-footer__tel-shop"><?php echo esc_html( $state['label_parts'][0] ); ?></span><?php endif; ?><span class="phone-cta-footer__tel-rest"><?php echo esc_html( $state['label_parts'][1] ); ?></span></span>
				</span>
			</a>
		<?php endif; ?>
	</div>
</div>
