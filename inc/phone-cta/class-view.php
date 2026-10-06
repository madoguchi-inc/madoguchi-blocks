<?php
/**
 * 店舗データ・ブロック属性・現在時刻から、カード／フッターの表示状態を決める。
 * HTML は作らない（render.php の責務）。WordPress に依存しない。
 */
class Madoguchi_Blocks_Phone_Cta_View {

	/**
	 * バナーのリンクに店舗を載せるクエリのキー。
	 * `s` は WordPress の検索クエリの予約語で、固定ページに付けると検索結果（404）に
	 * 飛ばされてしまうため使えない。
	 */
	const SHOPS_QUERY_KEY = 'shops';

	/**
	 * サービス別の既定文言。
	 * Figma「みんなの買取」202607_電話送客プロジェクト（コラム_PC / コラム_SP / 固定フッター）の文言を既定とする。
	 * src/phone-cta/texts.js の DEFAULT_TEXTS と必ず一致させる。
	 */
	public static function default_texts( string $service, string $host_service = '' ): array {
		$sets  = self::text_sets();
		$texts = isset( $sets[ $service ] ) ? $sets[ $service ] : $sets['other'];

		// 他サービスのカードを載せているサイトでは、載せる側が持つクロスセル用の文言で上書きする。
		// 例: 回収の記事に出す買取店は「査定額を聞く」ではなく「買取できるか相談する」になる
		if ( '' !== $host_service && $host_service !== $service && isset( $sets[ $host_service ]['cross'][ $service ] ) ) {
			$texts = array_merge( $texts, $sets[ $host_service ]['cross'][ $service ] );
		}
		unset( $texts['cross'] );
		return $texts;
	}

	/**
	 * サービスキー => 文言セット。'other' はキーが無いサービスの受け皿。
	 * 'cross' は「そのサービスのサイトに、別サービスのカードを載せるとき」の上書き。
	 */
	private static function text_sets(): array {
		$points = array( '強引な営業なし', '個人情報必要なし', '相談だけでもOK' );

		$kaitori = array(
			'description'          => '複数の買取店で査定してもらうことが高く売るコツ！', // 見出し上の小見出し（ゴールド）
			'heading'              => '今すぐ電話でかんたん無料査定',
			'points'               => $points,
			'free_tag'             => '査定無料', // SP ボタン左端の縦書きタブ
			'balloon'              => 'その場でかんたん無料査定！',
			'footer_catch_badge'   => '完全無料査定',
			'footer_catch'         => '複数社で比較して1番高く売ろう',
			'footer_generic_label' => '電話で査定額を聞く',
			'web_label'            => 'WEBでカンタン無料査定はこちら',
			'web_label_parts'      => array( 'WEBでカンタン', '無料査定はこちら' ), // 「WEBでカンタン」で改行する
			'web_shop_label'       => '', // 空なら web_label_parts をそのまま使う（店名を入れない）
			'web_button_sub'       => '24時間年中無休で受付中！',
			'web_button'           => 'オンライン無料一括査定',
			'web_button_sp'        => 'オンライン一括査定', // SP の黒ボタンは Figma 上この短い文言
			'shop_label'           => '{shop}に電話で査定額を聞く',
		);

		// 回収。Figma「不用品回収の窓口 サイトデザイン」コラム内テーブル・CTAデザイン。
		// 買取と違い POINT バッジと「査定無料」の縦書きタブを持たない
		$fuyouhin = array(
			'description'          => '「いくらかかる？」「これも回収できる？」',
			'heading'              => '不用品回収のお悩みを相談できる業者をご紹介します！',
			'points'               => array(),
			'free_tag'             => '',
			'balloon'              => 'その場でかんたん見積もり！',
			'footer_catch_badge'   => '完全無料',
			'footer_catch'         => '複数社で比較して1番安く済ませよう',
			'footer_generic_label' => '電話で相談する',
			'web_label'            => 'WEBでカンタン無料お見積もりはこちら',
			'web_label_parts'      => array( 'WEBでカンタン', '無料お見積もりはこちら' ),
			'web_shop_label'       => '',
			'web_button_sub'       => '24時間年中無休で受付中！',
			'web_button'           => 'オンライン無料一括見積もり',
			'web_button_sp'        => 'オンライン一括見積もり',
			'shop_label'           => '{shop}に電話で相談する',
			'cross'                => array(
				// 回収の記事に載せる買取店（「回収予定の不用品、実は買取できるかも」の導線）
				'kaitori' => array(
					'shop_label'      => '{shop}に買取できるか相談する',
					'web_label'       => 'WEB無料査定はこちら',
					'web_shop_label'  => '{shop}のWEB無料査定はこちら',
					'balloon'         => '',
					'free_tag'        => '', // Figma のクロスセルのカードに縦書きタブは無い
				),
			),
		);

		$other = array(
			'description'          => '複数の業者に見積もりを取ることが安く済ませるコツ！',
			'heading'              => '今すぐ電話でかんたん無料見積もり',
			'points'               => $points,
			'free_tag'             => '見積無料',
			'balloon'              => 'その場でかんたん無料見積もり！',
			'footer_catch_badge'   => '完全無料',
			'footer_catch'         => '複数社で比較して1番安く済ませよう',
			'footer_generic_label' => '電話で見積もりを聞く',
			'web_label'            => 'WEBでカンタン無料見積もりはこちら',
			'web_label_parts'      => array( 'WEBでカンタン', '無料見積もりはこちら' ),
			'web_shop_label'       => '',
			'web_button_sub'       => '24時間年中無休で受付中！',
			'web_button'           => 'オンライン無料一括見積もり',
			'web_button_sp'        => 'オンライン一括見積もり',
			'shop_label'           => '{shop}に電話で見積もりを聞く',
		);

		return array(
			'kaitori'  => $kaitori,
			'fuyouhin' => $fuyouhin,
			'other'    => $other,
		);
	}

