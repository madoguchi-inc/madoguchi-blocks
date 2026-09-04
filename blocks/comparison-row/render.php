<?php
/**
 * 比較テーブルの行（子・動的レンダリング）
 *
 * 親のグリッドに整列させるため、行ラッパーは display:contents とし、
 * 各セル（店名・CTA・各列値）を親グリッドの直接の要素として並べる（CTAは2列目固定）。
 * 店名はCTAボタンと同じURLへのリンクにし、文字色を上書きできる。
 *
 * @var array    $attributes ブロック属性。
 * @var string   $content    CTA列に置いた子ブロック（CTAボタン等）のレンダリング結果。
 * @var WP_Block $block      ブロックインスタンス。
 */

$name       = isset( $attributes['name'] ) ? wp_strip_all_tags( $attributes['name'] ) : '';
$rating     = isset( $attributes['rating'] ) ? floatval( $attributes['rating'] ) : 0;
$values     = ( isset( $attributes['values'] ) && is_array( $attributes['values'] ) ) ? $attributes['values'] : array();
$name_color = isset( $attributes['nameColor'] ) ? madoguchi_blocks_sanitize_color( $attributes['nameColor'] ) : '';
$cta_note   = isset( $attributes['ctaNote'] ) ? wp_kses( $attributes['ctaNote'], array( 'br' => array(), 'strong' => array(), 'em' => array() ) ) : '';

// CTA下の補足文の文字サイズ・色（未設定ならテーブル側の既定値をCSS継承で使う）
$cta_note_styles = array();
$cta_note_size   = isset( $attributes['ctaNoteFontSize'] ) ? (int) $attributes['ctaNoteFontSize'] : 0;
if ( $cta_note_size > 0 ) {
	$cta_note_styles[] = '--md-cta-note-size:' . $cta_note_size . 'px';
}
$cta_note_color = isset( $attributes['ctaNoteColor'] ) ? madoguchi_blocks_sanitize_color( $attributes['ctaNoteColor'] ) : '';
if ( $cta_note_color ) {
	$cta_note_styles[] = '--md-cta-note-color:' . $cta_note_color;
}
$cta_note_style_attr = $cta_note_styles ? ' style="' . esc_attr( implode( ';', $cta_note_styles ) ) . '"' : '';

// 親から列定義・CTA表示可否を受け取る（評価表示方式は行ごとの自属性）
$columns        = isset( $block->context['madoguchi/comparisonColumns'] ) && is_array( $block->context['madoguchi/comparisonColumns'] )
	? $block->context['madoguchi/comparisonColumns']
	: array();
$col_count      = count( $columns );
$show_cta       = ! isset( $block->context['madoguchi/comparisonShowCta'] ) || (bool) $block->context['madoguchi/comparisonShowCta'];
$rating_display = isset( $attributes['ratingDisplay'] ) ? $attributes['ratingDisplay'] : 'star';
$width          = max( 0, min( 100, ( $rating / 5 ) * 100 ) );

// CTA列（子ブロック）のURLを取得し、店名も同じリンクにする
$cta_url = '';
if ( ! empty( $block->parsed_block['innerBlocks'] ) ) {
	foreach ( $block->parsed_block['innerBlocks'] as $inner_block ) {
		if ( isset( $inner_block['blockName'], $inner_block['attrs']['url'] ) && 'madoguchi/cta-button' === $inner_block['blockName'] ) {
			$cta_url = $inner_block['attrs']['url'];
			break;
		}
	}
}

$name_style = $name_color ? ' style="color:' . esc_attr( $name_color ) . '"' : '';

/**
 * チェック列の記号（◎／✓／△／✕）の SVG。
 * 図形は Figma「記号」コンポーネントの path をそのまま使う。色は CSS（currentColor）で与える。
 * ※ エディタ側 src/comparison-row/marks.js にも同じ図形を持つ。追加・変更したら両方を同期すること。
 */
