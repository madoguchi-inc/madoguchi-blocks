<?php
use PHPUnit\Framework\TestCase;

class BannerLinkTest extends TestCase {

	private function card( string $uuid, string $number_id = '' ): array {
		return array( 'uuid' => $uuid, 'number_id' => $number_id );
	}

	public function test_joins_shops_in_order(): void {
		$param = Madoguchi_Blocks_Phone_Cta_View::shops_param(
			array( $this->card( 'aaa' ), $this->card( 'bbb' ), $this->card( 'ccc' ) )
		);
		$this->assertSame( 'aaa,bbb,ccc', $param );
	}

	public function test_includes_number_id_only_when_selected(): void {
		$param = Madoguchi_Blocks_Phone_Cta_View::shops_param( array( $this->card( 'aaa', '12' ), $this->card( 'bbb' ) ) );
		$this->assertSame( 'aaa:12,bbb', $param );
	}

	public function test_skips_cards_without_uuid(): void {
		$param = Madoguchi_Blocks_Phone_Cta_View::shops_param( array( $this->card( '' ), $this->card( 'bbb' ) ) );
		$this->assertSame( 'bbb', $param );
	}

	public function test_returns_empty_when_nothing_to_add(): void {
		$this->assertSame( '', Madoguchi_Blocks_Phone_Cta_View::shops_param( array() ) );
		$this->assertSame( '', Madoguchi_Blocks_Phone_Cta_View::shops_param( array( $this->card( '' ) ) ) );
	}
}
