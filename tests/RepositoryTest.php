<?php
use PHPUnit\Framework\TestCase;

class Fake_Store extends Madoguchi_Blocks_Phone_Cta_Store {
	public $transients = array();
	public $options    = array();
	public function get_transient( string $key ) { return array_key_exists( $key, $this->transients ) ? $this->transients[ $key ] : false; }
	public function set_transient( string $key, $value, int $ttl ): void { $this->transients[ $key ] = $value; }
	public function delete_transient( string $key ): void { unset( $this->transients[ $key ] ); }
	public function get_option( string $key ) { return array_key_exists( $key, $this->options ) ? $this->options[ $key ] : false; }
	public function update_option( string $key, $value ): void { $this->options[ $key ] = $value; }
	public function delete_option( string $key ): void { unset( $this->options[ $key ] ); }
	public function get_transient_keys_with_prefix( string $prefix ): array {
		return array_values( array_filter( array_keys( $this->transients ), function ( $k ) use ( $prefix ) { return 0 === strpos( $k, $prefix ); } ) );
	}
}

/**
 * 外部オブジェクトキャッシュ利用時を模す: 前方一致の transient 列挙が常に空を返す。
 */
class Fake_Store_No_Prefix_Listing extends Fake_Store {
	public function get_transient_keys_with_prefix( string $prefix ): array {
		return array();
	}
}

class RepositoryTest extends TestCase {
	private $calls = array();

	private function repo( array $responses, ?Fake_Store $store = null ): array {
		$store = $store ?: new Fake_Store();
		$this->calls = array();
		$http  = function ( string $url ) use ( $responses ) {
			$this->calls[] = $url;
			foreach ( $responses as $suffix => $res ) {
				if ( substr( $url, -strlen( $suffix ) ) === $suffix ) {
					return $res;
				}
			}
			return array( 'code' => 0, 'body' => '' );
		};
		$repo = new Madoguchi_Blocks_Phone_Cta_Repository( array( 'kaitori' => 'https://api.example/' ), $store, $http );
		return array( $repo, $store );
	}

	public function test_list_fetches_then_uses_transient(): void {
		list( $repo, $store ) = $this->repo( array( '/v1/phone_ctas' => array( 'code' => 200, 'body' => '{"shops":[{"uuid":"u1","name":"大吉"}]}' ) ) );
		$this->assertSame( array( array( 'uuid' => 'u1', 'name' => '大吉' ) ), $repo->list( 'kaitori' ) );
		$this->assertSame( array( 'https://api.example/v1/phone_ctas' ), $this->calls );
		$repo->list( 'kaitori' );
		$this->assertCount( 1, $this->calls, 'transient ヒット時は HTTP を呼ばない' );
		$this->assertArrayHasKey( 'madoguchi_phone_cta_list_kaitori', $store->transients );
	}

	public function test_list_returns_empty_on_failure_or_unknown_service(): void {
		list( $repo ) = $this->repo( array() );
		$this->assertSame( array(), $repo->list( 'kaitori' ) );
		$this->assertSame( array(), $repo->list( 'nope' ) );
	}

	public function test_list_does_not_cache_malformed_json_as_success(): void {
		list( $repo, $store ) = $this->repo( array( '/v1/phone_ctas' => array( 'code' => 200, 'body' => 'not json' ) ) );
		$this->assertSame( array(), $repo->list( 'kaitori' ) );
		// 成功としては残さない（失敗マーカーでバックオフする）
		$this->assertSame( Madoguchi_Blocks_Phone_Cta_Repository::FAILED, $store->transients['madoguchi_phone_cta_list_kaitori'] );
	}

	public function test_list_backs_off_after_failure(): void {
		list( $repo, $store ) = $this->repo( array( '/v1/phone_ctas' => array( 'code' => 500, 'body' => '' ) ) );
		$this->assertSame( array(), $repo->list( 'kaitori' ) );
		$this->assertSame( Madoguchi_Blocks_Phone_Cta_Repository::FAILED, $store->transients['madoguchi_phone_cta_list_kaitori'] );
		// マーカーが残っている間は API を叩き直さない（編集画面で店舗ごとに呼ばれるため）
		$before = count( $this->calls );
		$this->assertSame( array(), $repo->list( 'kaitori' ) );
		$this->assertCount( $before, $this->calls );
	}

