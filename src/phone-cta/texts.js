/**
 * 電話CTA 2 ブロック（店舗別／固定フッター）のサービス別既定文言。
 * inc/phone-cta/class-view.php の Madoguchi_Blocks_Phone_Cta_View::default_texts() と
 * 必ず一致させる（PHP 側は表示側の既定値、こちらはエディタでのサービス切替時のプリセット）。
 * 文言の出典: Figma「みんなの買取」202607_電話送客プロジェクト。
 */

const POINTS = [ '強引な営業なし', '個人情報必要なし', '相談だけでもOK' ];

export const DEFAULT_TEXTS = {
	kaitori: {
		description: '複数の買取店で査定してもらうことが高く売るコツ！',
		heading: '今すぐ電話でかんたん無料査定',
		points: POINTS,
		freeTag: '査定無料',
		balloon: 'その場でかんたん無料査定！',
		footerCatchBadge: '完全無料査定',
		footerCatch: '複数社で比較して1番高く売ろう',
		genericLabel: '電話で査定額を聞く',
		webLabel: 'WEBでカンタン無料査定はこちら',
		webButtonSub: '24時間年中無休で受付中！',
		webButton: 'オンライン無料一括査定',
		webButtonSp: 'オンライン一括査定',
		shopLabel: '{shop}に電話で査定額を聞く',
	},
	other: {
		description: '複数の業者に見積もりを取ることが安く済ませるコツ！',
		heading: '今すぐ電話でかんたん無料見積もり',
		points: POINTS,
		freeTag: '見積無料',
		balloon: 'その場でかんたん無料見積もり！',
		footerCatchBadge: '完全無料',
		footerCatch: '複数社で比較して1番安く済ませよう',
		genericLabel: '電話で見積もりを聞く',
		webLabel: 'WEBでカンタン無料見積もりはこちら',
		webButtonSub: '24時間年中無休で受付中！',
		webButton: 'オンライン無料一括見積もり',
		webButtonSp: 'オンライン一括見積もり',
		shopLabel: '{shop}に電話で見積もりを聞く',
	},
};

/**
 * ボタン文言を「店名＋助詞」と「残り」に分ける（PHP 側 View::label_parts と同じ規則）。
 * 表示側では各片を inline-block にして「おたからやに｜電話で査定額を聞く」で折り返す。
 *
 * @param {string} template '{shop}' を含む文言テンプレート。
 * @param {string} name     店名。
 * @return {[string, string]} [店名側, 残り]。テンプレートに {shop} が無ければ [ '', 全文 ]。
 */
export function splitLabel( template, name ) {
	const pos = template.indexOf( '{shop}' );
	if ( pos < 0 ) {
		return [ '', template ];
	}
	const prefix = template.slice( 0, pos );
	const suffix = template.slice( pos + '{shop}'.length );
	const m = suffix.match( /^([にのへでと])([\s\S]*)$/ );
	if ( m ) {
		return [ prefix + name + m[ 1 ], m[ 2 ] ];
	}
	return [ prefix + name, suffix ];
}

/**
 * サービスキーから既定文言セットを引く。'kaitori' 以外はすべて 'other' と同じ文言。
 *
 * @param {string} service サービスキー（'kaitori' | 'fuyouhin' | 'osouji' 等）。
 * @return {Object} DEFAULT_TEXTS の該当エントリ。
 */
export function textsFor( service ) {
	return 'kaitori' === service ? DEFAULT_TEXTS.kaitori : DEFAULT_TEXTS.other;
}
