<?php
/**
 * 電話CTA（店舗別）の描画。リクエスト時の JST で受付時間内/外を判定して出し分ける。
 *
 * @var array $attributes
 */
if ( empty( $attributes['isVisible'] ) ) {
	return;
}
$service = isset( $attributes['service'] ) && Madoguchi_Blocks_Phone_Cta_Services::is_valid( $attributes['service'] ) ? $attributes['service'] : 'kaitori';
$items   = isset( $attributes['shops'] ) && is_array( $attributes['shops'] ) ? array_slice( $attributes['shops'], 0, 3 ) : array();
if ( empty( $items ) ) {
	return;
}

$repo  = Madoguchi_Blocks_Phone_Cta_Repository::default();
$now   = Madoguchi_Blocks_Phone_Cta_Reception::now_jst();
$texts = Madoguchi_Blocks_Phone_Cta_View::default_texts( $service );
$show_campaign = ! isset( $attributes['showCampaign'] ) || $attributes['showCampaign'];

$cards = array();
foreach ( $items as $item ) {
	if ( ! is_array( $item ) || empty( $item['uuid'] ) ) {
		continue;
	}
	$shop = $repo->find( $service, (string) $item['uuid'] );
	if ( null === $shop ) {
		continue; // マスタに無い／非公開の店舗は出さない
	}
	$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $shop, $item, $now );
	if ( null !== $state ) {
		$cards[] = $state;
	}
}
if ( empty( $cards ) ) {
	return;
}

$heading     = madoguchi_blocks_phone_cta_kses( isset( $attributes['heading'] ) ? $attributes['heading'] : $texts['heading'] );
$description = madoguchi_blocks_phone_cta_kses( isset( $attributes['description'] ) ? $attributes['description'] : $texts['description'] );

// 記事内で最初のブロックだけ id="phone-cta"（固定フッターの汎用リンク先）
$extra = array(
	'class'          => 'phone-cta phone-cta--cols-' . count( $cards ),
	'data-phone-cta' => '',
	'data-service'   => $service,
);
if ( madoguchi_blocks_phone_cta_once( 'first-block:' . (int) get_the_ID() ) ) {
	$extra['id'] = 'phone-cta';
}
$wrapper = get_block_wrapper_attributes( $extra );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
		<h2 class="phone-cta__heading"><?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
	<?php endif; ?>
	<?php if ( '' !== trim( wp_strip_all_tags( $description ) ) ) : ?>
		<p class="phone-cta__description"><?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<?php endif; ?>
	<ul class="phone-cta__list">
		<?php foreach ( $cards as $c ) : ?>
			<li class="phone-cta__card <?php echo $c['is_open'] ? 'is-open' : 'is-closed'; ?>"
				data-shop-uuid="<?php echo esc_attr( $c['uuid'] ); ?>"
				data-shop-name="<?php echo esc_attr( $c['name'] ); ?>"
				data-tel="<?php echo esc_attr( $c['tel_href'] ); ?>"
				data-open="<?php echo $c['is_open'] ? '1' : '0'; ?>"
				data-fallback-url="<?php echo esc_attr( $c['fallback_url'] ); ?>"
				data-reception-text="<?php echo esc_attr( $c['reception_text'] ); ?>">
				<?php if ( '' !== $c['logo_url'] ) : ?>
					<img class="phone-cta__logo" src="<?php echo esc_url( $c['logo_url'] ); ?>" alt="<?php echo esc_attr( $c['name'] ); ?>" loading="lazy">
				<?php else : ?>
					<p class="phone-cta__name"><?php echo esc_html( $c['name'] ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== trim( wp_strip_all_tags( $c['lead'] ) ) ) : ?>
					<p class="phone-cta__lead"><?php echo madoguchi_blocks_phone_cta_kses( $c['lead'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endif; ?>
				<?php if ( '' !== $c['specialty'] ) : ?>
					<p class="phone-cta__specialty"><?php echo esc_html( $c['specialty'] ); ?></p>
				<?php endif; ?>

				<?php if ( 'web' === $c['mode'] ) : ?>
					<a class="phone-cta__button phone-cta__button--web" href="<?php echo esc_url( $c['fallback_url'] ); ?>" target="_blank" rel="noopener">
						<?php echo madoguchi_blocks_phone_cta_icon( 'touch' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $texts['web_label'] ); ?>
					</a>
				<?php else : ?>
					<a class="phone-cta__button" href="<?php echo esc_url( $c['tel_href'] ); ?>">
						<?php echo madoguchi_blocks_phone_cta_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $c['label'] ); ?>
					</a>
					<p class="phone-cta__number"><?php echo esc_html( $c['tel_display'] ); ?></p>
				<?php endif; ?>

				<?php if ( 'tel_closed' === $c['mode'] ) : ?>
					<p class="phone-cta__notice"><?php esc_html_e( '現在は受付時間外です', 'madoguchi-blocks' ); ?></p>
				<?php endif; ?>

				<p class="phone-cta__hours">
					<?php if ( '' !== $c['reception_text'] ) : ?>
						<?php echo esc_html( sprintf( __( '受付時間：%s', 'madoguchi-blocks' ), $c['reception_text'] ) ); ?>
					<?php endif; ?>
					<?php if ( $c['is_toll_free'] ) : ?>
						<span class="phone-cta__badge"><?php esc_html_e( '通話料無料', 'madoguchi-blocks' ); ?></span>
					<?php endif; ?>
				</p>
				<?php if ( ! $c['is_toll_free'] ) : ?>
					<p class="phone-cta__note"><?php esc_html_e( '通話料はお客様のご負担となります', 'madoguchi-blocks' ); ?></p>
				<?php endif; ?>

				<?php if ( $show_campaign && is_array( $c['campaign'] ) ) : $cp = $c['campaign']; ?>
					<div class="phone-cta__campaign">
						<?php if ( ! empty( $cp['image_url'] ) ) : ?>
							<img class="phone-cta__campaign-image" src="<?php echo esc_url( $cp['image_url'] ); ?>" alt="<?php echo esc_attr( isset( $cp['name'] ) ? $cp['name'] : '' ); ?>" loading="lazy">
						<?php endif; ?>
						<?php if ( ! empty( $cp['name'] ) ) : ?>
							<p class="phone-cta__campaign-name"><?php echo esc_html( $cp['name'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $cp['body'] ) ) : ?>
							<p class="phone-cta__campaign-body"><?php echo nl2br( esc_html( $cp['body'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $cp['terms'] ) ) : ?>
							<p class="phone-cta__campaign-terms"><?php echo nl2br( esc_html( $cp['terms'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
