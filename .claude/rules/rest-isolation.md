# REST 表示の厳格化ルール（配信先テーマCSSからの隔離）

ブロックの追加・改修、スタイル（SCSS）の変更、インライン style を出す属性の追加を行うときは、
**REST API 配信時に配信先サイトのテーマCSSの影響を受けない**状態を必ず維持する。
このルールは、ブロック／スタイル／render.php／属性のいずれかに触れる作業では**必ず遵守する**。

## 背景（仕組み）

- 通常フロントは `wp_enqueue_style('madoguchi-blocks-style')` で `build/style.css` を使う。
- REST（`content.rendered`）では `inc/class-style-inliner.php` が本文先頭に `<style>` を1回だけ差し込む。
  差し込む CSS は **`build/style-rest.css`**（無ければ `build/style.css` にフォールバック）。
- `build/style-rest.css` は `tools/build-rest-css.js` が `build/style.css` から生成する:
  1. rem → px 固定（配信先の root font-size に依存させない）
  2. 全宣言に `!important`（配信先の `.entry-content p` 等に負けない）
  3. スコープ付き `all: revert` リセット（配信先テーマの `p`/`a`/`ul` 装飾を遮断）
- **ブロックの外側**（記事本文の段落など）には一切影響させない。配信先の `!important` 指定までは打ち消せない（CSS仕様の限界）。

## 変更時に必ず守ること

### 1. 新しいブロックを追加したら
- `inc/class-style-inliner.php` の `$block_names` に新ブロック名（`madoguchi/*`）を追加する。
  → これを忘れると、そのブロックを単体で置いた記事の REST 出力に CSS が同梱されない。
- `tools/build-rest-css.js` の `ROOTS` に、そのブロックのルート要素セレクタを追加する。
  → これを忘れると、リセット／基礎タイポグラフィが効かず配信先CSSの影響を受ける。
  → ただし **インライン style 主体の要素**（`.cta-button-wrap` 等）は `ROOTS` に入れない。

### 2. インライン style で出す属性を追加したら
- render.php がルート要素等にインラインで出す値（背景色・幅%・グリッド列定義など）は、
  `tools/build-rest-css.js` の `INLINE_GUARDS` に登録し、その宣言を `!important` 化の対象から除外する。
  → 除外しないと、CSS 側の `!important` がインライン style に勝ってしまい、ブロック単位の設定が無効になる。
  - `sel`: セレクタ部分一致、`selEnds`: カンマ区切りセレクタのいずれかが末尾一致、`props: null` は全宣言除外。
- カスタムプロパティ（`--md-*`）は `build-rest-css.js` が既定で `!important` 化しないため追加対応は不要。

### 3. ビルドと確認（毎回）
- スタイル／ブロックを変更したら必ず `bash build.sh` を実行し、`build/style-rest.css` を再生成する
  （`wp-scripts build` 単独や CSS だけの手動ビルドで止めない。生成ログの `style-rest.css generated` を確認）。
- 生成された `build/style-rest.css` で、次を目視確認する:
  - 追加したブロックのルートに `all:revert` と基礎タイポグラフィ（`font-family`/`font-size` 等）が当たっている
  - インライン style を優先させたい宣言に `!important` が**付いていない**（`INLINE_GUARDS` が効いている）
  - `rem` が残っていない（画像パス等の例外を除く）
- 仕上げに、**配信先テーマを模した強いCSS**（`.entry-content p{color:red!important…}`、`html{font-size:20px}` 等）を
  当てたページで `style.css`（before）と `style-rest.css`（after）を比較し、after でブロック内が意図どおり、
  かつブロック外の段落が配信先スタイルのままであることを確認する。

## チェックリスト

- [ ] 新ブロックを `inc/class-style-inliner.php` の `$block_names` に追加したか
- [ ] 新ブロックのルートを `tools/build-rest-css.js` の `ROOTS` に追加したか（インライン主体要素は除外）
- [ ] インライン style で出す新属性を `INLINE_GUARDS` に登録したか
- [ ] `bash build.sh` を実行し `build/style-rest.css` を再生成・コミットしたか
- [ ] 敵対的テーマCSS下での before/after を確認したか（ブロック内は隔離／ブロック外は不干渉）

## 注意

- `build/style-rest.css` は**自動生成物**。直接編集しない（`tools/build-rest-css.js` を直す）。
- ブロックの外側や配信先の `!important` 指定に手を出す形での「厳格化」はしない（副作用が大きい）。
