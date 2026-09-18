# TSK-4107 SEO記事内 買取店別「電話CTA」ブロック ＋ 固定フッター ＋ 店舗マスタ

- タスク: https://dev.madoguchi.inc/tasks/ffkv4wjsmtgtw392（旧 TSK-4107、みんなの買取）
- 同時設計: 固定フッター（追従型CTA）https://dev.madoguchi.inc/tasks/r1jr66cemtgtw392（旧 TSK-4104）
- Figma（みんなの買取 `09cMeGFEa866Zmc8rH3DMg`、ページ「202607_電話送客プロジェクト」）
  - 記事内CTA: コラム_PC `14065-29201` / コラム_SP `14065-30761`
  - 受付時間外の出し分け: `14065-33169`
  - 固定フッター: `14065-33207`
  - 部品: SEO/CTA `13244-2443`、キャンペーンバナー_横長 `14140-36629`
  - ※ プロジェクトの関連リンクにある toBLP（`eA5ePiWLBQABJ6KLbwor8S`）は太陽光の toB LP で無関係
- 参考: ウリドキ https://uridoki.net/watch/kiji_277863/

## 前提（コード調査より）

- 公開サイト ekaitori.com は React SPA。記事は edit.ekaitori.com（ヘッドレス WP）の REST から `content.rendered` を取り、そのまま差し込む。Rails は記事 HTML に関与しない
- SPA は madoguchi-blocks の view.js を読み込まない。差し込まれた HTML 内の `<script>` も実行されない。よって表示側の出し分けは **render.php（サーバー側）で完結**させる
- edit.ekaitori.com の nginx・WP にキャッシュ層はなく、SPA も素の fetch。記事表示ごとに render.php が実行される前提で時刻判定をサーバー側に置く（将来キャッシュを入れる場合は data 属性を使ってブラウザ側判定へ切り替える）
- 店舗マスタの実体は estima の `company_shops`。電話番号・受付時間・キャンペーンの項目は未整備。公開 API `/v1/partner_shops/company_shops` に電話番号は含まれない
- プラグインには外部 API 通信の実績がない。設定ページ（`inc/settings.php`）はブランドカラーのみ

## 要件定義

### 機能要件

- 記事内に、買取店 1〜3 社の「電話で査定額を聞く」CTA カードを置ける（PC は件数＝列数、SP は縦 1 列）
- 店舗情報（店舗名・ロゴ・電話番号・受付時間・得意品目・紹介文・ボタン文言・キャンペーン・公開可否）は **estima の管理画面で一元管理**し、記事側は店舗を選ぶだけ。マスタ変更は開発なしで全記事に反映される（最大 10 分遅れ）
- 1 店舗に複数の電話番号（料金形態などのラベル付き）を持て、記事側でどの番号を使うか選べる。未選択なら既定番号
- 受付時間は **曜日別**（1 日 1 レンジ、日跨ぎ可）。行の無い曜日は休み。24 時間対応フラグあり
- 表示時、リクエスト時刻（JST）で受付時間内かを判定し、時間内は電話ボタン、時間外は WEB 査定ボタン（fallback URL）に切り替える
- 記事ごとに、CTA の表示/非表示、掲載店舗と並び順、見出し・説明文、ボタン文言・紹介文の上書き、キャンペーン表示/非表示を設定できる
- 固定フッター（画面下固定バー）も同じ仕組みで、1 店舗を指定して店名入り文言／時間外の切り替えができる
- 複数事業（買取 `kaitori` / 回収 `fuyouhin` / 清掃 `osouji`）の店舗を、同じプラグインから出せる。1 ブロック＝1 サービス
- 番号ごとに通話料無料かを持ち、無料でない番号（050 など）は注記を出す

### 非機能・制約

- estima への API リクエストは WordPress の PHP からのみ。ブラウザ（SPA・エディタ）から estima を直接叩かない
- API 呼び出しは transient（TTL 10 分）で抑え、失敗時は「最後に成功したデータ」で描画し続ける
- REST 配信時の CSS 隔離規約（`.claude/rules/rest-isolation.md`）、ブロック設計規約（`.claude/rules/block-design.md`）を守る
- 電話番号は掲載用の業務番号であり、加盟店の代表電話（addresses.tel）は使わない

