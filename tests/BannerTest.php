<?php
use PHPUnit\Framework\TestCase;

/**
 * バナーはブロック属性の service で決まるが、block.json から service の既定値を外したので
 * サービス未指定のブロックが普通にある。そのときサイトの主サービスに寄せないと、
 * 回収のサイトで回収のバナーが一致せず「バナーだけ出ない」状態になる。
 */
class BannerTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['madoguchi_blocks_test_options'] = array();
	}

	public function test_uses_primary_service_when_block_has_no_service(): void {
		$GLOBALS['madoguchi_blocks_test_options']['madoguchi_blocks_primary_service'] = 'fuyouhin';

		$banner = Madoguchi_Blocks_Phone_Cta_Banners::resolve( array( 'bannerPreset' => 'amazon-gift-2000' ) );

		$this->assertNotNull( $banner );
		$this->assertStringContainsString( '/phone-cta/fuyouhin/banner-amazon-gift-2000.png', $banner['src'] );
		$this->assertSame( '/campaign/amazon-gift-2000', $banner['link'] );
	}

	public function test_falls_back_to_kaitori_without_the_setting(): void {
		// 設定が無いサイト（買取）は従来どおり
		$banner = Madoguchi_Blocks_Phone_Cta_Banners::resolve( array( 'bannerPreset' => 'amazon-gift-12000' ) );

		$this->assertNotNull( $banner );
		$this->assertStringContainsString( '/phone-cta/kaitori/banner-amazon-gift-12000.png', $banner['src'] );
	}

	public function test_block_service_wins_over_primary_service(): void {
		$GLOBALS['madoguchi_blocks_test_options']['madoguchi_blocks_primary_service'] = 'fuyouhin';

		$banner = Madoguchi_Blocks_Phone_Cta_Banners::resolve(
			array( 'service' => 'kaitori', 'bannerPreset' => 'amazon-gift-12000' )
		);

		$this->assertNotNull( $banner );
		$this->assertStringContainsString( '/phone-cta/kaitori/', $banner['src'] );
	}

	public function test_returns_null_when_pattern_is_not_in_the_service(): void {
		$GLOBALS['madoguchi_blocks_test_options']['madoguchi_blocks_primary_service'] = 'fuyouhin';

		// 回収のサイトで買取のパターンキーを指定してもバナーは出さない
		$this->assertNull( Madoguchi_Blocks_Phone_Cta_Banners::resolve( array( 'bannerPreset' => 'amazon-gift-12000' ) ) );
		$this->assertNull( Madoguchi_Blocks_Phone_Cta_Banners::resolve( array() ) );
	}

	public function test_default_service_helper(): void {
		$this->assertSame( 'kaitori', madoguchi_blocks_phone_cta_default_service() );

		$GLOBALS['madoguchi_blocks_test_options']['madoguchi_blocks_primary_service'] = 'fuyouhin';
		$this->assertSame( 'fuyouhin', madoguchi_blocks_phone_cta_default_service() );

		$GLOBALS['madoguchi_blocks_test_options']['madoguchi_blocks_primary_service'] = 'zzz';
		$this->assertSame( 'kaitori', madoguchi_blocks_phone_cta_default_service() );
	}
}
