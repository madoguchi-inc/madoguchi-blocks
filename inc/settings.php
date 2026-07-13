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