### やらないこと

- 通話の計測・課金（番号は店舗側支給、計測は先方）
- 祝日・臨時休業・1 日 2 レンジの受付時間
- 買取店一覧・店舗詳細ページ（Rails 側画面）の電話 CTA

## 仕様

### 1. estima（Rails エンジン）: 店舗マスタ

`company_shops` は変更せず、付属テーブルを 3 つ追加する。

**company_shop_phone_ctas**（店舗と 1:1）

| カラム | 型 | 内容 |
|---|---|---|
| company_shop_id | bigint, unique, FK | 対象店舗 |
| uuid | uuid, unique, default gen_random_uuid() | CarrierWave の保存パス用（`ApplicationUploader#store_dir` が `model.uuid` を使う）。API の識別子は company_shop.uuid を使う |
| is_public | boolean, default false | 電話CTAとして掲載可 |
| is_always_open | boolean, default false | 24 時間対応。true なら hours を無視 |
| reception_text | string | 受付時間の表示文。空なら hours から自動生成 |
| lead_text | string | カードの紹介文 |
| web_fallback_url | string | 時間外の遷移先。空なら時間外でも電話ボタン＋注記 |
| has_campaign | boolean, default false | |
| campaign_name / campaign_body / campaign_terms | string / text / text | |
| campaign_image | string（CarrierWave） | |
| campaign_starts_on / campaign_ends_on | date | 期間。API 側で期間外は `null` にする |

**company_shop_phone_cta_hours**（曜日別、親ごとに wday unique）

| カラム | 型 | 内容 |
|---|---|---|
| company_shop_phone_cta_id | FK | |
| wday | integer 0〜6 | 0=日 |
| start_at / end_at | time | `end_at <= start_at` は日跨ぎ |

- 行が無い曜日は休み。`is_always_open` が false で hours が 0 行なら保存エラー

**company_shop_phone_cta_numbers**（1:N）

| カラム | 型 | 内容 |
|---|---|---|
| company_shop_phone_cta_id | FK | |
| label | string | 用途・料金形態（例: 標準、高単価記事用） |
| phone_number | string | 表示用そのまま（`0120-000-000`） |
| is_toll_free | boolean, default true | 通話料無料か |
| is_default | boolean | 親内で 1 件のみ true（バリデーション） |
| position | integer | 並び順 |

**導出**
- 店舗名: `CompanyShopDisplayContent#shop_name` があればそれ、なければ `company_shops.name`
- ロゴ: `company_shops.logo` の CloudFront URL（既存の `to_api_data` と同じ組み方）
- `reception_text` 自動生成: 全曜日同じなら `10:00〜20:00`、違えば同一時間帯の曜日をまとめて `月〜金 10:00〜20:00 / 土日 10:00〜18:00 / 水曜定休`

**管理画面**（`Admins::CompanyShops::PhoneCtasController`、`admins/company_shops/:uuid/phone_cta` の edit/update）
- 加盟店詳細にタブ「電話CTA」を追加。1 画面で本体・hours（曜日×休み/開始/終了の 7 行グリッド、「月曜を全曜日にコピー」ボタン）・numbers（行追加式 nested attributes）を編集
- 権限は既存 `company_shop` の update を流用（`permission_registry` に新規追加しない）
- 保存後に API の Rails.cache を破棄
- **運用ルール**: 公開判定は `is_public` のみ。加盟店の解約・掲載停止（`state` / `is_display`）では自動的に除外されないため、解約時は運用担当が電話CTAを非公開にする（将来 `state = contracted` 条件を足すかは要判断）

### 2. estima: 公開 API

`Api::V1::PhoneCtasController`（`constraints subdomain: api`、認証なし、既存 `Api::V1` と同じ作法）。2 アクション。

| エンドポイント | 用途 | 備考 |
|---|---|---|
| `GET /v1/phone_ctas` | 公開中の店舗の **uuid と会社名だけ**を全件 | ページングなし。エディタのプルダウン専用（軽量） |
| `GET /v1/phone_ctas/:uuid` | 店舗 1 件の詳細（`:uuid` は company_shop の uuid） | 表示用。非公開・存在しない場合は 404 `{ "error": "not_found" }` |

