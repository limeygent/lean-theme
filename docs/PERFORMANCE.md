# Lean Theme — Performance Playbook

How to get a Lean Theme page to PSI mobile ~100. Proven on The Roc Foundation Repair:
blog posts **93 → 100**, home page **87 → 99** (the 99 is lab noise off 100).

Stack assumed: WordPress + a **page cache** + **Perfmatters** (asset optimization). The page
cache is host-dependent — **Breeze** on Cloudways, **SG Optimizer / Dynamic Cache** on
SiteGround. The rule is the same either way: the page cache does *caching only*; Perfmatters
owns all frontend optimization (RUCSS, defer/delay JS). See Part 3 for per-host setup.

---

## TL;DR — the levers that actually move the score

1. **Full-bleed marketing hero = CSS-background `<div class="hero-bg">`, NOT `<img class="hero-bg">`.** This is the single biggest lever and the least obvious — see "Hero patterns" below. An `<img>` hero *becomes* the measured LCP element; a background `<div>` isn't an LCP candidate, so the (fast) heading text is LCP instead. The theme preloads both forms, so the background image still loads early.
2. **Every image is WebP, right-sized, AND lazy-loaded below the fold.** Convert JPG/PNG → WebP, compress, serve at ~display size. Hand-coded content `<img>` without `width`/`height` do NOT get WP's auto-lazy → they load eagerly and saturate Slow-4G. Give them dimensions (Media Library) so WP lazy-loads them.
3. **The LCP element must be discoverable + high priority.** If the hero is a CSS `background:url()`, it is invisible to the preload scanner — preload it (the theme does this automatically; see below).
4. **Fonts: `font-display: optional` + preload.** `optional` gives zero CLS but the webfont must arrive in ~100 ms or it is dropped ("webfont not used"). Preloading makes it land in time.
5. **Perfmatters: Remove Unused CSS = Inline, Defer + Delay JS.** This is what kills render-blocking. The host page cache (Breeze / SG Optimizer) does caching only.
6. **Subset icon/glyph fonts** to the glyphs actually used.

---

## Hero patterns — the #1 LCP gotcha (learned the hard way)

The theme supports three hero forms. **Which one you use decides what Chrome measures as the LCP element**, and that is worth more than any amount of preload/decode/format tuning on the hero itself.

| Hero form | Markup | LCP element | Use for |
|---|---|---|---|
| **CSS background** ✅ | `<div class="hero-bg" style="background:url()">` | the **heading text** (Chrome doesn't count CSS backgrounds as LCP) → fast | **full-bleed marketing heroes** (centered headline + CTA over image) |
| **Responsive `<img>`** ✅ | `the_post_thumbnail(... 'blog-hero-img')` → `srcset`/`sizes`, wide, in-flow | the image, but small/responsive so it's genuinely fast | **blog post banners** (handled by `single.php`) |
| **Non-responsive `<img>`** ❌ | hand-coded `<img class="hero-bg">`, no `srcset`, `object-fit:cover` | the **full-size image** → measured directly → slow on throttled mobile | (avoid) |

**The trap:** an `<img class="hero-bg">` is *technically* the modern best practice (preloadable, discoverable), so it's tempting — but it promotes the image to *the measured LCP element*. On a throttled phone a big `object-fit:cover` image behind an overlay takes ~2s to rasterize/composite → "element render delay" → LCP 4.5–5s → score stuck in the high-70s/low-80s. Proven the hard way: the same render delay reproduced on **two different hosts** (SiteGround *and* Cloudways), so it was never the server — it was the hero being an `<img>`.

**The fix:** for a full-bleed hero, use the CSS-background `<div>`. The image isn't the LCP (the heading is), yet the theme still preloads it (`fetchpriority=high`), so it loads early — best of both. Same-site A/B: a `<div>`-background service hero scored 95 (LCP 2.7s) while an `<img>` hero of the *same design* scored 83 (LCP 4.7s), with a **bigger** background JPG. Only reach for an `<img>` hero when it's the wide, responsive blog-hero pattern.

---

## Part 1 — What the theme already does (automatic, all pages & sites)

These are baked into the theme; no per-page work needed:

- **`<head>` is built by `template-parts/lean-head.php`** (via `header.php`). **`wp_head()` is bypassed.**
  ⚠️ Any preload / meta / head tag must be added IN `lean-head.php`. A `wp_head` hook will NOT emit.
- **Hero preload** (`lean-head.php`): auto-detects the LCP hero and preloads it `fetchpriority=high`.
  Works for both an ACF `hero_background_image` and a content `<div class="hero-bg" style="background:url()">`.
- **`<img class="hero-bg">` hero** (`inc/performance.php`, `the_content` filter): stamps `fetchpriority="high"`
  and strips `loading="lazy"`.
- **Hero CLS guard** (`css/lean-pages.css`): `.blog-hero-img { aspect-ratio: 800/446 }` reserves the box.
- **Fonts** (`css/lean-pages.css`): Roboto `@font-face` uses `font-display: optional`; the two Roboto
  woff2 are preloaded in `lean-head.php` (`as=font crossorigin`, no `?ver` so the href matches the
  `@font-face` src exactly).
- **Bootstrap Icons font is subset** to the ~80 glyphs the CSS defines (woff2 128 KiB → 8 KiB).
- **Perfmatters config is auto-seeded on activation** (`inc/perfmatters-defaults.php` imports
  `assets/perfmatters/perfmatters-config.json`). See `assets/perfmatters/README.md`.

## Part 2 — Per-page checklist (apply to each remaining page)

1. **Inventory the page's images** (PSI "Improve image delivery", or pull page content and list
   `<img src>` + `background:url()`).
