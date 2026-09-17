<?php
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase {

	private function now( string $iso ): DateTimeImmutable {
		return new DateTimeImmutable( $iso, new DateTimeZone( 'Asia/Tokyo' ) );
	}

	private function shop( array $over = array() ): array {
		return array_merge( array(
			'uuid'             => 'u-1',
			'name'             => '買取大吉',
			'logo_url'         => 'https://cdn/logo.png',
			'specialty_text'   => '時計・バッグ',
			'lead_text'        => '全国1,300店舗',
			'button_label'     => '{shop}に電話で査定額を聞く',
			'web_fallback_url' => 'https://lp.example/',
			'is_always_open'   => false,
			'reception_text'   => '10:00〜20:00',
			'hours'            => array( array( 'wday' => 3, 'start' => '10:00', 'end' => '20:00' ) ), // 水曜のみ
			'numbers'          => array(
				array( 'id' => 1, 'label' => '標準', 'phone_number' => '0120-000-000', 'is_toll_free' => true, 'is_default' => false ),
				array( 'id' => 2, 'label' => '高単価', 'phone_number' => '050-1111-2222', 'is_toll_free' => false, 'is_default' => true ),
			),
			'campaign'         => null,
		), $over );
	}

	public function test_pick_number_prefers_requested_id_then_default_then_first(): void {
		$shop = $this->shop();
		$this->assertSame( 1, Madoguchi_Blocks_Phone_Cta_View::pick_number( $shop, 1 )['id'] );
		$this->assertSame( 2, Madoguchi_Blocks_Phone_Cta_View::pick_number( $shop, null )['id'] );
		$this->assertSame( 2, Madoguchi_Blocks_Phone_Cta_View::pick_number( $shop, 999 )['id'] );
		$shop['numbers'][1]['is_default'] = false;
		$this->assertSame( 1, Madoguchi_Blocks_Phone_Cta_View::pick_number( $shop, null )['id'] );
		$this->assertNull( Madoguchi_Blocks_Phone_Cta_View::pick_number( array( 'numbers' => array() ), null ) );
	}

	public function test_button_label_replaces_shop_and_respects_override(): void {
		$shop = $this->shop();
		$this->assertSame( '買取大吉に電話で査定額を聞く', Madoguchi_Blocks_Phone_Cta_View::button_label( $shop, '' ) );
		$this->assertSame( '大吉へ電話', Madoguchi_Blocks_Phone_Cta_View::button_label( $shop, '大吉へ電話' ) );
		$this->assertSame( '今すぐ買取大吉へ', Madoguchi_Blocks_Phone_Cta_View::button_label( $shop, '今すぐ{shop}へ' ) );
	}

	public function test_card_state_open(): void {
		$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $this->shop(), array( 'numberId' => 1 ), $this->now( '2026-09-16 12:00' ) );
		$this->assertSame( 'tel', $state['mode'] );
		$this->assertTrue( $state['is_open'] );
		$this->assertSame( 'tel:+81120000000', $state['tel_href'] );
		$this->assertSame( '0120-000-000', $state['tel_display'] );
		$this->assertTrue( $state['is_toll_free'] );
		$this->assertSame( '買取大吉に電話で査定額を聞く', $state['label'] );
	}

	public function test_card_state_closed_with_fallback_is_web(): void {
		$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $this->shop(), array(), $this->now( '2026-09-16 21:00' ) );
		$this->assertSame( 'web', $state['mode'] );
		$this->assertSame( 'https://lp.example/', $state['fallback_url'] );
	}

	public function test_card_state_closed_without_fallback_keeps_tel(): void {
		$shop  = $this->shop( array( 'web_fallback_url' => '' ) );
		$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $shop, array(), $this->now( '2026-09-16 21:00' ) );
		$this->assertSame( 'tel_closed', $state['mode'] );
		$this->assertFalse( $state['is_open'] );
	}

	public function test_card_state_overrides_lead_and_label(): void {
		$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $this->shop(), array( 'leadText' => '独自リード', 'buttonLabel' => '独自ラベル' ), $this->now( '2026-09-16 12:00' ) );
		$this->assertSame( '独自リード', $state['lead'] );
		$this->assertSame( '独自ラベル', $state['label'] );
	}

	public function test_card_state_without_numbers_is_null(): void {
		$this->assertNull( Madoguchi_Blocks_Phone_Cta_View::card_state( $this->shop( array( 'numbers' => array() ) ), array(), $this->now( '2026-09-16 12:00' ) ) );
	}

	public function test_footer_states(): void {
		$open   = $this->now( '2026-09-16 12:00' );
		$closed = $this->now( '2026-09-16 21:00' );
		$attrs  = array( 'numberId' => null, 'buttonLabel' => '', 'balloonText' => '' );

		$s = Madoguchi_Blocks_Phone_Cta_View::footer_state( $this->shop(), $attrs, $open, 'kaitori' );
		$this->assertSame( 'tel', $s['mode'] );
		$this->assertSame( '買取大吉に電話で査定額を聞く', $s['label'] );
		$this->assertSame( 'その場でかんたん無料査定！', $s['balloon'] );

		$s = Madoguchi_Blocks_Phone_Cta_View::footer_state( $this->shop(), $attrs, $closed, 'kaitori' );
		$this->assertSame( 'web', $s['mode'] );

		$s = Madoguchi_Blocks_Phone_Cta_View::footer_state( $this->shop( array( 'web_fallback_url' => '' ) ), $attrs, $closed, 'kaitori' );
		$this->assertSame( 'web_only', $s['mode'] );

		$s = Madoguchi_Blocks_Phone_Cta_View::footer_state( null, $attrs, $open, 'kaitori' );
		$this->assertSame( 'generic', $s['mode'] );
		$this->assertSame( '電話で査定額を聞く', $s['label'] );

		$s = Madoguchi_Blocks_Phone_Cta_View::footer_state( null, $attrs, $open, 'fuyouhin' );
		$this->assertSame( '電話で見積もりを聞く', $s['label'] );
	}

	public function test_default_texts_by_service(): void {
		$this->assertSame( '電話で査定額を聞ける提携買取店', Madoguchi_Blocks_Phone_Cta_View::default_texts( 'kaitori' )['heading'] );
		$this->assertSame( '電話で見積もりを聞ける提携業者', Madoguchi_Blocks_Phone_Cta_View::default_texts( 'osouji' )['heading'] );
	}
}