- 両方 `is_public = true` のみ対象。`Cache-Control: public, max-age=300`。Rails.cache 5 分（キーは `phone_ctas/index` と `phone_ctas/{uuid}`、管理画面保存で両方破棄）
- 一覧レスポンス:

```json
{
  "shops": [
    { "uuid": "company_shop uuid", "name": "買取大吉" }
  ]
}
```

- 単体レスポンス:

```json
{
  "uuid": "company_shop uuid",
  "name": "買取大吉",
  "logo_url": "https://.../logo.png",
  "lead_text": "…",
  "web_fallback_url": "https://…",
  "is_always_open": false,
  "reception_text": "月〜金 10:00〜20:00 / 土日 10:00〜18:00",
  "hours": [{ "wday": 1, "start": "10:00", "end": "20:00" }],
  "numbers": [
    { "id": 12, "label": "標準", "phone_number": "0120-000-000", "is_toll_free": true, "is_default": true,
      "tel_href": "tel:+81120000000", "qr_svg": "<svg …>" }
  ],
  "campaign": { "name": "…", "body": "…", "image_url": "…", "starts_on": "2026-09-01", "ends_on": "2026-09-30", "terms": "…" }
}
```

- `numbers[].tel_href` は数字以外を除き先頭 0 を `+81` に（国番号付き入力は `+` を保持）。`qr_svg` は `tel_href` を rqrcode で SVG 化したもの（PC モーダルの QR。生成失敗時は `null`）
- `campaign` は has_campaign=false または期間外なら `null`
- `is_always_open` が true のとき `hours` は `[]`。`hours` の `end <= start` は日跨ぎ（`start == end` は禁止していないので WP 側は日跨ぎ扱いになる）
- 3 事業とも estima エンジン側の 1 実装を各ホストがそのまま提供する

### 3. madoguchi-blocks: 設定・取得層

**設定ページ追加項目**（`inc/settings.php`）

| option | 内容 |
|---|---|
| `madoguchi_blocks_phone_cta_api_urls` | `{ kaitori: "https://api.ekaitori.com", fuyouhin: "", osouji: "" }`。サービスキーはプラグイン内定数 `Madoguchi_Blocks_Phone_Cta_Services::KEYS`（kaitori=買取 / fuyouhin=回収 / osouji=清掃）で固定。URL 空のサービスはエディタに出ない |
| 「店舗データを今すぐ更新」ボタン | サービス別／全件で transient を削除し再取得 |

**取得層** `inc/phone-cta/class-repository.php`（`Madoguchi_Blocks_Phone_Cta_Repository`。既存コードに合わせ PHP 名前空間は使わず、クラス名プレフィックスで表す）

- `list(string $service): array` … エディタのプルダウン用。transient `madoguchi_phone_cta_list_{service}`（TTL 600 秒）→ 無ければ `wp_remote_get("{base}/v1/phone_ctas", timeout 5s)`。失敗時は `[]`（表示には使わないのでフォールバック不要）
- `find(string $service, string $uuid): ?array` … 表示用。transient `madoguchi_phone_cta_{service}_{uuid}`（TTL 600 秒）→ 無ければ `GET /v1/phone_ctas/{uuid}` → 成功で transient と option `madoguchi_phone_cta_last_{service}_{uuid}`（最後に成功したデータ）を更新 → 通信失敗（タイムアウト・5xx・壊れた JSON）は option を返し、再試行を間引くため 60 秒の短期 transient（last-good、無ければ失敗マーカー）を置く → 404 は `null` を同じ TTL でネガティブキャッシュし、option も削除（非公開になった店舗を出さない）
- `refresh(?string $service)` … 一覧・単体の transient をまとめて削除（option は残す）
- 1 記事あたりの店舗は最大 3 社、サイト全体でも数十社なので、店舗ごとの単体取得でも API 呼び出しは「店舗数 ÷ 10 分」で収まる
- 取得処理はこのクラスに閉じ、将来データソースを変える場合はここだけ差し替える

