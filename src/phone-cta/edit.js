/**
 * 電話CTA（店舗別） — エディタ表示
 * 店舗マスタから選んだ 1〜3 店舗の「電話で査定額を聞く」カードをプレビューする。
 * サービス／店舗の選択・番号・文言の上書きはサイドバーで行う。
 * マークアップは blocks/phone-cta/render.php と同じクラス構成にし、style.css を共有する。
 */

import { __ } from "@wordpress/i18n";
import {
  useBlockProps,
  RichText,
  InspectorControls,
  MediaUpload,
  MediaUploadCheck,
} from "@wordpress/block-editor";
import {
  PanelBody,
  SelectControl,
  ToggleControl,
  TextControl,
  Button,
  Notice,
} from "@wordpress/components";
import { useServices, useShopDetail } from "./use-shops";
import ShopPicker from "./shop-picker";
import CardIcon from "../condition-card/icons";
import { textsFor, splitLabel } from "./texts";

const MAX_SHOPS = 3;
const CUSTOM_BANNER = "custom";

// 同梱バナーのパターン一覧（madoguchi-blocks.php が localize。未供給なら「なし」だけ）
const BANNER_PATTERNS = (typeof window !== "undefined" &&
  window.madoguchiBlocksData?.phoneCtaBanners) || [
  { value: "", label: "バナーなし", url: "", alt: "" },
];

function bannerPatternOf(preset) {
  return BANNER_PATTERNS.find((p) => p.value === preset);
}
const MAX_POINTS = 3;

// render-helpers.php の madoguchi_blocks_phone_cta_icon() と同じ構造（白丸の中にアイコン）
function Icon({ iconKey }) {
  return (
    <span className="phone-cta__icon" aria-hidden="true">
      <CardIcon iconKey={iconKey} className="" size={18} />
    </span>
  );
}

function CardPreview({ service, item }) {
  const texts = textsFor(service);
  const { shop, loading, error } = useShopDetail(service, item.uuid);
  if (!item.uuid) {
    return (
      <li className="phone-cta__card">
        <div className="phone-cta__lead">
          <p className="phone-cta__lead-text">
            {__("店舗を選択してください", "madoguchi-blocks")}
          </p>
        </div>
      </li>
    );
  }
  if (loading) {
    return (
      <li className="phone-cta__card">
        <div className="phone-cta__lead">
          <p className="phone-cta__lead-text">
            {__("読み込み中…", "madoguchi-blocks")}
          </p>
        </div>
      </li>
    );
  }
  if (error || !shop) {
    return (
      <li className="phone-cta__card is-closed">
        <p className="phone-cta__notice">
          {__("マスタに存在しません", "madoguchi-blocks")}
        </p>
      </li>
    );
  }
  const number =
    (shop.numbers || []).find((n) => String(n.id) === String(item.numberId)) ||
    (shop.numbers || []).find((n) => n.is_default) ||
    (shop.numbers || [])[0];
  // ボタン文言は店名以外サービスごとに固定（PHP 側 View::label_parts と同じ）
  const [labelShop, labelRest] = splitLabel(texts.shopLabel, shop.name);
  const lead = item.leadText || shop.lead_text;
  return (
    <li className="phone-cta__card is-open">
      <div className="phone-cta__intro">
        <div className="phone-cta__logo-box">
          {shop.logo_url ? (
            <img
              className="phone-cta__logo"
              src={shop.logo_url}
              alt={shop.name}
            />
          ) : (
            <p className="phone-cta__name">{shop.name}</p>
          )}
        </div>
        {lead && (
          <div className="phone-cta__lead">
            <p className="phone-cta__lead-text">{lead}</p>
          </div>
        )}
      </div>
      <div className="phone-cta__action">
        <span className="phone-cta__button phone-cta__button--balloon">
          <span className="phone-cta__balloon">{texts.balloon}</span>
          <span className="phone-cta__free">{texts.freeTag}</span>
          <span className="phone-cta__button-body">
            <Icon iconKey="phone" />
            <span className="phone-cta__button-label">
              {labelShop && (
                <span className="phone-cta__button-shop">{labelShop}</span>
              )}
              <span className="phone-cta__button-rest">{labelRest}</span>
            </span>
          </span>
          <span className="phone-cta__chevron" aria-hidden="true" />
        </span>
        {shop.reception_text && (
          <p className="phone-cta__hours">
            <span className="phone-cta__hours-text">
              {`受付時間：${shop.reception_text}`}
            </span>
          </p>
        )}
      </div>
    </li>
  );
}

