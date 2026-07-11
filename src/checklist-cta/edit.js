/**
 * チェックリスト型CTA — エディタ表示
 * ボタンは CTAボタンブロック（madoguchi/cta-button）を内包する。
 * 文言・URL・色などはボタン側のサイドバーで設定する。
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText, InnerBlocks } from '@wordpress/block-editor';

// ボタン部分に置けるブロック（CTAボタンのみ）
const ALLOWED_BLOCKS = [ 'madoguchi/cta-button' ];

// 既定は従来のチェックリスト型CTAボタンと同じ見た目・文言
const TEMPLATE = [
	[
		'madoguchi/cta-button',
		{
			text: '【無料】対応できる不用品回収業者を確認する',
			url: '#cv_form',
			bgType: 'gradient',
			gradientFrom: '#fb8c00',
			gradientTo: '#f56f00',
			gradientAngle: 180,
			borderRadius: 12,
			fontSize: 20,
			padding: { top: '16px', right: '40px', bottom: '16px', left: '40px' }
		}
	]
];

export default function Edit( { attributes, setAttributes }) {
	const { heading, lead, items, note } = attributes;
	const blockProps = useBlockProps({ className: 'checklist-cta' });

	return (
		<div { ...blockProps }>
			<div className="checklist-cta__head">
				<span className="checklist-cta__head-icon" aria-hidden="true"></span>
				<RichText
					tagName="p"
					className="checklist-cta__title"
					value={ heading }
					onChange={ ( value ) => setAttributes({ heading: value }) }
					placeholder={ __( '見出し（例：不用品回収業者に相談した方がいいケース）', 'madoguchi-blocks' ) }
				/>
			</div>
			<RichText
				tagName="div"
				className="checklist-cta__lead"
				value={ lead }
				onChange={ ( value ) => setAttributes({ lead: value }) }
				placeholder={ __( '説明文（例：以下に2つ以上当てはまる方は…）', 'madoguchi-blocks' ) }
			/>
			<div className="checklist-cta__box">
				<RichText
					tagName="ul"
					className="checklist-cta__list"
					multiline="li"
					value={ items }
					onChange={ ( value ) => setAttributes({ items: value }) }
					placeholder={ __( 'チェック項目（Enterで項目を追加）', 'madoguchi-blocks' ) }
				/>
			</div>
			<p className="checklist-cta__note">
				<span className="checklist-cta__note-icon" aria-hidden="true"></span>
				<RichText
					tagName="span"
					className="checklist-cta__note-text"
					value={ note }
					onChange={ ( value ) => setAttributes({ note: value }) }
					placeholder={ __( '補足文（例：2つ以上当てはまる方は…）', 'madoguchi-blocks' ) }
				/>
			</p>
			<div className="checklist-cta__action">
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ TEMPLATE }
				/>
			</div>
		</div>
	);
}
