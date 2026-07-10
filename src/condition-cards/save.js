/**
 * 条件別カードリンク（親） — フロント表示（保存マークアップ）
 */

import { useBlockProps, RichText, InnerBlocks } from '@wordpress/block-editor';

export default function save({ attributes }) {
	const { heading, accentColor } = attributes;
	const blockProps = useBlockProps.save({
		className: 'condition-cards',
		style: accentColor ? { '--md-brand': accentColor } : undefined
	});

	return (
		<div { ...blockProps }>
			<RichText.Content
				tagName="p"
				className="condition-cards__heading"
				value={ heading }
			/>
			<div className="condition-cards__list">
				<InnerBlocks.Content />
			</div>
		</div>
	);
}
