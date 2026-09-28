<?php
/**
 * ブランドカラー等の設定ページ（Settings API）
 *
 * メディアごとにブロックの主要色を切り替えられるようにする。
 * 保存した色は CSS カスタムプロパティ --md-brand として出力される。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 既定のブランドカラー（元テーマの $color_orange 相当）
if ( ! defined( 'MADOGUCHI_BLOCKS_DEFAULT_BRAND' ) ) {
	define( 'MADOGUCHI_BLOCKS_DEFAULT_BRAND', '#f56f00' );
}

/**
 * 保存済みブランドカラーを取得する（未設定・不正値は既定色にフォールバック）。
 *
 * @return string HEXカラー
 */
function madoguchi_blocks_brand_color() {
	$color = get_option( 'madoguchi_blocks_brand_color', MADOGUCHI_BLOCKS_DEFAULT_BRAND );
	$color = sanitize_hex_color( $color );

	return $color ? $color : MADOGUCHI_BLOCKS_DEFAULT_BRAND;
}

/**
 * 保存済みの著者テンプレート一覧を取得する。
 *
 * 各要素: array( 'id', 'name', 'role', 'bio', 'avatarUrl' )
 *
 * @return array[] 著者テンプレートの配列（未設定なら空配列）。
 */
function madoguchi_blocks_author_templates() {
	$templates = get_option( 'madoguchi_blocks_author_templates', array() );

	return is_array( $templates ) ? $templates : array();
}

/**
 * ID を指定して著者テンプレートを1件取得する。
 *
 * @param string $id テンプレートID。
 * @return array|null 見つかった著者テンプレート。無ければ null。
 */
function madoguchi_blocks_get_author_template( $id ) {
	if ( '' === (string) $id ) {
		return null;
	}
	foreach ( madoguchi_blocks_author_templates() as $t ) {
		if ( isset( $t['id'] ) && (string) $t['id'] === (string) $id ) {
			return $t;
		}
	}

	return null;
}

/**
 * 著者テンプレートのサニタイズ（保存時）。
 *
 * 全項目が空の行は捨て、ID が無い行には新規IDを採番する。
 *
 * @param mixed $input フォーム送信値。
 * @return array[] サニタイズ済みの配列。
 */
function madoguchi_blocks_sanitize_author_templates( $input ) {
	$out  = array();
	$seen = array();
	if ( ! is_array( $input ) ) {
		return $out;
	}
	foreach ( $input as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$name   = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
		$role   = isset( $row['role'] ) ? sanitize_text_field( $row['role'] ) : '';
		$bio    = isset( $row['bio'] ) ? wp_kses( $row['bio'], array( 'br' => array() ) ) : '';
		$avatar = isset( $row['avatarUrl'] ) ? esc_url_raw( $row['avatarUrl'] ) : '';

		// 全項目が空の行は保存しない
		if ( '' === $name && '' === $role && '' === trim( wp_strip_all_tags( $bio ) ) && '' === $avatar ) {
			continue;
		}

		// ID を確定（未採番・重複は新規採番）
		$id = isset( $row['id'] ) ? preg_replace( '/[^a-zA-Z0-9_]/', '', $row['id'] ) : '';
		if ( '' === $id || isset( $seen[ $id ] ) ) {
			$id = uniqid( 'at_' );
		}
		$seen[ $id ] = true;

		$out[] = array(
			'id'        => $id,
			'name'      => $name,
			'role'      => $role,
			'bio'       => $bio,
			'avatarUrl' => $avatar,
		);
	}

	return $out;
}

/**
 * 設定項目を登録する。
 */
function madoguchi_blocks_register_settings() {
	register_setting(
		'madoguchi_blocks',
		'madoguchi_blocks_brand_color',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_hex_color',
			'default'           => MADOGUCHI_BLOCKS_DEFAULT_BRAND,
		)
	);

	register_setting(
		'madoguchi_blocks',
		'madoguchi_blocks_author_templates',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'madoguchi_blocks_sanitize_author_templates',
			'default'           => array(),
		)
	);

	add_settings_section(
		'madoguchi_blocks_main',
		__( '基本設定', 'madoguchi-blocks' ),
		'__return_false',
		'madoguchi-blocks'
	);

	add_settings_field(
		'madoguchi_blocks_brand_color',
		__( 'ブランドカラー', 'madoguchi-blocks' ),
		'madoguchi_blocks_brand_color_field',
		'madoguchi-blocks',
		'madoguchi_blocks_main'
	);

	add_settings_section(
		'madoguchi_blocks_authors',
		__( '著者テンプレート', 'madoguchi-blocks' ),
		'madoguchi_blocks_authors_section_intro',
		'madoguchi-blocks'
	);

	add_settings_field(
		'madoguchi_blocks_author_templates',
		__( '著者プロフィール', 'madoguchi-blocks' ),
		'madoguchi_blocks_author_templates_field',
		'madoguchi-blocks',
		'madoguchi_blocks_authors'
	);

	register_setting(
		'madoguchi_blocks',
		'madoguchi_blocks_phone_cta_api_urls',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'madoguchi_blocks_sanitize_phone_cta_api_urls',
			'default'           => array(),
		)
	);

	add_settings_section(
		'madoguchi_blocks_phone_cta',
		__( '電話CTA（店舗マスタ API）', 'madoguchi-blocks' ),
		'madoguchi_blocks_phone_cta_section_intro',
		'madoguchi-blocks'
	);
	add_settings_field(
		'madoguchi_blocks_phone_cta_api_urls',
		__( 'サービス別 API ベース URL', 'madoguchi-blocks' ),
		'madoguchi_blocks_phone_cta_api_urls_field',
		'madoguchi-blocks',
		'madoguchi_blocks_phone_cta'
	);
}
add_action( 'admin_init', 'madoguchi_blocks_register_settings' );

