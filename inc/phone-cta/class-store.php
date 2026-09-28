<?php
/**
 * transient / option への薄いラッパー。テストではサブクラスで配列に差し替える。
 */
class Madoguchi_Blocks_Phone_Cta_Store {

	public function get_transient( string $key ) {
		return get_transient( $key );
	}

	public function set_transient( string $key, $value, int $ttl ): void {
		set_transient( $key, $value, $ttl );
	}

	public function delete_transient( string $key ): void {
		delete_transient( $key );
	}

	public function get_option( string $key ) {
		return get_option( $key, false );
	}

	public function update_option( string $key, $value ): void {
		update_option( $key, $value, false ); // autoload しない
	}

	public function delete_option( string $key ): void {
		delete_option( $key );
	}

	/**
	 * 前方一致で transient 名を列挙する（refresh 用）。
	 * オブジェクトキャッシュ利用時は DB に無いことがあるため、既知のキーも別途消す前提。
	 */
	public function get_transient_keys_with_prefix( string $prefix ): array {
		global $wpdb;
		$like = $wpdb->esc_like( '_transient_' . $prefix ) . '%';
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		return array_map( function ( $name ) {
			return substr( $name, strlen( '_transient_' ) );
		}, $rows ? $rows : array() );
	}
}