	/**
	 * 使う電話番号を決める。指定 id → is_default → 先頭。無ければ null。
	 */
	public static function pick_number( array $shop, $number_id ): ?array {
		$numbers = isset( $shop['numbers'] ) && is_array( $shop['numbers'] ) ? array_values( array_filter( $shop['numbers'], 'is_array' ) ) : array();
		if ( empty( $numbers ) ) {
			return null;
		}
		if ( null !== $number_id && '' !== $number_id ) {
			foreach ( $numbers as $n ) {
				if ( isset( $n['id'] ) && (string) $n['id'] === (string) $number_id ) {
					return $n;
				}
			}
		}
		foreach ( $numbers as $n ) {
			if ( ! empty( $n['is_default'] ) ) {
				return $n;
			}
		}
		return $numbers[0];
	}

	/**
	 * ボタン文言。店名以外はサービス（商材）ごとの固定文言で、{shop} だけを店名に置換する。
	 * 店舗マスタやブロック属性からの上書きは受け付けない（文言を全記事で揃えるため）。
	 */
	public static function button_label( array $shop, string $service, string $host_service = '' ): string {
		return implode( '', self::label_parts( $shop, $service, $host_service ) );
	}

	/**
	 * ボタン文言を「店名＋助詞」と「残り」の 2 片に分ける。
	 * Figma では「おたからやに｜電話で査定額を聞く」で折り返すため、render 側で各片を inline-block にして
	 * 語の途中で折れないようにする。テンプレートに {shop} が無ければ [ '', 全文 ]。
	 *
	 * @return array{0:string,1:string}
	 */
	public static function label_parts( array $shop, string $service, string $host_service = '' ): array {
		return self::split_label( self::default_texts( $service, $host_service )['shop_label'], $shop );
	}

	/**
	 * WEB ボタンの文言 2 片。{shop} 入りのテンプレート（web_shop_label）があれば店名を差し込み、
	 * 無ければ店名を含まない固定の 2 片（web_label_parts）をそのまま使う。
	 *
	 * @return array{0:string,1:string}
	 */
	public static function web_label_parts( array $shop, string $service, string $host_service = '' ): array {
		$texts = self::default_texts( $service, $host_service );
		if ( isset( $texts['web_shop_label'] ) && '' !== $texts['web_shop_label'] ) {
			return self::split_label( $texts['web_shop_label'], $shop );
		}
		return $texts['web_label_parts'];
	}

	/**
	 * テンプレートの {shop} を店名に置き換え、助詞の直後で 2 片に割る。
	 *
	 * @return array{0:string,1:string}
	 */
	private static function split_label( string $template, array $shop ): array {
		$name = isset( $shop['name'] ) ? (string) $shop['name'] : '';
		$pos  = strpos( $template, '{shop}' );
		if ( false === $pos ) {
			return array( '', $template );
		}
		$prefix = substr( $template, 0, $pos );
		$suffix = substr( $template, $pos + strlen( '{shop}' ) );
		if ( preg_match( '/^([にのへでと])(.*)$/us', $suffix, $m ) ) {
			return array( $prefix . $name . $m[1], $m[2] );
		}
		return array( $prefix . $name, $suffix );
	}

