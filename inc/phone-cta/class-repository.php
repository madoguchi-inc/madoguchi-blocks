<?php
/**
 * estima の電話CTA API から店舗情報を取り、店舗ごとにキャッシュする。
 *
 * - list(): エディタのプルダウン用（uuid と name のみ）。失敗時は空配列
 * - find(): 表示用の詳細。transient → API → 失敗時は最後に成功したデータ（option）
 *   404 はネガティブキャッシュし、last-good も削除（非公開になった店舗を出さない）
 */
class Madoguchi_Blocks_Phone_Cta_Repository {

	const TTL       = 600;
	const TIMEOUT   = 5;
	const NOT_FOUND = '__404__';
	const FAIL_TTL  = 60;      // 取得失敗時のバックオフ（この間は再問い合わせしない）
	const FAILED    = '__fail__';
	// 店舗ごとの transient キー一覧。外部オブジェクトキャッシュ利用時は wp_options を
	// 前方一致で引いても見つからないため、書いたキーを自前で覚えて refresh で消す
	const INDEX_OPTION = 'madoguchi_phone_cta_keys';

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
		if ( self::FAILED === $cached ) {
			return array();
		}
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$res = $this->request( $base . 'v1/phone_ctas' );
		if ( 200 !== $res['code'] ) {
			// 編集画面は店舗ごとに list() を呼ぶので、estima が落ちている間に
			// 5 秒待ちの問い合わせを何度も走らせないよう短くバックオフする
			$this->store->set_transient( $key, self::FAILED, self::FAIL_TTL );
			return array();
		}
		$json = json_decode( $res['body'], true );
		if ( ! is_array( $json ) || ! isset( $json['shops'] ) || ! is_array( $json['shops'] ) ) {
			$this->store->set_transient( $key, self::FAILED, self::FAIL_TTL );
			return array(); // 壊れた JSON は成功として transient 化しない
		}
		$shops = $json['shops'];
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
		if ( self::NOT_FOUND === $cached || self::FAILED === $cached ) {
			return null;
		}
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$res = $this->request( $base . 'v1/phone_ctas/' . rawurlencode( $uuid ) );
		if ( 200 === $res['code'] ) {
			$shop = json_decode( $res['body'], true );
			if ( is_array( $shop ) && isset( $shop['uuid'] ) ) {
				$this->remember_key( $key );
				$this->store->set_transient( $key, $shop, self::TTL );
				$this->store->update_option( $last_key, $shop );
				return $shop;
			}
		}
		if ( 404 === $res['code'] ) {
			$this->remember_key( $key );
			$this->store->set_transient( $key, self::NOT_FOUND, self::TTL );
			$this->store->delete_option( $last_key );
			return null;
		}
		// 通信失敗・5xx・壊れた JSON: 最後に成功したデータで描画を続け、短い TTL でバックオフする
		// （estima がダウンしている間、ページビューのたびに 5 秒の wp_remote_get を再実行させない）
		$last = $this->store->get_option( $last_key );
		if ( is_array( $last ) ) {
			$this->remember_key( $key );
			$this->store->set_transient( $key, $last, self::FAIL_TTL );
			return $last;
		}
		$this->remember_key( $key );
		$this->store->set_transient( $key, self::FAILED, self::FAIL_TTL );
		return null;
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
		// 外部オブジェクトキャッシュ利用時は transient が wp_options に無いため前方一致列挙が
		// 何も見つけられない。list() の決め打ちキーだけは常に個別削除して確実に消す。
		$services = null === $service ? array_keys( Madoguchi_Blocks_Phone_Cta_Services::KEYS ) : array( $service );
		foreach ( $services as $s ) {
			$this->store->delete_transient( 'madoguchi_phone_cta_list_' . $s );
		}
		// 店舗ごとのキーも同じ理由で列挙できないので、find() が覚えた索引から個別に消す
		$this->forget_keys( $service );
	}

	/**
	 * find() が書いた店舗ごとの transient キーを索引に足す。
	 */
	private function remember_key( string $key ): void {
		$keys = $this->store->get_option( self::INDEX_OPTION );
		$keys = is_array( $keys ) ? $keys : array();
		if ( isset( $keys[ $key ] ) ) {
			return;
		}
		$keys[ $key ] = 1;
		$this->store->update_option( self::INDEX_OPTION, $keys );
	}

	/**
	 * 索引にある店舗キーの transient を消す。$service を渡すとそのサービス分だけ。
	 * last-good（option 側）は残す（API が落ちている間の描画に使うため）。
	 */
	private function forget_keys( ?string $service ): void {
		$keys = $this->store->get_option( self::INDEX_OPTION );
		if ( ! is_array( $keys ) ) {
			return;
		}
		$prefix    = null === $service ? 'madoguchi_phone_cta_' : 'madoguchi_phone_cta_' . $service . '_';
		$remaining = array();
		foreach ( array_keys( $keys ) as $key ) {
			if ( 0 === strpos( $key, $prefix ) ) {
				$this->store->delete_transient( $key );
			} else {
				$remaining[ $key ] = 1;
			}
		}
		$this->store->update_option( self::INDEX_OPTION, $remaining );
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
