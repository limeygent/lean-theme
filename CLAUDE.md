# Lean Theme

## Repository
- **GitHub:** https://github.com/limeygent/lean-theme
- **Branch:** main

## Overview
A lightweight WordPress theme/module with built-in SEO functionality, Bootstrap CSS, custom shortcodes, and migration tools from Yoast SEO.

## Usage Modes
- **Standalone:** Use as the active WordPress theme (functions.php loads lean-loader.php)
- **Integration:** Copy into existing theme as `/lean/` subfolder, add to functions.php:
  ```php
  require_once get_template_directory() . '/lean/lean-loader.php';
  ```

## Key Constants (defined in lean-loader.php)
- `LEAN_THEME_DIR` - Absolute path to lean-theme directory
- `LEAN_THEME_URL` - URL to lean-theme directory
- `LEAN_IS_STANDALONE` - true if Lean is the active theme, false if embedded

## Project Structure
- `lean-loader.php` - Main entry point for all functionality
- `functions.php` - Standalone theme bootstrap (just includes loader)
- `/inc/` - PHP includes (SEO, settings, shortcodes, forms)
- `/css/` - Stylesheets (Bootstrap, custom styles)
- `/template-parts/` - Theme template partials
- `/code-snippets/` - Standalone code snippets for SEO functionality
- `/assets/` - Fonts and other assets
