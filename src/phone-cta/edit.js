/**
 * 電話CTA（店舗別） — エディタ表示
 * 店舗マスタから選んだ 1〜3 店舗の「電話で査定額を聞く」カードをプレビューする。
 * サービス／店舗の選択・番号・文言の上書きはサイドバーで行う。
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	Button,
	Notice,
} from '@wordpress/components';
import { useServices, useShopDetail } from './use-shops';
import ShopPicker from './shop-picker';
import CardIcon from '../condition-card/icons';
import { textsFor } from './texts';

const MAX_SHOPS = 3;

function CardPreview( { service, item } ) {
	const { shop, loading, error } = useShopDetail( service, item.uuid );
	if ( ! item.uuid ) {
		return (
			<li className="phone-cta__card">
				<p className="phone-cta__lead">
					{ __( '店舗を選択してください', 'madoguchi-blocks' ) }
				</p>
			</li>
		);
	}
	if ( loading ) {
		return (
			<li className="phone-cta__card">
				<p className="phone-cta__lead">
					{ __( '読み込み中…', 'madoguchi-blocks' ) }
				</p>
			</li>
		);
	}
	if ( error || ! shop ) {
		return (
			<li className="phone-cta__card is-closed">
				<p className="phone-cta__notice">
					{ __( 'マスタに存在しません', 'madoguchi-blocks' ) }
				</p>
			</li>
		);
	}
	const number =
		( shop.numbers || [] ).find(
			( n ) => String( n.id ) === String( item.numberId )
		) ||
		( shop.numbers || [] ).find( ( n ) => n.is_default ) ||
		( shop.numbers || [] )[ 0 ];
	const label = (
		item.buttonLabel ||
		shop.button_label ||
		textsFor( service ).shopLabel
	).replace( '{shop}', shop.name );
	return (
		<li className="phone-cta__card is-open">
			{ shop.logo_url ? (
				<img
					className="phone-cta__logo"
					src={ shop.logo_url }
					alt={ shop.name }
				/>
			) : (
				<p className="phone-cta__name">{ shop.name }</p>
			) }
			{ ( item.leadText || shop.lead_text ) && (
				<p className="phone-cta__lead">
					{ item.leadText || shop.lead_text }
				</p>
			) }
			{ shop.specialty_text && (
				<p className="phone-cta__specialty">{ shop.specialty_text }</p>
			) }
			<span className="phone-cta__button">
				<CardIcon iconKey="phone" className="" size={ 18 } />
				{ label }
			</span>
			{ number && (
				<p className="phone-cta__number">{ number.phone_number }</p>
			) }
			<p className="phone-cta__hours">
				{ shop.reception_text
					? `受付時間：${ shop.reception_text }`
					: '' }
				{ number?.is_toll_free !== false && (
					<span className="phone-cta__badge">通話料無料</span>
				) }
			</p>
		</li>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const { service, heading, description, shops, showCampaign, isVisible } =
		attributes;
	const { services, loading: servicesLoading } = useServices();
	const serviceOptions = Object.entries( services ).map(
		( [ value, label ] ) => ( { label, value } )
	);
	const noServices = ! servicesLoading && serviceOptions.length === 0;

	const updateShop = ( i, next ) =>
		setAttributes( {
			shops: shops.map( ( s, j ) => ( j === i ? next : s ) ),
		} );
	const removeShop = ( i ) =>
		setAttributes( { shops: shops.filter( ( _, j ) => j !== i ) } );
	const moveShop = ( i, dir ) => {
		const j = i + dir;
		if ( j < 0 || j >= shops.length ) {
			return;
		}
		const next = [ ...shops ];
		[ next[ i ], next[ j ] ] = [ next[ j ], next[ i ] ];
		setAttributes( { shops: next } );
	};
	const addShop = () =>
		setAttributes( {
			shops: [
				...shops,
				{ uuid: '', numberId: null, buttonLabel: '', leadText: '' },
			],
		} );

	// サービス切替時、見出し／説明が「切替前サービスの既定文言のまま」なら新サービスの既定文言に
	// 差し替える。ユーザーが書き換え済みのカスタム文言は上書きしない。
	const changeService = ( next ) => {
		const prevTexts = textsFor( service );
		const nextTexts = textsFor( next );
		const patch = { service: next, shops: [] };
		if ( heading === prevTexts.heading ) {
			patch.heading = nextTexts.heading;
		}
		if ( description === prevTexts.description ) {
			patch.description = nextTexts.description;
		}
		setAttributes( patch );
	};

	const blockProps = useBlockProps( {
		className: `phone-cta phone-cta--cols-${ Math.max(
			1,
			Math.min( 3, shops.length )
		) }`,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( '店舗の設定', 'madoguchi-blocks' ) }
					initialOpen
				>
					{ noServices && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'設定 > Madoguchi Blocks で電話CTAの API URL を設定してください。',
								'madoguchi-blocks'
							) }
						</Notice>
					) }
					<SelectControl
						label={ __( 'サービス', 'madoguchi-blocks' ) }
						value={ service }
						options={
							serviceOptions.length
								? serviceOptions
								: [ { label: service, value: service } ]
						}
						onChange={ changeService }
						help={ __(
							'サービスを変えると店舗の選択はリセットされます。',
							'madoguchi-blocks'
						) }
					/>
					{ shops.map( ( item, i ) => (
						<ShopPicker
							key={ i }
							service={ service }
							item={ item }
							index={ i }
							onChange={ ( next ) => updateShop( i, next ) }
							onRemove={ () => removeShop( i ) }
							onMove={ ( dir ) => moveShop( i, dir ) }
							canMoveUp={ i > 0 }
							canMoveDown={ i < shops.length - 1 }
						/>
					) ) }
					<Button
						variant="secondary"
						onClick={ addShop }
						disabled={ shops.length >= MAX_SHOPS }
					>
						{ shops.length >= MAX_SHOPS
							? __( '店舗は 3 件まで', 'madoguchi-blocks' )
							: __( '店舗を追加', 'madoguchi-blocks' ) }
					</Button>
				</PanelBody>
				<PanelBody
					title={ __( '表示', 'madoguchi-blocks' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'キャンペーンを表示', 'madoguchi-blocks' ) }
						checked={ showCampaign }
						onChange={ ( v ) =>
							setAttributes( { showCampaign: v } )
						}
					/>
					<ToggleControl
						label={ __( 'このブロックを表示', 'madoguchi-blocks' ) }
						help={ __(
							'オフにするとブロックを残したまま表示側から消えます。',
							'madoguchi-blocks'
						) }
						checked={ isVisible }
						onChange={ ( v ) => setAttributes( { isVisible: v } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<RichText
					tagName="h2"
					className="phone-cta__heading"
					value={ heading }
					onChange={ ( v ) => setAttributes( { heading: v } ) }
					placeholder={ __( '見出し', 'madoguchi-blocks' ) }
				/>
				<RichText
					tagName="p"
					className="phone-cta__description"
					value={ description }
					onChange={ ( v ) => setAttributes( { description: v } ) }
					placeholder={ __( '説明文', 'madoguchi-blocks' ) }
				/>
				{ shops.length === 0 ? (
					<p className="phone-cta__description">
						{ __(
							'サイドバーから店舗を追加してください。',
							'madoguchi-blocks'
						) }
					</p>
				) : (
					<ul className="phone-cta__list">
						{ shops.map( ( item, i ) => (
							<CardPreview
								key={ i }
								service={ service }
								item={ item }
							/>
						) ) }
					</ul>
				) }
				<p className="phone-cta__note">
					{ __(
						'※ 表示側では受付時間で電話／WEB査定ボタンが自動で切り替わります（プレビューは受付時間内の見た目）',
						'madoguchi-blocks'
					) }
				</p>
				{ ! isVisible && (
					<p className="phone-cta__notice">
						{ __(
							'このブロックは非表示に設定されています',
							'madoguchi-blocks'
						) }
					</p>
				) }
			</section>
		</>
	);
}
