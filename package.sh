#!/usr/bin/env bash
# madoguchi-blocks 配布パッケージ生成
#
# 実行時に必要なファイルだけを madoguchi-blocks/ 構造で固めた配布 zip を作る。
# 生成した zip は専用リポジトリ madoguchi-inc/madoguchi-blocks の GitHub リリースに
# 添付する（プラグイン側の plugin-update-checker がこの zip を更新パッケージに使う）。
#
# 使い方（このリポジトリ直下で実行）:
#   1. npm ci && bash build.sh          # 先に build/ を最新化しておく
#   2. madoguchi-blocks.php の Version: を上げる（例 1.1.1 → 1.1.2）
#   3a. bash package.sh            # zip を生成するだけ
#   3b. bash package.sh --release  # zip 生成 + GitHub リリース作成・添付
#
# 注意: リリースのタグ名は Version: ヘッダーと必ず一致させること（更新判定に使われる）。
set -e

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
SLUG="madoguchi-blocks"
REPO="madoguchi-inc/madoguchi-blocks"

# プラグインヘッダーから Version を取得
VERSION="$(grep -E '^\s*\*\s*Version:' "$PLUGIN_DIR/madoguchi-blocks.php" | head -1 | sed -E 's/.*Version:[[:space:]]*//; s/[[:space:]]+$//')"
if [ -z "$VERSION" ]; then
	echo "ERROR: Version をヘッダーから取得できません" >&2
	exit 1
fi

# build/ が未生成のまま配布するのを防ぐ
if [ ! -f "$PLUGIN_DIR/build/index.js" ] || [ ! -f "$PLUGIN_DIR/build/style.css" ]; then
	echo "ERROR: build/ が未生成です。先に 'npm ci && bash build.sh' を実行してください" >&2
	exit 1
fi

DIST="$PLUGIN_DIR/dist-zip"
STAGE="$DIST/$SLUG"
ZIP="$DIST/${SLUG}-${VERSION}.zip"

rm -rf "$DIST"
mkdir -p "$STAGE"

# 実行時に必要なものだけコピー（src/ scss/ *.sh dist-zip/ は含めない）
for item in madoguchi-blocks.php inc blocks build assets lib README.md; do
	if [ -e "$PLUGIN_DIR/$item" ]; then
		cp -R "$PLUGIN_DIR/$item" "$STAGE/"
	fi
done

# zip 化（展開時のトップが madoguchi-blocks/ になるよう dist-zip 内で固める）
( cd "$DIST" && zip -qr "$ZIP" "$SLUG" )
echo "==> 生成: $ZIP"

# --release 指定時は GitHub リリースを作成し zip を添付する
if [ "$1" = "--release" ]; then
	echo "==> GitHub リリース作成: $REPO $VERSION"
	gh release create "$VERSION" "$ZIP" \
		--repo "$REPO" \
		--title "v$VERSION" \
		--generate-notes
	echo "==> 完了。各サイトの管理画面に更新通知が出ます（最大約12時間のラグ）"
else
	echo "==> zip のみ生成しました。リリースまで行う場合は --release を付けて再実行してください"
fi
