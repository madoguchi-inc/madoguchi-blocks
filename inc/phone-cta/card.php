<?php
/**
 * 電話CTA の店舗カード 1 枚。
 *
 * 記事内のブロック（blocks/phone-cta/render.php）と、キャンペーンLP のテンプレートの
 * 両方から読み込む。同じ見た目・同じ挙動にするため、マークアップはここ 1 か所だけに置く。
 *
 * PC のモーダルは隠しチェックボックスと label で開く（JS を使わない）。
 * 記事本文が REST 経由で配信される先では view.js が動かないため。
 *
 * @var array      $c             card_state() の戻り値
 * @var bool       $show_pc_modal PC で番号と QR のモーダルを出すか
 * @var array|null $modal_banner  モーダルの下に出すキャンペーンバナー（無ければ null）
 */
if ( ! isset( $c ) || ! is_array( $c ) ) {
	return;
}
$show_pc_modal = isset( $show_pc_modal ) ? (bool) $show_pc_modal : true;
$modal_banner  = isset( $modal_banner ) && is_array( $modal_banner ) ? $modal_banner : null;
?>
<li class="phone-cta__card <?php echo $c['is_open'] ? 'is-open' : 'is-closed'; ?>"
	data-service="<?php echo esc_attr( $c['service'] ); ?>"
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
		<?php if ( '' !== trim( wp_strip_all_tags( $c['lead'] ) ) ) : ?>
			<div class="phone-cta__lead">
				<p class="phone-cta__lead-text"><?php echo madoguchi_blocks_phone_cta_kses( $c['lead'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			</div>
		<?php endif; ?>
	</div>

	<div class="phone-cta__action">
		<?php if ( 'web' === $c['mode'] ) : ?>
			<a class="phone-cta__button phone-cta__button--web" href="<?php echo esc_url( $c['fallback_url'] ); ?>" target="_blank" rel="noopener">
				<?php if ( '' !== $c['free_tag'] ) : ?><span class="phone-cta__free"><?php echo esc_html( $c['free_tag'] ); ?></span><?php endif; ?>
				<span class="phone-cta__button-body">
					<?php echo madoguchi_blocks_phone_cta_icon( 'touch' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php // 2 片の間に空白文字が入らないよう 1 行で出す ?>
					<span class="phone-cta__button-label"><?php if ( '' !== $c['web_label_parts'][0] ) : ?><span class="phone-cta__button-head"><?php echo esc_html( $c['web_label_parts'][0] ); ?></span><?php endif; ?><span class="phone-cta__button-tail"><?php echo esc_html( $c['web_label_parts'][1] ); ?></span></span>
				</span>
				<span class="phone-cta__chevron" aria-hidden="true"></span>
			</a>
		<?php else : ?>
			<?php
			// ボタンの中身は SP の tel: リンクと PC のモーダル起点で同じものを使う
			$has_balloon  = 'tel' === $c['mode'] && '' !== $c['balloon'];
			$button_inner = ( $has_balloon ? '<span class="phone-cta__balloon">' . esc_html( $c['balloon'] ) . '</span>' : '' )
				. ( '' !== $c['free_tag'] ? '<span class="phone-cta__free">' . esc_html( $c['free_tag'] ) . '</span>' : '' )
				. '<span class="phone-cta__button-body">'
				. madoguchi_blocks_phone_cta_icon( 'phone' )
				. '<span class="phone-cta__button-label">'
				. ( '' !== $c['label_parts'][0] ? '<span class="phone-cta__button-head">' . esc_html( $c['label_parts'][0] ) . '</span>' : '' )
				. '<span class="phone-cta__button-tail">' . esc_html( $c['label_parts'][1] ) . '</span>'
				. '</span></span>'
				. '<span class="phone-cta__chevron" aria-hidden="true"></span>';
			$button_class = 'phone-cta__button' . ( $has_balloon ? ' phone-cta__button--balloon' : '' );
			$qr_svg       = madoguchi_blocks_phone_cta_qr_svg( $c['qr_svg'] );
			$use_modal    = $show_pc_modal && '' !== $c['tel_display'];
			// 同じ店舗を 2 回選んだ場合やブロックを複数置いた場合に id が衝突しないよう通し番号を混ぜる
			$modal_id     = 'phone-cta-modal-' . (int) get_the_ID() . '-' . sanitize_html_class( $c['uuid'] ) . '-' . madoguchi_blocks_phone_cta_seq();
			?>
			<?php if ( $use_modal ) : ?>
				<?php // PC 用: チェックボックスでモーダルを開く（SPA では view.js が動かないため JS を使わない） ?>
				<input class="phone-cta__modal-toggle" type="checkbox" id="<?php echo esc_attr( $modal_id ); ?>">
				<label class="<?php echo esc_attr( $button_class . ' phone-cta__button--modal' ); ?>" for="<?php echo esc_attr( $modal_id ); ?>">
					<?php echo $button_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</label>
			<?php endif; ?>
			<a class="<?php echo esc_attr( $button_class . ( $use_modal ? ' phone-cta__button--tel' : '' ) ); ?>" href="<?php echo esc_url( $c['tel_href'] ); ?>">
				<?php echo $button_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
			<?php if ( $use_modal ) : ?>
				<div class="phone-cta__modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( sprintf( __( '%s の電話番号', 'madoguchi-blocks' ), $c['name'] ) ); ?>">
					<label class="phone-cta__modal-backdrop" for="<?php echo esc_attr( $modal_id ); ?>" aria-hidden="true"></label>
					<div class="phone-cta__modal-panel">
						<label class="phone-cta__modal-close" for="<?php echo esc_attr( $modal_id ); ?>"><span class="screen-reader-text"><?php esc_html_e( '閉じる', 'madoguchi-blocks' ); ?></span></label>
						<div class="phone-cta__modal-head">
							<div class="phone-cta__modal-logo">
								<?php if ( '' !== $c['logo_url'] ) : ?>
									<img src="<?php echo esc_url( $c['logo_url'] ); ?>" alt="<?php echo esc_attr( $c['name'] ); ?>" loading="lazy">
								<?php endif; ?>
							</div>
							<div class="phone-cta__modal-titles">
								<p class="phone-cta__modal-name"><?php echo esc_html( $c['name'] ); ?></p>
								<?php if ( '' !== trim( wp_strip_all_tags( $c['lead'] ) ) ) : ?>
									<p class="phone-cta__modal-lead"><?php echo madoguchi_blocks_phone_cta_kses( $c['lead'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
								<?php endif; ?>
							</div>
						</div>
						<div class="phone-cta__modal-tel">
							<span class="phone-cta__modal-tel-icon"><?php echo madoguchi_blocks_phone_cta_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<div class="phone-cta__modal-tel-body">
								<p class="phone-cta__modal-number"><?php echo esc_html( $c['tel_display'] ); ?></p>
								<?php if ( '' !== $c['reception_text'] ) : ?>
									<p class="phone-cta__modal-hours">
										<span class="phone-cta__modal-hours-label"><?php esc_html_e( '受付時間', 'madoguchi-blocks' ); ?></span>
										<span class="phone-cta__modal-hours-value"><?php echo esc_html( $c['reception_text'] ); ?></span>
									</p>
								<?php endif; ?>
							</div>
							<?php if ( '' !== $qr_svg ) : ?>
								<div class="phone-cta__modal-qr">
									<span class="phone-cta__modal-qr-hint"><?php esc_html_e( 'スマホで電話番号を読み取る', 'madoguchi-blocks' ); ?></span>
									<span class="phone-cta__modal-qr-image"><?php echo $qr_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								</div>
							<?php endif; ?>
						</div>
						<?php if ( null !== $modal_banner ) : ?>
							<?php
							$modal_banner_img = sprintf(
								'<img src="%1$s"%2$s alt="%3$s"%4$s%5$s loading="lazy" decoding="async">',
								esc_url( $modal_banner['src'] ),
								'' !== $modal_banner['srcset'] ? ' srcset="' . esc_attr( $modal_banner['srcset'] ) . '"' : '',
								esc_attr( $modal_banner['alt'] ),
								$modal_banner['width'] > 0 ? ' width="' . (int) $modal_banner['width'] . '"' : '',
								$modal_banner['height'] > 0 ? ' height="' . (int) $modal_banner['height'] . '"' : ''
							);
							?>
							<div class="phone-cta__modal-banner">
								<?php if ( '' !== $modal_banner['link'] ) : ?>
									<a href="<?php echo esc_url( $modal_banner['link'] ); ?>"><?php echo $modal_banner_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
								<?php else : ?>
									<?php echo $modal_banner_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
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