$mark_svg_attrs = 'class="comparison-table__mark-icon comparison-table__mark-icon--%s" width="15" height="15" fill="none" stroke="currentColor" aria-hidden="true" focusable="false"';
$mark_svgs      = array(
	'circle'   => '<svg viewBox="0 0 16.7647 16.7647" ' . sprintf( $mark_svg_attrs, 'circle' ) . ' stroke-width="1.76471" stroke-linecap="square"><path d="M15.8824 8.38235C15.8824 12.5245 12.5245 15.8824 8.38235 15.8824C4.24022 15.8824 0.882353 12.5245 0.882353 8.38235C0.882353 4.24022 4.24022 0.882353 8.38235 0.882353C12.5245 0.882353 15.8824 4.24022 15.8824 8.38235Z"/><path d="M11.8824 8.38235C11.8824 10.3153 10.3153 11.8824 8.38235 11.8824C6.44936 11.8824 4.88235 10.3153 4.88235 8.38235C4.88235 6.44936 6.44936 4.88235 8.38235 4.88235C10.3153 4.88235 11.8824 6.44936 11.8824 8.38235Z"/></svg>',
	'check'    => '<svg viewBox="0 0 17 15" ' . sprintf( $mark_svg_attrs, 'check' ) . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1.00004 8L6.0385 12L16 2"/></svg>',
	'triangle' => '<svg viewBox="0 0 18.7059 15.8824" ' . sprintf( $mark_svg_attrs, 'triangle' ) . ' stroke-width="1.76471" stroke-linejoin="round"><path d="M17.8235 15H0.882353L9.35294 0.882353L17.8235 15Z"/></svg>',
	'cross'    => '<svg viewBox="0 0 15.8824 15.8824" ' . sprintf( $mark_svg_attrs, 'cross' ) . ' stroke-width="1.76471" stroke-linecap="round"><path d="M0.882353 0.882353L15 15M15 0.882353L0.882353 15"/></svg>',
);

