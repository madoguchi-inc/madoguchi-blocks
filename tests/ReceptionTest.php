<?php
use PHPUnit\Framework\TestCase;

class ReceptionTest extends TestCase {

	private function jst( string $iso ): DateTimeImmutable {
		return new DateTimeImmutable( $iso, new DateTimeZone( 'Asia/Tokyo' ) );
	}

	private function shop( array $hours, bool $always = false ): array {
		return array( 'is_always_open' => $always, 'hours' => $hours );
	}

	public function test_always_open_is_open_regardless_of_hours(): void {
		$shop = $this->shop( array(), true );
		$this->assertTrue( Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $this->jst( '2026-09-16 03:00' ) ) );
	}

	public function test_no_row_for_today_means_closed(): void {
		// 2026-09-16 は水曜（wday 3）。月曜だけの行しかない
		$shop = $this->shop( array( array( 'wday' => 1, 'start' => '10:00', 'end' => '20:00' ) ) );
		$this->assertFalse( Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $this->jst( '2026-09-16 12:00' ) ) );
	}

	public function test_inside_window_is_open(): void {
		$shop = $this->shop( array( array( 'wday' => 3, 'start' => '10:00', 'end' => '20:00' ) ) );
		$this->assertTrue( Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $this->jst( '2026-09-16 12:00' ) ) );
	}

	public function test_boundaries_start_inclusive_end_exclusive(): void {
		$shop = $this->shop( array( array( 'wday' => 3, 'start' => '10:00', 'end' => '20:00' ) ) );
		$this->assertTrue( Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $this->jst( '2026-09-16 10:00:00' ) ) );
		$this->assertFalse( Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $this->jst( '2026-09-16 20:00:00' ) ) );
		$this->assertFalse( Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $this->jst( '2026-09-16 09:59:59' ) ) );
	}

	public function test_overnight_window_same_day_side(): void {
		// 水曜 22:00〜02:00。水曜 23:00 は open
		$shop = $this->shop( array( array( 'wday' => 3, 'start' => '22:00', 'end' => '02:00' ) ) );
		$this->assertTrue( Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $this->jst( '2026-09-16 23:00' ) ) );
		$this->assertFalse( Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $this->jst( '2026-09-16 12:00' ) ) );
	}

	public function test_overnight_window_next_day_early_side(): void {
		// 水曜 22:00〜02:00。木曜（wday 4）01:00 は前日の行で open、03:00 は closed
		$shop = $this->shop( array( array( 'wday' => 3, 'start' => '22:00', 'end' => '02:00' ) ) );
		$this->assertTrue( Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $this->jst( '2026-09-17 01:00' ) ) );
		$this->assertFalse( Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $this->jst( '2026-09-17 03:00' ) ) );
	}

	public function test_sunday_uses_saturday_overnight_row(): void {
		// 土曜（6）22:00〜02:00 → 日曜（0）01:00 は open
		$shop = $this->shop( array( array( 'wday' => 6, 'start' => '22:00', 'end' => '02:00' ) ) );
		$this->assertTrue( Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $this->jst( '2026-09-20 01:00' ) ) );
	}

	public function test_malformed_hours_are_ignored(): void {
		$shop = $this->shop( array( array( 'wday' => 3, 'start' => 'xx', 'end' => '20:00' ), 'garbage' ) );
		$this->assertFalse( Madoguchi_Blocks_Phone_Cta_Reception::is_open( $shop, $this->jst( '2026-09-16 12:00' ) ) );
	}

	public function test_services_keys(): void {
		$this->assertSame( array( 'kaitori', 'fuyouhin', 'osouji' ), array_keys( Madoguchi_Blocks_Phone_Cta_Services::KEYS ) );
		$this->assertTrue( Madoguchi_Blocks_Phone_Cta_Services::is_valid( 'kaitori' ) );
		$this->assertFalse( Madoguchi_Blocks_Phone_Cta_Services::is_valid( 'foo' ) );
	}

	public function test_services_resolve_falls_back(): void {
		// ブロックの既定サービス（第 2 引数の既定は kaitori）
		$this->assertSame( 'fuyouhin', Madoguchi_Blocks_Phone_Cta_Services::resolve( 'fuyouhin' ) );
		$this->assertSame( 'kaitori', Madoguchi_Blocks_Phone_Cta_Services::resolve( 'foo' ) );
		$this->assertSame( 'kaitori', Madoguchi_Blocks_Phone_Cta_Services::resolve( null ) );

		// カードごとの上書き。空・不正ならブロックのサービスに従う
		$this->assertSame( 'osouji', Madoguchi_Blocks_Phone_Cta_Services::resolve( 'osouji', 'fuyouhin' ) );
		$this->assertSame( 'fuyouhin', Madoguchi_Blocks_Phone_Cta_Services::resolve( '', 'fuyouhin' ) );
		$this->assertSame( 'fuyouhin', Madoguchi_Blocks_Phone_Cta_Services::resolve( null, 'fuyouhin' ) );
		$this->assertSame( 'fuyouhin', Madoguchi_Blocks_Phone_Cta_Services::resolve( array( 'osouji' ), 'fuyouhin' ) );
	}
}
