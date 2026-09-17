/**
 * 電話CTAブロック — 1 店舗分の選択 UI（店舗 → 番号 → 文言上書き）。
 * サイドバー（Task 9）とフッター（Task 10）の両方から使う共通コンポーネント。
 * item: { uuid, numberId, buttonLabel, leadText }
 */

import { __ } from '@wordpress/i18n';
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
import { useShopList, useShopDetail } from './use-shops';

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
} ) {
	const { shops, loading: listLoading } = useShopList( service );
	const {
		shop,
		loading: detailLoading,
		error,
	} = useShopDetail( service, item.uuid );

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
					<TextControl
						label={ __( 'ボタン文言の上書き', 'madoguchi-blocks' ) }
						help={ __(
							'空ならマスタの文言。{shop} は店舗名に置き換わります。',
							'madoguchi-blocks'
						) }
						value={ item.buttonLabel || '' }
						onChange={ ( buttonLabel ) =>
							onChange( { ...item, buttonLabel } )
						}
					/>
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
