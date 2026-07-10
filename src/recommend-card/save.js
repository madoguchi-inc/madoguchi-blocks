/**
 * おすすめリンクカード — 本体は render.php が描画する動的ブロック。
 * ただしボタン強調パターンで内包する子ブロック（CTAボタン）を保存するため
 * InnerBlocks.Content を出力する。
 */

import { InnerBlocks } from '@wordpress/block-editor';

export default function save() {
	return <InnerBlocks.Content />;
}
