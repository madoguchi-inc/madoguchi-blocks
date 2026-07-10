# fuyouhin-blocks 記事内SEOブロック追加（TSK-4035 / 4030 / 3999 / 3988）

## 概要

配布用 Gutenberg ブロックプラグイン `fuyouhin-blocks` に、SEO記事へ埋め込むコンテンツブロックを追加・改修する。
複数メディア（不用品回収の窓口・みんなの買取・おそうじ合衆国・gaiheki+・オヨビー）へ「ぶちこめば動く」テーマ非依存プラグインとして配布する既存コンセプトを踏襲する。

対象パス: `source/fuyouhin.support/fuyouhin-blocks/`

## 要件定義

| TSK | 内容 | 区分 |
|---|---|---|
| TSK-4035 | 買取業者比較テーブル（アフィリ導線付き）。記事ごとに店舗・表示順・リンク編集可、店舗別CTA、スマホ横スクロール＋店名列固定、将来のクリック計測用 data 属性 | 新規ブロック |
| TSK-4030 | 著者情報ボックス（＋CTAボタン）。文中に自由挿入 | 新規ブロック |
| TSK-3999 | 口コミ/レビューセクション。見出し・本文・投稿者名・評価を構造化（SEO/LLMO向け schema.org） | 新規ブロック |
| TSK-3988 | 既存カード型内部リンク（condition-card/cards）にデザイン付与。①アイコンのみ ②イラスト入り の2バリエーション、画像差し替え、メディア別ブランドカラー | 既存改修 |

### 制約・前提
- WordPress 6.1+ / PHP 7.4+
- テーマ非依存（他サイトの `wp-content/plugins/` に配置して動作）
- クリック計測（TSK-4035）は今回スコープ外。HTML に `data-article` / `data-shop` / `data-click-type` 属性のみ付与し将来接続できる状態にする
- `condition-card` 改修は本番投入前想定のため `deprecated` 対応は省略（静的save変更による既存投稿の再保存が必要な旨をREADMEに明記）

## 仕様

### ブロック構成（namespace `fuyouhin/`）
- `fuyouhin/comparison-table`（新規・動的）… 買取業者比較テーブル
- `fuyouhin/author-box`（新規・動的）… 著者情報
- `fuyouhin/review-section`（新規・動的）… 口コミ（JSON-LD 構造化）
- `fuyouhin/condition-card` / `fuyouhin/condition-cards`（既存・静的save維持のまま改修）

新規3ブロックは **動的ブロック**（`save` は `null`、`render.php` でサーバー生成）とし、デザインを中央（render.php/CSS）で更新できるようにする。

### style 埋め込み（REST API 対応）
- `render_block` フィルタで、`fuyouhin/*` ブロックが本文に出現した最初の1回だけ、コンパイル済み `build/style.css` を `<style>` としてインライン注入（1リクエスト1回・重複排除）。
- WordPress REST API は `content.rendered` 生成時にこのフィルタを通すため、CSS が本文と共に API に乗る → headless / 他サイトでも同一デザインを再現。
- 静的・動的の両ブロックがこの仕組みで自己完結する。
- エディタ側は従来どおり `wp_enqueue_style('fuyouhin-blocks-style')`。

### ブランドカラー（メディア別）
- 管理画面に設定ページ（Settings API）を追加し、ブランドカラー（`fuyouhin_blocks_brand_color`）を保存。
- CSS はカスタムプロパティ `--fy-brand` を参照。インライン `<style>` 先頭に `:root{--fy-brand: <保存値>}` を出力。
- 各ブロックでインスタンス単位の上書き（InspectorControls のカラー設定）も可能。

### 各ブロックの属性・出力

**comparison-table（TSK-4035）**
- 属性: `caption`(見出し), `columns`(表示列の定義配列), `rows`(店舗配列: 店舗名/評価/各列値/CTA文言/アフィリURL), `articleId`(計測用)
- 出力: 横スクロールラッパ＋`<table>`。1列目（店舗名）は `position: sticky` で固定。各CTAは `<a class="...__cta" data-article data-shop data-click-type="cta">`、店舗名リンクは `data-click-type="name"`。スマホでスクロール可能を示すシャドウ/ヒント。

**author-box（TSK-4030）**
- 属性: `name`, `role`(肩書き), `bio`, `avatarUrl`, `ctaLabel`, `ctaUrl`
- 出力: アバター＋著者情報＋任意のCTAボタン。

