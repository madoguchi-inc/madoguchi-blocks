<?php
/**
 * 店舗データ・ブロック属性・現在時刻から、カード／フッターの表示状態を決める。
 * HTML は作らない（render.php の責務）。WordPress に依存しない。
 */
class Madoguchi_Blocks_Phone_Cta_View {

	/**
	 * サービス別の既定文言。
	 */
	public static function default_texts( string $service ): array {
		$kaitori = array(
			'heading'              => '電話で査定額を聞ける提携買取店',
			'description'          => '複数の買取店で査定してもらうことが高く売るコツ！',
			'footer_catch'         => '完全無料査定 複数社で比較して1番高く売ろう',
			'footer_generic_label' => '電話で査定額を聞く',
			'footer_balloon'       => 'その場でかんたん無料査定！',
			'web_label'            => 'WEBでカンタン無料査定はこちら',
			'web_button'           => '24時間年中無休で受付中！ オンライン無料一括査定',
			'shop_label'           => '{shop}に電話で査定額を聞く',
		);
		$other = array(
			'heading'              => '電話で見積もりを聞ける提携業者',
			'description'          => '複数の業者に見積もりを取ることが安く済ませるコツ！',
			'footer_catch'         => '完全無料 複数社で比較して1番安く済ませよう',
			'footer_generic_label' => '電話で見積もりを聞く',
			'footer_balloon'       => 'その場でかんたん無料見積もり！',
			'web_label'            => 'WEBでカンタン無料見積もりはこちら',
			'web_button'           => '24時間年中無休で受付中！ オンライン無料一括見積もり',
			'shop_label'           => '{shop}に電話で見積もりを聞く',
		);
		return 'kaitori' === $service ? $kaitori : $other;
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

	public static function button_label( array $shop, string $override ): string {
		$template = '' !== trim( $override ) ? $override : ( isset( $shop['button_label'] ) && '' !== $shop['button_label'] ? $shop['button_label'] : '{shop}に電話で査定額を聞く' );
		return str_replace( '{shop}', isset( $shop['name'] ) ? (string) $shop['name'] : '', $template );
	}

	/**
	 * 記事内カード 1 枚の状態。
	 *
	 * @param array $item ブロック属性 shops[] の 1 要素（uuid, numberId, buttonLabel, leadText）
	 */
	public static function card_state( array $shop, array $item, DateTimeImmutable $now ): ?array {
		$number = self::pick_number( $shop, isset( $item['numberId'] ) ? $item['numberId'] : null );
		if ( null === $number ) {
			return null;
		}
		$is_open  = Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $now );
		$fallback = isset( $shop['web_fallback_url'] ) ? trim( (string) $shop['web_fallback_url'] ) : '';
		if ( $is_open ) {
			$mode = 'tel';
		} elseif ( '' !== $fallback ) {
			$mode = 'web';
		} else {
			$mode = 'tel_closed';
		}
		$lead = isset( $item['leadText'] ) && '' !== trim( (string) $item['leadText'] ) ? (string) $item['leadText'] : ( isset( $shop['lead_text'] ) ? (string) $shop['lead_text'] : '' );
		$phone_number = isset( $number['phone_number'] ) ? (string) $number['phone_number'] : '';

		return array(
			'mode'           => $mode,
			'is_open'        => $is_open,
			'uuid'           => isset( $shop['uuid'] ) ? (string) $shop['uuid'] : '',
			'name'           => isset( $shop['name'] ) ? (string) $shop['name'] : '',
			'logo_url'       => isset( $shop['logo_url'] ) ? (string) $shop['logo_url'] : '',
			'lead'           => $lead,
			'specialty'      => isset( $shop['specialty_text'] ) ? (string) $shop['specialty_text'] : '',
			'label'          => self::button_label( $shop, isset( $item['buttonLabel'] ) ? (string) $item['buttonLabel'] : '' ),
			'tel_href'       => '' !== $phone_number ? Madoguchi_Blocks_Phone_Cta_Tel::to_href( $phone_number ) : '',
			'tel_display'    => $phone_number,
			'is_toll_free'   => ! isset( $number['is_toll_free'] ) || (bool) $number['is_toll_free'],
			'reception_text' => isset( $shop['reception_text'] ) ? (string) $shop['reception_text'] : '',
			'fallback_url'   => $fallback,
			'campaign'       => isset( $shop['campaign'] ) && is_array( $shop['campaign'] ) ? $shop['campaign'] : null,
		);
	}

	/**
	 * 固定フッターの状態。$shop が null なら汎用文言。
	 *
	 * @param array $attrs ブロック属性 shop（numberId, buttonLabel, balloonText）
	 */
	public static function footer_state( ?array $shop, array $attrs, DateTimeImmutable $now, string $service ): array {
		$texts   = self::default_texts( $service );
		$balloon = isset( $attrs['balloonText'] ) && '' !== trim( (string) $attrs['balloonText'] ) ? (string) $attrs['balloonText'] : $texts['footer_balloon'];

		if ( null === $shop ) {
			return array(
				'mode'         => 'generic',
				'label'        => isset( $attrs['buttonLabel'] ) && '' !== trim( (string) $attrs['buttonLabel'] ) ? (string) $attrs['buttonLabel'] : $texts['footer_generic_label'],
				'tel_href'     => '',
				'balloon'      => '',
				'fallback_url' => '',
				'name'         => '',
				'uuid'         => '',
				'is_open'      => false,
			);
		}

		$number       = self::pick_number( $shop, isset( $attrs['numberId'] ) ? $attrs['numberId'] : null );
		$phone_number = isset( $number['phone_number'] ) ? (string) $number['phone_number'] : '';
		$is_open      = null !== $number && Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $now );
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
			'label'        => 'web' === $mode ? $texts['web_label'] : self::button_label( $shop, isset( $attrs['buttonLabel'] ) ? (string) $attrs['buttonLabel'] : '' ),
			'tel_href'     => '' !== $phone_number ? Madoguchi_Blocks_Phone_Cta_Tel::to_href( $phone_number ) : '',
			'balloon'      => $balloon,
			'fallback_url' => $fallback,
			'name'         => isset( $shop['name'] ) ? (string) $shop['name'] : '',
			'uuid'         => isset( $shop['uuid'] ) ? (string) $shop['uuid'] : '',
			'is_open'      => $is_open,
		);
	}
}
