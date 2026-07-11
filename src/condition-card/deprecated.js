/**
 * 条件カード（子） — 旧バージョンの保存マークアップ（deprecated）
 *
 * v1: カード全体を <a>（linkLabel / linkUrl 属性）でラップしていた。
 * エディタで開いて再保存すると、旧属性の文言・URLを引き継いだ
 * CTAボタンブロック（madoguchi/cta-button）へ自動移行する。
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import CardIcon from './icons';

const v1 = {
	attributes: {
		title: { type: 'string', default: '' },
		description: { type: 'string', default: '' },
		linkLabel: { type: 'string', default: '' },
		linkUrl: { type: 'string', default: '' },
		variation: { type: 'string', default: 'icon' },
		iconKey: { type: 'string', default: 'truck' },
		imageUrl: { type: 'string', default: '' },
		accentColor: { type: 'string', default: '' }
	},
	supports: {
		html: false,
		reusable: false
	},
	migrate( attributes ) {
		const { linkLabel, linkUrl, ...rest } = attributes;
		return [
			rest,
			[
				createBlock( 'madoguchi/cta-button', {
					text: linkLabel,
					url: linkUrl,
					align: 'left',
					fontSize: 15,
					borderRadius: 8,
					padding: { top: '10px', right: '24px', bottom: '10px', left: '24px' }
				})
			]
		];
	},
	save({ attributes }) {
		const { title, description, linkLabel, linkUrl, variation, iconKey, imageUrl, accentColor } = attributes;
		const blockProps = useBlockProps.save({
			className: `condition-card condition-card--${ variation }`,
			style: accentColor ? { '--md-brand': accentColor } : undefined
		});

		return (
			<a { ...blockProps } href={ linkUrl || '#' }>
				<div className="condition-card__media" aria-hidden="true">
					{ variation === 'illustration' && imageUrl ? (
						<img className="condition-card__image" src={ imageUrl } alt="" />
					) : (
						<span className="condition-card__icon">
							<CardIcon iconKey={ iconKey } />
						</span>
					) }
				</div>
				<div className="condition-card__content">
					<RichText.Content
						tagName="p"
						className="condition-card__title"
						value={ title }
					/>
					<RichText.Content
						tagName="p"
						className="condition-card__desc"
						value={ description }
					/>
					<span className="condition-card__link">
						<RichText.Content
							tagName="span"
							className="condition-card__link-label"
							value={ linkLabel }
						/>
						<span className="condition-card__link-arrow" aria-hidden="true"></span>
					</span>
				</div>
			</a>
		);
	}
};

export default [ v1 ];
