# madoguchi-blocks

記事内に設置できるカスタム Gutenberg ブロック集の WordPress プラグイン（テーマ非依存）。
チェックリスト型CTA・条件別カードリンク・買取業者比較テーブル・著者情報・口コミ・CTAボタン。

- 旧: `fuyouhin.support` モノレポ内 (`madoguchi-blocks/`) で開発 → **独立リポジトリに分離済み**
- 配布・自動更新も本リポジトリで完結する

## Tech Stack

PHP (WordPress プラグイン / block.json 登録) / JS (`@wordpress/scripts` = webpack) / SCSS (dart-sass)

- 動作要件: **WordPress 6.6+** / PHP 7.4+
  - wp-scripts 30.x のビルドは `react-jsx-runtime` に依存するため、6.6 未満ではエディタにブロックが出ない（フロントは PHP レンダリングのため影響なし）

## Commands

```bash
npm ci                # 依存インストール（初回・CI）
npm run start         # エディタJSのウォッチ開発
bash build.sh         # 一括ビルド（JS → CSS → view.js。この順序が必須）
npm run lint:js       # ESLint (wp-scripts)
```

> ⚠️ `wp-scripts build` は `--output-path`(`build/`) を**クリーンしてから**出力する。
> JS だけビルドすると `build/style.css` と `build/view.js` が消えるため、必ず `build.sh`
> （JS → CSS → view.js コピーを順に実行）を使う。

## ビルド構成

- エントリ: `src/index.js`（各ブロックの edit/save）→ `build/index.js`
- スタイル: `scss/style.scss`（明示 import のみ）→ dart-sass で `build/style.css`
- フロントJS: `src/view.js` を `build/view.js` へコピー
- `build/` は成果物を**コミット済み**（drop-in で動く）。`node_modules/` `dist-zip/` は gitignore
- CSS は node-sass ではなく **dart-sass** を使用（Node 21 でのネイティブビルド回避。`overrides` で chokidar を v3 に固定）

## 配布・自動更新

各サイトに導入後、**GitHub リリースを更新元とした自動更新**に対応（[plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) v5.7 を `lib/` に同梱）。

> **リリース作業のルール**: リリースを行う際は `.claude/rules/release.md` の手順を必ず遵守すること。

**リリース手順（このリポジトリ直下で実行）:**

```bash
npm ci && bash build.sh          # build/ を最新化
# madoguchi-blocks.php の Version: を上げる（例 1.1.1 → 1.1.2）
git commit -am "..."             # 変更をコミット
bash package.sh --release        # 配布zip生成 + GitHub リリース作成・添付
```

- **リリースのタグ名 = `madoguchi-blocks.php` の `Version:` ヘッダー**（更新判定に使うため厳守）
- 配布zipは実行時ファイルのみ（`madoguchi-blocks.php` / `inc/` / `blocks/` / `build/` / `assets/` / `lib/` / `README.md`）。`src/` `scss/` `*.sh` `node_modules/` は含めない
- 各サイトの「プラグイン」画面に標準の更新通知。「自動更新を有効化」ON で wp-cron による完全自動更新
