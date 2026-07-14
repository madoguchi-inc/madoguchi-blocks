/**
 * 比較テーブルの行（子） — エディタ表示
 * 編集は行カードとして表示（列整列はフロントで行う）。CTA列は InnerBlocks。
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	RichText,
	InspectorControls,
	PanelColorSettings
} from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';

const ALLOWED_BLOCKS = [ 'madoguchi/cta-button' ];
// セル内では既定でコンパクトなCTAボタンにする
const TEMPLATE = [ [ 'madoguchi/cta-button', {
	text: '公式サイト',
	fontSize: 14,
	borderRadius: 6,
	padding: { top: '9px', right: '14px', bottom: '9px', left: '14px' }
} ] ];

export default function Edit( { attributes, setAttributes, context }) {
	const { name, rating, values, nameColor } = attributes;
	const columns = ( context && context['madoguchi/comparisonColumns'] ) || [];
	const nameLabel = ( context && context['madoguchi/comparisonNameLabel'] ) || '';

	const blockProps = useBlockProps({ className: 'comparison-row-edit' });
	// CTAボタンは削除できないよう固定（挿入・削除・並べ替えを禁止。属性の編集は可能）
	const innerProps = useInnerBlocksProps(
		{ className: 'comparison-row-edit__cta' },
		{ allowedBlocks: ALLOWED_BLOCKS, template: TEMPLATE, templateLock: 'all' }
	);

	const updateValue = ( i, v ) => {
		const nv = Array.isArray( values ) ? values.slice() : [];
		nv[ i ] = v;
		setAttributes({ values: nv });
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( '評価', 'madoguchi-blocks' ) }>
					<RangeControl
						label={ __( '評価（星）', 'madoguchi-blocks' ) }
						value={ rating || 0 }
						onChange={ ( v ) => setAttributes({ rating: v }) }
						min={ 0 }
						max={ 5 }
						step={ 0.1 }
					/>
				</PanelBody>
				<PanelColorSettings
					title={ __( 'カラー設定', 'madoguchi-blocks' ) }
					colorSettings={ [ {
						value: nameColor,
						onChange: ( color ) => setAttributes({ nameColor: color || '' }),
						label: __( '名称の文字色', 'madoguchi-blocks' )
					} ] }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="comparison-row-edit__field">
					<span className="comparison-row-edit__lbl">{ nameLabel || __( '名称', 'madoguchi-blocks' ) }</span>
					<RichText
						tagName="span"
						className="comparison-row-edit__name"
						style={ nameColor ? { color: nameColor } : undefined }
						value={ name }
						onChange={ ( v ) => setAttributes({ name: v }) }
						placeholder={ nameLabel || __( '名称（例：業者・商品名）', 'madoguchi-blocks' ) }
						allowedFormats={ [] }
					/>
					{ rating > 0 && <span className="comparison-row-edit__rating">★ { Number( rating ).toFixed( 1 ) }</span> }
				</div>
				<div className="comparison-row-edit__field comparison-row-edit__field--cta">
					<span className="comparison-row-edit__lbl">{ __( 'CTA', 'madoguchi-blocks' ) }</span>
					<div { ...innerProps } />
				</div>
				{ columns.map( ( col, i ) => (
					<div className="comparison-row-edit__field" key={ i }>
						<span className="comparison-row-edit__lbl">{ col.label || ( __( '列', 'madoguchi-blocks' ) + ' ' + ( i + 1 ) ) }</span>
						<RichText
							tagName="span"
							className="comparison-row-edit__val"
							value={ ( values && values[ i ] ) || '' }
							onChange={ ( v ) => updateValue( i, v ) }
							placeholder={ __( '○ / - / 内容', 'madoguchi-blocks' ) }
							allowedFormats={ [] }
						/>
					</div>
				) ) }
			</div>
		</>
	);
}