	/**
	 * 記事内カード 1 枚の状態。
	 *
	 * @param array  $item         ブロック属性 shops[] の 1 要素（uuid, numberId, leadText, webUrl）
	 * @param string $service      このカードのサービスキー。ボタン文言の固定テンプレートを引くのに使う
	 * @param string $host_service サイトの主サービス。$service と違えばクロスセル用の文言になる
	 */
	public static function card_state( array $shop, array $item, DateTimeImmutable $now, string $service = 'kaitori', string $host_service = '' ): ?array {
		$number = self::pick_number( $shop, isset( $item['numberId'] ) ? $item['numberId'] : null );
		if ( null === $number ) {
			return null;
		}
		$phone_number = isset( $number['phone_number'] ) ? (string) $number['phone_number'] : '';
		// 番号が空のマスタ行は発信できない。href="" のボタン（押すと記事が再読み込みされるだけ）に
		// ならないよう、WEB 査定があればそちらへ、無ければカードごと出さない
		$is_open = '' !== $phone_number && Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $now );
		// WEB 査定のリンク先は記事のカード単位の指定を優先し、空ならマスタの値を使う
		$fallback = isset( $item['webUrl'] ) && '' !== trim( (string) $item['webUrl'] )
			? trim( (string) $item['webUrl'] )
			: ( isset( $shop['web_fallback_url'] ) ? trim( (string) $shop['web_fallback_url'] ) : '' );
		if ( $is_open ) {
			$mode = 'tel';
		} elseif ( '' !== $fallback ) {
			$mode = 'web';
		} elseif ( '' === $phone_number ) {
			return null;
		} else {
			$mode = 'tel_closed';
		}
		$lead  = isset( $item['leadText'] ) && '' !== trim( (string) $item['leadText'] ) ? (string) $item['leadText'] : ( isset( $shop['lead_text'] ) ? (string) $shop['lead_text'] : '' );
		$texts = self::default_texts( $service, $host_service );

