/**
 * 著者情報 — エディタ表示
 * 名前・肩書き・プロフィールはカード内で直接編集する。CTAボタンは持たない。
 * 枠線の色・幅を設定できる。
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	PanelColorSettings,
	InnerBlocks
} from '@wordpress/block-editor';
import { PanelBody, RangeControl, Button } from '@wordpress/components';

// 著者ボックス内に置けるブロック
const ALLOWED_BLOCKS = [ 'madoguchi/cta-button' ];

export default function Edit( { attributes, setAttributes }) {
	const { name, role, bio, avatarUrl, accentColor, borderColor, borderWidth, borderRadius } = attributes;
	const blockProps = useBlockProps({
		className: 'author-box author-box--edit',
		style: {
			...( accentColor ? { '--md-brand': accentColor } : {} ),
			...( borderColor ? { '--md-border-color': borderColor } : {} ),
			'--md-border-width': ( borderWidth || 0 ) + 'px',
			'--md-radius': ( borderRadius || 0 ) + 'px'
		}
	});

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( '著者アイコン', 'madoguchi-blocks' ) }>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) => setAttributes({ avatarUrl: media.url }) }
							allowedTypes={ [ 'image' ] }
							render={ ( { open }) => (
								<Button variant="secondary" onClick={ open }>
									{ avatarUrl ? __( '画像を変更', 'madoguchi-blocks' ) : __( '画像を選択', 'madoguchi-blocks' ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
					{ avatarUrl && (
						<Button variant="link" isDestructive onClick={ () => setAttributes({ avatarUrl: '' }) }>
							{ __( '画像を削除', 'madoguchi-blocks' ) }
						</Button>
					) }
				</PanelBody>
				<PanelBody title={ __( '枠線', 'madoguchi-blocks' ) }>
					<RangeControl
						label={ __( '枠線の太さ（px）', 'madoguchi-blocks' ) }
						value={ borderWidth }
						onChange={ ( value ) => setAttributes({ borderWidth: value }) }
						min={ 0 }
						max={ 8 }
					/>
					<RangeControl
						label={ __( '角丸（px）', 'madoguchi-blocks' ) }
						value={ borderRadius }
						onChange={ ( value ) => setAttributes({ borderRadius: value }) }
						min={ 0 }
						max={ 40 }
					/>
				</PanelBody>
				<PanelColorSettings
					title={ __( 'カラー設定', 'madoguchi-blocks' ) }
					colorSettings={ [
						{
							value: accentColor,
							onChange: ( color ) => setAttributes({ accentColor: color || '' }),
							label: __( 'アクセントカラー', 'madoguchi-blocks' )
						},
						{
							value: borderColor,
							onChange: ( color ) => setAttributes({ borderColor: color || '' }),
							label: __( '枠線の色', 'madoguchi-blocks' )
						}
					] }
				/>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="author-box__avatar">
					{ avatarUrl ? (
						<img src={ avatarUrl } alt="" />
					) : (
						<svg className="author-box__avatar-svg" viewBox="0 0 64 64" width="64" height="64" aria-hidden="true">
							<circle cx="32" cy="32" r="32" fill="#e8eaed" />
							<circle cx="32" cy="26" r="11" fill="#b6bcc4" />
							<path d="M14 55c0-10 8-16 18-16s18 6 18 16z" fill="#b6bcc4" />
						</svg>
					) }
				</div>
				<div className="author-box__body">
					<RichText
						tagName="p"
						className="author-box__role"
						value={ role }
						onChange={ ( value ) => setAttributes({ role: value }) }
						placeholder={ __( '肩書き（例：不用品回収アドバイザー）', 'madoguchi-blocks' ) }
						allowedFormats={ [] }
					/>
					<RichText
						tagName="p"
						className="author-box__name"
						value={ name }
						onChange={ ( value ) => setAttributes({ name: value }) }
						placeholder={ __( '著者名（例：山田 太郎）', 'madoguchi-blocks' ) }
						allowedFormats={ [] }
					/>
					<RichText
						tagName="p"
						className="author-box__bio"
						value={ bio }
						onChange={ ( value ) => setAttributes({ bio: value }) }
						placeholder={ __( 'プロフィール文（例：年間1,000件以上の回収に携わる…）', 'madoguchi-blocks' ) }
					/>
					<div className="author-box__inner">
						<InnerBlocks
							allowedBlocks={ ALLOWED_BLOCKS }
							renderAppender={ InnerBlocks.ButtonBlockAppender }
						/>
					</div>
				</div>
			</div>
		</>
	);
}
