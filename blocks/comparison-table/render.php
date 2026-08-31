<?php
/**
 * 比較テーブル（親・動的レンダリング）
 *
 * CSSグリッドでヘッダ＋各行（子ブロック）を整列させる。
 * 行は madoguchi/comparison-row 子ブロック（$content）として差し込まれる。
 * 1列目の見出しは nameLabel（未設定時は「項目」）。
 *
 * 列に「グループ見出し」（columns[].group）が設定されている場合はヘッダーを2段にする:
 *   1段目 … グループ見出し（隣接する同名の列をまとめて横に結合）
 *   2段目 … 各列の見出し
 *   グループに属さない列（名称列・CTA列を含む）は1〜2段目を縦に結合する。
 * 2段時はヘッダーセルの列位置をインライン grid-column で明示し、行位置はクラスで指定する。
 * 行1〜2が全列で埋まるため、子行のセル（自動配置）は行3以降に流れる。
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

// CTA列の表示可否・ヘッダー文言（未設定時は空のまま＝従来どおり）
$show_cta  = ! isset( $attributes['showCta'] ) || (bool) $attributes['showCta'];
$cta_label = isset( $attributes['ctaLabel'] ) ? trim( wp_strip_all_tags( $attributes['ctaLabel'] ) ) : '';

// 名称列の幅（px）。未設定・不正値は既定の160pxにフォールバック。
$name_col_width = isset( $attributes['nameColWidth'] ) ? (int) $attributes['nameColWidth'] : 160;
if ( $name_col_width <= 0 ) {
	$name_col_width = 160;
}

// グリッドの列定義: 店名 + CTA（表示時は2列目固定） + 各列
$template = $name_col_width . 'px';
if ( $show_cta ) {
	$template .= ' minmax(130px, auto)';
}
for ( $i = 0; $i < $col_count; $i++ ) {
	// 列の横幅（px）が設定されていればそれを固定トラック幅にし、未設定は既存どおり可変（minmax）にする。
	$col_width = isset( $columns[ $i ]['width'] ) ? (int) $columns[ $i ]['width'] : 0;
	$template .= $col_width > 0 ? ' ' . $col_width . 'px' : ' minmax(90px, 1fr)';
}

// 列グループ: 連続する同じ非空 group を1グループにまとめる（色は先頭列の groupColor を使う）
$groups          = array();
$col_group_index = array();
$prev_group      = null;
for ( $i = 0; $i < $col_count; $i++ ) {
	$col         = is_array( $columns[ $i ] ) ? $columns[ $i ] : array();
	$group_label = isset( $col['group'] ) ? trim( wp_strip_all_tags( (string) $col['group'] ) ) : '';
	if ( '' === $group_label ) {
		$col_group_index[ $i ] = null;
		$prev_group            = null;
		continue;
	}
	if ( null !== $prev_group && $groups[ $prev_group ]['label'] === $group_label ) {
		$groups[ $prev_group ]['count']++;
	} else {
		$groups[]   = array(
			'label' => $group_label,
			'color' => isset( $col['groupColor'] ) ? sanitize_hex_color( $col['groupColor'] ) : '',
			'start' => $i,
			'count' => 1,
		);
		$prev_group = count( $groups ) - 1;
	}
	$col_group_index[ $i ] = $prev_group;
}
$two_rows = ! empty( $groups );
// 列 i のグリッド列番号（名称=1、CTA=2（表示時））
$col_base = 1 + ( $show_cta ? 1 : 0 );

// ラッパー（アクセントカラー・文字サイズ・名称列の背景色）
$cell_align   = ( isset( $attributes['cellAlign'] ) && 'center' === $attributes['cellAlign'] ) ? 'center' : 'left';
$wrapper_args = array( 'class' => 'comparison-table' . ( 'left' === $cell_align ? ' comparison-table--align-left' : '' ) );
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
// ヘッダー行の背景色・文字色（未設定は既存の既定色のまま）
$header_bg = isset( $attributes['headerBgColor'] ) ? sanitize_hex_color( $attributes['headerBgColor'] ) : '';
if ( $header_bg ) {
	$styles[] = '--md-header-bg:' . $header_bg;
}
$header_text = isset( $attributes['headerTextColor'] ) ? sanitize_hex_color( $attributes['headerTextColor'] ) : '';
if ( $header_text ) {
	$styles[] = '--md-header-text:' . $header_text;
}
// CTA下の補足文の既定の文字サイズ・色（各行は自身の属性で上書き可能）
$cta_note_size = isset( $attributes['ctaNoteFontSize'] ) ? (int) $attributes['ctaNoteFontSize'] : 0;
if ( $cta_note_size > 0 ) {
	$styles[] = '--md-cta-note-size:' . $cta_note_size . 'px';
}
$cta_note_color = isset( $attributes['ctaNoteColor'] ) ? sanitize_hex_color( $attributes['ctaNoteColor'] ) : '';
if ( $cta_note_color ) {
	$styles[] = '--md-cta-note-color:' . $cta_note_color;
}
if ( $styles ) {
	$wrapper_args['style'] = implode( ';', $styles ) . ';';
}
$wrapper = get_block_wrapper_attributes( $wrapper_args );

/**
 * ヘッダーセルの style 属性を組み立てる。
 *
 * @param array $declarations CSS宣言（'prop:value' 形式）の配列。空要素は除外。
 * @return string ' style="…"' または空文字。
 */
