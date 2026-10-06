<?php
use PHPUnit\Framework\TestCase;

class BannerLinkTest extends TestCase {

	private function card( string $uuid, string $number_id = '', string $service = '' ): array {
		return array( 'uuid' => $uuid, 'number_id' => $number_id, 'service' => $service );
	}

	/** 形の正しい uuid。parse 側は形を検証するので、分解のテストではこれを使う */
	private function uuid( string $tail ): string {
		return '61b53641-7b06-48cc-a5a7-4848' . str_pad( $tail, 8, '0', STR_PAD_LEFT );
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

	public function test_appends_service_only_when_it_differs_from_default(): void {
		// 回収の記事に買取店を混ぜたとき、買取のカードにだけ @kaitori が付く
		$param = Madoguchi_Blocks_Phone_Cta_View::shops_param(
			array( $this->card( 'aaa', '', 'fuyouhin' ), $this->card( 'bbb', '3', 'kaitori' ) ),
			'fuyouhin'
		);
		$this->assertSame( 'aaa,bbb:3@kaitori', $param );
	}

	public function test_keeps_buyer_urls_unchanged(): void {
		// 買取の記事（既定=kaitori、全カードも kaitori）は従来どおり uuid[:numberId] だけ
		$param = Madoguchi_Blocks_Phone_Cta_View::shops_param(
			array( $this->card( 'aaa', '1', 'kaitori' ), $this->card( 'bbb', '', 'kaitori' ) ),
			'kaitori'
		);
		$this->assertSame( 'aaa:1,bbb', $param );
	}

	public function test_parses_param_back_into_picks(): void {
		$param = $this->uuid( '1' ) . ':3@kaitori,' . $this->uuid( '2' );
		$picks = Madoguchi_Blocks_Phone_Cta_View::parse_shops_param( $param, 'fuyouhin' );
		$this->assertSame(
			array(
				array( 'uuid' => $this->uuid( '1' ), 'number_id' => '3', 'service' => 'kaitori' ),
				array( 'uuid' => $this->uuid( '2' ), 'number_id' => '', 'service' => 'fuyouhin' ),
			),
			$picks
		);
	}

	public function test_parse_drops_malformed_uuid_and_unknown_service(): void {
		$param = 'not-a-uuid,' . $this->uuid( '1' ) . '@zzz,' . $this->uuid( '2' ) . ':abc';
		$picks = Madoguchi_Blocks_Phone_Cta_View::parse_shops_param( $param, 'fuyouhin' );
		// 1 件目は形が違うので落ちる。2 件目は知らないサービスなので既定に戻る。3 件目は番号だけ捨てる
		$this->assertCount( 2, $picks );
		$this->assertSame( 'fuyouhin', $picks[0]['service'] );
		$this->assertSame( '', $picks[1]['number_id'] );
	}

	public function test_parse_dedupes_and_caps(): void {
		$param = $this->uuid( '1' ) . ',' . $this->uuid( '1' ) . ':9,' . $this->uuid( '2' ) . ',' . $this->uuid( '3' ) . ',' . $this->uuid( '4' );
		$picks = Madoguchi_Blocks_Phone_Cta_View::parse_shops_param( $param, 'fuyouhin' );
		$this->assertCount( 3, $picks );
		// 同じ店舗は先に出た方を採る
		$this->assertSame( '', $picks[0]['number_id'] );
	}

	public function test_parse_returns_empty_for_blank(): void {
		$this->assertSame( array(), Madoguchi_Blocks_Phone_Cta_View::parse_shops_param( '', 'fuyouhin' ) );
	}
}
