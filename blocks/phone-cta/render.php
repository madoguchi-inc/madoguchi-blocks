<?php
/**
 * 電話CTA（店舗別）の描画。リクエスト時の JST で受付時間内/外を判定して出し分ける。
 * 見た目は Figma「みんなの買取」コラム_PC / コラム_SP / 電話査定受付時間外 に合わせる。
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
$show_campaign = ! empty( $attributes['showCampaign'] ); // 既定は非表示（Figma のカードにキャンペーン枠は無い）

$cards = array();
foreach ( $items as $item ) {
	if ( ! is_array( $item ) || empty( $item['uuid'] ) ) {
		continue;
	}
	$shop = $repo->find( $service, (string) $item['uuid'] );
	if ( null === $shop ) {
		continue; // マスタに無い／非公開の店舗は出さない
	}
	$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $shop, $item, $now, $service );
	if ( null !== $state ) {
		$cards[] = $state;
	}
}
if ( empty( $cards ) ) {
	return;
}

$heading     = madoguchi_blocks_phone_cta_kses( isset( $attributes['heading'] ) ? $attributes['heading'] : $texts['heading'] );
$description = madoguchi_blocks_phone_cta_kses( isset( $attributes['description'] ) ? $attributes['description'] : $texts['description'] );

// POINT バッジ（空文字は除外。属性が無ければサービス既定）
$points = isset( $attributes['points'] ) && is_array( $attributes['points'] ) ? $attributes['points'] : $texts['points'];
$points = array_values( array_filter( array_map( 'strval', $points ), static function ( $p ) {
	return '' !== trim( $p );
} ) );
$points = array_slice( $points, 0, 3 );

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

$has_heading     = '' !== trim( wp_strip_all_tags( $heading ) );
$has_description = '' !== trim( wp_strip_all_tags( $description ) );
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $has_heading || $has_description || ! empty( $points ) ) : ?>
		<header class="phone-cta__header">
			<?php if ( $has_heading || $has_description ) : ?>
				<div class="phone-cta__titles">
					<?php if ( $has_description ) : ?>
						<p class="phone-cta__description"><?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<?php endif; ?>
					<?php if ( $has_heading ) : ?>
						<h2 class="phone-cta__heading"><?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $points ) ) : ?>
				<ul class="phone-cta__points">
					<?php foreach ( $points as $i => $point ) : ?>
						<li class="phone-cta__point">
							<span class="phone-cta__point-label"><?php echo esc_html( 'POINT' . ( $i + 1 ) ); ?></span>
							<span class="phone-cta__point-text"><?php echo esc_html( $point ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</header>
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
				<div class="phone-cta__intro">
					<div class="phone-cta__logo-box">
						<?php if ( '' !== $c['logo_url'] ) : ?>
							<img class="phone-cta__logo" src="<?php echo esc_url( $c['logo_url'] ); ?>" alt="<?php echo esc_attr( $c['name'] ); ?>" loading="lazy">
						<?php else : ?>
							<p class="phone-cta__name"><?php echo esc_html( $c['name'] ); ?></p>
						<?php endif; ?>
					</div>
					<?php if ( '' !== trim( wp_strip_all_tags( $c['lead'] ) ) || '' !== $c['specialty'] ) : ?>
						<div class="phone-cta__lead">
							<?php if ( '' !== trim( wp_strip_all_tags( $c['lead'] ) ) ) : ?>
								<p class="phone-cta__lead-text"><?php echo madoguchi_blocks_phone_cta_kses( $c['lead'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
							<?php endif; ?>
							<?php if ( '' !== $c['specialty'] ) : ?>
								<p class="phone-cta__specialty"><?php echo esc_html( $c['specialty'] ); ?></p>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="phone-cta__action">
					<?php if ( 'web' === $c['mode'] ) : ?>
						<a class="phone-cta__button phone-cta__button--web" href="<?php echo esc_url( $c['fallback_url'] ); ?>" target="_blank" rel="noopener">
							<span class="phone-cta__free"><?php echo esc_html( $texts['free_tag'] ); ?></span>
							<span class="phone-cta__button-body">
								<?php echo madoguchi_blocks_phone_cta_icon( 'touch' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span class="phone-cta__button-label"><?php echo esc_html( $texts['web_label'] ); ?></span>
							</span>
							<span class="phone-cta__chevron" aria-hidden="true"></span>
						</a>
					<?php else : ?>
						<a class="phone-cta__button<?php echo 'tel' === $c['mode'] ? ' phone-cta__button--balloon' : ''; ?>" href="<?php echo esc_url( $c['tel_href'] ); ?>">
							<?php if ( 'tel' === $c['mode'] ) : ?>
								<span class="phone-cta__balloon"><?php echo esc_html( $texts['balloon'] ); ?></span>
							<?php endif; ?>
							<span class="phone-cta__free"><?php echo esc_html( $texts['free_tag'] ); ?></span>
							<span class="phone-cta__button-body">
								<?php echo madoguchi_blocks_phone_cta_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php // 2 片の間に空白文字が入らないよう 1 行で出す ?>
								<span class="phone-cta__button-label"><?php if ( '' !== $c['label_parts'][0] ) : ?><span class="phone-cta__button-shop"><?php echo esc_html( $c['label_parts'][0] ); ?></span><?php endif; ?><span class="phone-cta__button-rest"><?php echo esc_html( $c['label_parts'][1] ); ?></span></span>
							</span>
							<span class="phone-cta__chevron" aria-hidden="true"></span>
						</a>
					<?php endif; ?>

					<?php if ( 'tel_closed' === $c['mode'] ) : ?>
						<p class="phone-cta__notice"><?php esc_html_e( '現在は受付時間外です', 'madoguchi-blocks' ); ?></p>
					<?php endif; ?>

					<?php // Figma どおり受付時間の 1 行だけ（電話番号はボタンの tel: リンクに持たせる） ?>
					<?php if ( '' !== $c['reception_text'] ) : ?>
						<p class="phone-cta__hours">
							<span class="phone-cta__hours-text"><?php echo esc_html( sprintf( __( '受付時間：%s', 'madoguchi-blocks' ), $c['reception_text'] ) ); ?></span>
						</p>
					<?php endif; ?>
					<?php if ( 'web' !== $c['mode'] && ! $c['is_toll_free'] ) : ?>
						<p class="phone-cta__note"><?php esc_html_e( '通話料はお客様のご負担となります', 'madoguchi-blocks' ); ?></p>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php
	// キャンペーンは Figma のカードに無いので、カードの外（一覧の下）に店名付きでまとめて出す。既定は非表示。
	$campaigns = array();
	if ( $show_campaign ) {
		foreach ( $cards as $c ) {
			if ( is_array( $c['campaign'] ) ) {
				$campaigns[] = $c;
			}
		}
	}
	?>
	<?php if ( ! empty( $campaigns ) ) : ?>
		<ul class="phone-cta__campaigns">
			<?php foreach ( $campaigns as $c ) : $cp = $c['campaign']; ?>
				<li class="phone-cta__campaign">
					<?php if ( ! empty( $cp['image_url'] ) ) : ?>
						<img class="phone-cta__campaign-image" src="<?php echo esc_url( $cp['image_url'] ); ?>" alt="<?php echo esc_attr( isset( $cp['name'] ) ? $cp['name'] : '' ); ?>" loading="lazy">
					<?php endif; ?>
					<div class="phone-cta__campaign-text">
						<p class="phone-cta__campaign-name">
							<span class="phone-cta__campaign-shop"><?php echo esc_html( $c['name'] ); ?></span>
							<?php if ( ! empty( $cp['name'] ) ) : ?>
								<?php echo esc_html( $cp['name'] ); ?>
							<?php endif; ?>
						</p>
						<?php if ( ! empty( $cp['body'] ) ) : ?>
							<p class="phone-cta__campaign-body"><?php echo nl2br( esc_html( $cp['body'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $cp['terms'] ) ) : ?>
							<p class="phone-cta__campaign-terms"><?php echo nl2br( esc_html( $cp['terms'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
