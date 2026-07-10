<?php
/**
 * 口コミ・レビューブロック（動的レンダリング）
 *
 * 吹き出し＋デフォルトアバター（性別＋番号で変化）のレビューカードを出力し、
 * 併せて schema.org/Review・AggregateRating を JSON-LD で同梱する。
 *
 * @var array    $attributes ブロック属性。
 * @var string   $content    内部コンテンツ（未使用）。
 * @var WP_Block $block      ブロックインスタンス。
 */

$heading   = isset( $attributes['heading'] ) ? wp_strip_all_tags( $attributes['heading'] ) : '';
$item_name = isset( $attributes['itemName'] ) ? wp_strip_all_tags( $attributes['itemName'] ) : '';
$reviews   = ( isset( $attributes['reviews'] ) && is_array( $attributes['reviews'] ) ) ? $attributes['reviews'] : array();

// 口コミが1件も無ければ描画しない
if ( empty( $reviews ) ) {
	return '';
}

// レビュー対象名（未設定なら記事タイトルにフォールバック）
$item_label = '' !== $item_name ? $item_name : wp_strip_all_tags( get_the_title() );

// 平均評価・件数を集計
$sum   = 0;
$count = 0;
foreach ( $reviews as $r ) {
	$rating = isset( $r['rating'] ) ? floatval( $r['rating'] ) : 0;
	if ( $rating > 0 ) {
		$sum += $rating;
		$count++;
	}
}
$avg = $count ? round( $sum / $count, 1 ) : 0;

// 本文をプレーンテキスト化する（JSON-LD用。<br>は改行に変換）
$to_plain = function( $html ) {
	$text = str_replace( array( '<br>', '<br/>', '<br />' ), "\n", (string) $html );
	return trim( wp_strip_all_tags( $text ) );
};

// JSON-LD（schema.org）を構築
$review_ld = array();
foreach ( $reviews as $r ) {
	$author = isset( $r['author'] ) ? wp_strip_all_tags( $r['author'] ) : '';
	$body   = $to_plain( isset( $r['body'] ) ? $r['body'] : '' );
	$rating = isset( $r['rating'] ) ? floatval( $r['rating'] ) : 0;

	$entry = array(
		'@type'      => 'Review',
		'author'     => array(
			'@type' => 'Person',
			'name'  => '' !== $author ? $author : '匿名',
		),
		'reviewBody' => $body,
	);
	if ( $rating > 0 ) {
		$entry['reviewRating'] = array(
			'@type'       => 'Rating',
			'ratingValue' => $rating,
			'bestRating'  => 5,
			'worstRating' => 1,
		);
	}
	$review_ld[] = $entry;
}

$ld = array(
	'@context' => 'https://schema.org',
	'@type'    => 'Service',
	'name'     => '' !== $item_label ? $item_label : '口コミ',
	'review'   => $review_ld,
);
if ( $count > 0 ) {
	$ld['aggregateRating'] = array(
		'@type'       => 'AggregateRating',
		'ratingValue' => $avg,
		'reviewCount' => $count,
		'bestRating'  => 5,
		'worstRating' => 1,
	);
}

// スラッシュはエスケープしたまま（</script> の混入を防ぐ）。日本語は可読性のため非エスケープ。
$json = wp_json_encode( $ld, JSON_UNESCAPED_UNICODE );

// ラッパー（アクセントカラー・吹き出し角丸）
$wrapper_args = array( 'class' => 'review-section' );
$styles       = array();
$accent       = isset( $attributes['accentColor'] ) ? sanitize_hex_color( $attributes['accentColor'] ) : '';
if ( $accent ) {
	$styles[] = '--md-brand:' . $accent;
}
$bubble   = isset( $attributes['bubbleRadius'] ) ? max( 0, (int) $attributes['bubbleRadius'] ) : 12;
$styles[] = '--md-bubble-radius:' . $bubble . 'px';
$body_fs  = isset( $attributes['bodyFontSize'] ) ? (int) $attributes['bodyFontSize'] : 0;
if ( $body_fs > 0 ) {
	$styles[] = '--md-review-body:' . $body_fs . 'px';
}
$wrapper_args['style'] = implode( ';', $styles ) . ';';
$wrapper               = get_block_wrapper_attributes( $wrapper_args );
?>
<section <?php echo $wrapper; ?> itemscope itemtype="https://schema.org/Service">
	<?php if ( '' !== $heading ) : ?>
		<h3 class="review-section__heading"><?php echo esc_html( $heading ); ?></h3>
	<?php endif; ?>

	<?php if ( $count > 0 ) : ?>
		<p class="review-section__summary">
			<span class="review-section__summary-score"><?php echo esc_html( number_format( $avg, 1 ) ); ?></span>
			<span class="review-section__summary-meta"><?php echo esc_html( sprintf( '%d件の口コミ', $count ) ); ?></span>
		</p>
	<?php endif; ?>

	<div class="review-section__list">
		<?php foreach ( $reviews as $r ) : ?>
			<?php
			$author       = isset( $r['author'] ) ? wp_strip_all_tags( $r['author'] ) : '';
			$rating       = isset( $r['rating'] ) ? floatval( $r['rating'] ) : 0;
			$body_html    = wp_kses( isset( $r['body'] ) ? $r['body'] : '', array( 'br' => array() ) );
			$gender       = ( isset( $r['gender'] ) && 'female' === $r['gender'] ) ? 'female' : 'male';
			$avatar_index = isset( $r['avatarIndex'] ) ? (int) $r['avatarIndex'] : 0;
			$age          = isset( $r['ageGroup'] ) ? wp_strip_all_tags( $r['ageGroup'] ) : '';
			$gender_label = 'female' === $gender ? '女性' : '男性';
			// 性別が未指定の旧データ対策: gender/ageGroup が無く legacy attribute がある場合はそれを使う
			$attr = implode( '・', array_filter( array( $age, ( '' !== $age || isset( $r['gender'] ) ) ? $gender_label : '' ) ) );
			if ( '' === $attr && ! empty( $r['attribute'] ) ) {
				$attr = wp_strip_all_tags( $r['attribute'] );
			}
			$width = max( 0, min( 100, ( $rating / 5 ) * 100 ) );
			?>
			<article class="review-card">
				<div class="review-card__avatar"><?php echo madoguchi_blocks_review_avatar_svg( $gender, $avatar_index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<div class="review-card__body">
					<div class="review-card__head">
						<span class="review-card__author"><?php echo esc_html( '' !== $author ? $author : '匿名' ); ?></span>
						<?php if ( '' !== $attr ) : ?>
							<span class="review-card__attribute"><?php echo esc_html( $attr ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( $rating > 0 ) : ?>
						<div class="review-card__rating" aria-label="<?php echo esc_attr( sprintf( '評価 %s / 5', number_format( $rating, 1 ) ) ); ?>">
							<span class="review-card__stars">
								<span class="review-card__stars-fill" style="width:<?php echo esc_attr( $width ); ?>%"></span>
							</span>
						</div>
					<?php endif; ?>
					<?php if ( '' !== trim( wp_strip_all_tags( $body_html ) ) ) : ?>
						<p class="review-card__text"><?php echo $body_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<script type="application/ld+json"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
</section>