	public function test_refresh_deletes_shop_transients_even_without_prefix_listing(): void {
		$store = new Fake_Store_No_Prefix_Listing();
		list( $repo ) = $this->repo( array( '/v1/phone_ctas/u1' => array( 'code' => 200, 'body' => '{"uuid":"u1","name":"大吉","numbers":[]}' ) ), $store );
		$repo->find( 'kaitori', 'u1' );
		$this->assertArrayHasKey( 'madoguchi_phone_cta_kaitori_u1', $store->transients );

		$repo->refresh( 'kaitori' );
		$this->assertArrayNotHasKey( 'madoguchi_phone_cta_kaitori_u1', $store->transients );
		// last-good は残す（API が落ちている間の描画に使う）
		$this->assertArrayHasKey( 'madoguchi_phone_cta_last_kaitori_u1', $store->options );
	}

	public function test_refresh_of_other_service_keeps_shop_transient(): void {
		$store = new Fake_Store_No_Prefix_Listing();
		list( $repo ) = $this->repo( array( '/v1/phone_ctas/u1' => array( 'code' => 200, 'body' => '{"uuid":"u1","name":"大吉","numbers":[]}' ) ), $store );
		$repo->find( 'kaitori', 'u1' );
		$repo->refresh( 'fuyouhin' );
		$this->assertArrayHasKey( 'madoguchi_phone_cta_kaitori_u1', $store->transients );
	}

	public function test_find_fetches_and_saves_last_good(): void {
		list( $repo, $store ) = $this->repo( array( '/v1/phone_ctas/u1' => array( 'code' => 200, 'body' => '{"uuid":"u1","name":"大吉","numbers":[]}' ) ) );
		$shop = $repo->find( 'kaitori', 'u1' );
		$this->assertSame( '大吉', $shop['name'] );
		$this->assertSame( $shop, $store->options['madoguchi_phone_cta_last_kaitori_u1'] );
		$this->assertSame( $shop, $store->transients['madoguchi_phone_cta_kaitori_u1'] );
	}

	public function test_find_uses_last_good_when_http_fails(): void {
		$store = new Fake_Store();
		$store->options['madoguchi_phone_cta_last_kaitori_u1'] = array( 'uuid' => 'u1', 'name' => '保存済み' );
		list( $repo ) = $this->repo( array( '/v1/phone_ctas/u1' => array( 'code' => 500, 'body' => '' ) ), $store );
		$this->assertSame( '保存済み', $repo->find( 'kaitori', 'u1' )['name'] );
	}

	public function test_find_404_negative_caches_and_drops_last_good(): void {
		$store = new Fake_Store();
		$store->options['madoguchi_phone_cta_last_kaitori_u1'] = array( 'uuid' => 'u1' );
		list( $repo ) = $this->repo( array( '/v1/phone_ctas/u1' => array( 'code' => 404, 'body' => '{"error":"not_found"}' ) ), $store );
		$this->assertNull( $repo->find( 'kaitori', 'u1' ) );
		$this->assertArrayNotHasKey( 'madoguchi_phone_cta_last_kaitori_u1', $store->options );
		$this->assertNull( $repo->find( 'kaitori', 'u1' ) );
		$this->assertCount( 1, $this->calls, '404 はネガティブキャッシュされ再問い合わせしない' );
	}

	public function test_find_rejects_bad_uuid_without_http(): void {
		list( $repo ) = $this->repo( array() );
		$this->assertNull( $repo->find( 'kaitori', '../etc' ) );
		$this->assertSame( array(), $this->calls );
	}

