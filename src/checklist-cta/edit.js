/**
 * チェックリスト型CTA — エディタ表示
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls
} from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes }) {
	const { heading, lead, items, note, buttonLabel, buttonUrl } = attributes;
	const blockProps = useBlockProps({ className: 'checklist-cta' });

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'CTAボタン設定', 'madoguchi-blocks' ) }>
					<TextControl
						label={ __( 'リンク先URL', 'madoguchi-blocks' ) }
						value={ buttonUrl }
						onChange={ ( value ) => setAttributes({ buttonUrl: value }) }
						help={ __( '例: #cv_form ／ /form/ など', 'madoguchi-blocks' ) }
					/>
				</PanelBody>
			</InspectorControls>
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
				<p className="checklist-cta__action">
					<span className="checklist-cta__button">
						<span className="checklist-cta__button-icon" aria-hidden="true"></span>
						<RichText
							tagName="span"
							className="checklist-cta__button-label"
							value={ buttonLabel }
							onChange={ ( value ) => setAttributes({ buttonLabel: value }) }
							placeholder={ __( 'ボタン文言', 'madoguchi-blocks' ) }
							allowedFormats={ [] }
						/>
						<span className="checklist-cta__button-arrow" aria-hidden="true"></span>
					</span>
				</p>
			</div>
		</>
	);
}