		return array(
			'mode'           => $mode,
			'is_open'        => $is_open,
			'uuid'           => isset( $shop['uuid'] ) ? (string) $shop['uuid'] : '',
			// バナーのリンクに「この記事で出している店舗」を載せるのに使う
			'number_id'      => isset( $number['id'] ) ? (string) $number['id'] : '',
			'name'           => isset( $shop['name'] ) ? (string) $shop['name'] : '',
			'logo_url'       => isset( $shop['logo_url'] ) ? (string) $shop['logo_url'] : '',
			'lead'           => $lead,
			// サービスはカード単位で変わりうる（回収の記事に買取店を混ぜるクロスセル）。
			// 色と文言がカードごとに変わるので、ブロック単位の値ではなくここに持たせる
			'service'        => $service,
			'label'          => self::button_label( $shop, $service, $host_service ),
			'label_parts'    => self::label_parts( $shop, $service, $host_service ),
			'web_label_parts' => self::web_label_parts( $shop, $service, $host_service ),
			'balloon'        => $texts['balloon'],
			'free_tag'       => $texts['free_tag'],
			'tel_href'       => '' !== $phone_number ? Madoguchi_Blocks_Phone_Cta_Tel::to_href( $phone_number ) : '',
			'tel_display'    => $phone_number,
			'qr_svg'         => isset( $number['qr_svg'] ) ? (string) $number['qr_svg'] : '',
			'is_toll_free'   => ! isset( $number['is_toll_free'] ) || (bool) $number['is_toll_free'],
			'reception_text' => isset( $shop['reception_text'] ) ? (string) $shop['reception_text'] : '',
			'fallback_url'   => $fallback,
		);
	}

	/**
	 * バナーのリンクに載せる店舗の指定を組み立てる。
	 *
	 * 遷移先のキャンペーンLPは、記事で見ていた店舗をそのまま並べて見せる。
	 * 記事本文の HTML は閲覧者によらず同じなので、描画時に確定するこの値を URL に焼き込める。
	 *
	 *   "<uuid>[:<numberId>][@<service>],<uuid>,..."  並び順はカードの順（＝LP の STEP の順）
	 *
	 * numberId は「記事で実際に使った番号」を付ける。付けないと LP 側が既定番号に
	 * 落ちてしまい、記事と違う番号が出る（番号ごとの送客先が変わってしまう）。
	 *
	 * service は既定と違うカードにだけ付ける。回収の記事に買取店を混ぜる（クロスセル）と、
	 * uuid だけでは LP 側がどのサービスの API を引けばよいか分からないため。
	 * 省略時は受け手側の既定サービスになるので、買取の既存 URL とは互換が保たれる。
	 *
	 * @param array  $cards           card_state() の配列
	 * @param string $default_service 省略してよいサービス（通常はブロックのサービス）
	 * @return string 載せるものが無ければ空文字
	 */
	public static function shops_param( array $cards, string $default_service = '' ): string {
		$parts = array();
		foreach ( $cards as $card ) {
			if ( empty( $card['uuid'] ) ) {
				continue;
			}
			$part      = (string) $card['uuid'];
			$number_id = isset( $card['number_id'] ) ? (string) $card['number_id'] : '';
			if ( '' !== $number_id ) {
				$part .= ':' . $number_id;
			}
			$service = isset( $card['service'] ) ? (string) $card['service'] : '';
			if ( '' !== $service && $service !== $default_service ) {
				$part .= '@' . $service;
			}
			$parts[] = $part;
		}
		return implode( ',', $parts );
	}

	/**
	 * `?s=` の 1 要素を uuid / numberId / service に分解する。shops_param() の逆。
	 * uuid の形を満たさない要素は null（LP 側で落とす）。
	 *
	 * @return array{uuid:string,number_id:string,service:string}|null
	 */
	public static function parse_shop_param_part( string $part ): ?array {
		$part = trim( $part );
		if ( '' === $part ) {
			return null;
		}
		$service = '';
		$at      = strpos( $part, '@' );
		if ( false !== $at ) {
			$service = substr( $part, $at + 1 );
			$part    = substr( $part, 0, $at );
		}
		$number_id = '';
		$colon     = strpos( $part, ':' );
		if ( false !== $colon ) {
			$number_id = substr( $part, $colon + 1 );
			$part      = substr( $part, 0, $colon );
		}
		if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $part ) ) {
			return null;
		}
		if ( '' !== $number_id && ! preg_match( '/^[0-9]+$/', $number_id ) ) {
			$number_id = '';
		}
		if ( '' !== $service && ! Madoguchi_Blocks_Phone_Cta_Services::is_valid( $service ) ) {
			$service = '';
		}
		return array(
			'uuid'      => strtolower( $part ),
			'number_id' => $number_id,
			'service'   => $service,
		);
	}

	/**
	 * `?s=` を分解して、先頭から最大 $max 件の店舗指定を返す。uuid の重複は先勝ち。
	 *
	 * @param string $param           クエリの値
	 * @param string $default_service service が省略された要素に使うサービス
	 * @param int    $max             取り出す最大件数
	 * @return array<int,array{uuid:string,number_id:string,service:string}>
	 */
	public static function parse_shops_param( string $param, string $default_service, int $max = 3 ): array {
		$picks = array();
		foreach ( explode( ',', $param ) as $raw ) {
			$part = self::parse_shop_param_part( $raw );
			if ( null === $part || isset( $picks[ $part['uuid'] ] ) ) {
				continue;
			}
			if ( '' === $part['service'] ) {
				$part['service'] = $default_service;
			}
			$picks[ $part['uuid'] ] = $part;
			if ( count( $picks ) >= $max ) {
				break;
			}
		}
		return array_values( $picks );
	}

	/**
	 * 固定フッターの状態。$shop が null なら汎用文言。
	 * Figma では店舗未指定（それ以外）でも「その場でかんたん無料査定！」を添えるので、汎用でも吹き出しを返す。
	 *
	 * @param array $attrs ブロック属性 shop（numberId, balloonText）
	 */
	public static function footer_state( ?array $shop, array $attrs, DateTimeImmutable $now, string $service ): array {
		$texts   = self::default_texts( $service );
		$balloon = isset( $attrs['balloonText'] ) && '' !== trim( (string) $attrs['balloonText'] ) ? (string) $attrs['balloonText'] : $texts['balloon'];

		if ( null === $shop ) {
			$generic = $texts['footer_generic_label'];
			return array(
				'mode'         => 'generic',
				'label'        => $generic,
				'label_parts'  => array( '', $generic ),
				'tel_href'     => '',
				'balloon'      => $balloon,
				'fallback_url' => '',
				'name'         => '',
				'uuid'         => '',
				'is_open'      => false,
			);
		}

		$number       = self::pick_number( $shop, isset( $attrs['numberId'] ) ? $attrs['numberId'] : null );
		$phone_number = isset( $number['phone_number'] ) ? (string) $number['phone_number'] : '';
		// 番号が空なら発信できないので受付中とは扱わない（href="" のボタンを出さないため）
		$is_open      = '' !== $phone_number && Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $now );
		$fallback     = isset( $shop['web_fallback_url'] ) ? trim( (string) $shop['web_fallback_url'] ) : '';
		if ( $is_open ) {
			$mode = 'tel';
		} elseif ( '' !== $fallback ) {
			$mode = 'web';
		} else {
			$mode = 'web_only';
		}
		return array(
			'mode'         => $mode,
			'label'        => 'web' === $mode ? $texts['web_label'] : self::button_label( $shop, $service ),
			'label_parts'  => 'web' === $mode ? array( '', $texts['web_label'] ) : self::label_parts( $shop, $service ),
			'tel_href'     => '' !== $phone_number ? Madoguchi_Blocks_Phone_Cta_Tel::to_href( $phone_number ) : '',
			'balloon'      => 'tel' === $mode ? $balloon : '',
			'fallback_url' => $fallback,
			'name'         => isset( $shop['name'] ) ? (string) $shop['name'] : '',
			'uuid'         => isset( $shop['uuid'] ) ? (string) $shop['uuid'] : '',
			'is_open'      => $is_open,
		);
	}
}
