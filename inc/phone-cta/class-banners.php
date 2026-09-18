<?php
/**
 * 電話CTAブロックに置けるバナーのパターン（同梱画像）。
 *
 * 1 パターンは PC / SP / PC モーダルの 3 枚を持ち、**サービス（事業）ごとに別セット**を定義する。
 * 画像は assets/img/phone-cta/<service>/ に置き、`@2x` があれば srcset で出す。
 * パターンを増やすときは画像を置いて PATTERNS に 1 件足すだけでエディタの選択肢にも反映される。
 */
class Madoguchi_Blocks_Phone_Cta_Banners {

	const NONE   = '';
	const CUSTOM = 'custom';

	/** SP 用画像に切り替える最大幅。scss の mq-max(md) と必ず合わせる */
	const SP_MAX_WIDTH = 896;

	/**
	 * サービスキー => パターンキー => 定義
	 *   label … エディタの選択肢に出す名前
	 *   alt   … 代替テキストの既定値
	 *   pc / sp / modal … [ file（<service>/ 配下）, width, height ]。sp / modal は省略可（無ければ pc を使う）
	 */
	const PATTERNS = array(
		'kaitori'  => array(
			'amazon-gift-12000' => array(
				'label' => 'Amazonギフト券 12,000円分プレゼント中！',
				'alt'   => 'みんなの買取を利用していただいた方限定 Amazonギフト券12,000円分プレゼント中！',
				'pc'    => array( 'file' => 'banner-amazon-gift-12000.png', 'width' => 760, 'height' => 101 ),
				'sp'    => array( 'file' => 'banner-amazon-gift-12000-sp.png', 'width' => 381, 'height' => 143 ),
				'modal' => array( 'file' => 'banner-amazon-gift-12000-modal.png', 'width' => 650, 'height' => 106 ),
			),
		),
		// 回収・清掃は画像が用意できたら同じ形で追加する（それまでは「なし」とカスタム画像のみ）
		'fuyouhin' => array(),
		'osouji'   => array(),
	);

	/**
	 * サービスのパターン一覧。
	 */
	public static function patterns_for( string $service ): array {
		return isset( self::PATTERNS[ $service ] ) ? self::PATTERNS[ $service ] : array();
	}

	/**
	 * エディタの選択肢（サービス => [ なし / 各パターン / カスタム画像 ]）。
	 * プレビューには PC 画像の URL を渡す。
	 */
	public static function options(): array {
		$by_service = array();
		foreach ( array_keys( Madoguchi_Blocks_Phone_Cta_Services::KEYS ) as $service ) {
			$options = array(
				array(
					'value' => self::NONE,
					'label' => 'バナーなし',
					'url'   => '',
					'alt'   => '',
				),
			);
			foreach ( self::patterns_for( $service ) as $key => $p ) {
				$options[] = array(
					'value' => $key,
					'label' => $p['label'],
					'url'   => self::image_url( $service, $p['pc']['file'] ),
					'alt'   => $p['alt'],
				);
			}
			$options[] = array(
				'value' => self::CUSTOM,
				'label' => 'カスタム画像（メディアから選ぶ）',
				'url'   => '',
				'alt'   => '',
			);
			$by_service[ $service ] = $options;
		}
		return $by_service;
	}

	/**
	 * ブロック属性から出力するバナーを決める。出さない場合は null。
	 *
	 * @param array  $attributes ブロック属性（service / bannerPreset / bannerImageUrl / bannerImageAlt / bannerLinkUrl）
	 * @param string $variant    'pc' | 'sp' | 'modal'
	 * @return array{src:string,srcset:string,alt:string,width:int,height:int,link:string}|null
	 */
	public static function resolve( array $attributes, string $variant = 'pc' ): ?array {
		$service = isset( $attributes['service'] ) ? (string) $attributes['service'] : 'kaitori';
		$preset  = isset( $attributes['bannerPreset'] ) ? (string) $attributes['bannerPreset'] : self::NONE;
		$link    = isset( $attributes['bannerLinkUrl'] ) ? trim( (string) $attributes['bannerLinkUrl'] ) : '';

		// カスタム画像は 1 枚を PC / SP / モーダルで共用する
		if ( self::CUSTOM === $preset ) {
			$url = isset( $attributes['bannerImageUrl'] ) ? trim( (string) $attributes['bannerImageUrl'] ) : '';
			if ( '' === $url ) {
				return null;
			}
			return array(
				'src'    => $url,
				'srcset' => '',
				'alt'    => isset( $attributes['bannerImageAlt'] ) ? (string) $attributes['bannerImageAlt'] : '',
				'width'  => 0,
				'height' => 0,
				'link'   => $link,
			);
		}

		$patterns = self::patterns_for( $service );
		if ( ! isset( $patterns[ $preset ] ) ) {
			return null; // 'なし'・未知のキー・そのサービスに無いパターンは出さない
		}

		$p     = $patterns[ $preset ];
		$image = isset( $p[ $variant ] ) ? $p[ $variant ] : $p['pc'];

		$src    = self::image_url( $service, $image['file'] );
		$retina = self::retina_file( $service, $image['file'] );
		$alt    = isset( $attributes['bannerImageAlt'] ) && '' !== trim( (string) $attributes['bannerImageAlt'] )
			? (string) $attributes['bannerImageAlt']
			: $p['alt'];

		return array(
			'src'    => $src,
			'srcset' => null !== $retina ? $src . ' 1x, ' . self::image_url( $service, $retina ) . ' 2x' : '',
			'alt'    => $alt,
			'width'  => (int) $image['width'],
			'height' => (int) $image['height'],
			'link'   => $link,
		);
	}

	private static function image_url( string $service, string $file ): string {
		return MADOGUCHI_BLOCKS_URL . 'assets/img/phone-cta/' . $service . '/' . $file;
	}

	/**
	 * `foo.png` に対する `foo@2x.png` が同梱されていればそのファイル名を返す。
	 */
	private static function retina_file( string $service, string $file ): ?string {
		$ext  = pathinfo( $file, PATHINFO_EXTENSION );
		$base = pathinfo( $file, PATHINFO_FILENAME );
		$name = $base . '@2x' . ( '' !== $ext ? '.' . $ext : '' );
		return file_exists( MADOGUCHI_BLOCKS_DIR . 'assets/img/phone-cta/' . $service . '/' . $name ) ? $name : null;
	}
}