$wrapper = get_block_wrapper_attributes( array( 'class' => 'comparison-table__row' ) );
?>
<div <?php echo $wrapper; ?>>
	<div class="comparison-table__gcell comparison-table__gcell--name">
		<?php if ( '' !== trim( (string) $cta_url ) ) : ?>
			<a class="comparison-table__shop-link" href="<?php echo esc_url( $cta_url ); ?>">
				<span class="comparison-table__shop"<?php echo $name_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 事前にesc_attr済み ?>><?php echo esc_html( $name ); ?></span>
			</a>
		<?php else : ?>
			<span class="comparison-table__shop"<?php echo $name_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 事前にesc_attr済み ?>><?php echo esc_html( $name ); ?></span>
		<?php endif; ?>
		<?php if ( 'pr' === $rating_display ) : ?>
			<span class="comparison-table__pr-badge"><?php esc_html_e( 'PR', 'madoguchi-blocks' ); ?></span>
		<?php elseif ( 'star' === $rating_display && $rating > 0 ) : ?>
			<span class="comparison-table__rating" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: 評価値 */ __( '評価 %s / 5', 'madoguchi-blocks' ), number_format( $rating, 1 ) ) ); ?>">
				<span class="comparison-table__stars"><span class="comparison-table__stars-fill" style="width:<?php echo esc_attr( $width ); ?>%"></span></span>
				<span class="comparison-table__score"><?php echo esc_html( number_format( $rating, 1 ) ); ?></span>
			</span>
		<?php endif; ?>
	</div>
	<?php if ( $show_cta ) : ?>
		<div class="comparison-table__gcell comparison-table__gcell--cta">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped CTA列の子ブロック ?>
			<?php if ( '' !== trim( wp_strip_all_tags( $cta_note ) ) ) : ?>
				<p class="comparison-table__cta-note"<?php echo $cta_note_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 事前にesc_attr済み ?>><?php echo $cta_note; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 事前にwp_kses済み ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php for ( $i = 0; $i < $col_count; $i++ ) : ?>
		<?php
		// 列単位の文字サイズ・色（親のヘッダーセルと同じ値を値セルにも適用する）
		$col        = isset( $columns[ $i ] ) && is_array( $columns[ $i ] ) ? $columns[ $i ] : array();
		$col_type   = isset( $col['type'] ) ? $col['type'] : 'text';
		$col_styles = array();
		$col_size   = isset( $col['fontSize'] ) ? (int) $col['fontSize'] : 0;
		if ( $col_size > 0 ) {
			$col_styles[] = '--md-col-size:' . $col_size . 'px';
		}
		$col_color = isset( $col['color'] ) ? madoguchi_blocks_sanitize_color( $col['color'] ) : '';
		if ( $col_color ) {
			$col_styles[] = '--md-col-color:' . $col_color;
		}
		$col_style_attr = $col_styles ? ' style="' . esc_attr( implode( ';', $col_styles ) ) . '"' : '';
		$cell_value     = isset( $values[ $i ] ) ? $values[ $i ] : '';
		$cell_class     = 'comparison-table__gcell';
		if ( in_array( $col_type, array( 'check', 'review', 'score' ), true ) ) {
			$cell_class .= ' comparison-table__gcell--' . $col_type;
		}
		?>
		<div class="<?php echo esc_attr( $cell_class ); ?>"<?php echo $col_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 事前にesc_attr済み ?>>
			<?php if ( 'check' === $col_type ) : ?>
				<?php
				// チェック列: { mark: 'none'|'circle'|'check'|'triangle'|'cross', text: string } の想定
				// （旧データ・型不一致時はテキストのみ扱いにフォールバック）。記号の下に補足テキストを縦に並べる。
				$mark = ( is_array( $cell_value ) && isset( $cell_value['mark'] ) ) ? $cell_value['mark'] : '';
				$text = ( is_array( $cell_value ) && isset( $cell_value['text'] ) )
					? wp_kses( $cell_value['text'], array( 'br' => array(), 'strong' => array(), 'em' => array() ) )
					: ( is_array( $cell_value ) ? '' : wp_strip_all_tags( (string) $cell_value ) );
				?>
				<span class="comparison-table__check">
					<?php if ( isset( $mark_svgs[ $mark ] ) ) : ?>
						<?php echo $mark_svgs[ $mark ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 固定SVG ?>
					<?php endif; ?>
					<?php if ( '' !== trim( wp_strip_all_tags( $text ) ) ) : ?>
						<span class="comparison-table__check-text"><?php echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 事前にwp_kses済み ?></span>
					<?php endif; ?>
				</span>
			<?php elseif ( 'review' === $col_type ) : ?>
				<?php
				// 口コミ列: { mode: 'stars'|'pr'|'none', rating: number }。旧データ・数値のみの場合は星評価として扱う。
				// 表示は「数値（上）→ 星（下）」の縦並び。
				$mode   = ( is_array( $cell_value ) && isset( $cell_value['mode'] ) ) ? $cell_value['mode'] : 'stars';
				$rating = ( is_array( $cell_value ) && isset( $cell_value['rating'] ) ) ? floatval( $cell_value['rating'] ) : ( is_numeric( $cell_value ) ? floatval( $cell_value ) : 0 );
				$rwidth = max( 0, min( 100, ( $rating / 5 ) * 100 ) );
				?>
				<?php if ( 'pr' === $mode ) : ?>
					<span class="comparison-table__pr-badge"><?php esc_html_e( 'PR', 'madoguchi-blocks' ); ?></span>
				<?php elseif ( 'none' !== $mode ) : ?>
					<span class="comparison-table__rating" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: 評価値 */ __( '評価 %s / 5', 'madoguchi-blocks' ), number_format( $rating, 1 ) ) ); ?>">
						<span class="comparison-table__score"><?php echo esc_html( number_format( $rating, 1 ) ); ?></span>
						<span class="comparison-table__stars"><span class="comparison-table__stars-fill" style="width:<?php echo esc_attr( $rwidth ); ?>%"></span></span>
					</span>
				<?php endif; ?>
			<?php elseif ( 'score' === $col_type ) : ?>
				<?php
				// 検証スコア列: 数値（0〜5）を星の塗り幅で表す。
				$score  = ( is_array( $cell_value ) && isset( $cell_value['rating'] ) ) ? floatval( $cell_value['rating'] ) : ( is_numeric( $cell_value ) ? floatval( $cell_value ) : 0 );
				$swidth = max( 0, min( 100, ( $score / 5 ) * 100 ) );
				?>
				<span class="comparison-table__stars comparison-table__stars--score"><span class="comparison-table__stars-fill comparison-table__stars-fill--score" style="width:<?php echo esc_attr( $swidth ); ?>%"></span></span>
			<?php else : ?>
				<?php echo esc_html( is_array( $cell_value ) ? '' : wp_strip_all_tags( (string) $cell_value ) ); ?>
			<?php endif; ?>
		</div>
	<?php endfor; ?>
</div>
