/**
 * 電話CTA 2 ブロック（店舗別／固定フッター）のサービス別既定文言。
 * inc/phone-cta/class-view.php の Madoguchi_Blocks_Phone_Cta_View::default_texts() と
 * 必ず一致させる（PHP 側は表示側の既定値、こちらはエディタでのサービス切替時のプリセット）。
 */

export const DEFAULT_TEXTS = {
	kaitori: {
		heading: '電話で査定額を聞ける提携買取店',
		description: '複数の買取店で査定してもらうことが高く売るコツ！',
		footerCatch: '完全無料査定 複数社で比較して1番高く売ろう',
		shopLabel: '{shop}に電話で査定額を聞く',
		webButton: '24時間年中無休で受付中！ オンライン無料一括査定',
		genericLabel: '電話で査定額を聞く',
		balloon: 'その場でかんたん無料査定！',
	},
	other: {
		heading: '電話で見積もりを聞ける提携業者',
		description: '複数の業者に見積もりを取ることが安く済ませるコツ！',
		footerCatch: '完全無料 複数社で比較して1番安く済ませよう',
		shopLabel: '{shop}に電話で見積もりを聞く',
		webButton: '24時間年中無休で受付中！ オンライン無料一括見積もり',
		genericLabel: '電話で見積もりを聞く',
		balloon: 'その場でかんたん無料見積もり！',
	},
};

/**
 * サービスキーから既定文言セットを引く。'kaitori' 以外はすべて 'other' と同じ文言。
 *
 * @param {string} service サービスキー（'kaitori' | 'fuyouhin' | 'osouji' 等）。
 * @return {Object} DEFAULT_TEXTS の該当エントリ。
 */
export function textsFor( service ) {
	return 'kaitori' === service ? DEFAULT_TEXTS.kaitori : DEFAULT_TEXTS.other;
}