2. **Convert every JPG/PNG to WebP + compress** (Tinify — Part 4). Re-compress oversized WebPs too.
3. **Right-size**: serve at ~the displayed dimensions (a touch over for retina is fine). PSI tells you
   the displayed px.
4. **LCP image**: must be WebP, NOT `loading="lazy"`, and preloaded. Content/below-fold images SHOULD
   be `loading="lazy"` (they then don't affect LCP/score — don't over-invest compressing them).
5. **Swap URLs in page content** (images are referenced by URL; you can't overwrite an attachment's
   binary via REST, so upload a new WebP and replace the URL in the page/post content). Use clean
   filenames — no `-1` dedupe suffix.
6. **Re-test PSI mobile.** Target: LCP < 2.5 s, CLS < 0.1, TBT 0, no render-blocking.

## Part 3 — Per-site plugin setup (Perfmatters + host page cache)

The theme seeds Perfmatters on activation, but verify:

**Perfmatters** (host-independent)
- CSS → Remove Unused CSS: **ON**, Used CSS Method: **Inline** (empty = "Inline (Default)" in current
  versions), Stylesheet Behavior: Delay.
- JavaScript → Defer: **ON**. Delay: **ON**, "Only Delay Specified Scripts", inclusion: `bootstrap.bundle`
  (keeps the mobile nav working on first tap).
- Minify: leave OFF (the page-cache layer handles it, and doubling up breaks RUCSS).

**Page cache — let Perfmatters own frontend optimization; the cache does server caching only.**
The mistake to avoid on any host is *two* plugins minifying/combining/lazy-loading the same assets —
they fight and regress the score. Pick your host below:

- **Cloudways → Breeze:** caching (+ minify) ONLY. Turn **off** Combine/Defer CSS & JS so it doesn't
  fight Perfmatters.
- **SiteGround → SG Optimizer (Speed Optimizer):** use it for **caching only** (Dynamic Cache +
  Memcached + NGINX Direct Delivery). Turn **OFF** its frontend optimizations that overlap Perfmatters:
  - Frontend → CSS: minify + combine **OFF** (Perfmatters RUCSS owns CSS).
  - Frontend → JavaScript: minify + combine + defer **OFF** (Perfmatters owns JS).
  - Media → **Lazy load images OFF** ⚠️ — if SG lazy-loads the hero, it nukes LCP. The theme marks the
    hero eager/`fetchpriority=high`; a second lazy-loader re-hides it.

