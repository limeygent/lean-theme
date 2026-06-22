# Lean Theme — Performance Playbook

How to get a Lean Theme page to PSI mobile ~100. Proven on The Roc Foundation Repair:
blog posts **93 → 100**, home page **87 → 99** (the 99 is lab noise off 100).

Stack assumed: WordPress on Cloudways + **Breeze** (page cache) + **Perfmatters** (asset optimization).

---

## TL;DR — the levers that actually move the score

1. **Every image is WebP and right-sized.** Convert JP/PNG → WebP, compress, and serve at ~display size (not 900px in a 680px slot). The LCP image matters most.
2. **The LCP element must be discoverable + high priority.** If the hero is a CSS `background:url()`, it is invisible to the preload scanner — preload it (the theme now does this automatically; see below).
3. **Fonts: `font-display: optional` + preload.** `optional` gives zero CLS but the webfont must arrive in ~100 ms or it is dropped ("webfont not used"). Preloading makes it land in time.
4. **Perfmatters: Remove Unused CSS = Inline, Defer + Delay JS.** This is what kills render-blocking. Breeze does caching + minify only.
5. **Subset icon/glyph fonts** to the glyphs actually used.

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

## Part 3 — Per-site plugin setup (Perfmatters + Breeze)

The theme seeds Perfmatters on activation, but verify:

**Perfmatters**
- CSS → Remove Unused CSS: **ON**, Used CSS Method: **Inline** (empty = "Inline (Default)" in current
  versions), Stylesheet Behavior: Delay.
- JavaScript → Defer: **ON**. Delay: **ON**, "Only Delay Specified Scripts", inclusion: `bootstrap.bundle`
  (keeps the mobile nav working on first tap).
- Minify: leave OFF (Breeze does it).

**Breeze** — caching + minify ONLY. Turn **off** Combine/Defer CSS & JS so it doesn't fight Perfmatters.

**After any CSS or font change:** Perfmatters → Clear Used CSS, then purge Breeze.
**After a content/image change:** purge Breeze.

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

## Part 6 — Deploy workflow (this theme is uploaded manually)

1. Make theme changes locally, commit + push to `limeygent/lean-theme`.
2. Upload the changed theme files to the site.
3. If CSS/fonts changed: Perfmatters → Clear Used CSS. Always purge Breeze.
4. Re-test PSI mobile (fresh, incognito) — browser font/asset cache can mask changes.
