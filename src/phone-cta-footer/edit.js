/**
 * 電話CTA 固定フッター — エディタ表示
 * 画面下部に固定表示される電話CTAバーのプレビュー。店舗を指定すると店名入りの
 * 文言になる。受付時間の判定は表示側（render.php）で行うため、ここでは常に
 * 「受付時間内」の見た目でプレビューする。
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

export default function Edit( { attributes, setAttributes } ) {
	const { service, shop, catchText, showWebButton, webButtonUrl, isVisible } =
		attributes;
	const { services, loading } = useServices();
	const serviceOptions = Object.entries( services ).map(
		( [ value, label ] ) => ( { label, value } )
	);
	const { shop: detail } = useShopDetail( service, shop?.uuid );

	const item = shop || {
		uuid: '',
		numberId: null,
		buttonLabel: '',
		balloonText: '',
	};
	const label = detail
		? (
				item.buttonLabel ||
				detail.button_label ||
				'{shop}に電話で査定額を聞く'
		  ).replace( '{shop}', detail.name )
		: item.buttonLabel || __( '電話で査定額を聞く', 'madoguchi-blocks' );

	// 静的な編集キャンバスでは position: fixed を解除して見せる
	const blockProps = useBlockProps( {
		className: 'phone-cta-footer',
		style: { position: 'relative', borderRadius: 8 },
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
						onChange={ ( v ) =>
							setAttributes( { service: v, shop: null } )
						}
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
									'空なら「その場でかんたん無料査定！」',
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
				<RichText
					tagName="p"
					className="phone-cta-footer__catch"
					value={ catchText }
					onChange={ ( v ) => setAttributes( { catchText: v } ) }
					placeholder={ __( 'キャッチコピー', 'madoguchi-blocks' ) }
				/>
				<div className="phone-cta-footer__buttons">
					{ showWebButton && (
						<span className="phone-cta-footer__web">
							{ __(
								'24時間年中無休で受付中！ オンライン無料一括査定',
								'madoguchi-blocks'
							) }
						</span>
					) }
					<span className="phone-cta-footer__tel">
						{ detail && (
							<span className="phone-cta-footer__balloon">
								{ item.balloonText ||
									__(
										'その場でかんたん無料査定！',
										'madoguchi-blocks'
									) }
							</span>
						) }
						<CardIcon iconKey="phone" className="" size={ 18 } />
						{ label }
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
