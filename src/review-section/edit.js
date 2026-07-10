/**
 * 口コミ・レビュー — エディタ表示
 *
 * - 投稿者名・本文はエディタ内（各カード）で直接編集する。
 * - 性別・年代・アイコン番号・評価はサイドバーで設定する。
 * - デフォルトアイコンは性別＋番号で切り替わる。
 * - 吹き出しの角丸はブロック単位で設定できる。
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	PanelColorSettings
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	RangeControl,
	Button
} from '@wordpress/components';
import ReviewAvatar, { AVATAR_COUNT } from './avatars';

// 年代の選択肢
const AGE_OPTIONS = [
	{ label: __( '未設定', 'madoguchi-blocks' ), value: '' },
	{ label: '10代', value: '10代' },
	{ label: '20代', value: '20代' },
	{ label: '30代', value: '30代' },
	{ label: '40代', value: '40代' },
	{ label: '50代', value: '50代' },
	{ label: '60代', value: '60代' },
	{ label: '70代以上', value: '70代以上' }
];

const GENDER_OPTIONS = [
	{ label: __( '男性', 'madoguchi-blocks' ), value: 'male' },
	{ label: __( '女性', 'madoguchi-blocks' ), value: 'female' }
];

// 属性表示（年代・性別）を組み立てる
function attributeText( review ) {
	const gender = review.gender === 'female' ? '女性' : ( review.gender === 'male' ? '男性' : '' );
	return [ review.ageGroup, gender ].filter( Boolean ).join( '・' );
}

export default function Edit( { attributes, setAttributes }) {
	const { heading, itemName, reviews, accentColor, bubbleRadius, bodyFontSize } = attributes;
	const blockProps = useBlockProps({
		className: 'review-section review-section--edit',
		style: {
			...( accentColor ? { '--md-brand': accentColor } : {} ),
			'--md-bubble-radius': ( bubbleRadius || 0 ) + 'px',
			...( bodyFontSize ? { '--md-review-body': bodyFontSize + 'px' } : {} )
		}
	});

	const updateReview = ( index, key, value ) => {
		const next = reviews.map( ( r, i ) => ( i === index ? { ...r, [ key ]: value } : r ) );
		setAttributes({ reviews: next });
	};

	const addReview = () => {
		setAttributes({
			reviews: [ ...reviews, { author: '', gender: 'male', ageGroup: '', avatarIndex: 0, rating: 5, body: '' } ]
		});
	};

	const removeReview = ( index ) => {
		setAttributes({ reviews: reviews.filter( ( _, i ) => i !== index ) });
	};

	// アイコン番号の選択肢（1個目〜N個目）
	const avatarOptions = Array.from({ length: AVATAR_COUNT }, ( _, i ) => (
		{ label: ( i + 1 ) + __( '個目', 'madoguchi-blocks' ), value: String( i ) }
	) );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'レビュー対象', 'madoguchi-blocks' ) }>
					<TextControl
						label={ __( 'レビュー対象名（構造化データ用）', 'madoguchi-blocks' ) }
						value={ itemName }
						onChange={ ( value ) => setAttributes({ itemName: value }) }
						help={ __( '未入力の場合は記事タイトルを使用します。', 'madoguchi-blocks' ) }
					/>
				</PanelBody>
				<PanelBody title={ __( '表示設定', 'madoguchi-blocks' ) }>
					<RangeControl
						label={ __( '吹き出しの角丸（px）', 'madoguchi-blocks' ) }
						value={ bubbleRadius }
						onChange={ ( value ) => setAttributes({ bubbleRadius: value }) }
						min={ 0 }
						max={ 40 }
					/>
					<RangeControl
						label={ __( '本文の文字サイズ（px）', 'madoguchi-blocks' ) }
						value={ bodyFontSize }
						onChange={ ( value ) => setAttributes({ bodyFontSize: value }) }
						min={ 10 }
						max={ 28 }
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
				<PanelBody title={ __( '口コミ（属性・評価）', 'madoguchi-blocks' ) }>
					<p className="review-section__panel-help">
						{ __( '投稿者名と本文はエディタ内の各カードで直接編集できます。', 'madoguchi-blocks' ) }
					</p>
					{ reviews.map( ( review, index ) => (
						<div
							key={ index }
							style={ { paddingBottom: '12px', marginBottom: '12px', borderBottom: '1px solid #e0e0e0' } }
						>
							<strong>{ __( '口コミ', 'madoguchi-blocks' ) } { index + 1 }</strong>
							<SelectControl
								label={ __( '性別', 'madoguchi-blocks' ) }
								value={ review.gender || 'male' }
								options={ GENDER_OPTIONS }
								onChange={ ( value ) => updateReview( index, 'gender', value ) }
							/>
							<SelectControl
								label={ __( '年代', 'madoguchi-blocks' ) }
								value={ review.ageGroup || '' }
								options={ AGE_OPTIONS }
								onChange={ ( value ) => updateReview( index, 'ageGroup', value ) }
							/>
							<SelectControl
								label={ __( 'アイコン', 'madoguchi-blocks' ) }
								value={ String( review.avatarIndex || 0 ) }
								options={ avatarOptions }
								onChange={ ( value ) => updateReview( index, 'avatarIndex', parseInt( value, 10 ) ) }
							/>
							<RangeControl
								label={ __( '評価', 'madoguchi-blocks' ) }
								value={ review.rating || 0 }
								onChange={ ( value ) => updateReview( index, 'rating', value ) }
								min={ 0 }
								max={ 5 }
								step={ 0.5 }
							/>
							<Button variant="link" isDestructive onClick={ () => removeReview( index ) }>
								{ __( 'この口コミを削除', 'madoguchi-blocks' ) }
							</Button>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addReview }>
						{ __( '口コミを追加', 'madoguchi-blocks' ) }
					</Button>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<RichText
					tagName="h3"
					className="review-section__heading"
					value={ heading }
					onChange={ ( value ) => setAttributes({ heading: value }) }
					placeholder={ __( '見出し（例：利用者の口コミ）', 'madoguchi-blocks' ) }
					allowedFormats={ [] }
				/>
				{ reviews.length === 0 ? (
					<p className="review-section__empty">
						{ __( '右のサイドバー「口コミ」から口コミを追加してください。', 'madoguchi-blocks' ) }
					</p>
				) : (
					<div className="review-section__list">
						{ reviews.map( ( review, index ) => {
							const width = Math.max( 0, Math.min( 100, ( ( review.rating || 0 ) / 5 ) * 100 ) );
							const attr = attributeText( review );
							return (
								<article className="review-card" key={ index }>
									<div className="review-card__avatar">
										<ReviewAvatar gender={ review.gender || 'male' } index={ review.avatarIndex || 0 } />
									</div>
									<div className="review-card__body">
										<div className="review-card__head">
											<RichText
												tagName="span"
												className="review-card__author"
												value={ review.author || '' }
												onChange={ ( value ) => updateReview( index, 'author', value ) }
												placeholder={ __( '投稿者名', 'madoguchi-blocks' ) }
												allowedFormats={ [] }
											/>
											{ attr && (
												<span className="review-card__attribute">{ attr }</span>
											) }
										</div>
										{ review.rating > 0 && (
											<div className="review-card__rating">
												<span className="review-card__stars">
													<span className="review-card__stars-fill" style={ { width: width + '%' } }></span>
												</span>
											</div>
										) }
										<RichText
											tagName="p"
											className="review-card__text"
											value={ review.body || '' }
											onChange={ ( value ) => updateReview( index, 'body', value ) }
											placeholder={ __( '口コミ本文', 'madoguchi-blocks' ) }
											allowedFormats={ [] }
										/>
									</div>
								</article>
							);
						}) }
					</div>
				) }
			</div>
		</>
	);
}