**中継 REST**（`inc/phone-cta/class-rest.php`）
- `GET /wp-json/madoguchi/v1/phone-cta/shops?service=kaitori` … `edit_posts` 必須。`Repository::list()`（uuid と会社名）を返す
- `GET /wp-json/madoguchi/v1/phone-cta/shops/{uuid}?service=kaitori` … `edit_posts` 必須。`Repository::find()` を返す。エディタが店舗を選んだ後、番号プルダウンとカードプレビューに使う
- `POST /wp-json/madoguchi/v1/phone-cta/refresh?service=` … `manage_options` 必須
- 000-harden-rest.php の制限には該当しない（独自 namespace）

**時刻判定** `inc/phone-cta/class-reception.php`（`Madoguchi_Blocks_Phone_Cta_Reception::is_open(array $shop, DateTimeImmutable $now_jst): bool`）
- `is_always_open` → true
- 今日の wday の行が無い → false
- `start < end` … `start <= now < end`
- `end <= start`（日跨ぎ） … `now >= start || now < end`。前日の行が日跨ぎで `now < 前日.end` の場合も true
- 純粋関数として PHPUnit（`composer.json` dev 依存を新規追加、WP 読み込み不要）でテスト

**電話番号の正規化** `Reception` と同居させず `inc/phone-cta/class-tel.php` の `Madoguchi_Blocks_Phone_Cta_Tel::to_href(string $display): string`
- 数字以外を除去し、先頭 0 を `+81` に置換して `tel:+81120000000`。変換不能なら表示文字列を数字のみで `tel:` に

### 4. madoguchi-blocks: ブロック `madoguchi/phone-cta`（動的、render.php）

**属性**

| 属性 | 型 / 既定 | 内容 |
|---|---|---|
| service | string / `kaitori` | サービスキー |
| heading | string / 「今すぐ電話でかんたん無料査定」 | RichText。見出し（黒・24px） |
| description | string / 「複数の買取店で査定してもらうことが高く売るコツ！」 | RichText。見出しの**上**に出る小見出し（ゴールド・13px） |
| points | string[] / 「強引な営業なし」「個人情報必要なし」「相談だけでもOK」 | POINT1〜3 のバッジ文言。空文字は出さない |
| showPcModal | bool / true | PC で押下時に電話番号と QR のモーダルを出す（SP は tel: で発信） |
| bannerPreset | string / `''` | バナーのパターン。`''`（なし）／**選択中サービスの**同梱パターンのキー（例 `amazon-gift-12000`）／`custom`（メディアから選ぶ）。サービスを切り替えると、そのサービスに無いパターンは「なし」に戻る |
| bannerImageUrl | string / `''` | `custom` のときの画像 URL |
| bannerImageAlt | string / `''` | 代替テキスト。空ならパターン既定 |
| bannerLinkUrl | string / `''` | バナーのリンク先。空ならリンクなし |
| shops | array / `[]` | `{ uuid, numberId, leadText }` × 1〜3。numberId null なら既定番号。leadText 空ならマスタ値。ボタン文言は上書き不可 |
| showCampaign | bool / **false** | true のときマスタのキャンペーンをカード一覧の**下**に店名付きで出す（Figma のカードに枠が無いため既定はオフ） |
| isVisible | bool / true | false なら何も出力しない |

**エディタ（edit.js）**
- キャンバス: heading / description は RichText。カードは中継 REST の店舗データで描画（受付時間内の見た目、注記「表示側は受付時間で自動切替」）
- サイドバー: サービス選択 → 店舗リスト（店舗プルダウン、番号プルダウン、紹介文上書き、上へ/下へ/削除。4 件目は追加不可）→ 先頭のバナー（パターン選択／カスタム画像／代替テキスト／リンク先）→ POINT バッジ（3 つのテキスト）→ トグル（キャンペーン表示 / このブロックを表示）
- API URL 未設定・取得失敗・店舗がマスタに無い場合は黄色の Notice を出し、保存は妨げない

**出力（render.php）**

