<?php
/**
 * ブロックエディタ向けの中継 REST。ブラウザから estima を直接叩かせないための窓口。
 */
class Madoguchi_Blocks_Phone_Cta_Rest {

	const NS = 'madoguchi/v1';

	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		$service_arg = array(
			'required'          => true,
			'type'              => 'string',
			'validate_callback' => function ( $value ) {
				return Madoguchi_Blocks_Phone_Cta_Services::is_valid( (string) $value );
			},
		);

		register_rest_route( self::NS, '/phone-cta/services', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'services' ),
			'permission_callback' => array( $this, 'can_edit' ),
		) );

		register_rest_route( self::NS, '/phone-cta/shops', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'shops' ),
			'permission_callback' => array( $this, 'can_edit' ),
			'args'                => array( 'service' => $service_arg ),
		) );

		register_rest_route( self::NS, '/phone-cta/shops/(?P<uuid>[A-Za-z0-9_-]{1,64})', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'shop' ),
			'permission_callback' => array( $this, 'can_edit' ),
			'args'                => array( 'service' => $service_arg ),
		) );

		register_rest_route( self::NS, '/phone-cta/refresh', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'refresh' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'service' => array( 'required' => false, 'type' => 'string' ),
			),
		) );
	}

	public function can_edit() {
		return current_user_can( 'edit_posts' );
	}

	public function services() {
		return rest_ensure_response( array( 'services' => madoguchi_blocks_phone_cta_enabled_services() ) );
	}

	public function shops( WP_REST_Request $req ) {
		$repo = Madoguchi_Blocks_Phone_Cta_Repository::default();
		return rest_ensure_response( array( 'shops' => $repo->list( (string) $req['service'] ) ) );
	}

	public function shop( WP_REST_Request $req ) {
		$repo = Madoguchi_Blocks_Phone_Cta_Repository::default();
		$shop = $repo->find( (string) $req['service'], (string) $req['uuid'] );
		if ( null === $shop ) {
			return new WP_Error( 'madoguchi_phone_cta_not_found', __( '店舗がマスタに存在しないか、非公開です。', 'madoguchi-blocks' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( $shop );
	}

	public function refresh( WP_REST_Request $req ) {
		$service = isset( $req['service'] ) && Madoguchi_Blocks_Phone_Cta_Services::is_valid( (string) $req['service'] ) ? (string) $req['service'] : null;
		Madoguchi_Blocks_Phone_Cta_Repository::default()->refresh( $service );
		return rest_ensure_response( array( 'ok' => true ) );
	}
}
