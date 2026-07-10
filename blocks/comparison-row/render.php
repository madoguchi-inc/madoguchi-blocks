<?php
/**
 * 比較テーブルの行（子・動的レンダリング）
 *
 * 親のグリッドに整列させるため、行ラッパーは display:contents とし、
 * 各セル（店名・各列値・CTA）を親グリッドの直接の要素として並べる。
 *
 * @var array    $attributes ブロック属性。
 * @var string   $content    CTA列に置いた子ブロック（CTAボタン等）のレンダリング結果。
 * @var WP_Block $block      ブロックインスタンス。
 */

$name   = isset( $attributes['name'] ) ? wp_strip_all_tags( $attributes['name'] ) : '';
$rating = isset( $attributes['rating'] ) ? floatval( $attributes['rating'] ) : 0;
$values = ( isset( $attributes['values'] ) && is_array( $attributes['values'] ) ) ? $attributes['values'] : array();

// 親から列定義を受け取る
$columns   = isset( $block->context['madoguchi/comparisonColumns'] ) && is_array( $block->context['madoguchi/comparisonColumns'] )
	? $block->context['madoguchi/comparisonColumns']
	: array();
$col_count = count( $columns );
$width     = max( 0, min( 100, ( $rating / 5 ) * 100 ) );

$wrapper = get_block_wrapper_attributes( array( 'class' => 'comparison-table__row' ) );
?>
<div <?php echo $wrapper; ?>>
	<div class="comparison-table__gcell comparison-table__gcell--name">
		<span class="comparison-table__shop"><?php echo esc_html( $name ); ?></span>
		<?php if ( $rating > 0 ) : ?>
			<span class="comparison-table__rating">
				<span class="comparison-table__stars"><span class="comparison-table__stars-fill" style="width:<?php echo esc_attr( $width ); ?>%"></span></span>
				<span class="comparison-table__score"><?php echo esc_html( number_format( $rating, 1 ) ); ?></span>
			</span>
		<?php endif; ?>
	</div>
	<?php for ( $i = 0; $i < $col_count; $i++ ) : ?>
		<div class="comparison-table__gcell"><?php echo esc_html( isset( $values[ $i ] ) ? wp_strip_all_tags( $values[ $i ] ) : '' ); ?></div>
	<?php endfor; ?>
	<div class="comparison-table__gcell comparison-table__gcell--cta"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped CTA列の子ブロック ?></div>
</div>
