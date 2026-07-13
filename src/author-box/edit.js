/**
 * 著者情報 — エディタ表示
 * 名前・肩書き・プロフィールはカード内で直接編集する。CTAボタンは持たない。
 * 枠線の色・幅を設定できる。
 *
 * 著者テンプレート（設定 > Madoguchi Blocks で登録）を選ぶと、
 * その内容が全記事に反映される「参照方式」で表示される。
 * テンプレート選択中は手入力欄を隠し、テンプレートの内容をプレビュー表示する。
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
import { PanelBody, RangeControl, SelectControl, Button, Notice } from '@wordpress/components';

// 著者ボックス内に置けるブロック
const ALLOWED_BLOCKS = [ 'madoguchi/cta-button' ];

// 設定画面から供給される著者テンプレート一覧
const AUTHOR_TEMPLATES = ( window.madoguchiBlocksData && window.madoguchiBlocksData.authorTemplates ) || [];

// プレーンなプロフィール文（<br> 区切り）を React 要素へ変換する
function bioToLines( bio ) {
	return String( bio || '' )
		.split( /<br\s*\/?>/i )
		.map( ( line, i, arr ) => (
			<span key={ i }>{ line }{ i < arr.length - 1 ? <br /> : null }</span>
		) );
}

export default function Edit( { attributes, setAttributes }) {
	const { templateId, name, role, bio, avatarUrl, accentColor, borderColor, borderWidth, borderRadius } = attributes;
	const blockProps = useBlockProps({
		className: 'author-box author-box--edit',
		style: {
			...( accentColor ? { '--md-brand': accentColor } : {} ),
			...( borderColor ? { '--md-border-color': borderColor } : {} ),
			'--md-border-width': ( borderWidth || 0 ) + 'px',
			'--md-radius': ( borderRadius || 0 ) + 'px'
		}
	});

	// 選択中テンプレート（見つからなければ null）
	const activeTemplate = templateId
		? AUTHOR_TEMPLATES.find( ( t ) => t.id === templateId ) || null
		: null;
	const templateMissing = templateId && ! activeTemplate;

	// プレビューに使う値（テンプレート優先）
	const showName = activeTemplate ? activeTemplate.name : name;
	const showRole = activeTemplate ? activeTemplate.role : role;
	const showBio = activeTemplate ? activeTemplate.bio : bio;
	const showAvatar = activeTemplate ? activeTemplate.avatarUrl : avatarUrl;

	const templateOptions = [
		{ label: __( 'テンプレートを使わない（手動入力）', 'madoguchi-blocks' ), value: '' },
		...AUTHOR_TEMPLATES.map( ( t ) => ( {
			label: t.name || __( '(名称未設定)', 'madoguchi-blocks' ),
			value: t.id
		} ) )
	];
	// 削除済みIDを保持している場合も選択肢に残して状態が分かるようにする
	if ( templateMissing ) {
		templateOptions.push({ label: __( '（削除されたテンプレート）', 'madoguchi-blocks' ), value: templateId });
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( '著者テンプレート', 'madoguchi-blocks' ) }>
					<SelectControl
						label={ __( 'テンプレート', 'madoguchi-blocks' ) }
						value={ templateId || '' }
						options={ templateOptions }
						onChange={ ( value ) => setAttributes({ templateId: value }) }
						help={ AUTHOR_TEMPLATES.length
							? __( 'テンプレートは「設定 > Madoguchi Blocks」で登録・編集します。選択中は下の手入力より優先されます。', 'madoguchi-blocks' )
							: __( 'テンプレートは未登録です。「設定 > Madoguchi Blocks > 著者テンプレート」で追加できます。', 'madoguchi-blocks' ) }
					/>
					{ templateMissing && (
						<Notice status="warning" isDismissible={ false }>
							{ __( '選択したテンプレートが見つかりません。この記事の手入力値で表示されます。', 'madoguchi-blocks' ) }
						</Notice>
					) }
				</PanelBody>
				<PanelBody title={ __( '著者アイコン', 'madoguchi-blocks' ) } initialOpen={ ! activeTemplate }>
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
					{ showAvatar ? (
						<img src={ showAvatar } alt="" />
					) : (
						<svg className="author-box__avatar-svg" viewBox="0 0 64 64" width="64" height="64" aria-hidden="true">
							<circle cx="32" cy="32" r="32" fill="#e8eaed" />
							<circle cx="32" cy="26" r="11" fill="#b6bcc4" />
							<path d="M14 55c0-10 8-16 18-16s18 6 18 16z" fill="#b6bcc4" />
						</svg>
					) }
				</div>
				<div className="author-box__body">
					{ activeTemplate ? (
						// テンプレート選択中はプレビュー表示（内容の編集は設定画面で行う）
						<>
							{ showRole && <p className="author-box__role">{ showRole }</p> }
							{ showName && <p className="author-box__name">{ showName }</p> }
							{ showBio && <p className="author-box__bio">{ bioToLines( showBio ) }</p> }
						</>
					) : (
						<>
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
						</>
					) }
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
