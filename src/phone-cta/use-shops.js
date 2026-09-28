/**
 * 電話CTAブロック — 店舗マスタ REST 取得用フック群。
 * サービス一覧 / 店舗一覧 / 店舗詳細（uuid ごとにメモ化）を提供する。
 */

import { useEffect, useState, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const BASE = '/madoguchi/v1/phone-cta';

function useRequest( path, enabled ) {
	const [ state, setState ] = useState( {
		data: null,
		loading: !! enabled,
		error: null,
	} );
	useEffect( () => {
		if ( ! enabled ) {
			setState( { data: null, loading: false, error: null } );
			return undefined;
		}
		let alive = true;
		setState( ( s ) => ( { ...s, loading: true, error: null } ) );
		apiFetch( { path } )
			.then(
				( data ) =>
					alive && setState( { data, loading: false, error: null } )
			)
			.catch(
				( error ) =>
					alive && setState( { data: null, loading: false, error } )
			);
		return () => {
			alive = false;
		};
	}, [ path, enabled ] );
	return state;
}

/** URL 設定済みのサービス一覧 */
export function useServices() {
	const { data, loading, error } = useRequest( `${ BASE }/services`, true );
	return { services: data?.services || {}, loading, error };
}

/**
 * サービスの店舗一覧（uuid, name）
 *
 * @param {string} service サービスキー。
 */
export function useShopList( service ) {
	const { data, loading, error } = useRequest(
		`${ BASE }/shops?service=${ encodeURIComponent( service || '' ) }`,
		!! service
	);
	return { shops: data?.shops || [], loading, error };
}

const detailCache = new Map();
/**
 * 店舗詳細。同じ uuid は再取得しない
 *
 * @param {string} service サービスキー。
 * @param {string} uuid    店舗 UUID。
 */
export function useShopDetail( service, uuid ) {
	const key = service && uuid ? `${ service }:${ uuid }` : '';
	const [ shop, setShop ] = useState(
		key ? detailCache.get( key ) || null : null
	);
	const [ loading, setLoading ] = useState(
		!! key && ! detailCache.has( key )
	);
	const [ error, setError ] = useState( null );
	const lastKey = useRef( key );

	useEffect( () => {
		lastKey.current = key;
		if ( ! key ) {
			setShop( null );
			setLoading( false );
			setError( null );
			return;
		}
		if ( detailCache.has( key ) ) {
			setShop( detailCache.get( key ) );
			setLoading( false );
			setError( null );
			return;
		}
		setLoading( true );
		setError( null );
		apiFetch( {
			path: `${ BASE }/shops/${ encodeURIComponent(
				uuid
			) }?service=${ encodeURIComponent( service ) }`,
		} )
			.then( ( data ) => {
				detailCache.set( key, data );
				if ( lastKey.current === key ) {
					setShop( data );
					setLoading( false );
				}
			} )
			.catch( ( e ) => {
				if ( lastKey.current === key ) {
					setShop( null );
					setError( e );
					setLoading( false );
				}
			} );
	}, [ key, service, uuid ] );

	return { shop, loading, error };
}