```html
<section class="phone-cta phone-cta--cols-3" id="phone-cta" data-phone-cta data-service="kaitori">
  <div class="phone-cta__banner">                   <!-- bannerPreset があるときだけ。リンク指定時は <a> で包む -->
    <img class="phone-cta__banner-image" src="…/assets/img/phone-cta/banner-amazon-gift-12000.png"
         srcset="… 1x, …@2x.png 2x" alt="…" width="760" height="101" loading="lazy" decoding="async">
  </div>
  <header class="phone-cta__header">              <!-- PC: 左に小見出し＋見出し、右に POINT、下にゴールド罫線＋▼ -->
    <div class="phone-cta__titles">
      <p class="phone-cta__description">複数の買取店で査定してもらうことが高く売るコツ！</p>
      <h2 class="phone-cta__heading">今すぐ電話でかんたん無料査定</h2>   <!-- SP は両脇に ▼ -->
    </div>
    <ul class="phone-cta__points">
      <li class="phone-cta__point"><span class="phone-cta__point-label">POINT1</span><span class="phone-cta__point-text">強引な営業なし</span></li>
      …
    </ul>
  </header>
  <ul class="phone-cta__list">
    <li class="phone-cta__card is-open" data-shop-uuid="…" data-shop-name="買取大吉"
        data-tel="+81120000000" data-open="1" data-fallback-url="…" data-reception-text="…">
      <div class="phone-cta__intro">                <!-- PC: 縦積み / SP: ロゴ左・紹介文右 -->
        <div class="phone-cta__logo-box"><img class="phone-cta__logo" src="…" alt="買取大吉" loading="lazy"></div>
        <div class="phone-cta__lead"><p class="phone-cta__lead-text">…</p></div>
      </div>
      <div class="phone-cta__action">
        <a class="phone-cta__button phone-cta__button--balloon" href="tel:+81120000000">
          <span class="phone-cta__balloon">その場でかんたん無料査定！</span>   <!-- 受付時間内のみ -->
          <span class="phone-cta__free">査定無料</span>                        <!-- SP のみ表示（縦書きタブ） -->
          <span class="phone-cta__button-body">
            <span class="phone-cta__icon"><svg>…</svg></span>
            <span class="phone-cta__button-label"><span class="phone-cta__button-head">買取大吉に</span><span class="phone-cta__button-tail">電話で査定額を聞く</span></span>
          </span>
          <span class="phone-cta__chevron"></span>                             <!-- SP のみ表示 -->
        </a>
        <p class="phone-cta__hours"><span class="phone-cta__hours-text">受付時間：10:00〜20:00</span></p>
      </div>
    </li>
  </ul>
  <ul class="phone-cta__campaigns"><li class="phone-cta__campaign">…</li></ul>   <!-- showCampaign=true のときだけ -->
</section>
```

- 見た目は Figma「みんなの買取」202607_電話送客プロジェクト（コラム_PC 14065-29201 / コラム_SP 14065-30761 / 電話査定受付時間外 14065-33169）に合わせる。ゴールド `#c4ab46`、黒 `#18191e`、オレンジグラデ `#eb4614→#f78b08`、カード罫線 `#ddd`、紹介文背景 `#f7f7f7`、SP「査定無料」タブ `#f39072`
- バナーは `inc/phone-cta/class-banners.php` の `PATTERNS` から選ぶ。**サービス（事業）ごとに別セット**で、1 パターンが PC・SP・PC モーダルの 3 枚を持つ

| 用途 | 例（買取） | サイズ | 出し方 |
|---|---|---|---|
| ブロック先頭 PC | `banner-amazon-gift-12000.png` | 760×101 | `<picture>` の `<img>` |
| ブロック先頭 SP | `banner-amazon-gift-12000-sp.png` | 381×143 | `<source media="(max-width: 896px)">`（`SP_MAX_WIDTH` は scss の `mq-max(md)` と合わせる） |
| PC モーダル下部 | `banner-amazon-gift-12000-modal.png` | 650×106 | モーダル内の `<img>` |

