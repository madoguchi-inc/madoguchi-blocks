/**
 * 比較テーブルのチェック列で使う記号（◎／✓／△／✕）。
 * 図形は Figma「記号」コンポーネントの path をそのまま使い、色は CSS（currentColor）で与える。
 * ※ フロントはサーバー描画のため、blocks/comparison-row/render.php の $mark_svgs にも
 *   同じ図形を持つ。追加・変更したら両方を同期すること。
 */

import { __ } from '@wordpress/i18n';

// 記号キー → 選択肢ラベル（行エディタの SelectControl 用）
export const MARK_OPTIONS = [
	{ label: __( 'なし', 'madoguchi-blocks' ), value: 'none' },
	{ label: __( '◎（対応）', 'madoguchi-blocks' ), value: 'circle' },
	{ label: __( '✓（あり）', 'madoguchi-blocks' ), value: 'check' },
	{ label: __( '△（一部・条件付き）', 'madoguchi-blocks' ), value: 'triangle' },
	{ label: __( '✕（なし）', 'madoguchi-blocks' ), value: 'cross' }
];

// 記号キー → viewBox・線の属性・path
const MARKS = {
	circle: {
		viewBox: '0 0 16.7647 16.7647',
		strokeWidth: 1.76471,
		strokeLinecap: 'square',
		shape: (
			<>
				<path d="M15.8824 8.38235C15.8824 12.5245 12.5245 15.8824 8.38235 15.8824C4.24022 15.8824 0.882353 12.5245 0.882353 8.38235C0.882353 4.24022 4.24022 0.882353 8.38235 0.882353C12.5245 0.882353 15.8824 4.24022 15.8824 8.38235Z" />
				<path d="M11.8824 8.38235C11.8824 10.3153 10.3153 11.8824 8.38235 11.8824C6.44936 11.8824 4.88235 10.3153 4.88235 8.38235C4.88235 6.44936 6.44936 4.88235 8.38235 4.88235C10.3153 4.88235 11.8824 6.44936 11.8824 8.38235Z" />
			</>
		)
	},
	check: {
		viewBox: '0 0 17 15',
		strokeWidth: 2,
		strokeLinecap: 'round',
		strokeLinejoin: 'round',
		shape: <path d="M1.00004 8L6.0385 12L16 2" />
	},
	triangle: {
		viewBox: '0 0 18.7059 15.8824',
		strokeWidth: 1.76471,
		strokeLinejoin: 'round',
		shape: <path d="M17.8235 15H0.882353L9.35294 0.882353L17.8235 15Z" />
	},
	cross: {
		viewBox: '0 0 15.8824 15.8824',
		strokeWidth: 1.76471,
		strokeLinecap: 'round',
		shape: <path d="M0.882353 0.882353L15 15M15 0.882353L0.882353 15" />
	}
};

/**
 * 記号を描画するコンポーネント（該当しないキーは何も描画しない）。
 *
 * @param {Object} props
 * @param {string} props.mark   記号キー。
 * @param {number} [props.size] 表示サイズ px（既定 15）。
 * @return {JSX.Element|null} SVG 要素。
 */
export default function MarkIcon( { mark, size = 15 }) {
	const def = MARKS[ mark ];
	if ( ! def ) {
		return null;
	}
	return (
		<svg
			className={ 'comparison-table__mark-icon comparison-table__mark-icon--' + mark }
			viewBox={ def.viewBox }
			width={ size }
			height={ size }
			fill="none"
			stroke="currentColor"
			strokeWidth={ def.strokeWidth }
			strokeLinecap={ def.strokeLinecap }
			strokeLinejoin={ def.strokeLinejoin }
			aria-hidden="true"
			focusable="false"
		>
			{ def.shape }
		</svg>
	);
}
