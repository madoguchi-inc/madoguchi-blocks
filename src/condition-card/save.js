/**
 * 条件カード（子） — フロント表示（保存マークアップ）
 * カード全体を内部リンク <a> でラップする。
 * ①アイコンのみ ②イラスト入り の2バリエーションに対応。
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';
import CardIcon from './icons';

export default function save({ attributes }) {
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
