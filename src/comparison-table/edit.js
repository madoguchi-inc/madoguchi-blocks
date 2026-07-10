/**
 * 買取業者比較テーブル（親） — エディタ表示
 * 列（列名）はエディタ内で追加/編集/削除。行は comparison-row 子ブロックとして追加する。
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	RichText,
	InspectorControls,
	PanelColorSettings
} from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl, Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';

const ALLOWED_BLOCKS = [ 'madoguchi/comparison-row' ];
const TEMPLATE = [ [ 'madoguchi/comparison-row' ] ];

export default function Edit( { attributes, setAttributes, clientId }) {
	const { caption, articleId, columns, accentColor, fontSize } = attributes;

	const blockProps = useBlockProps({
		className: 'comparison-table comparison-table--edit',
		style: {
			...( accentColor ? { '--md-brand': accentColor } : {} ),
			...( fontSize ? { '--md-table-size': fontSize + 'px' } : {} )
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
				</PanelBody>
				<PanelColorSettings
					title={ __( 'カラー設定', 'madoguchi-blocks' ) }
					colorSettings={ [ {
						value: accentColor,
						onChange: ( color ) => setAttributes({ accentColor: color || '' }),
						label: __( 'アクセントカラー', 'madoguchi-blocks' )
					} ] }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				<RichText
					tagName="p"
					className="comparison-table__caption"
					value={ caption }
					onChange={ ( value ) => setAttributes({ caption: value }) }
					placeholder={ __( '見出し（例：おすすめ買取業者を比較）', 'madoguchi-blocks' ) }
					allowedFormats={ [] }
				/>

				<div className="comparison-table__columns-edit">
					<span className="comparison-table__columns-edit-label">{ __( '列：', 'madoguchi-blocks' ) }</span>
					<span className="comparison-table__columns-edit-fixed">{ __( '業者（固定）', 'madoguchi-blocks' ) }</span>
					{ columns.map( ( col, index ) => (
						<span className="comparison-table__col-chip" key={ index }>
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
					<span className="comparison-table__col-chip comparison-table__col-chip--cta">{ __( 'CTA（固定）', 'madoguchi-blocks' ) }</span>
					<Button variant="secondary" size="small" icon="plus" onClick={ addColumn }>
						{ __( '列を追加', 'madoguchi-blocks' ) }
					</Button>
				</div>

				<div { ...innerProps } />
			</div>
		</>
	);
}
