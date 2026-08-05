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
import { PanelBody, RangeControl, SelectControl } from '@wordpress/components';

const ALLOWED_BLOCKS = [ 'madoguchi/cta-button' ];
// セル内では既定でコンパクトなCTAボタンにする
const TEMPLATE = [ [ 'madoguchi/cta-button', {
	text: '公式サイト',
	fontSize: 14,
	borderRadius: 6,
	padding: { top: '9px', right: '14px', bottom: '9px', left: '14px' }
} ] ];

export default function Edit( { attributes, setAttributes, context }) {
	const { name, rating, values, nameColor, ctaNote, ratingDisplay, ctaNoteFontSize, ctaNoteColor } = attributes;
	const columns = ( context && context['madoguchi/comparisonColumns'] ) || [];
	const nameLabel = ( context && context['madoguchi/comparisonNameLabel'] ) || '';
	const showCta = ! context || context['madoguchi/comparisonShowCta'] !== false;

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

	// チェック列・口コミ列は値をオブジェクトで持つため、既存キーを保ったまま1項目だけ更新する
	const updateValueField = ( i, key, v, fallback ) => {
		const current = ( values && values[ i ] && 'object' === typeof values[ i ] ) ? values[ i ] : fallback;
		updateValue( i, { ...current, [ key ]: v } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( '評価', 'madoguchi-blocks' ) }>
					<SelectControl
						label={ __( '表示方式', 'madoguchi-blocks' ) }
						value={ ratingDisplay || 'star' }
						options={ [
							{ label: __( '☆（星評価）', 'madoguchi-blocks' ), value: 'star' },
							{ label: __( 'PRバッジ', 'madoguchi-blocks' ), value: 'pr' },
							{ label: __( 'なし（表示しない）', 'madoguchi-blocks' ), value: 'none' }
						] }
						onChange={ ( value ) => setAttributes({ ratingDisplay: value }) }
					/>
					{ 'star' === ( ratingDisplay || 'star' ) && (
						<RangeControl
							label={ __( '評価（星）', 'madoguchi-blocks' ) }
							value={ rating || 0 }
							onChange={ ( v ) => setAttributes({ rating: v }) }
							min={ 0 }
							max={ 5 }
							step={ 0.1 }
						/>
					) }
				</PanelBody>
				<PanelColorSettings
					title={ __( 'カラー設定', 'madoguchi-blocks' ) }
					colorSettings={ [ {
						value: nameColor,
						onChange: ( color ) => setAttributes({ nameColor: color || '' }),
						label: __( '名称の文字色', 'madoguchi-blocks' )
					}, ...( showCta ? [ {
						value: ctaNoteColor,
						onChange: ( color ) => setAttributes({ ctaNoteColor: color || '' }),
						label: __( 'CTA下の補足文の文字色（この行のみ上書き）', 'madoguchi-blocks' )
					} ] : [] ) ] }
				/>
				{ showCta && (
					<PanelBody title={ __( 'CTA補足文', 'madoguchi-blocks' ) } initialOpen={ false }>
						<RangeControl
							label={ __( '文字サイズ（px・この行のみ上書き）', 'madoguchi-blocks' ) }
							value={ ctaNoteFontSize || 0 }
							onChange={ ( value ) => setAttributes({ ctaNoteFontSize: value }) }
							min={ 0 }
							max={ 20 }
							help={ __( '0はテーブル側の既定値を使用します。', 'madoguchi-blocks' ) }
						/>
					</PanelBody>
				) }
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
					{ 'pr' === ratingDisplay && <span className="comparison-row-edit__rating">{ __( 'PR', 'madoguchi-blocks' ) }</span> }
					{ 'star' === ( ratingDisplay || 'star' ) && rating > 0 && <span className="comparison-row-edit__rating">★ { Number( rating ).toFixed( 1 ) }</span> }
				</div>
				{ showCta && (
					<div className="comparison-row-edit__field comparison-row-edit__field--cta">
						<span className="comparison-row-edit__lbl">{ __( 'CTA', 'madoguchi-blocks' ) }</span>
						<div>
							<div { ...innerProps } />
							<RichText
								tagName="p"
								className="comparison-row-edit__cta-note"
								style={ {
									...( ctaNoteFontSize ? { '--md-cta-note-size': ctaNoteFontSize + 'px' } : {} ),
									...( ctaNoteColor ? { '--md-cta-note-color': ctaNoteColor } : {} )
								} }
								value={ ctaNote }
								onChange={ ( value ) => setAttributes({ ctaNote: value }) }
								placeholder={ __( 'CTA下の補足文（任意・例：初回限定）', 'madoguchi-blocks' ) }
							/>
						</div>
					</div>
				) }
				{ columns.map( ( col, i ) => {
					const colType = col.type || 'text';
					const cellValue = values && values[ i ];
					const cellStyle = {
						...( col.fontSize ? { fontSize: col.fontSize + 'px' } : {} ),
						...( col.color ? { color: col.color } : {} )
					};
					return (
						<div className="comparison-row-edit__field" key={ i }>
							<span className="comparison-row-edit__lbl">{ col.label || ( __( '列', 'madoguchi-blocks' ) + ' ' + ( i + 1 ) ) }</span>
							{ 'text' === colType && (
								<RichText
									tagName="span"
									className="comparison-row-edit__val"
									style={ cellStyle }
									value={ ( 'string' === typeof cellValue && cellValue ) || '' }
									onChange={ ( v ) => updateValue( i, v ) }
									placeholder={ __( '○ / - / 内容', 'madoguchi-blocks' ) }
									allowedFormats={ [] }
								/>
							) }
							{ 'check' === colType && (
								<span className="comparison-row-edit__check">
									<SelectControl
										value={ ( cellValue && cellValue.mark ) || 'none' }
										options={ [
											{ label: __( 'なし', 'madoguchi-blocks' ), value: 'none' },
											{ label: __( '✓（あり）', 'madoguchi-blocks' ), value: 'check' },
											{ label: __( '✕（なし）', 'madoguchi-blocks' ), value: 'cross' }
										] }
										onChange={ ( v ) => updateValueField( i, 'mark', v, { mark: 'none', text: '' } ) }
									/>
									<RichText
										tagName="span"
										className="comparison-row-edit__val"
										style={ cellStyle }
										value={ ( cellValue && cellValue.text ) || '' }
										onChange={ ( v ) => updateValueField( i, 'text', v, { mark: 'none', text: '' } ) }
										placeholder={ __( '補足テキスト（任意）', 'madoguchi-blocks' ) }
										allowedFormats={ [] }
									/>
								</span>
							) }
							{ 'review' === colType && (
								<span className="comparison-row-edit__review">
									<SelectControl
										value={ ( cellValue && cellValue.mode ) || 'stars' }
										options={ [
											{ label: __( '☆（星評価）', 'madoguchi-blocks' ), value: 'stars' },
											{ label: __( 'PRバッジ', 'madoguchi-blocks' ), value: 'pr' }
										] }
										onChange={ ( v ) => updateValueField( i, 'mode', v, { mode: 'stars', rating: 0 } ) }
									/>
									{ 'pr' !== ( ( cellValue && cellValue.mode ) || 'stars' ) && (
										<RangeControl
											value={ ( cellValue && cellValue.rating ) || 0 }
											onChange={ ( v ) => updateValueField( i, 'rating', v, { mode: 'stars', rating: 0 } ) }
											min={ 0 }
											max={ 5 }
											step={ 0.5 }
										/>
									) }
								</span>
							) }
							{ 'score' === colType && (
								<RangeControl
									value={ ( 'number' === typeof cellValue && cellValue ) || 0 }
									onChange={ ( v ) => updateValue( i, v ) }
									min={ 0 }
									max={ 5 }
									step={ 0.5 }
								/>
							) }
						</div>
					);
				} ) }
			</div>
		</>
	);
}