$head_style_attr = function( $declarations ) {
	$declarations = array_filter( $declarations );
	return $declarations ? ' style="' . esc_attr( implode( ';', $declarations ) ) . '"' : '';
};

// 列単位の文字サイズ・色（ヘッダー・値セルの両方に同じ値を適用する）
$col_style_decls = function( $col ) {
	$decls    = array();
	$col_size = isset( $col['fontSize'] ) ? (int) $col['fontSize'] : 0;
	if ( $col_size > 0 ) {
		$decls[] = '--md-col-size:' . $col_size . 'px';
	}
	$col_color = isset( $col['color'] ) ? sanitize_hex_color( $col['color'] ) : '';
	if ( $col_color ) {
		$decls[] = '--md-col-color:' . $col_color;
	}
	return $decls;
};

// 縦結合（1〜2段目）するヘッダーセルのクラス・列位置（2段時のみ付与）
$span2_class = $two_rows ? ' comparison-table__gcell--span2' : '';

// スクロールヒントの矢印（Figma uil:arrow-left / right）
$arrow_left  = '<svg viewBox="0 0 13 13" width="13" height="13" fill="currentColor" aria-hidden="true" focusable="false"><path d="M0.0812977 6.08775C0.13288 5.95488 0.210225 5.83349 0.308893 5.73054L5.72781 0.318337C5.82886 0.217412 5.94883 0.137354 6.08085 0.0827334C6.21288 0.0281131 6.35439 0 6.4973 0C6.78591 0 7.0627 0.114509 7.26678 0.318337C7.36783 0.419262 7.44799 0.539078 7.50268 0.670943C7.55737 0.802808 7.58552 0.94414 7.58552 1.08687C7.58552 1.37513 7.47086 1.65158 7.26678 1.8554L3.6903 5.41664H11.9162C12.2037 5.41664 12.4793 5.53068 12.6826 5.73368C12.8858 5.93667 13 6.212 13 6.49908C13 6.78616 12.8858 7.06148 12.6826 7.26448C12.4793 7.46747 12.2037 7.58152 11.9162 7.58152H3.6903L7.26678 11.1427C7.36837 11.2434 7.44899 11.3631 7.50401 11.495C7.55904 11.6269 7.58737 11.7684 7.58737 11.9113C7.58737 12.0542 7.55904 12.1957 7.50401 12.3276C7.44899 12.4595 7.36837 12.5792 7.26678 12.6798C7.16603 12.7813 7.04616 12.8618 6.9141 12.9168C6.78203 12.9717 6.64037 13 6.4973 13C6.35423 13 6.21257 12.9717 6.0805 12.9168C5.94843 12.8618 5.82856 12.7813 5.72781 12.6798L0.308893 7.26761C0.210225 7.16467 0.13288 7.04328 0.0812977 6.9104C-0.0271002 6.64687 -0.0271002 6.35128 0.0812977 6.08775Z"/></svg>';
$arrow_right = '<svg viewBox="0 0 13 13" width="13" height="13" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12.9187 6.08775C12.8671 5.95488 12.7898 5.83349 12.6911 5.73054L7.27219 0.318337C7.17114 0.217412 7.05117 0.137354 6.91915 0.0827334C6.78712 0.0281131 6.64561 0 6.5027 0C6.21409 0 5.9373 0.114509 5.73322 0.318337C5.63217 0.419262 5.55201 0.539078 5.49732 0.670943C5.44263 0.802808 5.41448 0.94414 5.41448 1.08687C5.41448 1.37513 5.52914 1.65158 5.73322 1.8554L9.3097 5.41664H1.08378C0.796346 5.41664 0.520682 5.53068 0.317433 5.73368C0.114184 5.93667 0 6.212 0 6.49908C0 6.78616 0.114184 7.06148 0.317433 7.26448C0.520682 7.46747 0.796346 7.58152 1.08378 7.58152H9.3097L5.73322 11.1427C5.63163 11.2434 5.55101 11.3631 5.49599 11.495C5.44096 11.6269 5.41263 11.7684 5.41263 11.9113C5.41263 12.0542 5.44096 12.1957 5.49599 12.3276C5.55101 12.4595 5.63163 12.5792 5.73322 12.6798C5.83397 12.7813 5.95384 12.8618 6.0859 12.9168C6.21797 12.9717 6.35963 13 6.5027 13C6.64577 13 6.78743 12.9717 6.9195 12.9168C7.05157 12.8618 7.17144 12.7813 7.27219 12.6798L12.6911 7.26761C12.7898 7.16467 12.8671 7.04328 12.9187 6.9104C13.0271 6.64687 13.0271 6.35128 12.9187 6.08775Z"/></svg>';
?>
<div <?php echo $wrapper; ?>>
	<?php if ( '' !== $caption ) : ?>
		<p class="comparison-table__caption"><?php echo esc_html( $caption ); ?></p>
	<?php endif; ?>

	<div class="comparison-table__scroll" data-article="<?php echo esc_attr( $article ); ?>">
		<div class="comparison-table__grid<?php echo $two_rows ? ' comparison-table__grid--two-rows' : ''; ?>" style="grid-template-columns:<?php echo esc_attr( $template ); ?>;">
			<div class="comparison-table__gcell comparison-table__gcell--head comparison-table__gcell--name<?php echo esc_attr( $span2_class ); ?>"<?php echo $two_rows ? ' style="grid-column:1"' : ''; ?>><?php echo esc_html( $name_label ); ?></div>
			<?php if ( $show_cta ) : ?>
				<div class="comparison-table__gcell comparison-table__gcell--head comparison-table__gcell--cta<?php echo esc_attr( $span2_class ); ?>"<?php echo $two_rows ? ' style="grid-column:2"' : ''; ?>><?php echo esc_html( $cta_label ); ?></div>
			<?php endif; ?>
			<?php for ( $i = 0; $i < $col_count; $i++ ) : ?>
				<?php
				$col       = is_array( $columns[ $i ] ) ? $columns[ $i ] : array();
				$label     = isset( $col['label'] ) ? wp_strip_all_tags( $col['label'] ) : '';
				$col_decls = $col_style_decls( $col );
				$group_idx = isset( $col_group_index[ $i ] ) ? $col_group_index[ $i ] : null;
				$grid_col  = $col_base + $i + 1;
				?>
				<?php if ( ! $two_rows ) : ?>
					<div class="comparison-table__gcell comparison-table__gcell--head"<?php echo $head_style_attr( $col_decls ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 事前にesc_attr済み ?>><?php echo esc_html( $label ); ?></div>
				<?php elseif ( null === $group_idx ) : ?>
					<div class="comparison-table__gcell comparison-table__gcell--head comparison-table__gcell--span2"<?php echo $head_style_attr( array_merge( array( 'grid-column:' . $grid_col ), $col_decls ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 事前にesc_attr済み ?>><?php echo esc_html( $label ); ?></div>
				<?php else : ?>
					<?php
					$group      = $groups[ $group_idx ];
					$group_decl = $group['color'] ? '--md-group-bg:' . $group['color'] : '';
					?>
					<?php if ( $group['start'] === $i ) : ?>
						<div class="comparison-table__gcell comparison-table__gcell--head comparison-table__gcell--group"<?php echo $head_style_attr( array( 'grid-column:' . $grid_col . ' / span ' . (int) $group['count'], $group_decl ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 事前にesc_attr済み ?>><?php echo esc_html( $group['label'] ); ?></div>
					<?php endif; ?>
					<div class="comparison-table__gcell comparison-table__gcell--head comparison-table__gcell--sub"<?php echo $head_style_attr( array_merge( array( 'grid-column:' . $grid_col, $group_decl ), $col_decls ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 事前にesc_attr済み ?>><?php echo esc_html( $label ); ?></div>
				<?php endif; ?>
			<?php endfor; ?>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 各行(子ブロック) ?>
		</div>
	</div>

	<p class="comparison-table__hint" aria-hidden="true">
		<span class="comparison-table__hint-arrow"><?php echo $arrow_left; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 固定SVG ?></span>
		<span class="comparison-table__hint-text"><?php esc_html_e( '横にスクロールできます', 'madoguchi-blocks' ); ?></span>
		<span class="comparison-table__hint-arrow"><?php echo $arrow_right; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 固定SVG ?></span>
	</p>
</div>
