/**
 * CTAボタン — エディタ表示
 * ボタン内のテキスト（バッジ／ラベル）はカード内で直接編集する。
 * 背景色・文字色・角丸・整列・全方向margin/paddingをサイドバーで設定する。
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	PanelColorSettings
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	RangeControl,
	__experimentalBoxControl as BoxControl
} from '@wordpress/components';

// margin/padding のオブジェクトを React の style へ変換する
function boxToStyle( box, prop ) {
	const style = {};
	if ( ! box ) {
		return style;
	}
	[ 'top', 'right', 'bottom', 'left' ].forEach( ( side ) => {
		if ( box[ side ] ) {
			style[ prop + side.charAt( 0 ).toUpperCase() + side.slice( 1 ) ] = box[ side ];
		}
	});
	return style;
}

export default function Edit( { attributes, setAttributes }) {
	const { text, badgeText, badgePosition, badgeGap, badgeFontSize, url, backgroundColor, bgType, gradientFrom, gradientTo, gradientAngle, textColor, borderRadius, fontSize, align, margin, padding } = attributes;
	const showBadge = badgePosition !== 'none';
	const isGradient = bgType === 'gradient';

	// プレビュー用の背景（グラデーション or 単色）
	let bgStyle;
	if ( isGradient && gradientFrom && gradientTo ) {
		bgStyle = `linear-gradient(${ gradientAngle }deg, ${ gradientFrom }, ${ gradientTo })`;
	} else if ( ! isGradient && backgroundColor ) {
		bgStyle = backgroundColor;
	}

	// 背景タイプに応じた色設定
	const colorSettings = isGradient
		? [
			{ value: gradientFrom, onChange: ( c ) => setAttributes({ gradientFrom: c || '' }), label: __( 'グラデーション開始色', 'madoguchi-blocks' ) },
			{ value: gradientTo, onChange: ( c ) => setAttributes({ gradientTo: c || '' }), label: __( 'グラデーション終了色', 'madoguchi-blocks' ) },
			{ value: textColor, onChange: ( c ) => setAttributes({ textColor: c || '' }), label: __( '文字色', 'madoguchi-blocks' ) }
		]
		: [
			{ value: backgroundColor, onChange: ( c ) => setAttributes({ backgroundColor: c || '' }), label: __( '背景色', 'madoguchi-blocks' ) },
			{ value: textColor, onChange: ( c ) => setAttributes({ textColor: c || '' }), label: __( '文字色', 'madoguchi-blocks' ) }
		];

	const blockProps = useBlockProps({
		className: 'cta-button-wrap',
		style: { textAlign: align, ...boxToStyle( margin, 'margin' ) }
	});

	const buttonStyle = {
		...( bgStyle ? { background: bgStyle } : {} ),
		...( textColor ? { color: textColor } : {} ),
		borderRadius: ( borderRadius || 0 ) + 'px',
		gap: ( badgeGap || 0 ) + 'px',
		...( fontSize ? { fontSize: fontSize + 'px' } : {} ),
		...boxToStyle( padding, 'padding' )
	};

	// バッジ（表示位置に応じて前後）
	const badge = showBadge ? (
		<RichText
			tagName="span"
			className="cta-button__badge"
			style={ badgeFontSize ? { fontSize: badgeFontSize + 'px' } : undefined }
			value={ badgeText }
			onChange={ ( value ) => setAttributes({ badgeText: value }) }
			placeholder={ __( 'バッジ', 'madoguchi-blocks' ) }
			allowedFormats={ [] }
		/>
	) : null;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'リンク設定', 'madoguchi-blocks' ) }>
					<TextControl
						label={ __( 'リンク先URL', 'madoguchi-blocks' ) }
						value={ url }
						onChange={ ( value ) => setAttributes({ url: value }) }
						help={ __( '例: /lp/ や https://... など', 'madoguchi-blocks' ) }
					/>
				</PanelBody>
				<PanelBody title={ __( '表示', 'madoguchi-blocks' ) }>
					<SelectControl
						label={ __( '整列', 'madoguchi-blocks' ) }
						value={ align }
						options={ [
							{ label: __( '左寄せ', 'madoguchi-blocks' ), value: 'left' },
							{ label: __( '中央', 'madoguchi-blocks' ), value: 'center' },
							{ label: __( '右寄せ', 'madoguchi-blocks' ), value: 'right' }
						] }
						onChange={ ( value ) => setAttributes({ align: value }) }
					/>
					<RangeControl
						label={ __( '角丸（px）', 'madoguchi-blocks' ) }
						value={ borderRadius }
						onChange={ ( value ) => setAttributes({ borderRadius: value }) }
						min={ 0 }
						max={ 50 }
					/>
					<RangeControl
						label={ __( '文字サイズ（px）', 'madoguchi-blocks' ) }
						value={ fontSize }
						onChange={ ( value ) => setAttributes({ fontSize: value }) }
						min={ 10 }
						max={ 40 }
					/>
				</PanelBody>
				<PanelBody title={ __( 'バッジ', 'madoguchi-blocks' ) }>
					<SelectControl
						label={ __( 'バッジの表示', 'madoguchi-blocks' ) }
						value={ badgePosition }
						options={ [
							{ label: __( 'なし', 'madoguchi-blocks' ), value: 'none' },
							{ label: __( 'テキストの前', 'madoguchi-blocks' ), value: 'before' },
							{ label: __( 'テキストの後', 'madoguchi-blocks' ), value: 'after' }
						] }
						onChange={ ( value ) => setAttributes({ badgePosition: value }) }
					/>
					{ showBadge && (
						<>
							<RangeControl
								label={ __( 'バッジとテキストの間隔（px）', 'madoguchi-blocks' ) }
								value={ badgeGap }
								onChange={ ( value ) => setAttributes({ badgeGap: value }) }
								min={ 0 }
								max={ 40 }
							/>
							<RangeControl
								label={ __( 'バッジの文字サイズ（px）', 'madoguchi-blocks' ) }
								value={ badgeFontSize }
								onChange={ ( value ) => setAttributes({ badgeFontSize: value }) }
								min={ 8 }
								max={ 24 }
							/>
						</>
					) }
				</PanelBody>
				<PanelBody title={ __( '余白（margin）', 'madoguchi-blocks' ) }>
					<BoxControl
						label={ __( '外側の余白', 'madoguchi-blocks' ) }
						values={ margin }
						onChange={ ( value ) => setAttributes({ margin: value }) }
					/>
				</PanelBody>
				<PanelBody title={ __( '内側余白（padding）', 'madoguchi-blocks' ) }>
					<BoxControl
						label={ __( 'ボタン内の余白', 'madoguchi-blocks' ) }
						values={ padding }
						onChange={ ( value ) => setAttributes({ padding: value }) }
					/>
				</PanelBody>
				<PanelBody title={ __( '背景', 'madoguchi-blocks' ) }>
					<SelectControl
						label={ __( '背景タイプ', 'madoguchi-blocks' ) }
						value={ bgType }
						options={ [
							{ label: __( '単色', 'madoguchi-blocks' ), value: 'solid' },
							{ label: __( 'グラデーション', 'madoguchi-blocks' ), value: 'gradient' }
						] }
						onChange={ ( value ) => setAttributes({ bgType: value }) }
					/>
					{ isGradient && (
						<RangeControl
							label={ __( 'グラデーションの角度（度）', 'madoguchi-blocks' ) }
							value={ gradientAngle }
							onChange={ ( value ) => setAttributes({ gradientAngle: value }) }
							min={ 0 }
							max={ 360 }
						/>
					) }
				</PanelBody>
				<PanelColorSettings
					title={ __( 'カラー設定', 'madoguchi-blocks' ) }
					colorSettings={ colorSettings }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				<span className="cta-button" style={ buttonStyle }>
					{ badgePosition === 'before' && badge }
					<RichText
						tagName="span"
						className="cta-button__label"
						value={ text }
						onChange={ ( value ) => setAttributes({ text: value }) }
						placeholder={ __( 'ボタンの文言', 'madoguchi-blocks' ) }
						allowedFormats={ [] }
					/>
					{ badgePosition === 'after' && badge }
				</span>
			</div>
		</>
	);
}
