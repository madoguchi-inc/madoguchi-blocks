/**
 * ブロックエディタ用 CSS（build/style-editor.css）を build/style.css から生成する。
 *
 * プラグインの CSS は自社テーマの html{font-size:62.5%}（1rem=10px）前提で書かれている。
 * ブロックエディタのキャンバス（iframe）はテーマの設定に依らず root が 16px のことが多く、
 * そのままでは rem 値が 1.6 倍になり、編集画面でレイアウトが崩れる
 * （例: 電話CTAの POINT バッジが横幅を食い潰して見出しが 1 文字ずつ折り返す）。
 *
 * そこで REST 用 CSS と同じ rem→px 変換だけを行った CSS を作り、エディタ専用スタイルとして
 * 後から読み込ませる（!important やリセットは付けない。エディタ内では配信先テーマの
 * 敵対的な CSS を想定する必要がなく、付けると逆にエディタ UI と干渉するため）。
 *
 * セレクタは style.css と同一なので、読み込み順で後になるこちらの px 値が勝つ。
 *
 * 実行: node tools/build-editor-css.js（build.sh が CSS ビルド後に呼ぶ）
 */

const fs = require( 'fs' );
const path = require( 'path' );
const postcss = require( 'postcss' );

const SRC = path.resolve( __dirname, '../build/style.css' );
const DEST = path.resolve( __dirname, '../build/style-editor.css' );

function remToPx( value ) {
	// 1rem = 10px（html{font-size:62.5%} 前提の設計値）として固定変換する
	return value.replace( /(\d*\.?\d+)rem\b/g, ( _, n ) => {
		const px = parseFloat( n ) * 10;
		return `${ parseFloat( px.toFixed( 4 ) ) }px`;
	} );
}

const css = fs.readFileSync( SRC, 'utf8' );
const root = postcss.parse( css );

let converted = 0;

root.walkDecls( ( decl ) => {
	if ( /rem\b/.test( decl.value ) ) {
		decl.value = remToPx( decl.value );
		converted++;
	}
} );

const banner =
	'/*! madoguchi-blocks エディタ用（自動生成: tools/build-editor-css.js — 直接編集しない） */\n';
fs.writeFileSync( DEST, banner + root.toString() );

console.log( `style-editor.css generated (rem→px: ${ converted } decls)` );
