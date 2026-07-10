/**
 * 口コミのデフォルトアバター。
 * 性別（male/female）と番号（index）で色・髪型が変わる SVG を返す。
 * render.php 側（inc/review-avatars.php）と配色・形状を一致させること。
 */

// 番号ごとの服・髪の配色（PHP側と同じ並び）
export const AVATAR_CLOTHES = [ '#4a90d9', '#e0733f', '#4faf6d', '#8e6fc4', '#d9a441', '#d76b98' ];
export const AVATAR_HAIR    = [ '#3b2f2a', '#5a4632', '#222222', '#6b4f34', '#7a5a3a', '#1f2937' ];
export const AVATAR_COUNT   = 6;

const SKIN = '#f5cfa8';
const BG   = '#eef1f4';

/**
 * デフォルトアバターを描画する。
 *
 * @param {Object} props
 * @param {string} props.gender 'male' | 'female'
 * @param {number} props.index  0 起点の番号
 * @return {JSX.Element}
 */
export default function ReviewAvatar( { gender = 'male', index = 0 }) {
	const clothes = AVATAR_CLOTHES[ index % AVATAR_COUNT ];
	const hair = AVATAR_HAIR[ index % AVATAR_COUNT ];

	return (
		<svg className="review-card__avatar-svg" viewBox="0 0 64 64" width="60" height="60" aria-hidden="true" focusable="false">
			<circle cx="32" cy="32" r="32" fill={ BG } />
			<path d="M13 63c0-11 8.5-17 19-17s19 6 19 17z" fill={ clothes } />
			{ gender === 'female' ? (
				<>
					<path d="M18 44V27c0-9 6-14.5 14-14.5S46 18 46 27v17l-5-3V27c0-6-3.8-9.5-9-9.5S23 21 23 27v14z" fill={ hair } />
					<circle cx="32" cy="28.5" r="12" fill={ SKIN } />
					<path d="M20 28c0-7.5 5-12 12-12s12 4.5 12 12c-2-4.5-6-6.5-12-6.5S22 23.5 20 28z" fill={ hair } />
				</>
			) : (
				<>
					<circle cx="32" cy="28" r="12.5" fill={ SKIN } />
					<path d="M19.5 28c0-8 5-13.5 12.5-13.5S44.5 20 44.5 28c-2.2-5-6-7.5-12.5-7.5S21.7 23 19.5 28z" fill={ hair } />
				</>
			) }
		</svg>
	);
}
