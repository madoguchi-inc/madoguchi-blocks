/**
 * 著者情報 — 本体は render.php が描画する動的ブロック。
 * ただし内部に置いた子ブロック（CTAボタン等）を保存するため InnerBlocks.Content を出力する。
 */

import { InnerBlocks } from '@wordpress/block-editor';

export default function save() {
	return <InnerBlocks.Content />;
}
