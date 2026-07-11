# リリース運用ルール

「リリースして」「バージョンを上げて配布して」等と依頼されたら、以下の手順を**この順序で**実行する。

## 前提

- 配布・自動更新は GitHub リリースを更新元とする（`plugin-update-checker` v5.7 同梱）
- **リリースのタグ名 = `madoguchi-blocks.php` の `Version:` ヘッダー**（更新判定に使うため厳守）
- リポジトリ: `madoguchi-inc/madoguchi-blocks` / ベースブランチ: `main`

## 手順（リポジトリ直下で実行）

### 1. build/ を最新化
```bash
npm ci && bash build.sh
```
- ⚠️ `wp-scripts build` は `build/` をクリーンしてから出力する。必ず `build.sh`（JS → CSS → view.js の順）を使い、`wp-scripts build` を単独実行しない。

### 2. Version を上げる
- `madoguchi-blocks.php` の `* Version:` ヘッダーをインクリメントする（例 1.1.1 → 1.1.2）
- セマンティックバージョニングに従う（バグ修正=patch / 機能追加=minor / 破壊的変更=major）

### 3. 変更をコミット
```bash
git commit -am "..."
```
- `build/` は成果物としてコミット済みなので、ビルド差分も一緒にコミットする

### 4. 配布 zip 生成 + GitHub リリース作成・添付

**方法A（ローカルに gh CLI がある場合）:**
```bash
bash package.sh --release
```
- 内部で `gh release create <Version> <zip> --repo madoguchi-inc/madoguchi-blocks --title v<Version> --generate-notes` が走る

**方法B（gh CLI が無い環境。Claude Code リモート等）:**
- `Version:` を上げた変更を PR で **main にマージするだけ**でよい。`.github/workflows/release.yml` が
  main への push で起動し、`Version:` に対応するタグが未作成なら CI 上でビルド→zip→リリース作成まで自動実行する
  （タグ作成済みならスキップされる。`Version:` を上げ忘れるとリリースされない）
- ワークフローの完走と、リリース（タグ・zip 添付）の存在を必ず確認すること

- 配布 zip は実行時ファイルのみ（`madoguchi-blocks.php` / `inc/` / `blocks/` / `build/` / `assets/` / `lib/` / `README.md`）。`src/` `scss/` `*.sh` `node_modules/` は含めない
- zip だけ作って確認したい場合は `bash package.sh`（`--release` なし）

## チェックリスト

- [ ] `bash build.sh` を（`wp-scripts build` 単独ではなく）実行したか
- [ ] `madoguchi-blocks.php` の `Version:` を上げたか
- [ ] build 差分を含めてコミットしたか
- [ ] `package.sh --release` で作られたタグ名が `Version:` と一致しているか
- [ ] 各サイトの管理画面に更新通知が出る（最大約12時間のラグ）ことを周知したか

## 注意

- 破壊的操作（既存タグ・リリースの削除等）は勝手に行わない。既存タグと衝突したらユーザーに確認する。
- `Version:` を上げずにリリースすると自動更新が発火しない。バージョン更新は必須。
