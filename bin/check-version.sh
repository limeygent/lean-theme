#!/usr/bin/env bash
#
# Refuses a push that ships code without bumping the version.
#
# The theme's canonical version is the `Version:` header in style.css — WordPress
# reads it, and it is the only thing that tells you, from wp-admin, which build a
# site is running. It once sat at 1.1.0 for 45 commits, which made "did the dev
# site get the new zip?" unanswerable.
#
# Run manually, or automatically via .githooks/pre-push:
#   git config core.hooksPath .githooks
#
# Exit 0 = safe to push.
set -uo pipefail

cd "$(git rev-parse --show-toplevel)" || exit 1

ok=0
pass() { printf '  \033[32m✓\033[0m %s\n' "$1"; }
fail() { printf '  \033[31m✗\033[0m %s\n' "$1"; ok=1; }
info() { printf '    %s\n' "$1"; }

# Greatest of two semvers, via sort -V.
version_gt() { [ "$1" != "$2" ] && [ "$(printf '%s\n%s\n' "$1" "$2" | sort -V | tail -1)" = "$1" ]; }

theme_version() { sed -n 's/^ *Version: *\([0-9][^ ]*\).*/\1/p' style.css | head -1; }

echo "lean-theme"

VERSION="$(theme_version)"
if [ -z "$VERSION" ]; then
  fail "could not parse Version: from style.css"
else
  pass "style.css Version: $VERSION"
fi

# 1. The packaged zip must carry the same version as the working tree, or you are
#    about to ship a stale build under a fresh version number.
if [ -f lean-theme.zip ]; then
  ZIP_VERSION="$(unzip -p lean-theme.zip lean-theme/style.css 2>/dev/null | sed -n 's/^ *Version: *\([0-9][^ ]*\).*/\1/p' | head -1)"
  if [ -z "$ZIP_VERSION" ]; then
    fail "lean-theme.zip has no readable style.css Version"
  elif [ "$ZIP_VERSION" != "$VERSION" ]; then
    fail "lean-theme.zip is stale: ships $ZIP_VERSION, working tree is $VERSION"
    info "rebuild the zip before pushing"
  else
    pass "lean-theme.zip matches the working tree ($ZIP_VERSION)"
  fi
fi

# 2. If code changed since the last release tag, the version must have moved too.
LAST_TAG="$(git tag --list 'v*' --sort=-v:refname | head -1)"
if [ -z "$LAST_TAG" ]; then
  info "no release tag yet — tag this release as v$VERSION"
else
  LAST_VERSION="${LAST_TAG#v}"
  # Only count source changes; a zip-only or changelog-only commit is not a release.
  CHANGED="$(git diff --name-only "$LAST_TAG"..HEAD -- \
      '*.php' '*.js' '*.css' 'template-parts' 'inc' \
      ':(exclude)lean-theme.zip' | wc -l | tr -d ' ')"
  if [ "$CHANGED" -gt 0 ] && [ "$VERSION" = "$LAST_VERSION" ]; then
    fail "$CHANGED source file(s) changed since $LAST_TAG but Version is still $VERSION"
    info "bump style.css and add a CHANGELOG section"
  elif [ "$CHANGED" -gt 0 ] && ! version_gt "$VERSION" "$LAST_VERSION"; then
    fail "Version $VERSION is not greater than the last tag $LAST_VERSION"
  else
    pass "Version $VERSION vs last tag $LAST_TAG ($CHANGED source file(s) changed)"
  fi
fi

# 3. The version must be documented.
if [ -f CHANGELOG.md ] && ! grep -q "^## \[$VERSION\]" CHANGELOG.md; then
  fail "CHANGELOG.md has no '## [$VERSION]' section"
fi

# 4. The plugin lives in its own repo but is developed here; check it when present.
PLUGIN=plugin/nerdpress-seo
if [ -f "$PLUGIN/nerdpress-seo.php" ]; then
  echo
  echo "nerdpress-seo"
  HEADER="$(sed -n 's/^ \* Version: *\([0-9][^ ]*\).*/\1/p' "$PLUGIN/nerdpress-seo.php" | head -1)"
  CONST="$(sed -n "s/^define('NERDPRESS_VERSION', *'\([^']*\)').*/\1/p" "$PLUGIN/nerdpress-seo.php" | head -1)"
  if [ "$HEADER" != "$CONST" ]; then
    fail "header Version ($HEADER) != NERDPRESS_VERSION ($CONST)"
    info "the constant drives asset cache-busting and the admin badge"
  else
    pass "header Version == NERDPRESS_VERSION ($HEADER)"
  fi
  if [ -f "$PLUGIN/CHANGELOG.md" ] && ! grep -q "^## \[$HEADER\]" "$PLUGIN/CHANGELOG.md"; then
    fail "plugin CHANGELOG.md has no '## [$HEADER]' section"
  fi
fi

echo
[ "$ok" -eq 0 ] && echo "version checks passed" || echo "version checks FAILED — fix the above, or push with --no-verify"
exit "$ok"
