/**
 * チェックリスト型CTA — 旧バージョンの保存マークアップ（deprecated）
 *
 * v1: buttonLabel / buttonUrl 属性でボタンを自前描画していた。
 * エディタで開いて再保存すると、旧属性の文言・URLを引き継いだ
 * CTAボタンブロック（madoguchi/cta-button）へ自動移行する。
 * （旧デザインのグラデーション・角丸をCTAボタンの属性として再現する）
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';

const v1 = {
	attributes: {
		heading: { type: 'string', default: '' },
		lead: { type: 'string', default: '' },
		items: { type: 'string', default: '' },
		note: { type: 'string', default: '' },
		buttonLabel: { type: 'string', default: '【無料】対応できる不用品回収業者を確認する' },
		buttonUrl: { type: 'string', default: '#cv_form' }
	},
	supports: {
		html: false,
		anchor: true
	},
	migrate( attributes ) {
		const { buttonLabel, buttonUrl, ...rest } = attributes;
		return [
			rest,
			[
				createBlock( 'madoguchi/cta-button', {
					text: buttonLabel,
					url: buttonUrl,
					bgType: 'gradient',
					gradientFrom: '#fb8c00',
					gradientTo: '#f56f00',
					gradientAngle: 180,
					borderRadius: 12,
					fontSize: 20,
					padding: { top: '16px', right: '40px', bottom: '16px', left: '40px' }
				})
			]
		];
	},
	save({ attributes }) {
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
};

export default [ v1 ];
