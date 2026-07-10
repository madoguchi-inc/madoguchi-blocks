<?php
/**
 * 著者情報ブロック（動的レンダリング）
 *
 * 名前・アバターは未入力なら「この記事の著者」を既定として使う。
 * アバター画像も無ければデフォルトのイラストを表示する。
 * 併せて schema.org/Person を JSON-LD で同梱する。
 *
 * @var array    $attributes ブロック属性。
 * @var string   $content    内部コンテンツ（未使用）。
 * @var WP_Block $block      ブロックインスタンス。
 */

$name   = isset( $attributes['name'] ) ? trim( wp_strip_all_tags( $attributes['name'] ) ) : '';
$role   = isset( $attributes['role'] ) ? $attributes['role'] : '';
$bio    = isset( $attributes['bio'] ) ? $attributes['bio'] : '';
$avatar = isset( $attributes['avatarUrl'] ) ? $attributes['avatarUrl'] : '';

// 名前・アバターの既定値をこの記事の著者から補完する
$post = get_post();
if ( $post ) {
	if ( '' === $name ) {
		$name = get_the_author_meta( 'display_name', $post->post_author );
	}
	if ( '' === $avatar ) {
		$bio_meta = get_the_author_meta( 'description', $post->post_author );
		if ( '' === trim( wp_strip_all_tags( $bio ) ) && $bio_meta ) {
			$bio = $bio_meta;
		}
	}
}

// 名前もプロフィールも無ければ描画しない
if ( '' === $name && '' === trim( wp_strip_all_tags( $bio ) ) ) {
	return '';
}

// JSON-LD（schema.org/Person）を構築する。
// 表示と同じフォールバック後の値を使い、名前がある場合のみ出力する。
$json = '';
if ( '' !== $name ) {
	$ld = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Person',
		'name'     => $name,
	);

	$role_plain = trim( wp_strip_all_tags( (string) $role ) );
	if ( '' !== $role_plain ) {
		$ld['jobTitle'] = $role_plain;
	}

	// プロフィール文をプレーンテキスト化（<br> は改行に変換）
	$bio_plain = str_replace( array( '<br>', '<br/>', '<br />' ), "\n", (string) $bio );
	$bio_plain = trim( wp_strip_all_tags( $bio_plain ) );
	if ( '' !== $bio_plain ) {
		$ld['description'] = $bio_plain;
	}

	if ( $avatar ) {
		$ld['image'] = esc_url_raw( $avatar );
	}

	// スラッシュはエスケープしたまま（</script> の混入を防ぐ）。日本語は可読性のため非エスケープ。
	$json = wp_json_encode( $ld, JSON_UNESCAPED_UNICODE );
}

// デフォルトアバター（画像もこの記事の著者画像も無い場合）
$default_avatar = '<svg class="author-box__avatar-svg" viewBox="0 0 64 64" width="64" height="64" aria-hidden="true" focusable="false"><circle cx="32" cy="32" r="32" fill="#e8eaed"/><circle cx="32" cy="26" r="11" fill="#b6bcc4"/><path d="M14 55c0-10 8-16 18-16s18 6 18 16z" fill="#b6bcc4"/></svg>';

// ラッパー（アクセントカラー・枠線の色/太さ/角丸）
$wrapper_args = array( 'class' => 'author-box' );
$styles       = array();
$accent       = isset( $attributes['accentColor'] ) ? sanitize_hex_color( $attributes['accentColor'] ) : '';
if ( $accent ) {
	$styles[] = '--md-brand:' . $accent;
}
$border_color = isset( $attributes['borderColor'] ) ? sanitize_hex_color( $attributes['borderColor'] ) : '';
if ( $border_color ) {
	$styles[] = '--md-border-color:' . $border_color;
}
$border_width = isset( $attributes['borderWidth'] ) ? max( 0, (int) $attributes['borderWidth'] ) : 1;
$styles[]     = '--md-border-width:' . $border_width . 'px';
$radius       = isset( $attributes['borderRadius'] ) ? max( 0, (int) $attributes['borderRadius'] ) : 14;
$styles[]     = '--md-radius:' . $radius . 'px';
$wrapper_args['style'] = implode( ';', $styles ) . ';';
$wrapper               = get_block_wrapper_attributes( $wrapper_args );
?>
<div <?php echo $wrapper; ?>>
	<div class="author-box__avatar">
		<?php if ( $avatar ) : ?>
			<img src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" />
		<?php else : ?>
			<?php echo $default_avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
	</div>
	<div class="author-box__body">
		<?php if ( '' !== trim( wp_strip_all_tags( $role ) ) ) : ?>
			<p class="author-box__role"><?php echo esc_html( wp_strip_all_tags( $role ) ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $name ) : ?>
			<p class="author-box__name"><?php echo esc_html( $name ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== trim( wp_strip_all_tags( $bio ) ) ) : ?>
			<p class="author-box__bio"><?php echo wp_kses( $bio, array( 'br' => array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $content ) ) : ?>
			<div class="author-box__inner"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php endif; ?>
	</div>
	<?php if ( '' !== $json ) : ?>
		<script type="application/ld+json"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
	<?php endif; ?>
</div>
