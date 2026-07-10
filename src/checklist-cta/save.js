/**
 * チェックリスト型CTA — フロント表示（保存マークアップ）
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save({ attributes }) {
	const { heading, lead, items, note, buttonLabel, buttonUrl } = attributes;
	const blockProps = useBlockProps.save({ className: 'checklist-cta' });

	return (
		<div { ...blockProps }>
			<div className="checklist-cta__head">
				<span className="checklist-cta__head-icon" aria-hidden="true"></span>
				<RichText.Content
					tagName="p"
					className="checklist-cta__title"
					value={ heading }
				/>
			</div>
			<RichText.Content
				tagName="div"
				className="checklist-cta__lead"
				value={ lead }
			/>
			<div className="checklist-cta__box">
				<RichText.Content
					tagName="ul"
					className="checklist-cta__list"
					multiline="li"
					value={ items }
				/>
			</div>
			<p className="checklist-cta__note">
				<span className="checklist-cta__note-icon" aria-hidden="true"></span>
				<RichText.Content
					tagName="span"
					className="checklist-cta__note-text"
					value={ note }
				/>
			</p>
			<p className="checklist-cta__action">
				<a className="checklist-cta__button" href={ buttonUrl }>
					<span className="checklist-cta__button-icon" aria-hidden="true"></span>
					<RichText.Content
						tagName="span"
						className="checklist-cta__button-label"
						value={ buttonLabel }
					/>
					<span className="checklist-cta__button-arrow" aria-hidden="true"></span>
				</a>
			</p>
		</div>
	);
}