**After any CSS or font change:** Perfmatters → Clear Used CSS, then purge the host page cache
(Breeze: purge all; SiteGround: admin-bar "Purge SG Cache" or SG Optimizer → Caching → Purge).
**After a content/image change:** purge the host page cache.
**After switching the WP Address http → https (or any site-URL change):** the full-page cache stores
*absolute* URLs, so it can keep serving `http://` asset URLs (→ redirects → slow LCP) until flushed.
Always **Perfmatters → Clear Used CSS + purge the page cache** after a URL scheme change.

## Part 4 — Tooling

**Tinify (WebP convert + compress).** Key in `agency-dashboard/.env` → `TINIFY_API_KEY`.
POST image to `https://api.tinify.com/shrink` (Basic `api:<key>`), then POST
`{"convert":{"type":"image/webp"}}` to the result URL.

**Subset a glyph/icon font** (when the woff2 carries far more glyphs than the CSS uses):
```bash
# codepoints come from the subset CSS \fXXXX content rules
python3 -c "import re;print(','.join('U+'+c for c in sorted(set(re.findall(r'\\\\([0-9a-fA-F]{3,5})', open('css/bootstrap-icons.min.css').read())))))" > /tmp/codes
python3 -m venv /tmp/ft && /tmp/ft/bin/pip install -q fonttools brotli
/tmp/ft/bin/pyftsubset assets/fonts/<font>.woff2 --unicodes="$(cat /tmp/codes)" \
  --output-file=assets/fonts/<font>.woff2 --flavor=woff2 --no-hinting --desubroutinize
```
Keep the SAME filename → no `@font-face`/CSS change → no used-CSS regen. ⚠️ If you later add an icon to
the CSS, you MUST re-subset or that glyph renders blank.

## Part 5 — Gotchas (each one cost real debugging time)

- **`wp_head()` is bypassed** → head changes go in `lean-head.php`, not a `wp_head` hook.
- **`font-display: optional` without a preload → "webfont not used."** Always preload the body font.
  Preload `as=font` REQUIRES `crossorigin` (even same-origin) and must match the `@font-face` URL
  exactly (no `?ver`), or the browser double-downloads.
- **Don't manually preload CSS when Perfmatters RUCSS is on.** It inlines used CSS + delays the rest,
  so a `<link rel=preload as=style>` becomes "preloaded but not used" + wasted bytes. (Removed from
  the theme for this reason.)
- **CSS background images are invisible to the preload scanner** → preload them explicitly.
- **You can't overwrite an attachment's binary via REST.** Upload a new WebP, swap the URL in content,
  let the user delete the old media.
- **PSI score is noisy ±2-3.** Don't chase 99→100. "Unscored" insights (lazy below-fold images, logo
  at its size floor, unattributed forced reflow) won't change the score.
- **Never PSI-test a URL with a query string** (`?foo`, cache-buster, UTM). A query string bypasses the
  full-page cache (Breeze / SG Dynamic Cache), so every run is an *uncached* PHP render → inflated LCP
  that no real visitor sees. Test the **clean** permalink, and warm it once (load in incognito) before
  testing so PSI hits the cache. A warm SG hit is ~50 ms TTFB; a cache miss is many × that.
- **A stale cache masks every fix.** If a change (esp. http→https, see Part 3) doesn't show up, you're
  almost certainly looking at cached HTML. Clear Perfmatters Used CSS + purge the page cache, *then*
  re-test — before concluding the fix didn't work.

## Part 6 — Deploy workflow (this theme is uploaded manually)

1. Make theme changes locally, commit + push to `limeygent/lean-theme`.
2. Upload the changed theme files to the site.
3. If CSS/fonts changed: Perfmatters → Clear Used CSS. Always purge the host page cache
   (Breeze: purge all; SiteGround: "Purge SG Cache").
4. Re-test PSI mobile (fresh, incognito, **clean permalink — no query string**) — browser and
   server caches both mask changes.
