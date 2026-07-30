/**
 * 比較テーブル（親） — エディタ表示
 * 1列目の見出し・各列名はエディタ内で編集。行は comparison-row 子ブロックとして追加する。
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	useBlockProps,
	useInnerBlocksProps,
	RichText,
	InspectorControls,
	PanelColorSettings
} from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl, Button, ToggleControl, SelectControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';

const ALLOWED_BLOCKS = [ 'madoguchi/comparison-row' ];
const TEMPLATE = [ [ 'madoguchi/comparison-row' ] ];

export default function Edit( { attributes, setAttributes, clientId }) {
	const { caption, articleId, columns, accentColor, fontSize, nameLabel, nameColWidth, nameColBgColor, showCta, ctaLabel, ratingDisplay } = attributes;
	const [ dragIndex, setDragIndex ] = useState( null );

	const blockProps = useBlockProps({
		className: 'comparison-table comparison-table--edit',
		style: {
			...( accentColor ? { '--md-brand': accentColor } : {} ),
			...( fontSize ? { '--md-table-size': fontSize + 'px' } : {} ),
			...( nameColBgColor ? { '--md-name-bg': nameColBgColor } : {} )
		}
	});
	const innerProps = useInnerBlocksProps(
		{ className: 'comparison-table__rows-edit' },
		{ allowedBlocks: ALLOWED_BLOCKS, template: TEMPLATE, templateLock: false }
	);

	// 子行ブロックを取得（列削除時に values を揃えるため）
	const childRows = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks( clientId ),
		[ clientId ]
	);
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	const updateColumnLabel = ( index, value ) => {
		const next = columns.map( ( c, i ) => ( i === index ? { ...c, label: value } : c ) );
		setAttributes({ columns: next });
	};

	const addColumn = () => {
		setAttributes({ columns: [ ...columns, { label: '' } ] });
	};

	// 列削除時は全行の values から同じ位置を取り除く
	const removeColumn = ( index ) => {
		childRows.forEach( ( row ) => {
			const values = Array.isArray( row.attributes.values ) ? row.attributes.values.slice() : [];
			values.splice( index, 1 );
			updateBlockAttributes( row.clientId, { values });
		});
		setAttributes({ columns: columns.filter( ( _, i ) => i !== index ) });
	};

	// 配列の要素を fromIndex → toIndex へ移動する
	const reorder = ( arr, fromIndex, toIndex ) => {
		const next = arr.slice();
		const [ moved ] = next.splice( fromIndex, 1 );
		next.splice( toIndex, 0, moved );
		return next;
	};

	// 列の並べ替え時は全行の values も同じ順序に揃える
	const moveColumn = ( fromIndex, toIndex ) => {
		if ( fromIndex === toIndex || fromIndex < 0 || toIndex < 0 ) {
			return;
		}
		childRows.forEach( ( row ) => {
			const values = Array.isArray( row.attributes.values ) ? row.attributes.values.slice() : [];
			while ( values.length < columns.length ) {
				values.push( '' );
			}
			updateBlockAttributes( row.clientId, { values: reorder( values, fromIndex, toIndex ) });
		});
		setAttributes({ columns: reorder( columns, fromIndex, toIndex ) });
	};

	const handleDragStart = ( index ) => () => setDragIndex( index );
	const handleDragOver = ( event ) => event.preventDefault();
	const handleDrop = ( index ) => ( event ) => {
		event.preventDefault();
		if ( null !== dragIndex ) {
			moveColumn( dragIndex, index );
		}
		setDragIndex( null );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'テーブル設定', 'madoguchi-blocks' ) }>
					<RangeControl
						label={ __( '文字サイズ（px）', 'madoguchi-blocks' ) }
						value={ fontSize }
						onChange={ ( value ) => setAttributes({ fontSize: value }) }
						min={ 10 }
						max={ 22 }
					/>
					<TextControl
						label={ __( '計測用の記事ID（任意）', 'madoguchi-blocks' ) }
						value={ articleId }
						onChange={ ( value ) => setAttributes({ articleId: value }) }
						help={ __( '未入力の場合は現在の記事IDを使用します。', 'madoguchi-blocks' ) }
					/>
					<RangeControl
						label={ __( '名称列の幅（px）', 'madoguchi-blocks' ) }
						value={ nameColWidth }
						onChange={ ( value ) => setAttributes({ nameColWidth: value }) }
						min={ 100 }
						max={ 280 }
						step={ 10 }
					/>
					<ToggleControl
						label={ __( 'CTA列を表示', 'madoguchi-blocks' ) }
						checked={ showCta }
						onChange={ ( value ) => setAttributes({ showCta: value }) }
						help={ __( 'オフにすると各行のCTAボタン列を非表示にします。', 'madoguchi-blocks' ) }
					/>
					<SelectControl
						label={ __( '評価表示', 'madoguchi-blocks' ) }
						value={ ratingDisplay }
						options={ [
							{ label: __( '☆（星評価）', 'madoguchi-blocks' ), value: 'star' },
							{ label: __( 'PR表記', 'madoguchi-blocks' ), value: 'pr' }
						] }
						onChange={ ( value ) => setAttributes({ ratingDisplay: value }) }
					/>
				</PanelBody>
				<PanelColorSettings
					title={ __( 'カラー設定', 'madoguchi-blocks' ) }
					colorSettings={ [ {
						value: accentColor,
						onChange: ( color ) => setAttributes({ accentColor: color || '' }),
						label: __( 'アクセントカラー', 'madoguchi-blocks' )
					}, {
						value: nameColBgColor,
						onChange: ( color ) => setAttributes({ nameColBgColor: color || '' }),
						label: __( '名称列の背景色', 'madoguchi-blocks' )
					} ] }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				<RichText
					tagName="p"
					className="comparison-table__caption"
					value={ caption }
					onChange={ ( value ) => setAttributes({ caption: value }) }
					placeholder={ __( '見出し（例：おすすめサービスを比較）', 'madoguchi-blocks' ) }
					allowedFormats={ [] }
				/>

				<div className="comparison-table__columns-edit">
					<span className="comparison-table__columns-edit-label">{ __( '列（ドラッグで並べ替え）：', 'madoguchi-blocks' ) }</span>
					<span className="comparison-table__col-chip comparison-table__col-chip--name">
						<RichText
							tagName="span"
							className="comparison-table__col-label"
							value={ nameLabel || '' }
							onChange={ ( value ) => setAttributes({ nameLabel: value }) }
							placeholder={ __( '1列目の見出し（例：業者・商品名）', 'madoguchi-blocks' ) }
							allowedFormats={ [] }
						/>
						<span className="comparison-table__col-fixed-mark">{ __( '（固定）', 'madoguchi-blocks' ) }</span>
					</span>
					{ columns.map( ( col, index ) => (
						<span
							className={ 'comparison-table__col-chip' + ( dragIndex === index ? ' is-dragging' : '' ) }
							key={ index }
							draggable
							onDragStart={ handleDragStart( index ) }
							onDragOver={ handleDragOver }
							onDrop={ handleDrop( index ) }
							onDragEnd={ () => setDragIndex( null ) }
						>
							<span className="comparison-table__col-drag" aria-hidden="true">⠿</span>
							<RichText
								tagName="span"
								className="comparison-table__col-label"
								value={ col.label || '' }
								onChange={ ( value ) => updateColumnLabel( index, value ) }
								placeholder={ __( '列名', 'madoguchi-blocks' ) }
								allowedFormats={ [] }
							/>
							<button
								type="button"
								className="comparison-table__col-remove"
								onClick={ () => removeColumn( index ) }
								aria-label={ __( 'この列を削除', 'madoguchi-blocks' ) }
							>×</button>
						</span>
					) ) }
					{ showCta && (
						<span className="comparison-table__col-chip comparison-table__col-chip--cta">
							<RichText
								tagName="span"
								className="comparison-table__col-label"
								value={ ctaLabel || '' }
								onChange={ ( value ) => setAttributes({ ctaLabel: value }) }
								placeholder={ __( 'CTA見出し（例：詳細へ）', 'madoguchi-blocks' ) }
								allowedFormats={ [] }
							/>
							<span className="comparison-table__col-fixed-mark">{ __( '（固定）', 'madoguchi-blocks' ) }</span>
						</span>
					) }
					<Button variant="secondary" size="small" icon="plus" onClick={ addColumn }>
						{ __( '列を追加', 'madoguchi-blocks' ) }
					</Button>
				</div>

				<div { ...innerProps } />
			</div>
		</>
	);
}