- 画像は `assets/img/phone-cta/<service>/` に置き、`@2x` があれば srcset を付ける。パターンを増やすときは画像を置いて `PATTERNS` にそのサービスの 1 件を足す（エディタの選択肢は `madoguchiBlocksData.phoneCtaBanners`（サービス別）経由で自動反映）
- 回収 `fuyouhin` / 清掃 `osouji` は画像が用意できるまで空（「なし」とカスタム画像のみ）
- カスタム画像は 1 枚を PC・SP・モーダルで共用する
- ボタン文言は店名以外**サービスごとに固定**（買取「{shop}に電話で査定額を聞く」／回収・清掃「{shop}に電話で見積もりを聞く」。`default_texts` の `shop_label`）。店舗マスタ・ブロック属性からの上書きは無い
- ボタン文言は 2 片に分けて出す（前半 `phone-cta__button-head` を block にして常に同じ位置で改行）
  - 電話: `View::label_parts()` で「店名＋助詞」と「残り」→「おたからやに／電話で査定額を聞く」
  - WEB: `default_texts` の `web_label_parts`「WEBでカンタン」「無料査定はこちら」（回収・清掃は「無料見積もりはこちら」）
- アイコンは Figma の書き出し（`phone-filled` / `touch`）をそのまま `inc/phone-cta/render-helpers.php` と `src/phone-cta/icons.js` の 2 か所に持つ（塗りベース・`currentColor` 追従）。変更時は必ず両方を同期する。白丸は CSS 側で描き、グリフは丸に対して 70.6%（Figma の 22.588 / 32）。色は `#e04b2d`

- 出し分け（リクエスト時 JST、`Reception::is_open`）

| 状態 | ボタン |
|---|---|
| 受付時間内 / 24 時間 | 電話ボタン `tel:` |
| 時間外・fallback あり | `phone-cta__button--web`「WEBでカンタン／無料査定はこちら」（Figma の指タップアイコン。PC・SP とも表示）→ fallback URL。吹き出し・番号・通話料バッジは出さない |
| 時間外・fallback なし | 電話ボタンのまま（グレー）＋`phone-cta__notice`「現在は受付時間外です」 |

- カード内は Figma どおり「ロゴ／紹介文／ボタン／受付時間」のみ。「通話料無料」バッジは出さない。`is_toll_free=false` の番号だけ `phone-cta__note`「通話料はお客様のご負担となります」
- `showCampaign=true` かつ `campaign` non-null の店舗があれば、カード一覧の下に `phone-cta__campaigns`（店名バッジ・画像・名称・内容・注意事項）。カード内には出さない
- カードは行内で等高、中身は上下中央寄せ（Figma の justify-center）
- マスタに無い／非公開の店舗はスキップ。0 件なら `''` を返す。`id="phone-cta"` は記事内最初のブロックのみ付与（固定フッターの汎用リンク先）。「最初」の判定は投稿 ID 単位（REST の一覧レスポンスでは複数投稿が同一リクエストで描画されるため）。固定フッターの「1 記事 1 つ」も同様
- 列数クラスは出力件数から `--cols-1/2/3`

**PC の押下時モーダル**（Figma: 電話CTA押下時モーダル `14065-33436`）

- PC は `tel:` を押せないため、電話ボタンを押すと番号・受付時間・QR（スマホで読み取る用）のモーダルを出す。SP はボタンが `tel:` リンクのままで発信する
- **SPA には view.js が届かないため JS を使わない。** 隠しチェックボックス（`.phone-cta__modal-toggle`）＋ `<label>` で開閉し、`:checked ~ .phone-cta__modal` で表示する。閉じるのは右上の × と背景（どちらも同じ `for` の label）
- ボタンは同じ中身を 2 つ出し、CSS で出し分ける: PC は `<label class="…--modal">`、SP は `<a class="…--tel" href="tel:…">`
- QR は API の `numbers[].qr_svg` をそのまま差し込む。`madoguchi_blocks_phone_cta_qr_svg()` で svg/g/path/rect の許可リストを通し、スクリプトを含む場合は出さない
- ブロックにバナーを設定している場合はモーダル下部にもバナーを出す（同じパターンの**モーダル用画像**。Figma のキャンペーンバナー_横長）
- `showPcModal` を false にすると PC でも `tel:` リンクのままになる