export default function Edit({ attributes, setAttributes }) {
  const {
    service,
    heading,
    description,
    points,
    shops,
    bannerPreset,
    bannerImageUrl,
    bannerImageAlt,
    bannerLinkUrl,
    showCampaign,
    isVisible,
  } = attributes;
  const { services, loading: servicesLoading } = useServices();
  const serviceOptions = Object.entries(services).map(([value, label]) => ({
    label,
    value,
  }));
  const noServices = !servicesLoading && serviceOptions.length === 0;
  const pointList = Array.isArray(points) ? points : [];
  const visiblePoints = pointList
    .filter((p) => String(p || "").trim() !== "")
    .slice(0, MAX_POINTS);

  const updateShop = (i, next) =>
    setAttributes({
      shops: shops.map((s, j) => (j === i ? next : s)),
    });
  const removeShop = (i) =>
    setAttributes({ shops: shops.filter((_, j) => j !== i) });
  const moveShop = (i, dir) => {
    const j = i + dir;
    if (j < 0 || j >= shops.length) {
      return;
    }
    const next = [...shops];
    [next[i], next[j]] = [next[j], next[i]];
    setAttributes({ shops: next });
  };
  const addShop = () =>
    setAttributes({
      shops: [...shops, { uuid: "", numberId: null, leadText: "" }],
    });
  const updatePoint = (i, value) => {
    const next = [...pointList];
    while (next.length < MAX_POINTS) {
      next.push("");
    }
    next[i] = value;
    setAttributes({ points: next });
  };

  // サービス切替時、見出し／説明が「切替前サービスの既定文言のまま」なら新サービスの既定文言に
  // 差し替える。ユーザーが書き換え済みのカスタム文言は上書きしない。
  const changeService = (next) => {
    const prevTexts = textsFor(service);
    const nextTexts = textsFor(next);
    const patch = { service: next, shops: [] };
    if (heading === prevTexts.heading) {
      patch.heading = nextTexts.heading;
    }
    if (description === prevTexts.description) {
      patch.description = nextTexts.description;
    }
    setAttributes(patch);
  };

  // プレビューに出すバナー（カスタムは選択済み画像、パターンは同梱画像）
  const bannerPattern = bannerPatternOf(bannerPreset);
  const previewBanner =
    bannerPreset === CUSTOM_BANNER
      ? bannerImageUrl
        ? { url: bannerImageUrl, alt: bannerImageAlt }
        : null
      : bannerPattern?.url
      ? { url: bannerPattern.url, alt: bannerImageAlt || bannerPattern.alt }
      : null;

  const blockProps = useBlockProps({
    className: `phone-cta phone-cta--cols-${Math.max(
      1,
      Math.min(3, shops.length),
    )}`,
  });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__("店舗の設定", "madoguchi-blocks")} initialOpen>
          {noServices && (
            <Notice status="warning" isDismissible={false}>
              {__(
                "設定 > Madoguchi Blocks で電話CTAの API URL を設定してください。",
                "madoguchi-blocks",
              )}
            </Notice>
          )}
          <SelectControl
            label={__("サービス", "madoguchi-blocks")}
            value={service}
            options={
              serviceOptions.length
                ? serviceOptions
                : [{ label: service, value: service }]
            }
            onChange={changeService}
            help={__(
              "サービスを変えると店舗の選択はリセットされます。",
              "madoguchi-blocks",
            )}
          />
          {shops.map((item, i) => (
            <ShopPicker
              key={i}
              service={service}
              item={item}
              index={i}
              onChange={(next) => updateShop(i, next)}
              onRemove={() => removeShop(i)}
              onMove={(dir) => moveShop(i, dir)}
              canMoveUp={i > 0}
              canMoveDown={i < shops.length - 1}
            />
          ))}
          <Button
            variant="secondary"
            onClick={addShop}
            disabled={shops.length >= MAX_SHOPS}
          >
            {shops.length >= MAX_SHOPS
              ? __("店舗は 3 件まで", "madoguchi-blocks")
              : __("店舗を追加", "madoguchi-blocks")}
          </Button>
        </PanelBody>
        <PanelBody
          title={__("先頭のバナー", "madoguchi-blocks")}
          initialOpen={false}
        >
          <SelectControl
            label={__("バナーのパターン", "madoguchi-blocks")}
            value={bannerPreset}
            options={BANNER_PATTERNS.map((p) => ({
              label: p.label,
              value: p.value,
            }))}
            onChange={(v) => setAttributes({ bannerPreset: v })}
          />
          {bannerPreset === CUSTOM_BANNER && (
            <MediaUploadCheck>
              <MediaUpload
                onSelect={(media) =>
                  setAttributes({
                    bannerImageUrl: media.url,
                    bannerImageAlt: bannerImageAlt || media.alt || "",
                  })
                }
                allowedTypes={["image"]}
                render={({ open }) => (
                  <Button variant="secondary" onClick={open}>
                    {bannerImageUrl
                      ? __("バナー画像を変更", "madoguchi-blocks")
                      : __("バナー画像を選択", "madoguchi-blocks")}
                  </Button>
                )}
              />
              {bannerImageUrl && (
                <Button
                  variant="link"
                  isDestructive
                  onClick={() => setAttributes({ bannerImageUrl: "" })}
                >
                  {__("バナー画像を削除", "madoguchi-blocks")}
                </Button>
              )}
            </MediaUploadCheck>
          )}
          {bannerPreset !== "" && (
            <>
              <TextControl
                label={__("代替テキスト", "madoguchi-blocks")}
                help={__("空ならパターン既定の文言。", "madoguchi-blocks")}
                value={bannerImageAlt || ""}
                onChange={(v) => setAttributes({ bannerImageAlt: v })}
              />
              <TextControl
                label={__("リンク先（任意）", "madoguchi-blocks")}
                help={__(
                  "空ならリンクなしの画像として出します。",
                  "madoguchi-blocks",
                )}
                value={bannerLinkUrl || ""}
                onChange={(v) => setAttributes({ bannerLinkUrl: v })}
              />
            </>
          )}
        </PanelBody>
        <PanelBody
          title={__("POINT バッジ", "madoguchi-blocks")}
          initialOpen={false}
        >
          {[0, 1, 2].map((i) => (
            <TextControl
              key={i}
              label={`POINT${i + 1}`}
              value={pointList[i] || ""}
              onChange={(v) => updatePoint(i, v)}
            />
          ))}
          <p className="description">
            {__("空にしたバッジは表示されません。", "madoguchi-blocks")}
          </p>
        </PanelBody>
        <PanelBody title={__("表示", "madoguchi-blocks")} initialOpen={false}>
          <ToggleControl
            label={__("キャンペーンを表示", "madoguchi-blocks")}
            help={__(
              "オンにするとマスタのキャンペーンをカード一覧の下に店名付きで出します（既定はオフ）。",
              "madoguchi-blocks",
            )}
            checked={showCampaign}
            onChange={(v) => setAttributes({ showCampaign: v })}
          />
          <ToggleControl
            label={__("このブロックを表示", "madoguchi-blocks")}
            help={__(
              "オフにするとブロックを残したまま表示側から消えます。",
              "madoguchi-blocks",
            )}
            checked={isVisible}
            onChange={(v) => setAttributes({ isVisible: v })}
          />
        </PanelBody>
      </InspectorControls>

      <section {...blockProps}>
        {previewBanner && (
          <div className="phone-cta__banner">
            <img
              className="phone-cta__banner-image"
              src={previewBanner.url}
              alt={previewBanner.alt}
            />
          </div>
        )}
        <header className="phone-cta__header">
          <div className="phone-cta__titles">
            <RichText
              tagName="p"
              className="phone-cta__description"
              value={description}
              onChange={(v) => setAttributes({ description: v })}
              placeholder={__("小見出し", "madoguchi-blocks")}
            />
            <RichText
              tagName="h2"
              className="phone-cta__heading"
              value={heading}
              onChange={(v) => setAttributes({ heading: v })}
              placeholder={__("見出し", "madoguchi-blocks")}
            />
          </div>
          {visiblePoints.length > 0 && (
            <ul className="phone-cta__points">
              {visiblePoints.map((p, i) => (
                <li key={i} className="phone-cta__point">
                  <span className="phone-cta__point-label">
                    {`POINT${i + 1}`}
                  </span>
                  <span className="phone-cta__point-text">{p}</span>
                </li>
              ))}
            </ul>
          )}
        </header>
        {shops.length === 0 ? (
          <p className="phone-cta__note">
            {__("サイドバーから店舗を追加してください。", "madoguchi-blocks")}
          </p>
        ) : (
          <ul className="phone-cta__list">
            {shops.map((item, i) => (
              <CardPreview key={i} service={service} item={item} />
            ))}
          </ul>
        )}
        <p className="phone-cta__note">
          {__(
            "※ 表示側では受付時間で電話／WEB査定ボタンが自動で切り替わります（プレビューは受付時間内の見た目）",
            "madoguchi-blocks",
          )}
        </p>
        {!isVisible && (
          <p className="phone-cta__notice">
            {__("このブロックは非表示に設定されています", "madoguchi-blocks")}
          </p>
        )}
      </section>
    </>
  );
}
