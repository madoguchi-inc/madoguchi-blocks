/**
 * REST 配信用 CSS（build/style-rest.css）を build/style.css から生成する。
 *
 * REST API（content.rendered）で配信したブロックは、配信先サイトのテーマCSSと
 * 同じドキュメント内で描画されるため、そのままでは p / a / ul などへの装飾や
 * root font-size の違いに影響される。このスクリプトは次の3つの変換で
 * 「配信先のCSSの影響を受けない」自己完結スタイルを作る:
 *
 *   1. rem → px 変換（×10）
 *      プラグインのCSSは自社テーマの html{font-size:62.5%}（1rem=10px）前提で
 *      書かれているため、配信先の root font-size に依存しないよう px に固定する。
 *
 *   2. 全宣言に !important を付与
 *      配信先テーマのどんなセレクタ（.entry-content p 等）にも負けないようにする。
 *      ただし以下はブロック単位のインライン style 属性（render.php が出力）を
 *      優先させる必要があるため除外する:
 *        - カスタムプロパティ（--md-* : アクセントカラー等の上書きに使う）
 *        - .cta-button 系のルール（背景・色・角丸・余白などをインラインで出す）
 *        - 星評価の width（%）・比較テーブルの grid-template-columns
 *
 *   3. 先頭にスコープ付きリセットと基礎スタイルを付与
 *      各ブロックのルート配下を all: revert でUA既定へ戻し（配信先テーマの
 *      装飾を遮断）、フォント・box-sizing をブロック側で自前定義する。
 *      ルートクラスを3連にして特異性(0,3,0)を確保しつつ、プラグイン側の宣言は
 *      !important なのでリセットより常に優先される。CTAボタン配下は従来どおり
 *      インライン style 主体のためリセット対象から除外する。
 *
 * 実行: node tools/build-rest-css.js（build.sh が CSS ビルド後に呼ぶ）
 */

const fs = require( 'fs' );
const path = require( 'path' );
const postcss = require( 'postcss' );

const SRC = path.resolve( __dirname, '../build/style.css' );
const DEST = path.resolve( __dirname, '../build/style-rest.css' );

// リセット対象のブロックルート（.cta-button-wrap はインライン style 主体のため含めない）
const ROOTS = [
	'.checklist-cta',
	'.condition-cards',
	'.condition-card',
	'.author-box',
	'.review-section',
	'.comparison-table',
	'.recommend-card'
];

// !important を付与しない（インライン style を優先させる）セレクタ×プロパティ
// sel はセレクタの部分一致。props が null ならそのセレクタの全宣言を除外する。
const INLINE_GUARDS = [
	{ sel: '.cta-button', props: null },
	{ sel: '__stars-fill', props: [ 'width' ] },
	{ sel: '.comparison-table__grid', props: [ 'grid-template-columns' ] }
];

// ルートクラスを3連結して特異性を上げる（class="checklist-cta" に .a.a.a はマッチする）
const triple = ( root ) => root.repeat( 3 );

function isGuarded( selector, prop ) {
	return INLINE_GUARDS.some( ( g ) => {
		if ( ! selector.includes( g.sel ) ) {
			return false;
		}
		return null === g.props || g.props.includes( prop );
	});
}

function remToPx( value ) {
	// 1rem = 10px（html{font-size:62.5%} 前提の設計値）として固定変換する
	return value.replace( /(\d*\.?\d+)rem\b/g, ( _, n ) => {
		const px = parseFloat( n ) * 10;
		return `${ Number.isInteger( px ) ? px : parseFloat( px.toFixed( 2 ) ) }px`;
	});
}

function buildPrelude() {
	const roots3 = ROOTS.map( triple );
	const rootsSel = roots3.join( ',' );
	// 除外1: CTAボタン（ラッパー含む）配下 … インライン style とデフォルトCSSの関係を保つ
	// 除外2: svg 配下 … all: revert は SVG のプレゼンテーション属性（stroke/fill）まで
	//        巻き戻してアイコンが消えるため対象外にする（下の svg ガードで防御する）
	const descendantsSel = roots3
		.map( ( r ) => `${ r } *:not(.cta-button-wrap):not(.cta-button-wrap *):not(svg):not(svg *)` )
		.join( ',' );
	const boxSel = roots3
		.map( ( r ) => `${ r },${ r } *,${ r }::before,${ r }::after,${ r } *::before,${ r } *::after` )
		.join( ',' );
	const svgSel = roots3.map( ( r ) => `${ r } svg` ).join( ',' );

	const fontStack = '-apple-system,BlinkMacSystemFont,"Segoe UI","Hiragino Kaku Gothic ProN","Hiragino Sans",Meiryo,sans-serif';

	// CTAボタンはリセット除外のため、インラインで出さないタイポグラフィ系だけ個別に防御する
	// （font-size / color / background 等は render.php のインライン style に任せる）
	const wrap3 = triple( '.cta-button-wrap' );

	return (
		// ① 配信先テーマの装飾を遮断（UA既定へ戻す。カスタムプロパティは維持される）
		`${ rootsSel },${ descendantsSel }{all:revert;}` +
		// ② 基礎タイポグラフィをブロック側で自前定義（配信先の body スタイルに依存しない）
		`${ rootsSel }{font-family:${ fontStack } !important;font-size:16px !important;font-weight:400 !important;line-height:1.625 !important;letter-spacing:normal !important;text-align:left !important;color:#000 !important;}` +
		// ③ box-sizing はテーマ標準の border-box に統一（プラグインCSSの設計前提）
		`${ boxSel }{box-sizing:border-box !important;}` +
		// ④ svg ガード（リセット除外の代わりに、配信先が画像に掛けがちな装飾だけ打ち消す）
		`${ svgSel }{filter:none !important;opacity:1 !important;visibility:visible !important;transform:none !important;}` +
		// ⑤ CTAボタンのタイポグラフィガード（a / span への配信先装飾を打ち消す）
		`${ wrap3 } .cta-button,${ wrap3 } .cta-button *{font-family:${ fontStack } !important;font-weight:bold !important;font-style:normal !important;letter-spacing:normal !important;text-transform:none !important;text-indent:0 !important;word-spacing:normal !important;}` +
		`${ wrap3 } .cta-button{line-height:1.4 !important;}`
	);
}

const css = fs.readFileSync( SRC, 'utf8' );
const root = postcss.parse( css );

let converted = 0;
let importantified = 0;

root.walkDecls( ( decl ) => {
	if ( /rem\b/.test( decl.value ) ) {
		decl.value = remToPx( decl.value );
		converted++;
	}
	// カスタムプロパティはインライン style（style="--md-brand:..."）を優先させる
	if ( decl.prop.startsWith( '--' ) ) {
		return;
	}
	const selector = decl.parent && decl.parent.selector ? decl.parent.selector : '';
	if ( isGuarded( selector, decl.prop ) ) {
		return;
	}
	if ( ! decl.important ) {
		decl.important = true;
	}
	importantified++;
});

const banner = '/*! madoguchi-blocks REST用（自動生成: tools/build-rest-css.js — 直接編集しない） */';
fs.writeFileSync( DEST, banner + buildPrelude() + root.toString() );

console.log( `style-rest.css generated (rem→px: ${ converted } decls, !important: ${ importantified } decls)` );