**CSS** `scss/phone-cta/_block.scss`
- グリッドで各カード等高。SP（既存ミックスイン `mq-max`＝896px 以下）は 1 列、それ以上は `--cols-n` で n 列
- 色は Figma のトークンを直接持つ（`--md-brand` は使わない。買取のブランドカラーがそのままデザイン指定のため）
- `class-style-inliner.php` の `$block_names`、`tools/build-rest-css.js` の `ROOTS` に `.phone-cta` を登録

### 5. madoguchi-blocks: ブロック `madoguchi/phone-cta-footer`（動的）

**属性**

| 属性 | 型 / 既定 | 内容 |
|---|---|---|
| service | string / `kaitori` | |
| shop | object / null | `{ uuid, numberId, balloonText }`。null なら店名なしの汎用文言 |
| catchBadge | string / 「完全無料査定」 | キャッチ左のゴールドのバッジ。空なら出さない |
| catchText | string / 「複数社で比較して1番高く売ろう」 | RichText。両脇に「＼ ／」の斜線を CSS で付ける |
| showWebButton | bool / true | 黒ボタン |
| webButtonUrl | string / `/form` | |
| isVisible | bool / true | |

**出力**

```html
<div class="phone-cta-footer" data-phone-cta-footer data-service="kaitori"
     data-shop-uuid="…" data-shop-name="おたからや" data-tel="+81…" data-open="1">
  <p class="phone-cta-footer__catch"><span class="phone-cta-footer__catch-badge">完全無料査定</span><span class="phone-cta-footer__catch-text">複数社で比較して1番高く売ろう</span></p>
  <div class="phone-cta-footer__buttons">
    <a class="phone-cta-footer__web" href="/form">        <!-- PC: 「24時間年中無休で受付中！」を上段、アイコン＋文言を下段 / SP: アイコン左、文言 2 段 -->
      <span class="phone-cta__icon"><svg>…</svg></span>
      <span class="phone-cta-footer__web-main phone-cta-footer__web-main--pc">オンライン無料一括査定</span>
      <span class="phone-cta-footer__web-main phone-cta-footer__web-main--sp">オンライン一括査定</span>
      <span class="phone-cta-footer__web-sub">24時間年中無休で受付中！</span>
    </a>
    <a class="phone-cta-footer__tel" href="tel:+81…">
      <span class="phone-cta-footer__balloon">その場でかんたん無料査定！</span>   <!-- PC のみ（SP は CSS で非表示） -->
      <span class="phone-cta-footer__tel-main">
        <span class="phone-cta__icon"><svg>…</svg></span>
        <span class="phone-cta-footer__tel-label"><span class="phone-cta-footer__tel-shop">おたからやに</span><span class="phone-cta-footer__tel-rest">電話で査定額を聞く</span></span>
      </span>
    </a>
  </div>
</div>
```

- 見た目は Figma 固定フッター（14065-33207）。背景は `rgba(24,25,30,.6)→.4` の縦グラデ、黒ボタン `#18191e`、橙ボタンはオレンジグラデ。PC はボタンを中央寄せ（幅は内容なり）、SP は 2 等分

| 状態 | 橙ボタン |
|---|---|
| 店舗あり・時間内 | 「{店名}に電話で査定額を聞く」＋吹き出し、`tel:` |
| 店舗あり・時間外・fallback あり | 「WEBでカンタン無料査定はこちら」→ fallback |
| 店舗あり・時間外・fallback なし | 電話ボタン非表示（黒ボタンのみの 1 列レイアウト） |
| 店舗なし | 「電話で査定額を聞く」＋吹き出し → `#phone-cta` へページ内リンク（Figma「それ以外」） |

- `position: fixed; bottom: 0; z-index` は SPA の StickyBottomCta（z-60）と同等以上。記事内の位置は問わない
- 1 記事 1 つ。render.php は静的フラグで 2 つ目以降を `''` にする
- 文言既定値はサービスキー別の対応表（買取「査定額を聞く」／回収・清掃「見積もりを聞く」）をプラグイン内に持つ
- SCSS `scss/phone-cta-footer/_block.scss`、REST 隔離登録は phone-cta と同様

