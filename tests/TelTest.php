<?php
use PHPUnit\Framework\TestCase;

class TelTest extends TestCase {
	public function test_toll_free_number(): void {
		$this->assertSame( 'tel:+81120000000', Madoguchi_Blocks_Phone_Cta_Tel::to_href( '0120-000-000' ) );
	}
	public function test_ip_phone_number(): void {
		$this->assertSame( 'tel:+815012345678', Madoguchi_Blocks_Phone_Cta_Tel::to_href( '050-1234-5678' ) );
	}
	public function test_full_width_and_spaces(): void {
		$this->assertSame( 'tel:+81312345678', Madoguchi_Blocks_Phone_Cta_Tel::to_href( '０３（１２３４）５６７８' ) );
	}
	public function test_already_international(): void {
		$this->assertSame( 'tel:+81120000000', Madoguchi_Blocks_Phone_Cta_Tel::to_href( '+81 120-000-000' ) );
	}
	public function test_empty_returns_empty(): void {
		$this->assertSame( '', Madoguchi_Blocks_Phone_Cta_Tel::to_href( 'なし' ) );
	}
}