**review-section（TSK-3999）**
- 属性: `heading`, `reviews`(配列: author/rating/body/attribute), `itemName`(レビュー対象名)
- 出力: 吹き出し＋人物アイコンのレビューカード群。加えて `schema.org/Review` と `AggregateRating` を **JSON-LD**（`<script type="application/ld+json">`）で同梱。

**condition-card / condition-cards（TSK-3988 改修）**
- `condition-card` に属性追加: `variation`("icon" | "illustration"), `iconKey`(内蔵アイコン選択), `imageUrl`(イラスト差し替え用)
- InspectorControls にバリエーション切替・アイコン選択・画像URLを追加。
- scss を2バリエーション対応に拡張。ブランドカラーは `--fy-brand` を使用（ハードコード `$color_orange` から移行）。

### ファイル構成（追加・変更）
```
fuyouhin-blocks/
  fuyouhin-blocks.php            # 変更: 新規ブロック登録 + render_block CSSインライン + 設定ページ読込
  inc/
    class-style-inliner.php      # 新規: render_block でのCSSインライン（重複排除）
    settings.php                 # 新規: ブランドカラー設定ページ（Settings API）
    render-helpers.php           # 新規: render.php 共通ヘルパー（属性サニタイズ等）
  blocks/
    comparison-table/{block.json, render.php}   # 新規
    author-box/{block.json, render.php}         # 新規
    review-section/{block.json, render.php}     # 新規
    condition-card/block.json                   # 変更: 属性追加
  src/
    comparison-table/{edit.js, save.js(null)}   # 新規
    author-box/{edit.js, save.js(null)}         # 新規
    review-section/{edit.js, save.js(null)}     # 新規
    condition-card/edit.js                       # 変更: バリエーションUI
    index.js                                     # 変更: 新規3ブロック登録
  scss/
    comparison-table/_block.scss                 # 新規
    author-box/_block.scss                       # 新規
    review-section/_block.scss                   # 新規
    condition-card/_block.scss                   # 変更: 2バリエーション + --fy-brand
    style.scss                                   # 変更: import 追加
  build/                          # 再ビルド成果物（wp-scripts + node-sass）
  README.md                       # 変更: 新ブロック説明 + 再保存注意
```

### ビルド手順
```bash
# fuyouhin.support ルートの node_modules を使用
npx wp-scripts build fuyouhin-blocks/src/index.js --output-path fuyouhin-blocks/build
npx node-sass fuyouhin-blocks/scss/style.scss fuyouhin-blocks/build/style.css --output-style compressed
cp fuyouhin-blocks/src/view.js fuyouhin-blocks/build/view.js
```

## 検証項目

### 正常系
- [ ] `wp-scripts build` / `node-sass` がエラーなく完了する
- [ ] `php -l` で全PHPファイルの構文エラーがない、`npm run lint:php` が通る
- [ ] プラグイン有効化後、エディタのブロック挿入（カテゴリ「デザイン」）に4種（新規3＋既存カード）が表示される
- [ ] 各ブロックを挿入・編集・保存し、フロントで正しく表示される
- [ ] comparison-table: PC で表全体表示、スマホで横スクロール＋店名列固定、CTA/店舗名に data 属性が付く
- [ ] review-section: フロント出力に `schema.org/Review` の JSON-LD が含まれる（リッチリザルトテスト or 目視）
- [ ] condition-card: アイコンのみ／イラスト入りの2バリエーションが切替できる、ブランドカラーが反映される
- [ ] REST API（`/wp-json/wp/v2/posts/<id>`）の `content.rendered` に `<style>`（インラインCSS）が含まれる
- [ ] 同一記事に複数の fuyouhin ブロックがあっても `<style>` は1回のみ

### 異常系
- [ ] 属性未入力（リンクURL空・店舗0件・口コミ0件）でもレイアウトが崩れない・PHPエラーが出ない
- [ ] 画像URL未設定時にアイコンのみで成立する（condition-card）
- [ ] ブランドカラー未設定時に既定色でフォールバックする

## 進め方
共有ファイル（`src/index.js` / `scss/style.scss` / `fuyouhin-blocks.php`）を複数タスクが触るため、逐次実装。コミットは意味のある単位で分割する。
