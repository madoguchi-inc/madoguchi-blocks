# Madoguchi Blocks

記事内に設置できるカスタム Gutenberg ブロック集。**テーマ非依存**で、どの WordPress / どのテーマでも動きます。

- **チェックリスト型CTA** … 見出し＋チェック項目（クリックで✓トグル）＋CTAボタンブロックを内包
- **条件別カードリンク** … 見出し＋カード型内部リンク（親ブロック内にカードを追加）。①アイコンのみ ②イラスト入り の2バリエーション。リンクはCTAボタンブロックを内包
- **CTAボタン** … 記事内に自在に置けるCTAボタン（色・グラデーション・角丸・バッジ等を設定可）。チェックリスト型CTA・条件カード・著者情報・おすすめリンクカードのボタンとしても使用
- **おすすめリンクカード** … おすすめ記事へ誘導するリンクカード。①ボタン強調（CTAボタンを内包・カード全体クリックでボタンへ遷移） ②チェックリスト風（ラベル＋丸囲みシェブロン・カード全体がリンク） の2パターン
- **買取業者比較テーブル** … アフィリ導線付きの比較表。記事ごとに店舗・列・表示順・リンクを編集可。スマホは横スクロール（店名列固定）
- **著者情報** … 名前・肩書き・プロフィール・アイコン・CTA を文中に挿入。`schema.org/Person` の構造化データ（JSON-LD）を同梱（SEO/LLMO対応）
- **口コミ・レビュー** … 吹き出し＋人物アイコンのレビュー。`schema.org/Review` の構造化データ（JSON-LD）を同梱（SEO/LLMO対応）

### REST API でも同一デザイン（style 埋め込み・配信先CSSから隔離）

REST API 経由（`content.rendered`）でブロックを取得すると、本文の先頭に
プラグインの CSS が `<style>` として1回だけインライン同梱されます。これにより headless 構成や
他サイトへ配信しても同じデザインを再現できます（通常のフロント表示では従来どおり enqueue した CSS を使用）。

REST 用には**配信先サイトのテーマCSSの影響を受けない専用CSS**（`build/style-rest.css`、
`tools/build-rest-css.js` がビルド時に自動生成）を同梱します:

- **rem → px 固定化** … 配信先の root font-size（`html { font-size }`）に依存しない
- **全宣言に `!important` 付与** … `.entry-content p` など配信先のどんなセレクタにも負けない
  （ブロック単位のインライン設定を壊さないよう、カスタムプロパティ・CTAボタン系などは除外）
- **スコープ付きリセット** … ブロック配下を `all: revert` で UA 既定に戻し、配信先テーマの
  `p` / `a` / `ul` 等への装飾を遮断。フォント・`box-sizing` はブロック側で自前定義

ブロックの**外側**（記事の通常の段落など）には一切影響しません。
なお配信先が `!important` 付きで指定したスタイルまでは打ち消せません（CSSの仕様上の限界）。

### ブランドカラー（メディア別）

管理画面 > **設定 > Madoguchi Blocks** で、メディアごとのブランドカラー（既定色）を設定できます。
各ブロックの主要色（CTAボタン・リンク・強調）は CSS カスタムプロパティ `--md-brand` を参照します。

さらに、各ブロックの編集画面サイドバー **「カラー設定 > アクセントカラー」** のカラーピッカーで、
ブロック単位に色を上書きできます（未設定のブロックは上記の全体設定＝既定色にフォールバック）。

## インストール（ぶちこめば動く）

1. この `madoguchi-blocks` フォルダごと、対象サイトの `wp-content/plugins/` に置く
   （zip の場合は 管理画面 > プラグイン > 新規追加 > プラグインのアップロード からでも可）
2. 管理画面 > プラグイン で **「Madoguchi Blocks」** を有効化
3. 管理画面 > 設定 > Madoguchi Blocks でブランドカラーを設定（任意）
4. 投稿 / 固定ページの編集画面で、ブロック挿入（カテゴリ「デザイン」または検索）から追加

ビルド不要です（`build/` に成果物を同梱済み）。

> **注意（既存投稿の再保存）**: 条件別カードリンクは v1.1.0 で保存マークアップを変更しています。
> v1.0.0 で作成済みのカードがある場合、ブロック検証エラーになることがあります。
> その場合は該当ブロックを開いて再保存してください（本番投入前の想定のため deprecated 対応は行っていません）。

> **チェックリスト型CTA・条件カードのボタンについて**: v1.1.1 以前はボタンをブロック自身が描画していましたが、
> 現在は **CTAボタンブロック（`madoguchi/cta-button`）を内包**する形に統一しています。
> 旧形式で保存済みの投稿はフロント表示はそのまま動き、エディタで開いて保存し直すと
> 旧ボタンの文言・URLを引き継いだCTAボタンブロックへ自動移行されます（deprecated 対応済み）。