### 6. ekaitori.com（SPA）の最小改修

- `src/pages/Column.jsx`: 差し込む HTML に `data-phone-cta-footer` が含まれる場合、`StickyBottomCta` を描画しない（二重表示回避）。判定は `updatedContent.includes('data-phone-cta-footer')`
- 任意: `.phone-cta-footer__web[href^="/"]` のクリックを拾って `navigate()` に流す（SPA 内遷移）。初期リリースでは行わずフルページ遷移を許容

### 7. リリース順

1. estima: マイグレーション・モデル・管理画面・API（PR → 各ホストにデプロイ。brand_satei から）
2. madoguchi-blocks: 設定・Repository・Reception・2 ブロック・CSS（Version 上げてリリース。edit.ekaitori.com で API URL を設定）
3. ekaitori.com: Column.jsx の条件分岐（dev → staging 確認 → 本番）
4. 運用: estima 管理画面で店舗の電話CTA を登録 → 記事にブロック配置

## 検証項目

### estima
- [ ] マイグレーションが `estima/test/dummy` で通る。hours の wday unique、numbers の is_default 単一制約
- [ ] 管理画面: 電話CTAタブで本体・hours 7 行・numbers を保存でき、`is_public` を切り替えられる。権限のない管理者は 403
- [ ] `GET /v1/phone_ctas`: uuid と name だけの配列、非公開店舗が出ない
- [ ] `GET /v1/phone_ctas/:uuid`: campaign が期間外で null、logo_url が既存 API と同じ URL 形式、電話番号は numbers のもののみ、非公開・不在 uuid で 404、両アクションで `Cache-Control` ヘッダーが付く
- [ ] `reception_text` 自動生成: 全曜日同じ／平日と土日で異なる／定休日あり の 3 パターン
- [ ] 管理画面で保存後、API の Rails.cache が破棄される

### madoguchi-blocks
- [ ] `Reception::is_open` の PHPUnit: 時間内／時間外／曜日行なし／日跨ぎ（当日側・翌日早朝側）／24 時間／境界値（開始ちょうど・終了ちょうど）
- [ ] `Tel::to_href`: `0120-000-000` → `tel:+81120000000`、`050-1234-5678` → `tel:+815012345678`
- [ ] Repository: transient ヒット時に `wp_remote_get` が呼ばれない／通信失敗時に店舗別の last_good で描画／404 でカードが消え last_good も削除される／`refresh` で再取得
- [ ] 設定ページ: サービス別 URL の保存、URL 空のサービスがエディタに出ない、「今すぐ更新」で transient が消える
- [ ] エディタ: サービス→店舗→番号のプルダウン、3 件上限、上下並び替え、上書き文言、Notice（URL 未設定／取得失敗／マスタに無い店舗）
- [ ] render.php（phone-cta）: 1/2/3 件で列クラスが変わる、時間内・時間外×fallback 有無の 3 状態、通話料注記、キャンペーン表示/非表示、非公開店舗のスキップ、0 件で空出力、`isVisible=false` で空出力
- [ ] render.php（footer）: 4 状態の出し分け、2 つ目以降が出ない、サービス別の既定文言
- [ ] `npm run lint:js` / `bash build.sh` が通り、`build/style-rest.css` に `.phone-cta` `.phone-cta-footer` が `all:revert` 付きで含まれる
- [ ] REST 配信で `<style id="madoguchi-blocks-inline">` に両ブロックの CSS が入る（記事に含まれる時のみ）
- [ ] 敵対的テーマ CSS 下でカード・フッターが崩れない

### ekaitori.com
- [ ] フッターブロックのある記事で StickyBottomCta が出ず、WP 出力のフッターだけが表示される
- [ ] フッターブロックのない記事では従来どおり StickyBottomCta が出る
- [ ] SP 実機で電話ボタンから発信画面が開く。PC で番号がカード内に表示される
- [ ] 受付終了時刻をまたいでリロードするとボタンが切り替わる（staging で時刻を操作、または店舗の受付時間を短く設定して確認）
