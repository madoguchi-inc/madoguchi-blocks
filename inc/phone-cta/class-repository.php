<?php
/**
 * estima の電話CTA API から店舗情報を取り、店舗ごとにキャッシュする。
 *
 * - list(): エディタのプルダウン用（uuid と name のみ）。失敗時は空配列
 * - find(): 表示用の詳細。transient → API → 失敗時は最後に成功したデータ（option）
 *   404 はネガティブキャッシュし、last-good も削除（非公開になった店舗を出さない）
 */
class Madoguchi_Blocks_Phone_Cta_Repository {

	const TTL      = 600;
	const TIMEOUT  = 5;
	const NOT_FOUND = '__404__';

	/** @var array<string,string> */
	private $api_urls;
	/** @var Madoguchi_Blocks_Phone_Cta_Store */
	private $store;
	/** @var callable */
	private $http;

	public function __construct( array $api_urls, Madoguchi_Blocks_Phone_Cta_Store $store, callable $http ) {
		$this->api_urls = $api_urls;
		$this->store    = $store;
		$this->http     = $http;
	}

	/**
	 * 本番用。設定画面の URL と wp_remote_get を束ねる。
	 */
	public static function default(): self {
		$http = function ( string $url ): array {
			$res = wp_remote_get( $url, array( 'timeout' => self::TIMEOUT, 'headers' => array( 'Accept' => 'application/json' ) ) );
			if ( is_wp_error( $res ) ) {
				return array( 'code' => 0, 'body' => '' );
			}
			return array( 'code' => (int) wp_remote_retrieve_response_code( $res ), 'body' => (string) wp_remote_retrieve_body( $res ) );
		};
		return new self( madoguchi_blocks_phone_cta_api_urls(), new Madoguchi_Blocks_Phone_Cta_Store(), $http );
	}

	public function list( string $service ): array {
		$base = $this->base_url( $service );
		if ( null === $base ) {
			return array();
		}
		$key    = 'madoguchi_phone_cta_list_' . $service;
		$cached = $this->store->get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$res = $this->request( $base . 'v1/phone_ctas' );
		if ( 200 !== $res['code'] ) {
			return array();
		}
		$json  = json_decode( $res['body'], true );
		$shops = isset( $json['shops'] ) && is_array( $json['shops'] ) ? $json['shops'] : array();
		$list  = array();
		foreach ( $shops as $s ) {
			if ( isset( $s['uuid'], $s['name'] ) ) {
				$list[] = array( 'uuid' => (string) $s['uuid'], 'name' => (string) $s['name'] );
			}
		}
		$this->store->set_transient( $key, $list, self::TTL );
		return $list;
	}

	public function find( string $service, string $uuid ): ?array {
		$base = $this->base_url( $service );
		if ( null === $base || ! preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $uuid ) ) {
			return null;
		}
		$key      = 'madoguchi_phone_cta_' . $service . '_' . $uuid;
		$last_key = 'madoguchi_phone_cta_last_' . $service . '_' . $uuid;

		$cached = $this->store->get_transient( $key );
		if ( self::NOT_FOUND === $cached ) {
			return null;
		}
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$res = $this->request( $base . 'v1/phone_ctas/' . rawurlencode( $uuid ) );
		if ( 200 === $res['code'] ) {
			$shop = json_decode( $res['body'], true );
			if ( is_array( $shop ) && isset( $shop['uuid'] ) ) {
				$this->store->set_transient( $key, $shop, self::TTL );
				$this->store->update_option( $last_key, $shop );
				return $shop;
			}
		}
		if ( 404 === $res['code'] ) {
			$this->store->set_transient( $key, self::NOT_FOUND, self::TTL );
			$this->store->delete_option( $last_key );
			return null;
		}
		// 通信失敗・5xx・壊れた JSON: 最後に成功したデータで描画を続ける
		$last = $this->store->get_option( $last_key );
		return is_array( $last ) ? $last : null;
	}

	public function refresh( ?string $service = null ): void {
		$prefixes = array();
		if ( null === $service ) {
			$prefixes[] = 'madoguchi_phone_cta_';
		} else {
			$prefixes[] = 'madoguchi_phone_cta_list_' . $service;
			$prefixes[] = 'madoguchi_phone_cta_' . $service . '_';
		}
		foreach ( $prefixes as $prefix ) {
			foreach ( $this->store->get_transient_keys_with_prefix( $prefix ) as $key ) {
				if ( 0 === strpos( $key, 'madoguchi_phone_cta_last_' ) ) {
					continue; // option 側のキーは transient には無いが念のため
				}
				$this->store->delete_transient( $key );
			}
		}
	}

	private function base_url( string $service ): ?string {
		if ( ! Madoguchi_Blocks_Phone_Cta_Services::is_valid( $service ) || empty( $this->api_urls[ $service ] ) ) {
			return null;
		}
		return rtrim( (string) $this->api_urls[ $service ], '/' ) . '/';
	}

	private function request( string $url ): array {
		$res = call_user_func( $this->http, $url );
		return array(
			'code' => isset( $res['code'] ) ? (int) $res['code'] : 0,
			'body' => isset( $res['body'] ) ? (string) $res['body'] : '',
		);
	}
}
