<?php
/**
 * 比較テーブル（親・動的レンダリング）
 *
 * CSSグリッドでヘッダ＋各行（子ブロック）を整列させる。
 * 行は madoguchi/comparison-row 子ブロック（$content）として差し込まれる。
 * 1列目の見出しは nameLabel（未設定時は「項目」）。
 *
 * @var array    $attributes ブロック属性。
 * @var string   $content    子ブロック（行）のレンダリング結果。
 * @var WP_Block $block      ブロックインスタンス。
 */

$caption = isset( $attributes['caption'] ) ? wp_strip_all_tags( $attributes['caption'] ) : '';
$columns = ( isset( $attributes['columns'] ) && is_array( $attributes['columns'] ) ) ? $attributes['columns'] : array();

// 1列目（行名）の見出し。未設定なら汎用の既定値にフォールバックする。
$name_label = isset( $attributes['nameLabel'] ) ? trim( wp_strip_all_tags( $attributes['nameLabel'] ) ) : '';
if ( '' === $name_label ) {
	$name_label = __( '項目', 'madoguchi-blocks' );
}

// 行が無ければ描画しない
if ( '' === trim( (string) $content ) ) {
	return '';
}

$article = ( isset( $attributes['articleId'] ) && '' !== $attributes['articleId'] )
	? $attributes['articleId']
	: (string) get_the_ID();

$col_count = count( $columns );

// 名称列の幅（px）。未設定・不正値は既定の160pxにフォールバック。
$name_col_width = isset( $attributes['nameColWidth'] ) ? (int) $attributes['nameColWidth'] : 160;
if ( $name_col_width <= 0 ) {
	$name_col_width = 160;
}

// グリッドの列定義: 店名 + CTA（2列目固定） + 各列
$template = $name_col_width . 'px minmax(130px, auto)';
for ( $i = 0; $i < $col_count; $i++ ) {
	$template .= ' minmax(90px, 1fr)';
}

// ラッパー（アクセントカラー・文字サイズ・名称列の背景色）
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
$name_bg = isset( $attributes['nameColBgColor'] ) ? sanitize_hex_color( $attributes['nameColBgColor'] ) : '';
if ( $name_bg ) {
	$styles[] = '--md-name-bg:' . $name_bg;
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
			<div class="comparison-table__gcell comparison-table__gcell--head comparison-table__gcell--name"><?php echo esc_html( $name_label ); ?></div>
			<div class="comparison-table__gcell comparison-table__gcell--head comparison-table__gcell--cta"></div>
			<?php foreach ( $columns as $col ) : ?>
				<div class="comparison-table__gcell comparison-table__gcell--head"><?php echo esc_html( isset( $col['label'] ) ? wp_strip_all_tags( $col['label'] ) : '' ); ?></div>
			<?php endforeach; ?>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 各行(子ブロック) ?>
		</div>
	</div>

	<p class="comparison-table__hint" aria-hidden="true"><?php esc_html_e( '← 横にスクロールできます →', 'madoguchi-blocks' ); ?></p>
</div>