	public function test_find_backs_off_after_failure_with_last_good(): void {
		$store = new Fake_Store();
		$store->options['madoguchi_phone_cta_last_kaitori_u1'] = array( 'uuid' => 'u1', 'name' => '保存済み' );
		list( $repo ) = $this->repo( array( '/v1/phone_ctas/u1' => array( 'code' => 500, 'body' => '' ) ), $store );
		$this->assertSame( '保存済み', $repo->find( 'kaitori', 'u1' )['name'] );
		$this->assertCount( 1, $this->calls );
		// バックオフ中（TTL内）は last-good を返し続け、再問い合わせしない
		$this->assertSame( '保存済み', $repo->find( 'kaitori', 'u1' )['name'] );
		$this->assertCount( 1, $this->calls, '失敗直後のバックオフ中は再問い合わせしない' );
	}

	public function test_find_backs_off_after_failure_without_last_good(): void {
		list( $repo ) = $this->repo( array( '/v1/phone_ctas/u1' => array( 'code' => 500, 'body' => '' ) ) );
		$this->assertNull( $repo->find( 'kaitori', 'u1' ) );
		$this->assertCount( 1, $this->calls );
		// バックオフ中（TTL内）は null を返し続け、再問い合わせしない
		$this->assertNull( $repo->find( 'kaitori', 'u1' ) );
		$this->assertCount( 1, $this->calls, '失敗直後のバックオフ中は再問い合わせしない' );
	}

	public function test_refresh_clears_failure_marker(): void {
		$store = new Fake_Store();
		$store->transients['madoguchi_phone_cta_kaitori_u1'] = Madoguchi_Blocks_Phone_Cta_Repository::FAILED;
		list( $repo ) = $this->repo( array(), $store );
		$repo->refresh( 'kaitori' );
		$this->assertArrayNotHasKey( 'madoguchi_phone_cta_kaitori_u1', $store->transients );
	}

	public function test_refresh_clears_transients_but_keeps_options(): void {
		$store = new Fake_Store();
		$store->transients['madoguchi_phone_cta_list_kaitori'] = array();
		$store->transients['madoguchi_phone_cta_kaitori_u1']   = array( 'uuid' => 'u1' );
		$store->transients['madoguchi_phone_cta_osouji_u9']    = array( 'uuid' => 'u9' );
		$store->options['madoguchi_phone_cta_last_kaitori_u1'] = array( 'uuid' => 'u1' );
		list( $repo ) = $this->repo( array(), $store );
		$repo->refresh( 'kaitori' );
		$this->assertSame( array( 'madoguchi_phone_cta_osouji_u9' ), array_keys( $store->transients ) );
		$this->assertArrayHasKey( 'madoguchi_phone_cta_last_kaitori_u1', $store->options );
		$repo->refresh();
		$this->assertSame( array(), $store->transients );
	}

	public function test_refresh_deletes_deterministic_list_key_even_without_prefix_listing(): void {
		$store = new Fake_Store_No_Prefix_Listing();
		$store->transients['madoguchi_phone_cta_list_kaitori'] = array( array( 'uuid' => 'u1', 'name' => 'x' ) );
		list( $repo ) = $this->repo( array(), $store );
		$repo->refresh( 'kaitori' );
		$this->assertArrayNotHasKey( 'madoguchi_phone_cta_list_kaitori', $store->transients, 'オブジェクトキャッシュ利用時でも決め打ちキーは消す' );
	}

	public function test_refresh_all_deletes_every_service_list_key_even_without_prefix_listing(): void {
		$store = new Fake_Store_No_Prefix_Listing();
		foreach ( array_keys( Madoguchi_Blocks_Phone_Cta_Services::KEYS ) as $service ) {
			$store->transients[ 'madoguchi_phone_cta_list_' . $service ] = array();
		}
		list( $repo ) = $this->repo( array(), $store );
		$repo->refresh();
		foreach ( array_keys( Madoguchi_Blocks_Phone_Cta_Services::KEYS ) as $service ) {
			$this->assertArrayNotHasKey( 'madoguchi_phone_cta_list_' . $service, $store->transients );
		}
	}
}
