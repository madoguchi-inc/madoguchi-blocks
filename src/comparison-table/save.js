/**
 * 比較テーブル（親） — 本体は render.php が描画する動的ブロック。
 * 行（子ブロック）を保存するため InnerBlocks.Content を出力する。
 */

import { InnerBlocks } from '@wordpress/block-editor';

export default function save() {
	return <InnerBlocks.Content />;
}
