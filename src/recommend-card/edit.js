/**
 * おすすめリンクカード — エディタ表示
 *
 * ①ボタン強調（button）… CTAボタンブロックを内包。カード全体クリックはフロントでボタンへ委譲。
 * ②チェックリスト風（checklist）… カード全体がリンク。右端に丸囲みシェブロンを表示。
 * ③シンプル・ミニマル（simple）… 白カード＋細枠＋素のアイコン。カード全体がリンク。
 * ④情報ボックス（infobox）… 左にブランドカラーの帯（アイコン＋ラベル）。カード全体がリンク。
 * 本体は render.php が描画する動的ブロック。
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InnerBlocks,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	PanelColorSettings
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	Button
} from '@wordpress/components';
import CardIcon, { ICON_OPTIONS } from '../condition-card/icons';

// ボタン強調パターンに置けるブロック（CTAボタンのみ）
const ALLOWED_BLOCKS = [ 'madoguchi/cta-button' ];
const TEMPLATE = [ [ 'madoguchi/cta-button', { fontSize: 16 } ] ];

const PATTERNS = [ 'button', 'checklist', 'simple', 'infobox' ];

export default function Edit( { attributes, setAttributes }) {
	const { pattern, label, title, description, iconKey, imageUrl, linkUrl, accentColor, backgroundColor } = attributes;
	const current = PATTERNS.includes( pattern ) ? pattern : 'button';
	const isLink = current !== 'button'; // button 以外はカード全体がリンク
	const isInfobox = current === 'infobox';
	const blockProps = useBlockProps({
		className: `recommend-card recommend-card--edit recommend-card--${ current }`,
		style: {
			...( accentColor ? { '--md-brand': accentColor } : {} ),
			...( backgroundColor ? { background: backgroundColor } : {} )
		}
	});

	// ラベル（情報ボックスは左帯の中、それ以外はピル型で本文の上）
	const labelField = (
		<RichText
			tagName="p"
			className="recommend-card__label"
			value={ label }
			onChange={ ( value ) => setAttributes({ label: value }) }
			placeholder={ isInfobox
				? __( 'ラベル（例：関連記事）', 'madoguchi-blocks' )
				: __( 'ラベル（例：不用品回収を検討中の方へ）※空欄で非表示', 'madoguchi-blocks' ) }
			allowedFormats={ [] }
		/>
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( '表示スタイル', 'madoguchi-blocks' ) }>
					<SelectControl
						label={ __( 'パターン', 'madoguchi-blocks' ) }
						value={ current }
						options={ [
							{ label: __( 'ボタン強調（CTAボタンを内包）', 'madoguchi-blocks' ), value: 'button' },
							{ label: __( 'チェックリスト風（カード全体がリンク）', 'madoguchi-blocks' ), value: 'checklist' },
							{ label: __( 'シンプル・ミニマル（カード全体がリンク）', 'madoguchi-blocks' ), value: 'simple' },
							{ label: __( '情報ボックス（カード全体がリンク）', 'madoguchi-blocks' ), value: 'infobox' }
						] }
						onChange={ ( value ) => setAttributes({ pattern: value }) }
						help={ isLink
							? __( 'リンク先は下の「リンク設定」で指定します。', 'madoguchi-blocks' )
							: __( 'リンク先・ボタン文言はカード内のCTAボタン側で設定します。', 'madoguchi-blocks' ) }
					/>
					<SelectControl
						label={ __( 'アイコン', 'madoguchi-blocks' ) }
						value={ iconKey }
						options={ ICON_OPTIONS }
						onChange={ ( value ) => setAttributes({ iconKey: value }) }
					/>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) => setAttributes({ imageUrl: media.url }) }
							allowedTypes={ [ 'image' ] }
							render={ ( { open }) => (
								<Button variant="secondary" onClick={ open }>
									{ imageUrl ? __( 'イラストを変更', 'madoguchi-blocks' ) : __( 'アイコンの代わりにイラストを使う', 'madoguchi-blocks' ) }
								</Button>
							) }
						/>
						{ imageUrl && (
							<Button variant="link" isDestructive onClick={ () => setAttributes({ imageUrl: '' }) }>
								{ __( 'イラストを削除', 'madoguchi-blocks' ) }
							</Button>
						) }
					</MediaUploadCheck>
				</PanelBody>
				{ isLink && (
					<PanelBody title={ __( 'リンク設定', 'madoguchi-blocks' ) }>
						<TextControl
							label={ __( 'リンク先URL', 'madoguchi-blocks' ) }
							value={ linkUrl }
							onChange={ ( value ) => setAttributes({ linkUrl: value }) }
							help={ __( '例: /column/edogawa-fuyouhin/ など（カード全体がリンクになります）', 'madoguchi-blocks' ) }
						/>
					</PanelBody>
				) }
				<PanelColorSettings
					title={ __( 'カラー設定', 'madoguchi-blocks' ) }
					colorSettings={ [
						{
							value: accentColor,
							onChange: ( color ) => setAttributes({ accentColor: color || '' }),
							label: __( 'アクセントカラー', 'madoguchi-blocks' )
						},
						{
							value: backgroundColor,
							onChange: ( color ) => setAttributes({ backgroundColor: color || '' }),
							label: __( 'カード背景色（未設定はパターン既定）', 'madoguchi-blocks' )
						}
					] }
				/>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="recommend-card__media">
					{ imageUrl ? (
						<img className="recommend-card__image" src={ imageUrl } alt="" />
					) : (
						<span className="recommend-card__icon">
							<CardIcon iconKey={ iconKey } className="recommend-card__icon-svg" size={ 40 } />
						</span>
					) }
					{ isInfobox && labelField }
				</div>
				<div className="recommend-card__content">
					{ ! isInfobox && labelField }
					<RichText
						tagName="p"
						className="recommend-card__title"
						value={ title }
						onChange={ ( value ) => setAttributes({ title: value }) }
						placeholder={ __( 'タイトル（例：江戸川区で不用品回収業者を探している方へ）', 'madoguchi-blocks' ) }
					/>
					<RichText
						tagName="p"
						className="recommend-card__desc"
						value={ description }
						onChange={ ( value ) => setAttributes({ description: value }) }
						placeholder={ __( '説明文（例：対応できる業者を比較・紹介しています。）', 'madoguchi-blocks' ) }
					/>
					{ ! isLink && (
						<div className="recommend-card__action">
							<InnerBlocks
								allowedBlocks={ ALLOWED_BLOCKS }
								template={ TEMPLATE }
							/>
						</div>
					) }
				</div>
				{ isLink && (
					<span className="recommend-card__chevron" aria-hidden="true"></span>
				) }
			</div>
		</>
	);
}
