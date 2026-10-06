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
$service = Madoguchi_Blocks_Phone_Cta_Services::resolve( isset( $attributes['service'] ) ? $attributes['service'] : null );
$items   = isset( $attributes['shops'] ) && is_array( $attributes['shops'] ) ? array_slice( $attributes['shops'], 0, 3 ) : array();
if ( empty( $items ) ) {
	return;
}

$repo  = Madoguchi_Blocks_Phone_Cta_Repository::default();
$now   = Madoguchi_Blocks_Phone_Cta_Reception::now_jst();
// サイトの主サービスと違うブロック（回収の記事に置く買取カードなど）は文言と色が変わる
$host_service = madoguchi_blocks_phone_cta_primary_service();
$texts        = Madoguchi_Blocks_Phone_Cta_View::default_texts( $service, $host_service );
$show_pc_modal = ! isset( $attributes['showPcModal'] ) || $attributes['showPcModal']; // PC は tel: が押せないので番号と QR のモーダルを出す

$cards = array();
foreach ( $items as $item ) {
	if ( ! is_array( $item ) || empty( $item['uuid'] ) ) {
		continue;
	}
	// カードごとにサービスを変えられる（空ならブロックのサービス）。引く先のマスタとボタン文言が変わる
	$card_service = Madoguchi_Blocks_Phone_Cta_Services::resolve( isset( $item['service'] ) ? $item['service'] : null, $service );
	$shop         = $repo->find( $card_service, (string) $item['uuid'] );
	if ( null === $shop ) {
		continue; // マスタに無い／非公開の店舗は出さない
	}
	$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $shop, $item, $now, $card_service, $host_service );
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
	// サイトの主サービス。これと違うサービスのカードはクロスセル用の配色になる
	'data-host-service' => $host_service,
);
if ( madoguchi_blocks_phone_cta_once( 'first-block:' . (int) get_the_ID() ) ) {
	$extra['id'] = 'phone-cta';
}
$wrapper = get_block_wrapper_attributes( $extra );

$has_heading     = '' !== trim( wp_strip_all_tags( $heading ) );
$has_description = '' !== trim( wp_strip_all_tags( $description ) );

// バナー（同梱パターン／カスタム画像。既定はなし）。PC・SP・PC モーダルで画像を出し分ける
$banner       = Madoguchi_Blocks_Phone_Cta_Banners::resolve( $attributes, 'pc' );
$banner_sp    = Madoguchi_Blocks_Phone_Cta_Banners::resolve( $attributes, 'sp' );
$modal_banner = Madoguchi_Blocks_Phone_Cta_Banners::resolve( $attributes, 'modal' );

// 遷移先のキャンペーンLPで同じ店舗を出せるよう、記事で出している店舗をリンクに載せる。
// 記事に電話CTAブロックを複数置く構成（回収のカード群＋クロスセルの買取カード）があるので、
// このブロックのカードだけでなく本文全体から集める。1 つしか置いていなければ結果は同じ。
// 編集画面で utm 付きの URL を入れていても壊さないよう add_query_arg で連結する
$post_shops  = madoguchi_blocks_phone_cta_post_shops( get_the_ID() );
$shops_param = Madoguchi_Blocks_Phone_Cta_View::shops_param(
	! empty( $post_shops ) ? $post_shops : $cards,
	$service
);
if ( '' !== $shops_param ) {
	if ( null !== $banner && '' !== $banner['link'] ) {
		$banner['link'] = add_query_arg( 's', $shops_param, $banner['link'] );
	}
	if ( null !== $modal_banner && '' !== $modal_banner['link'] ) {
		$modal_banner['link'] = add_query_arg( 's', $shops_param, $modal_banner['link'] );
	}
}
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( null !== $banner ) : ?>
		<?php
		// SP 用画像があれば <picture> で幅に応じて差し替える（SPA でも JS なしで効く）
		$banner_sp_source = '';
		if ( null !== $banner_sp && $banner_sp['src'] !== $banner['src'] ) {
			$banner_sp_source = sprintf(
				'<source media="(max-width: %1$dpx)" srcset="%2$s"%3$s%4$s>',
				(int) Madoguchi_Blocks_Phone_Cta_Banners::SP_MAX_WIDTH,
				esc_attr( '' !== $banner_sp['srcset'] ? $banner_sp['srcset'] : $banner_sp['src'] ),
				$banner_sp['width'] > 0 ? ' width="' . (int) $banner_sp['width'] . '"' : '',
				$banner_sp['height'] > 0 ? ' height="' . (int) $banner_sp['height'] . '"' : ''
			);
		}
		$banner_img = sprintf(
			'<picture class="phone-cta__banner-picture">%1$s<img class="phone-cta__banner-image" src="%2$s"%3$s alt="%4$s"%5$s%6$s loading="lazy" decoding="async"></picture>',
			$banner_sp_source,
			esc_url( $banner['src'] ),
			'' !== $banner['srcset'] ? ' srcset="' . esc_attr( $banner['srcset'] ) . '"' : '',
			esc_attr( $banner['alt'] ),
			$banner['width'] > 0 ? ' width="' . (int) $banner['width'] . '"' : '',
			$banner['height'] > 0 ? ' height="' . (int) $banner['height'] . '"' : ''
		);
		?>
		<div class="phone-cta__banner">
			<?php if ( '' !== $banner['link'] ) : ?>
				<a class="phone-cta__banner-link" href="<?php echo esc_url( $banner['link'] ); ?>">
					<?php echo $banner_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			<?php else : ?>
				<?php echo $banner_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
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
			<?php echo madoguchi_blocks_phone_cta_card_html( $c, $show_pc_modal ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endforeach; ?>
	</ul>
</section>
