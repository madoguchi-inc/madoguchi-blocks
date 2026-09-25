/**
 * 電話CTAブロック — 1 店舗分の選択 UI（店舗 → 番号 → 文言上書き）。
 * サイドバー（Task 9）とフッター（Task 10）の両方から使う共通コンポーネント。
 * item: { service, uuid, numberId, leadText, webUrl }（ボタン文言は店名以外サービスごとに固定なので上書き項目は無い）
 * service は空なら「ブロックに従う」。入れるとそのカードだけ別サービスのマスタから店舗を引く。
 */

import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	Flex,
	FlexItem,
	Notice,
	SelectControl,
	Spinner,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { useShopList, useShopDetail, useServices } from './use-shops';

export default function ShopPicker( {
	service,
	item,
	index,
	onChange,
	onRemove,
	onMove,
	canMoveUp,
	canMoveDown,
	showLead = true,
	showWebUrl = true,
	showService = true,
} ) {
	// カードが自分でサービスを持っていればそれを使い、空ならブロックのサービスに従う
	const cardService = item.service || service;
	const { services } = useServices();
	const { shops, loading: listLoading } = useShopList( cardService );
	const {
		shop,
		loading: detailLoading,
		error,
	} = useShopDetail( cardService, item.uuid );

	const serviceOptions = [
		{
			label: sprintf(
				/* translators: %s: ブロックで選択中のサービス名 */
				__( 'ブロックに従う（%s）', 'madoguchi-blocks' ),
				services[ service ] || service
			),
			value: '',
		},
		...Object.entries( services ).map( ( [ value, label ] ) => ( {
			label,
			value,
		} ) ),
	];

	const shopOptions = [
		{ label: __( '店舗を選択…', 'madoguchi-blocks' ), value: '' },
		...shops.map( ( s ) => ( { label: s.name, value: s.uuid } ) ),
	];
	const numberOptions = [
		{ label: __( '既定の番号', 'madoguchi-blocks' ), value: '' },
		...( shop?.numbers || [] ).map( ( n ) => ( {
			label: `${ n.label }（${ n.phone_number }）`,
			value: String( n.id ),
		} ) ),
	];

	return (
		<div
			style={ {
				border: '1px solid #ddd',
				borderRadius: 4,
				padding: 8,
				marginBottom: 8,
			} }
		>
			<Flex justify="space-between" align="center">
				<FlexItem>
					<strong>
						{ index !== undefined ? `${ index + 1 }. ` : '' }
						{ shop?.name || __( '店舗', 'madoguchi-blocks' ) }
					</strong>
				</FlexItem>
				<FlexItem>
					{ onMove && (
						<Button
							size="small"
							icon="arrow-up-alt2"
							label={ __( '上へ', 'madoguchi-blocks' ) }
							disabled={ ! canMoveUp }
							onClick={ () => onMove( -1 ) }
						/>
					) }
					{ onMove && (
						<Button
							size="small"
							icon="arrow-down-alt2"
							label={ __( '下へ', 'madoguchi-blocks' ) }
							disabled={ ! canMoveDown }
							onClick={ () => onMove( 1 ) }
						/>
					) }
					{ onRemove && (
						<Button
							size="small"
							icon="trash"
							isDestructive
							label={ __( '削除', 'madoguchi-blocks' ) }
							onClick={ onRemove }
						/>
					) }
				</FlexItem>
			</Flex>
			{ showService && (
				<SelectControl
					label={ __( 'サービス', 'madoguchi-blocks' ) }
					value={ item.service || '' }
					options={ serviceOptions }
					onChange={ ( next ) =>
						onChange( {
							...item,
							service: next,
							uuid: '',
							numberId: null,
						} )
					}
					help={ __(
						'このカードだけ別のサービスの店舗を出したいときに変えます。変えると店舗の選択はリセットされます。',
						'madoguchi-blocks'
					) }
				/>
			) }
			{ listLoading && <Spinner /> }
			<SelectControl
				label={ __( '店舗', 'madoguchi-blocks' ) }
				value={ item.uuid || '' }
				options={ shopOptions }
				onChange={ ( uuid ) =>
					onChange( { ...item, uuid, numberId: null } )
				}
			/>
			{ item.uuid && error && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'この店舗はマスタに存在しないか、非公開です。表示側ではスキップされます。',
						'madoguchi-blocks'
					) }
				</Notice>
			) }
			{ detailLoading && <Spinner /> }
			{ shop && (
				<>
					<SelectControl
						label={ __( '電話番号', 'madoguchi-blocks' ) }
						value={
							item.numberId === null ||
							item.numberId === undefined
								? ''
								: String( item.numberId )
						}
						options={ numberOptions }
						onChange={ ( v ) =>
							onChange( {
								...item,
								numberId: v === '' ? null : v,
							} )
						}
					/>
					{ showWebUrl && (
						<TextControl
							label={ __(
								'WEB査定のリンク（任意）',
								'madoguchi-blocks'
							) }
							help={ __(
								'受付時間外に出す「WEBでカンタン無料査定」の遷移先。空なら店舗マスタの値を使います。',
								'madoguchi-blocks'
							) }
							value={ item.webUrl || '' }
							onChange={ ( webUrl ) =>
								onChange( { ...item, webUrl } )
							}
						/>
					) }
					{ showLead && (
						<TextareaControl
							label={ __( '紹介文の上書き', 'madoguchi-blocks' ) }
							help={ __(
								'空ならマスタの紹介文。',
								'madoguchi-blocks'
							) }
							value={ item.leadText || '' }
							onChange={ ( leadText ) =>
								onChange( { ...item, leadText } )
							}
						/>
					) }
				</>
			) }
		</div>
	);
}
