/**
 * 条件カード（子） — フロント表示（保存マークアップ）
 * リンクは内包した CTAボタンブロック（madoguchi/cta-button）が描画する。
 * カード全体のクリックは view.js がボタンへ委譲する。
 * ①アイコンのみ ②イラスト入り の2バリエーションに対応。
 */

import { useBlockProps, RichText, InnerBlocks } from '@wordpress/block-editor';
import CardIcon from './icons';

export default function save({ attributes }) {
	const { title, description, variation, iconKey, imageUrl, accentColor } = attributes;
	const blockProps = useBlockProps.save({
		className: `condition-card condition-card--${ variation }`,
		style: accentColor ? { '--md-brand': accentColor } : undefined
	});

	return (
		<div { ...blockProps }>
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
				<div className="condition-card__action">
					<InnerBlocks.Content />
				</div>
			</div>
		</div>
	);
}
