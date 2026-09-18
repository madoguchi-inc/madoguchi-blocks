/**
 * 電話CTA 固定フッター — エディタ表示
 * 画面下部に固定表示される電話CTAバーのプレビュー。店舗を指定すると店名入りの
 * 文言になる。受付時間の判定は表示側（render.php）で行うため、ここでは常に
 * 「受付時間内」の見た目でプレビューする。
 * マークアップは blocks/phone-cta-footer/render.php と同じクラス構成にし、style.css を共有する。
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
	TextControl,
	Button,
	Notice,
} from '@wordpress/components';
import { useServices, useShopDetail } from '../phone-cta/use-shops';
import ShopPicker from '../phone-cta/shop-picker';
import CardIcon from '../condition-card/icons';
import { textsFor, splitLabel } from '../phone-cta/texts';

// render-helpers.php の madoguchi_blocks_phone_cta_icon() と同じ構造（白丸の中にアイコン）
function Icon( { iconKey } ) {
	return (
		<span className="phone-cta__icon" aria-hidden="true">
			<CardIcon iconKey={ iconKey } className="" size={ 18 } />
		</span>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		service,
		shop,
		catchBadge,
		catchText,
		showWebButton,
		webButtonUrl,
		isVisible,
	} = attributes;
	const { services, loading } = useServices();
	const serviceOptions = Object.entries( services ).map(
		( [ value, label ] ) => ( { label, value } )
	);
	const { shop: detail } = useShopDetail( service, shop?.uuid );
	const texts = textsFor( service );

	const item = shop || {
		uuid: '',
		numberId: null,
		balloonText: '',
	};
	// ボタン文言は店名以外サービスごとに固定（PHP 側 View::footer_state と同じ）
	const [ labelShop, labelRest ] = detail
		? splitLabel( texts.shopLabel, detail.name )
		: [ '', texts.genericLabel ];

	// サービス切替時、キャッチコピーが「切替前サービスの既定文言のまま」なら新サービスの
	// 既定文言に差し替える。ユーザーが書き換え済みのカスタム文言は上書きしない。
	const changeService = ( next ) => {
		const prevTexts = textsFor( service );
		const nextTexts = textsFor( next );
		const patch = { service: next, shop: null };
		if ( catchText === prevTexts.footerCatch ) {
			patch.catchText = nextTexts.footerCatch;
		}
		if ( catchBadge === prevTexts.footerCatchBadge ) {
			patch.catchBadge = nextTexts.footerCatchBadge;
		}
		setAttributes( patch );
	};

	// 静的な編集キャンバスでは position: fixed を解除して見せる（暗い半透明の背景が
	// 白いキャンバスでは薄いので、編集中だけ不透明に近づける）
	const blockProps = useBlockProps( {
		className: 'phone-cta-footer',
		style: {
			position: 'relative',
			borderRadius: 8,
			background: 'rgba(24, 25, 30, 0.85)',
		},
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( '店舗の設定', 'madoguchi-blocks' ) }
					initialOpen
				>
					{ ! loading && serviceOptions.length === 0 && (
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
					/>
					{ shop ? (
						<>
							<ShopPicker
								service={ service }
								item={ item }
								onChange={ ( next ) =>
									setAttributes( { shop: next } )
								}
								onRemove={ () =>
									setAttributes( { shop: null } )
								}
								showLead={ false }
							/>
							<TextControl
								label={ __(
									'吹き出し文言',
									'madoguchi-blocks'
								) }
								help={ __(
									'空なら「その場でかんたん無料査定！」。PC のみ表示（SP では省略）。',
									'madoguchi-blocks'
								) }
								value={ item.balloonText || '' }
								onChange={ ( balloonText ) =>
									setAttributes( {
										shop: { ...item, balloonText },
									} )
								}
							/>
						</>
					) : (
						<Button
							variant="secondary"
							onClick={ () => setAttributes( { shop: item } ) }
						>
							{ __( '店舗を指定する', 'madoguchi-blocks' ) }
						</Button>
					) }
					{ ! shop && (
						<p className="description">
							{ __(
								'店舗を指定しない場合は「電話で査定額を聞く」の汎用文言で、記事内の電話CTAへスクロールします。',
								'madoguchi-blocks'
							) }
						</p>
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'キャッチコピー', 'madoguchi-blocks' ) }
					initialOpen={ false }
				>
					<TextControl
						label={ __( 'バッジ（ゴールド）', 'madoguchi-blocks' ) }
						help={ __(
							'空にするとバッジを出しません。',
							'madoguchi-blocks'
						) }
						value={ catchBadge || '' }
						onChange={ ( v ) => setAttributes( { catchBadge: v } ) }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'WEB ボタン・表示', 'madoguchi-blocks' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'オンライン一括査定ボタンを表示',
							'madoguchi-blocks'
						) }
						checked={ showWebButton }
						onChange={ ( v ) =>
							setAttributes( { showWebButton: v } )
						}
					/>
					{ showWebButton && (
						<TextControl
							label={ __( 'ボタンの遷移先', 'madoguchi-blocks' ) }
							value={ webButtonUrl }
							onChange={ ( v ) =>
								setAttributes( { webButtonUrl: v } )
							}
						/>
					) }
					<ToggleControl
						label={ __( 'このブロックを表示', 'madoguchi-blocks' ) }
						checked={ isVisible }
						onChange={ ( v ) => setAttributes( { isVisible: v } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<p className="phone-cta-footer__editor-hint">
					{ __(
						'表示側では画面の下に固定表示されます（1 記事に 1 つ。記事内のどこに置いても同じ）',
						'madoguchi-blocks'
					) }
				</p>
				<p className="phone-cta-footer__catch">
					{ ( catchBadge || '' ).trim() !== '' && (
						<span className="phone-cta-footer__catch-badge">
							{ catchBadge }
						</span>
					) }
					<RichText
						tagName="span"
						className="phone-cta-footer__catch-text"
						value={ catchText }
						onChange={ ( v ) => setAttributes( { catchText: v } ) }
						placeholder={ __( 'キャッチコピー', 'madoguchi-blocks' ) }
					/>
				</p>
				<div className="phone-cta-footer__buttons">
					{ showWebButton && (
						<span className="phone-cta-footer__web">
							<Icon iconKey="touch" />
							<span className="phone-cta-footer__web-main phone-cta-footer__web-main--pc">
								{ texts.webButton }
							</span>
							<span className="phone-cta-footer__web-main phone-cta-footer__web-main--sp">
								{ texts.webButtonSp }
							</span>
							<span className="phone-cta-footer__web-sub">
								{ texts.webButtonSub }
							</span>
						</span>
					) }
					<span className="phone-cta-footer__tel">
						<span className="phone-cta-footer__balloon">
							{ item.balloonText || texts.balloon }
						</span>
						<span className="phone-cta-footer__tel-main">
							<Icon iconKey="phone" />
							<span className="phone-cta-footer__tel-label">
								{ labelShop && (
									<span className="phone-cta-footer__tel-shop">
										{ labelShop }
									</span>
								) }
								<span className="phone-cta-footer__tel-rest">
									{ labelRest }
								</span>
							</span>
						</span>
					</span>
				</div>
				{ ! isVisible && (
					<p className="phone-cta-footer__catch">
						{ __(
							'（非表示に設定されています）',
							'madoguchi-blocks'
						) }
					</p>
				) }
			</div>
		</>
	);
}
