/**
 * 条件カード（子） — エディタ表示
 * ①アイコンのみ ②イラスト入り の2バリエーションに対応。
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
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
import CardIcon, { ICON_OPTIONS } from './icons';

export default function Edit( { attributes, setAttributes }) {
	const { title, description, linkLabel, linkUrl, variation, iconKey, imageUrl, accentColor } = attributes;
	const blockProps = useBlockProps({
		className: `condition-card condition-card--edit condition-card--${ variation }`,
		style: accentColor ? { '--md-brand': accentColor } : undefined
	});

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( '表示スタイル', 'madoguchi-blocks' ) }>
					<SelectControl
						label={ __( 'バリエーション', 'madoguchi-blocks' ) }
						value={ variation }
						options={ [
							{ label: __( 'アイコンのみ', 'madoguchi-blocks' ), value: 'icon' },
							{ label: __( 'イラスト入り', 'madoguchi-blocks' ), value: 'illustration' }
						] }
						onChange={ ( value ) => setAttributes({ variation: value }) }
					/>
					{ variation === 'icon' && (
						<SelectControl
							label={ __( 'アイコン', 'madoguchi-blocks' ) }
							value={ iconKey }
							options={ ICON_OPTIONS }
							onChange={ ( value ) => setAttributes({ iconKey: value }) }
						/>
					) }
					{ variation === 'illustration' && (
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ ( media ) => setAttributes({ imageUrl: media.url }) }
								allowedTypes={ [ 'image' ] }
								render={ ( { open }) => (
									<Button variant="secondary" onClick={ open }>
										{ imageUrl ? __( 'イラストを変更', 'madoguchi-blocks' ) : __( 'イラストを選択', 'madoguchi-blocks' ) }
									</Button>
								) }
							/>
							{ imageUrl && (
								<Button variant="link" isDestructive onClick={ () => setAttributes({ imageUrl: '' }) }>
									{ __( 'イラストを削除', 'madoguchi-blocks' ) }
								</Button>
							) }
						</MediaUploadCheck>
					) }
				</PanelBody>
				<PanelBody title={ __( 'リンク設定', 'madoguchi-blocks' ) }>
					<TextControl
						label={ __( 'リンク先URL', 'madoguchi-blocks' ) }
						value={ linkUrl }
						onChange={ ( value ) => setAttributes({ linkUrl: value }) }
						help={ __( '例: /column/minato-gomiyashiki/ など', 'madoguchi-blocks' ) }
					/>
				</PanelBody>
				<PanelColorSettings
					title={ __( 'カラー設定', 'madoguchi-blocks' ) }
					colorSettings={ [ {
						value: accentColor,
						onChange: ( color ) => setAttributes({ accentColor: color || '' }),
						label: __( 'アクセントカラー', 'madoguchi-blocks' )
					} ] }
				/>
			</InspectorControls>
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
					<RichText
						tagName="p"
						className="condition-card__title"
						value={ title }
						onChange={ ( value ) => setAttributes({ title: value }) }
						placeholder={ __( 'タイトル（例：ゴミ屋敷清掃に対応している業者を探す）', 'madoguchi-blocks' ) }
					/>
					<RichText
						tagName="p"
						className="condition-card__desc"
						value={ description }
						onChange={ ( value ) => setAttributes({ description: value }) }
						placeholder={ __( '説明文（例：大量の不用品や部屋全体の片付けを依頼したい方へ）', 'madoguchi-blocks' ) }
					/>
					<span className="condition-card__link">
						<RichText
							tagName="span"
							className="condition-card__link-label"
							value={ linkLabel }
							onChange={ ( value ) => setAttributes({ linkLabel: value }) }
							placeholder={ __( 'リンク文言（例：港区のおすすめゴミ屋敷清掃業者8選を見る）', 'madoguchi-blocks' ) }
							allowedFormats={ [] }
						/>
						<span className="condition-card__link-arrow" aria-hidden="true"></span>
					</span>
				</div>
			</div>
		</>
	);
}
