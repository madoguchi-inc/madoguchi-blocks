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
			'lead_text'        => '全国1,300店舗',
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

	public function test_pick_number_ignores_non_array_entries(): void {
		$shop = $this->shop( array( 'numbers' => array( 'garbage', array( 'id' => 1, 'phone_number' => '0120-000-000' ) ) ) );
		$number = Madoguchi_Blocks_Phone_Cta_View::pick_number( $shop, null );
		$this->assertSame( 1, $number['id'] );
		$this->assertNull( Madoguchi_Blocks_Phone_Cta_View::pick_number( array( 'numbers' => array( 'garbage', 'also garbage' ) ), null ) );
	}

	public function test_card_state_handles_missing_phone_number_without_error(): void {
		$shop  = $this->shop( array( 'numbers' => array( array( 'id' => 1 ) ) ) ); // phone_number キー欠落
		$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $shop, array(), $this->now( '2026-09-16 12:00' ) );
		$this->assertSame( '', $state['tel_href'] );
		$this->assertSame( '', $state['tel_display'] );
	}

	public function test_footer_state_handles_missing_phone_number_without_error(): void {
		$shop  = $this->shop( array( 'numbers' => array( array( 'id' => 1 ) ) ) ); // phone_number キー欠落
		$attrs = array( 'numberId' => null, 'balloonText' => '' );
		$state = Madoguchi_Blocks_Phone_Cta_View::footer_state( $shop, $attrs, $this->now( '2026-09-16 12:00' ), 'kaitori' );
		$this->assertSame( '', $state['tel_href'] );
	}

	public function test_button_label_is_fixed_per_service_except_shop_name(): void {
		$shop = $this->shop();
		$this->assertSame( '買取大吉に電話で査定額を聞く', Madoguchi_Blocks_Phone_Cta_View::button_label( $shop, 'kaitori' ) );
		$this->assertSame( '買取大吉に電話で見積もりを聞く', Madoguchi_Blocks_Phone_Cta_View::button_label( $shop, 'osouji' ) );
		// マスタ側に button_label が残っていても無視する（文言は全記事で揃える）
		$this->assertSame( '買取大吉に電話で査定額を聞く', Madoguchi_Blocks_Phone_Cta_View::button_label( $this->shop( array( 'button_label' => '独自' ) ), 'kaitori' ) );
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

	public function test_card_state_overrides_lead_but_not_label(): void {
		$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $this->shop(), array( 'leadText' => '独自リード', 'buttonLabel' => '独自ラベル' ), $this->now( '2026-09-16 12:00' ), 'kaitori' );
		$this->assertSame( '独自リード', $state['lead'] );
		$this->assertSame( '買取大吉に電話で査定額を聞く', $state['label'] ); // 旧属性 buttonLabel が残っていても無視
		$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $this->shop(), array(), $this->now( '2026-09-16 12:00' ), 'fuyouhin' );
		$this->assertSame( '買取大吉に電話で見積もりを聞く', $state['label'] );
	}

	public function test_card_state_prefers_item_web_url(): void {
		$closed = $this->now( '2026-09-16 21:00' );
		// カード単位の指定があればマスタより優先する
		$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $this->shop(), array( 'webUrl' => 'https://article.example/lp' ), $closed, 'kaitori' );
		$this->assertSame( 'web', $state['mode'] );
		$this->assertSame( 'https://article.example/lp', $state['fallback_url'] );

		// 空白のみならマスタの値を使う
		$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $this->shop(), array( 'webUrl' => '  ' ), $closed, 'kaitori' );
		$this->assertSame( 'https://lp.example/', $state['fallback_url'] );

		// マスタが空でもカード単位の指定があれば WEB ボタンになる
		$state = Madoguchi_Blocks_Phone_Cta_View::card_state( $this->shop( array( 'web_fallback_url' => '' ) ), array( 'webUrl' => 'https://article.example/lp' ), $closed, 'kaitori' );
		$this->assertSame( 'web', $state['mode'] );
	}

	public function test_card_state_without_numbers_is_null(): void {
		$this->assertNull( Madoguchi_Blocks_Phone_Cta_View::card_state( $this->shop( array( 'numbers' => array() ) ), array(), $this->now( '2026-09-16 12:00' ) ) );
	}

	public function test_footer_states(): void {
		$open   = $this->now( '2026-09-16 12:00' );
		$closed = $this->now( '2026-09-16 21:00' );
		$attrs  = array( 'numberId' => null, 'balloonText' => '' );

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

	public function test_label_parts_splits_after_particle(): void {
		$shop = $this->shop();
		// 店名＋「に」で切れる（買取 / 回収・清掃）
		$this->assertSame( array( '買取大吉に', '電話で査定額を聞く' ), Madoguchi_Blocks_Phone_Cta_View::label_parts( $shop, 'kaitori' ) );
		$this->assertSame( array( '買取大吉に', '電話で見積もりを聞く' ), Madoguchi_Blocks_Phone_Cta_View::label_parts( $shop, 'fuyouhin' ) );
		// 店名が無ければ助詞だけが前半
		$this->assertSame( array( 'に', '電話で査定額を聞く' ), Madoguchi_Blocks_Phone_Cta_View::label_parts( array(), 'kaitori' ) );
		// button_label と label_parts の結合は一致する
		$this->assertSame( '買取大吉に電話で査定額を聞く', Madoguchi_Blocks_Phone_Cta_View::button_label( $shop, 'kaitori' ) );
	}

	public function test_default_texts_by_service(): void {
		$this->assertSame( '今すぐ電話でかんたん無料査定', Madoguchi_Blocks_Phone_Cta_View::default_texts( 'kaitori' )['heading'] );
		$this->assertSame( '今すぐ電話でかんたん無料見積もり', Madoguchi_Blocks_Phone_Cta_View::default_texts( 'osouji' )['heading'] );
	}
}
