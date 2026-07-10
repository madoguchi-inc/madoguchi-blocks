<?php
/**
 * 買取業者比較テーブル（親・動的レンダリング）
 *
 * CSSグリッドでヘッダ＋各行（子ブロック）を整列させる。
 * 行は madoguchi/comparison-row 子ブロック（$content）として差し込まれる。
 *
 * @var array    $attributes ブロック属性。
 * @var string   $content    子ブロック（行）のレンダリング結果。
 * @var WP_Block $block      ブロックインスタンス。
 */

$caption = isset( $attributes['caption'] ) ? wp_strip_all_tags( $attributes['caption'] ) : '';
$columns = ( isset( $attributes['columns'] ) && is_array( $attributes['columns'] ) ) ? $attributes['columns'] : array();

// 行が無ければ描画しない
if ( '' === trim( (string) $content ) ) {
	return '';
}

$article = ( isset( $attributes['articleId'] ) && '' !== $attributes['articleId'] )
	? $attributes['articleId']
	: (string) get_the_ID();

$col_count = count( $columns );

// グリッドの列定義: 店名 + 各列 + CTA
$template = '160px';
for ( $i = 0; $i < $col_count; $i++ ) {
	$template .= ' minmax(90px, 1fr)';
}
$template .= ' minmax(130px, auto)';

// ラッパー（アクセントカラー・文字サイズ）
$wrapper_args = array( 'class' => 'comparison-table' );
$styles       = array();
$accent       = isset( $attributes['accentColor'] ) ? sanitize_hex_color( $attributes['accentColor'] ) : '';
if ( $accent ) {
	$styles[] = '--md-brand:' . $accent;
}
$font_size = isset( $attributes['fontSize'] ) ? (int) $attributes['fontSize'] : 0;
if ( $font_size > 0 ) {
	$styles[] = '--md-table-size:' . $font_size . 'px';
}
if ( $styles ) {
	$wrapper_args['style'] = implode( ';', $styles ) . ';';
}
$wrapper = get_block_wrapper_attributes( $wrapper_args );
?>
<div <?php echo $wrapper; ?>>
	<?php if ( '' !== $caption ) : ?>
		<p class="comparison-table__caption"><?php echo esc_html( $caption ); ?></p>
	<?php endif; ?>

	<div class="comparison-table__scroll" data-article="<?php echo esc_attr( $article ); ?>">
		<div class="comparison-table__grid" style="grid-template-columns:<?php echo esc_attr( $template ); ?>;">
			<div class="comparison-table__gcell comparison-table__gcell--head comparison-table__gcell--name"><?php esc_html_e( '業者', 'madoguchi-blocks' ); ?></div>
			<?php foreach ( $columns as $col ) : ?>
				<div class="comparison-table__gcell comparison-table__gcell--head"><?php echo esc_html( isset( $col['label'] ) ? wp_strip_all_tags( $col['label'] ) : '' ); ?></div>
			<?php endforeach; ?>
			<div class="comparison-table__gcell comparison-table__gcell--head comparison-table__gcell--cta"></div>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 各行(子ブロック) ?>
		</div>
	</div>

	<p class="comparison-table__hint" aria-hidden="true"><?php esc_html_e( '← 横にスクロールできます →', 'madoguchi-blocks' ); ?></p>
</div>