/**
 * ブランドカラー入力フィールドを描画する。
 */
function madoguchi_blocks_brand_color_field() {
	$color = madoguchi_blocks_brand_color();
	printf(
		'<input type="color" name="madoguchi_blocks_brand_color" value="%1$s" />',
		esc_attr( $color )
	);
	echo '<p class="description">' . esc_html__( '各ブロックの主要色（CTAボタン・強調・リンクなど）に使用されます。', 'madoguchi-blocks' ) . '</p>';
}

/**
 * 著者テンプレート セクションの説明文。
 */
function madoguchi_blocks_authors_section_intro() {
	echo '<p class="description">' . esc_html__( 'よく使う著者情報を登録しておくと、著者情報ブロックのサイドバーからテンプレートを選ぶだけで反映できます。ここで内容を編集すると、そのテンプレートを使用している全記事に反映されます（各記事で個別に手入力した著者情報には影響しません）。', 'madoguchi-blocks' ) . '</p>';
}

/**
 * 著者テンプレート1行分のHTMLを返す。
 *
 * @param string|int $index 行インデックス（新規追加用に __i__ を渡すこともある）。
 * @param array      $t     テンプレート値。
 * @return string
 */
function madoguchi_blocks_render_author_row( $index, $t ) {
	$base   = 'madoguchi_blocks_author_templates[' . $index . ']';
	$id     = isset( $t['id'] ) ? $t['id'] : '';
	$name   = isset( $t['name'] ) ? $t['name'] : '';
	$role   = isset( $t['role'] ) ? $t['role'] : '';
	$bio    = isset( $t['bio'] ) ? $t['bio'] : '';
	$avatar = isset( $t['avatarUrl'] ) ? $t['avatarUrl'] : '';

	ob_start();
	?>
	<div class="madoguchi-author-row" style="max-width:640px;margin:0 0 16px;padding:16px;border:1px solid #dcdcde;border-radius:6px;background:#fff;">
		<input type="hidden" class="madoguchi-author-id" name="<?php echo esc_attr( $base ); ?>[id]" value="<?php echo esc_attr( $id ); ?>" />
		<p style="margin:0 0 8px;">
			<label style="display:block;font-weight:600;margin-bottom:2px;"><?php esc_html_e( '名前', 'madoguchi-blocks' ); ?></label>
			<input type="text" class="regular-text" name="<?php echo esc_attr( $base ); ?>[name]" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php esc_attr_e( '例：山田 太郎', 'madoguchi-blocks' ); ?>" />
		</p>
		<p style="margin:0 0 8px;">
			<label style="display:block;font-weight:600;margin-bottom:2px;"><?php esc_html_e( '肩書き', 'madoguchi-blocks' ); ?></label>
			<input type="text" class="regular-text" name="<?php echo esc_attr( $base ); ?>[role]" value="<?php echo esc_attr( $role ); ?>" placeholder="<?php esc_attr_e( '例：不用品回収アドバイザー', 'madoguchi-blocks' ); ?>" />
		</p>
		<p style="margin:0 0 8px;">
			<label style="display:block;font-weight:600;margin-bottom:2px;"><?php esc_html_e( 'プロフィール', 'madoguchi-blocks' ); ?></label>
			<textarea class="large-text" rows="3" name="<?php echo esc_attr( $base ); ?>[bio]" placeholder="<?php esc_attr_e( '例：年間1,000件以上の回収に携わる…', 'madoguchi-blocks' ); ?>"><?php echo esc_textarea( $bio ); ?></textarea>
			<span class="description"><?php esc_html_e( '改行はそのまま反映されます。使えるタグは <br> のみです。', 'madoguchi-blocks' ); ?></span>
		</p>
		<p style="margin:0 0 8px;">
			<label style="display:block;font-weight:600;margin-bottom:2px;"><?php esc_html_e( 'アイコン画像', 'madoguchi-blocks' ); ?></label>
			<input type="text" class="regular-text madoguchi-author-avatar" name="<?php echo esc_attr( $base ); ?>[avatarUrl]" value="<?php echo esc_attr( $avatar ); ?>" placeholder="https://..." />
			<button type="button" class="button madoguchi-author-avatar-select"><?php esc_html_e( '画像を選択', 'madoguchi-blocks' ); ?></button>
			<br />
			<img class="madoguchi-author-avatar-preview" src="<?php echo esc_url( $avatar ); ?>" alt="" style="<?php echo $avatar ? 'display:inline-block' : 'display:none'; ?>;margin-top:8px;width:56px;height:56px;object-fit:cover;border-radius:50%;" />
		</p>
		<p style="margin:0;">
			<button type="button" class="button-link madoguchi-author-remove" style="color:#b32d2e;"><?php esc_html_e( 'この著者を削除', 'madoguchi-blocks' ); ?></button>
		</p>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * 著者テンプレートの繰り返し入力フィールドを描画する。
 */
function madoguchi_blocks_author_templates_field() {
	$templates = madoguchi_blocks_author_templates();
	?>
	<div id="madoguchi-author-templates">
		<?php
		foreach ( $templates as $i => $t ) {
			echo madoguchi_blocks_render_author_row( (int) $i, $t ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
	<p>
		<button type="button" class="button" id="madoguchi-author-add">＋ <?php esc_html_e( '著者プロフィールを追加', 'madoguchi-blocks' ); ?></button>
	</p>
	<script type="text/html" id="madoguchi-author-row-template">
		<?php echo madoguchi_blocks_render_author_row( '__i__', array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</script>
	<script>
	( function() {
		var container = document.getElementById( 'madoguchi-author-templates' );
		var tmpl      = document.getElementById( 'madoguchi-author-row-template' );
		var addBtn    = document.getElementById( 'madoguchi-author-add' );
		if ( ! container || ! tmpl || ! addBtn ) {
			return;
		}
		// 追加行の name 添字が既存と衝突しないよう、現在数から連番を振る
		var nextIndex = container.querySelectorAll( '.madoguchi-author-row' ).length;

		addBtn.addEventListener( 'click', function() {
			var html = tmpl.innerHTML.replace( /__i__/g, 'new_' + ( nextIndex++ ) );
			var wrap = document.createElement( 'div' );
			wrap.innerHTML = html.trim();
			container.appendChild( wrap.firstChild );
		});

		// 削除・画像選択（イベント委譲）
		container.addEventListener( 'click', function( e ) {
			var removeBtn = e.target.closest( '.madoguchi-author-remove' );
			if ( removeBtn ) {
				var row = removeBtn.closest( '.madoguchi-author-row' );
				if ( row ) {
					row.parentNode.removeChild( row );
				}
				return;
			}

			var mediaBtn = e.target.closest( '.madoguchi-author-avatar-select' );
			if ( mediaBtn && window.wp && wp.media ) {
				e.preventDefault();
				var row     = mediaBtn.closest( '.madoguchi-author-row' );
				var input   = row.querySelector( '.madoguchi-author-avatar' );
				var preview = row.querySelector( '.madoguchi-author-avatar-preview' );
				var frame   = wp.media({ title: '<?php echo esc_js( __( 'アイコン画像を選択', 'madoguchi-blocks' ) ); ?>', multiple: false });
				frame.on( 'select', function() {
					var att = frame.state().get( 'selection' ).first().toJSON();
					input.value = att.url;
					preview.src = att.url;
					preview.style.display = 'inline-block';
				});
				frame.open();
			}
		});

		// URL を直接編集したらプレビューも更新
		container.addEventListener( 'input', function( e ) {
			if ( e.target.classList.contains( 'madoguchi-author-avatar' ) ) {
				var preview = e.target.closest( '.madoguchi-author-row' ).querySelector( '.madoguchi-author-avatar-preview' );
				preview.src = e.target.value;
				preview.style.display = e.target.value ? 'inline-block' : 'none';
			}
		});
	}() );
	</script>
	<?php
}

/**
 * 設定ページでメディアアップローダー（wp.media）を読み込む。
 *
 * @param string $hook 現在の管理画面フック名。
 */
function madoguchi_blocks_settings_enqueue( $hook ) {
	if ( 'settings_page_madoguchi-blocks' === $hook ) {
		wp_enqueue_media();
	}
}
add_action( 'admin_enqueue_scripts', 'madoguchi_blocks_settings_enqueue' );

/**
 * 設定ページを「設定」メニュー配下に追加する。
 */
function madoguchi_blocks_add_menu() {
	add_options_page(
		__( 'Madoguchi Blocks 設定', 'madoguchi-blocks' ),
		__( 'Madoguchi Blocks', 'madoguchi-blocks' ),
		'manage_options',
		'madoguchi-blocks',
		'madoguchi_blocks_settings_page'
	);
}
add_action( 'admin_menu', 'madoguchi_blocks_add_menu' );

/**
 * 設定ページ本体を描画する。
 */
function madoguchi_blocks_settings_page() {
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Madoguchi Blocks 設定', 'madoguchi-blocks' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'madoguchi_blocks' );
			do_settings_sections( 'madoguchi-blocks' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * 電話CTA: サービス別 API URL のサニタイズ。定義済みキーだけ残し、URL は esc_url_raw。
 */
function madoguchi_blocks_sanitize_phone_cta_api_urls( $value ) {
	$out = array();
	foreach ( array_keys( Madoguchi_Blocks_Phone_Cta_Services::KEYS ) as $key ) {
		$url         = isset( $value[ $key ] ) ? trim( (string) $value[ $key ] ) : '';
		$out[ $key ] = '' === $url ? '' : esc_url_raw( $url, array( 'http', 'https' ) );
	}
	return $out;
}

/**
 * 電話CTA: サービス別 API URL（3 キーすべて含む。未設定は空文字）。
 */
function madoguchi_blocks_phone_cta_api_urls() {
	$saved = get_option( 'madoguchi_blocks_phone_cta_api_urls', array() );
	$out   = array();
	foreach ( array_keys( Madoguchi_Blocks_Phone_Cta_Services::KEYS ) as $key ) {
		$out[ $key ] = isset( $saved[ $key ] ) ? (string) $saved[ $key ] : '';
	}
	return $out;
}

/**
 * 電話CTA: URL が設定されているサービスだけ（key => 表示名）。
 */
function madoguchi_blocks_phone_cta_enabled_services() {
	$out = array();
	foreach ( madoguchi_blocks_phone_cta_api_urls() as $key => $url ) {
		if ( '' !== $url ) {
			$out[ $key ] = Madoguchi_Blocks_Phone_Cta_Services::label( $key );
		}
	}
	return $out;
}

function madoguchi_blocks_phone_cta_section_intro() {
	echo '<p>' . esc_html__( '記事内の電話CTAブロックが店舗情報を取得する estima の API です。使うサービスだけ URL を入れてください(例: https://api.ekaitori.com)。店舗情報は 10 分キャッシュされます。', 'madoguchi-blocks' ) . '</p>';
}

function madoguchi_blocks_phone_cta_api_urls_field() {
	$urls = madoguchi_blocks_phone_cta_api_urls();
	echo '<table class="form-table" role="presentation" style="margin:0"><tbody>';
	foreach ( Madoguchi_Blocks_Phone_Cta_Services::KEYS as $key => $label ) {
		printf(
			'<tr><th scope="row" style="padding:6px 10px 6px 0;width:6em">%1$s <code>%2$s</code></th><td style="padding:6px 0"><input type="url" class="regular-text" name="madoguchi_blocks_phone_cta_api_urls[%2$s]" value="%3$s" placeholder="https://api.example.com"></td></tr>',
			esc_html( $label ),
			esc_attr( $key ),
			esc_attr( $urls[ $key ] )
		);
	}
	echo '</tbody></table>';

	$refresh_url = wp_nonce_url( admin_url( 'admin-post.php?action=madoguchi_phone_cta_refresh' ), 'madoguchi_phone_cta_refresh' );
	printf(
		'<p style="margin-top:12px"><a class="button" href="%s">%s</a> <span class="description">%s</span></p>',
		esc_url( $refresh_url ),
		esc_html__( '店舗データを今すぐ更新', 'madoguchi-blocks' ),
		esc_html__( 'キャッシュを消して、次の表示時に API から取り直します。', 'madoguchi-blocks' )
	);
	if ( isset( $_GET['phone_cta_refreshed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success inline"><p>' . esc_html__( '店舗データのキャッシュを消しました。', 'madoguchi-blocks' ) . '</p></div>';
	}
}

/**
 * 電話CTA: 「今すぐ更新」の受け口(admin-post)。
 */
function madoguchi_blocks_phone_cta_handle_refresh() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( '権限がありません。', 'madoguchi-blocks' ) );
	}
	check_admin_referer( 'madoguchi_phone_cta_refresh' );
	Madoguchi_Blocks_Phone_Cta_Repository::default()->refresh();
	wp_safe_redirect( add_query_arg( array( 'page' => 'madoguchi-blocks', 'phone_cta_refreshed' => '1' ), admin_url( 'options-general.php' ) ) );
	exit;
}
add_action( 'admin_post_madoguchi_phone_cta_refresh', 'madoguchi_blocks_phone_cta_handle_refresh' );
