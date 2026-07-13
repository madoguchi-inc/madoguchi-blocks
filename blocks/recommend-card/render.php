<?php
/**
 * おすすめリンクカードブロック（動的レンダリング）
 *
 * おすすめ記事へ誘導するリンクカード。4パターンを切り替えられる。
 *   - button    … ボタン強調タイプ。CTAボタンブロック（$content）を内包し、
 *                 カード全体のクリックは view.js がボタンへ委譲する。
 *   - checklist … チェックリスト風。カード全体が <a> リンクで、右端に丸囲みシェブロンを表示する。
 *   - simple    … シンプル・ミニマル。白カード＋細枠＋素のアイコン＋右シェブロン。カード全体が <a>。
 *   - infobox   … 情報ボックス。左にブランドカラーの帯（アイコン＋ラベル）。カード全体が <a>。
 *
 * @var array    $attributes ブロック属性。
 * @var string   $content    内部コンテンツ（button パターンのCTAボタン）。
 * @var WP_Block $block      ブロックインスタンス。
 */

$patterns    = array( 'button', 'checklist', 'simple', 'infobox' );
$pattern     = ( isset( $attributes['pattern'] ) && in_array( $attributes['pattern'], $patterns, true ) ) ? $attributes['pattern'] : 'button';
$is_link     = 'button' !== $pattern;
$label       = isset( $attributes['label'] ) ? wp_strip_all_tags( $attributes['label'] ) : '';
$title       = wp_kses( isset( $attributes['title'] ) ? $attributes['title'] : '', array( 'br' => array() ) );
$description = wp_kses( isset( $attributes['description'] ) ? $attributes['description'] : '', array( 'br' => array() ) );
$link_url    = isset( $attributes['linkUrl'] ) ? $attributes['linkUrl'] : '';
$image_url   = isset( $attributes['imageUrl'] ) ? $attributes['imageUrl'] : '';
$icon_key    = isset( $attributes['iconKey'] ) ? $attributes['iconKey'] : 'search';

// テキストが全て空で、内包ボタンも無ければ描画しない
$has_text = '' !== $label
	|| '' !== trim( wp_strip_all_tags( $title ) )
	|| '' !== trim( wp_strip_all_tags( $description ) );
if ( ! $has_text && '' === trim( (string) $content ) ) {
	return '';
}

// 内蔵アイコン（src/condition-card/icons.js と同じ stroke ベースの図形。要同期）
$icon_paths = array(
	'truck'     => '<path d="M3 6h11v9H3z"/><path d="M14 9h4l3 3v3h-7z"/><circle cx="7" cy="17.5" r="1.6"/><circle cx="17.5" cy="17.5" r="1.6"/>',
	'box'       => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M4 7.5l8 4.5 8-4.5"/><path d="M12 12v9"/>',
	'cleaning'  => '<path d="M14 3l4 4"/><path d="M16.5 5.5l-8 8"/><path d="M8.5 13.5l-4 7.5 8-3.5z"/>',
	'aircon'    => '<rect x="3" y="5" width="18" height="7" rx="1.5"/><path d="M6 9h9"/><path d="M7.5 15c0 1.6 1 2.6 2.6 2.6"/><path d="M12.5 15c0 2.1 1.3 3.1 3.1 3.1"/>',
	'furniture' => '<path d="M4 11V9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2"/><path d="M3 11a2 2 0 0 1 2 2v3h14v-3a2 2 0 0 1 2-2"/><path d="M6 19v-3M18 19v-3"/>',
	'memorial'  => '<path d="M12 20s-7-4.5-7-9.5A3.5 3.5 0 0 1 12 7a3.5 3.5 0 0 1 7 3.5C19 15.5 12 20 12 20z"/>',
	'buy'       => '<path d="M4 4h7l9 9-7 7-9-9z"/><circle cx="8" cy="8" r="1.4"/>',
	'search'    => '<circle cx="11" cy="11" r="6.5"/><path d="M15.8 15.8L20.5 20.5"/>',
	'checklist' => '<rect x="5" y="5" width="14" height="16" rx="2"/><path d="M9 5V4a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 4v1"/><path d="M8.5 11.5l2 2 4.5-4.5"/><path d="M8.5 17h7"/>',
	'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 7.5v.01"/>',
);
$icon_shape = isset( $icon_paths[ $icon_key ] ) ? $icon_paths[ $icon_key ] : $icon_paths['search'];
$icon_svg   = '<svg class="recommend-card__icon-svg" viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icon_shape . '</svg>';

// ラッパー（パターン別クラス + アクセントカラー + カード背景色）
$wrapper_args = array( 'class' => 'recommend-card recommend-card--' . $pattern );
$styles       = array();
$accent       = isset( $attributes['accentColor'] ) ? sanitize_hex_color( $attributes['accentColor'] ) : '';
if ( $accent ) {
	$styles[] = '--md-brand:' . $accent;
}
// カード背景色はインライン style で出力（パターン既定の背景より優先される）
$card_bg = isset( $attributes['backgroundColor'] ) ? sanitize_hex_color( $attributes['backgroundColor'] ) : '';
if ( $card_bg ) {
	$styles[] = 'background:' . $card_bg;
}
if ( $styles ) {
	$wrapper_args['style'] = implode( ';', $styles ) . ';';
}
$wrapper = get_block_wrapper_attributes( $wrapper_args );

// メディア（画像 or 内蔵アイコン）
ob_start();
?>
<?php $media_has_label = ( 'infobox' === $pattern && '' !== $label ); // ラベル入りの帯は読み上げ対象にする ?>
<div class="recommend-card__media"<?php echo $media_has_label ? '' : ' aria-hidden="true"'; ?>>
	<?php if ( $image_url ) : ?>
		<img class="recommend-card__image" src="<?php echo esc_url( $image_url ); ?>" alt="" loading="lazy" />
	<?php else : ?>
		<span class="recommend-card__icon"><?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<?php endif; ?>
	<?php if ( 'infobox' === $pattern && '' !== $label ) : // 情報ボックスはラベルを左帯の中に表示する ?>
		<p class="recommend-card__label"><?php echo esc_html( $label ); ?></p>
	<?php endif; ?>
</div>
<div class="recommend-card__content">
	<?php if ( 'infobox' !== $pattern && '' !== $label ) : ?>
		<p class="recommend-card__label"><?php echo esc_html( $label ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== trim( wp_strip_all_tags( $title ) ) ) : ?>
		<p class="recommend-card__title"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<?php endif; ?>
	<?php if ( '' !== trim( wp_strip_all_tags( $description ) ) ) : ?>
		<p class="recommend-card__desc"><?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<?php endif; ?>
	<?php if ( 'button' === $pattern && '' !== trim( (string) $content ) ) : ?>
		<div class="recommend-card__action"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php endif; ?>
</div>
<?php
$inner = ob_get_clean();

// button 以外はカード全体を <a>、button は <div>（<a> の入れ子を避け、クリックは view.js が委譲）
if ( $is_link ) :
	?>
	<a <?php echo $wrapper; ?> href="<?php echo esc_url( $link_url ? $link_url : '#' ); ?>">
		<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="recommend-card__chevron" aria-hidden="true"></span>
	</a>
	<?php
else :
	?>
	<div <?php echo $wrapper; ?>>
		<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php
endif;
