/**
 * 条件カード・おすすめリンクカードで使う内蔵アイコン。
 * カテゴリが増えても iconKey の差し替えだけで展開できるよう、
 * edit.js（エディタ）と save.js（保存マークアップ）の両方から共通利用する。
 * 全て stroke ベース（fill なし）で、色は currentColor に追従する。
 * ※ おすすめリンクカードはサーバー描画のため、blocks/recommend-card/render.php にも
 *   同じ図形を持つ。アイコンを追加・変更したら両方を同期すること。
 */

// キー → 表示ラベル（サイドバーの選択肢用）
export const ICON_OPTIONS = [
	{ label: '不用品回収（トラック）', value: 'truck' },
	{ label: '不用品・段ボール', value: 'box' },
	{ label: 'ハウスクリーニング（掃除）', value: 'cleaning' },
	{ label: 'エアコンクリーニング', value: 'aircon' },
	{ label: '家具・粗大ごみ', value: 'furniture' },
	{ label: '遺品整理', value: 'memorial' },
	{ label: '買取', value: 'buy' },
	{ label: '検索（虫めがね）', value: 'search' },
	{ label: 'チェックリスト', value: 'checklist' },
	{ label: 'インフォメーション', value: 'info' },
	{ label: '電話', value: 'phone' }
];

// キー → SVG の path/shape 要素
const PATHS = {
	truck: (
		<>
			<path d="M3 6h11v9H3z" />
			<path d="M14 9h4l3 3v3h-7z" />
			<circle cx="7" cy="17.5" r="1.6" />
			<circle cx="17.5" cy="17.5" r="1.6" />
		</>
	),
	box: (
		<>
			<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z" />
			<path d="M4 7.5l8 4.5 8-4.5" />
			<path d="M12 12v9" />
		</>
	),
	cleaning: (
		<>
			<path d="M14 3l4 4" />
			<path d="M16.5 5.5l-8 8" />
			<path d="M8.5 13.5l-4 7.5 8-3.5z" />
		</>
	),
	aircon: (
		<>
			<rect x="3" y="5" width="18" height="7" rx="1.5" />
			<path d="M6 9h9" />
			<path d="M7.5 15c0 1.6 1 2.6 2.6 2.6" />
			<path d="M12.5 15c0 2.1 1.3 3.1 3.1 3.1" />
		</>
	),
	furniture: (
		<>
			<path d="M4 11V9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2" />
			<path d="M3 11a2 2 0 0 1 2 2v3h14v-3a2 2 0 0 1 2-2" />
			<path d="M6 19v-3M18 19v-3" />
		</>
	),
	memorial: (
		<path d="M12 20s-7-4.5-7-9.5A3.5 3.5 0 0 1 12 7a3.5 3.5 0 0 1 7 3.5C19 15.5 12 20 12 20z" />
	),
	buy: (
		<>
			<path d="M4 4h7l9 9-7 7-9-9z" />
			<circle cx="8" cy="8" r="1.4" />
		</>
	),
	search: (
		<>
			<circle cx="11" cy="11" r="6.5" />
			<path d="M15.8 15.8L20.5 20.5" />
		</>
	),
	checklist: (
		<>
			<rect x="5" y="5" width="14" height="16" rx="2" />
			<path d="M9 5V4a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 4v1" />
			<path d="M8.5 11.5l2 2 4.5-4.5" />
			<path d="M8.5 17h7" />
		</>
	),
	info: (
		<>
			<circle cx="12" cy="12" r="9" />
			<path d="M12 11v5" />
			<path d="M12 7.5v.01" />
		</>
	),
	// 電話（塗り）: Figma phone-filled（16px 基準）を 24px 基準に拡大。
	// 他と異なり fill ベースのため、path 側で fill/stroke を上書きする（色は currentColor 追従）。
	phone: (
		<g transform="scale(1.5)" fill="currentColor" stroke="none">
			<path d="M10.3708 9.69851L10.0671 10.0181C10.0671 10.0181 9.34539 10.778 7.37539 8.70392C5.40541 6.6299 6.12713 5.87006 6.12713 5.87006L6.31833 5.66875C6.78939 5.17283 6.83379 4.37665 6.42279 3.7954L5.58217 2.60641C5.07352 1.887 4.09064 1.79197 3.50763 2.40576L2.46123 3.50743C2.17215 3.81178 1.97843 4.2063 2.00193 4.64397C2.06203 5.76365 2.54047 8.17274 5.21024 10.9835C8.04139 13.9642 10.6979 14.0826 11.7842 13.9754C12.1278 13.9415 12.4266 13.7562 12.6674 13.5027L13.6145 12.5057C14.2537 11.8326 14.0735 10.6788 13.2555 10.208L11.9819 9.47489C11.4448 9.16578 10.7905 9.25656 10.3708 9.69851Z" />
		</g>
	)
};

/**
 * カードのアイコンを描画するコンポーネント。
 *
 * @param {Object} props
 * @param {string} props.iconKey     アイコンキー。
 * @param {string} [props.className] SVG のクラス名（既定は条件カード用）。
 * @param {number} [props.size]      表示サイズ px（既定は条件カード用の28）。
 * @return {JSX.Element} SVG 要素。
 */
export default function CardIcon( { iconKey, className = 'condition-card__icon-svg', size = 28 }) {
	const shape = PATHS[ iconKey ] || PATHS.box;

	return (
		<svg
			className={ className }
			viewBox="0 0 24 24"
			width={ size }
			height={ size }
			fill="none"
			stroke="currentColor"
			strokeWidth="1.7"
			strokeLinecap="round"
			strokeLinejoin="round"
			aria-hidden="true"
			focusable="false"
		>
			{ shape }
		</svg>
	);
}
