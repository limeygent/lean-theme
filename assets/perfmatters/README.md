# Perfmatters config (theme-seeded performance settings)

The theme auto-imports the file `perfmatters-config.json` in this folder **once**, on
theme activation, via `inc/perfmatters-defaults.php` — so a fresh site loads with the
tuned settings that scored PSI ~100 (Remove Unused CSS = Inline, Defer + Delay JS, etc.).

## How to (re)generate the JSON

This file must be exported from a known-good, already-tuned site so it matches the live
Perfmatters option schema exactly (hand-writing the keys is fragile across plugin versions).

1. On the tuned site: **Perfmatters → Tools → Export Settings** → download the JSON.
2. Save it here as **`perfmatters-config.json`** (this exact name).
3. Commit it with the theme.

## Behaviour / safety

- Requires the **Perfmatters plugin** to already be active. The theme never installs it.
- Imports **once per site** (flag `lean_perfmatters_defaults_imported`); it will not
  re-clobber settings a site owner later changes by hand.
- No-ops silently if the plugin is inactive or this JSON is missing/invalid.
- Preserves any existing license / API key (never overwrites it blank).
- Disable entirely:
  ```php
  add_filter( 'lean_perfmatters_autoimport', '__return_false' );
  ```
- Re-trigger on a site (e.g. after updating the JSON): delete the
  `lean_perfmatters_defaults_imported` option, then re-activate the theme — or just use
  Perfmatters → Tools → Import manually.

## The settings this seeds (for reference)

- **CSS:** Remove Unused CSS = ON, Used CSS Method = **Inline** (kills render-blocking),
  Stylesheet Behavior = Delay.
- **JavaScript:** Defer = ON; Delay = ON, "Only Delay Specified Scripts" with
  `bootstrap.bundle` in the delay list (keeps the mobile nav working on first tap).
- **Minify:** left to Breeze (do not enable Perfmatters minify on top of Breeze).
