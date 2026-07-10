/**
 * 条件別カードリンク（親） — エディタ表示
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InnerBlocks,
	InspectorControls,
	PanelColorSettings
} from '@wordpress/block-editor';

const ALLOWED_BLOCKS = [ 'madoguchi/condition-card' ];
const TEMPLATE = [ [ 'madoguchi/condition-card' ] ];

export default function Edit( { attributes, setAttributes }) {
	const { heading, accentColor } = attributes;
	const blockProps = useBlockProps({
		className: 'condition-cards',
		style: accentColor ? { '--md-brand': accentColor } : undefined
	});

	return (
		<>
		<InspectorControls>
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
				className="condition-cards__heading"
				value={ heading }
				onChange={ ( value ) => setAttributes({ heading: value }) }
				placeholder={ __( '見出し（例：港区で条件別に業者を探す）', 'madoguchi-blocks' ) }
			/>
			<div className="condition-cards__list">
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ TEMPLATE }
					orientation="vertical"
					renderAppender={ InnerBlocks.ButtonBlockAppender }
				/>
			</div>
		</div>
		</>
	);
}
