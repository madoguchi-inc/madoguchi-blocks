<?php
/**
 * 電話CTAブロックの先頭に置けるバナーのパターン（同梱画像）。
 *
 * 画像は plugin の assets/img/phone-cta/ に同梱し、`@2x` があれば srcset で出す。
 * パターンを増やすときは PATTERNS に 1 件足すだけでエディタの選択肢にも反映される。
 */
class Madoguchi_Blocks_Phone_Cta_Banners {

	const NONE   = '';
	const CUSTOM = 'custom';

	/**
	 * key => [ label, file（assets/img/phone-cta/ 配下）, alt, width, height ]
	 */
	const PATTERNS = array(
		'amazon-gift-12000' => array(
			'label'  => 'Amazonギフト券 12,000円分プレゼント中！',
			'file'   => 'banner-amazon-gift-12000.png',
			'alt'    => 'みんなの買取を利用していただいた方限定 Amazonギフト券12,000円分プレゼント中！',
			'width'  => 760,
			'height' => 101,
		),
	);

	/**
	 * エディタの選択肢（なし／各パターン／カスタム画像）。
	 */
	public static function options(): array {
		$options = array(
			array(
				'value' => self::NONE,
				'label' => 'バナーなし',
				'url'   => '',
				'alt'   => '',
			),
		);
		foreach ( self::PATTERNS as $key => $p ) {
			$options[] = array(
				'value' => $key,
				'label' => $p['label'],
				'url'   => self::image_url( $p['file'] ),
				'alt'   => $p['alt'],
			);
		}
		$options[] = array(
			'value' => self::CUSTOM,
			'label' => 'カスタム画像（メディアから選ぶ）',
			'url'   => '',
			'alt'   => '',
		);
		return $options;
	}

	/**
	 * ブロック属性から出力するバナーを決める。出さない場合は null。
	 *
	 * @param array $attributes ブロック属性
	 * @return array{src:string,srcset:string,alt:string,width:int,height:int,link:string}|null
	 */
	public static function resolve( array $attributes ): ?array {
		$preset = isset( $attributes['bannerPreset'] ) ? (string) $attributes['bannerPreset'] : self::NONE;
		$link   = isset( $attributes['bannerLinkUrl'] ) ? trim( (string) $attributes['bannerLinkUrl'] ) : '';

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

		if ( ! isset( self::PATTERNS[ $preset ] ) ) {
			return null; // 'なし' と未知のキーは出さない
		}

		$p       = self::PATTERNS[ $preset ];
		$src     = self::image_url( $p['file'] );
		$retina  = self::retina_file( $p['file'] );
		$srcset  = null !== $retina ? $src . ' 1x, ' . self::image_url( $retina ) . ' 2x' : '';
		$alt     = isset( $attributes['bannerImageAlt'] ) && '' !== trim( (string) $attributes['bannerImageAlt'] )
			? (string) $attributes['bannerImageAlt']
			: $p['alt'];

		return array(
			'src'    => $src,
			'srcset' => $srcset,
			'alt'    => $alt,
			'width'  => (int) $p['width'],
			'height' => (int) $p['height'],
			'link'   => $link,
		);
	}

	private static function image_url( string $file ): string {
		return MADOGUCHI_BLOCKS_URL . 'assets/img/phone-cta/' . $file;
	}

	/**
	 * `foo.png` に対する `foo@2x.png` が同梱されていればそのファイル名を返す。
	 */
	private static function retina_file( string $file ): ?string {
		$ext  = pathinfo( $file, PATHINFO_EXTENSION );
		$base = pathinfo( $file, PATHINFO_FILENAME );
		$name = $base . '@2x' . ( '' !== $ext ? '.' . $ext : '' );
		return file_exists( MADOGUCHI_BLOCKS_DIR . 'assets/img/phone-cta/' . $name ) ? $name : null;
	}
}
