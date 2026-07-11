#!/usr/bin/env bash
# madoguchi-blocks 一括ビルド（独立リポジトリ版）
# 注意: wp-scripts build は --output-path(build/) をクリーンしてから出力するため、
#       JS ビルドの後に必ず CSS ビルドと view.js のコピーを行うこと（順序重要）。
set -e

# このリポジトリ直下（package.json / node_modules がある場所）で実行する
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

echo "==> JS (wp-scripts) — build/ をクリーンして出力"
npx wp-scripts build src/index.js --output-path build

echo "==> CSS (dart-sass)"
npx sass scss/style.scss build/style.css --style=compressed --no-source-map

echo "==> REST配信用CSS（rem→px・!important化・スコープ付きリセット）"
node tools/build-rest-css.js

echo "==> view.js コピー"
cp src/view.js build/view.js

echo "==> 完了: build/ の内容"
ls -1 build/