## 構成

```
madoguchi-blocks/
  madoguchi-blocks.php     … プラグイン本体（ブロック登録・アセット読み込み・自動更新）
  inc/                    … 設定画面・スタイルインライナ・レビューアバター
  blocks/                 … 各ブロックの block.json（サーバー登録用）
  build/                  … 配布用ビルド成果物（JS / CSS / view.js）★これが実行に必要
  assets/img/             … アイコンSVG（CSSが参照）
  lib/                    … plugin-update-checker（GitHubリリース自動更新ライブラリ）
  src/                    … エディタJS（edit/save）ソース ※再ビルド用
  scss/                   … スタイルのソース ※再ビルド用
  build.sh                … 一括ビルド ※開発用
  package.sh              … 配布zip生成・リリース ※開発用
```

実行時に必要なのは `madoguchi-blocks.php` / `inc/` / `blocks/` / `build/` / `assets/` / `lib/` です。
`src/` `scss/` `*.sh` は開発用で、配布だけなら無くても動きます（`package.sh` はこの区別で zip を作ります）。

## 注意

- ブロックの名前空間は `madoguchi/*` です（旧 `fuyouhin/*` から変更）。fuyouhin.support 本体のテーマが提供する `fuyouhin/*` ブロックとは名前が衝突しないため、併用できます。
- 動作要件: **WordPress 6.6+** / PHP 7.4+
  - ブロックエディタ用JS（wp-scripts ビルド）が `react-jsx-runtime` に依存するため、6.6 未満ではエディタにブロックが表示されません（フロント表示はPHPレンダリングのため影響なし）

## 再ビルド（ソースを変更した場合のみ）

このリポジトリ（fuyouhin.support）の `node_modules` を使う前提。一括ビルドスクリプトを用意しています:

```bash
bash madoguchi-blocks/build.sh
```

> ⚠️ **重要**: `wp-scripts build` は `--output-path`（`build/`）を**クリーンしてから**出力します。
> そのため JS だけをビルドすると `build/style.css` と `build/view.js` が消えます。
> 必ず「JS → CSS → view.js コピー」をセットで実行してください（`build.sh` はこの順序を守ります）。

手動で行う場合:

```bash
# JS（@wordpress/scripts）※build/ をクリーンする
npx wp-scripts build madoguchi-blocks/src/index.js --output-path madoguchi-blocks/build
# CSS（node-sass）
npx node-sass madoguchi-blocks/scss/style.scss madoguchi-blocks/build/style.css --output-style compressed
# フロント用スクリプト
cp madoguchi-blocks/src/view.js madoguchi-blocks/build/view.js
```

## 更新（自動アップデート）

導入済みの本プラグインは、**GitHub リリースを更新元とした自動更新**に対応しています。
更新チェックには [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker)（`lib/` に同梱、v5.7）を使い、
専用リポジトリ **[madoguchi-inc/madoguchi-blocks](https://github.com/madoguchi-inc/madoguchi-blocks)** の最新リリースを監視します。

- 新しいリリースを公開すると、各サイトの **プラグイン** 画面に WordPress 標準の更新通知が出ます（反映は最大約12時間のラグ／管理画面「更新」ページを開くと即チェック）
- プラグイン一覧で **「自動更新を有効化」** を ON にすると、以降は wp-cron で**完全自動更新**されます（クリック不要）
- 配布リポジトリは **public** 運用のためトークン不要。private へ切り替える場合のみ、各サイトの `wp-config.php` に
  `define( 'MADOGUCHI_BLOCKS_GH_TOKEN', '<read-only PAT>' );` を定義してください

### リリース手順（新バージョンを配る側）

1. ソースを修正し、`bash madoguchi-blocks/build.sh` で `build/` を最新化する
2. `madoguchi-blocks.php` の `Version:` を上げる（例 `1.1.1` → `1.1.2`）
3. 変更をコミットする
4. 配布zipを作ってリリースする:
   ```bash
   bash madoguchi-blocks/package.sh --release
   ```
   `dist-zip/madoguchi-blocks-<version>.zip` を生成し、専用リポジトリに **タグ = バージョン**（例 `1.1.2`）で
   GitHub リリースを作成・zip添付します（`--release` を付けなければ zip 生成のみ）。
5. リリース本文に変更点を書いておくと、各サイトの更新画面「詳細を表示」で読めます。

> ⚠️ **タグ名は必ず `Version:` ヘッダーと一致させること**。plugin-update-checker はリリースのタグ名を
> 新バージョンとして扱うため、ずれると更新判定が正しく動きません。
