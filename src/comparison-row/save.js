/**
 * 比較テーブルの行（子） — 本体は render.php が描画する動的ブロック。
 * CTA列に置いた子ブロックを保存するため InnerBlocks.Content を出力する。
 */

import { InnerBlocks } from '@wordpress/block-editor';

export default function save() {
	return <InnerBlocks.Content />;
}
