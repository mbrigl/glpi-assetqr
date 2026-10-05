#!/usr/bin/env bash
# Build the release archive dist/assetqr-<version>.tar.bz2 from the committed HEAD.
#
# The archive contains a single "assetqr/" directory (the plugin key), as required by
# the GLPI plugins catalog / marketplace. Files marked export-ignore in .gitattributes
# (devcontainer, tools, CI config) are left out.
set -euo pipefail

cd "$(dirname "$0")/.."

KEY=assetqr
VERSION=$(sed -n "s/^define('PLUGIN_ASSETQR_VERSION', '\(.*\)');/\1/p" setup.php)

if [ -z "$VERSION" ]; then
  echo "Could not read PLUGIN_ASSETQR_VERSION from setup.php" >&2
  exit 1
fi

# On a tag build, the tag must match the plugin version (v1.2.3 <-> 1.2.3)
if [ -n "${GITHUB_REF_NAME:-}" ] && [ "${GITHUB_REF_TYPE:-}" = "tag" ] && [ "$GITHUB_REF_NAME" != "v$VERSION" ]; then
  echo "Tag $GITHUB_REF_NAME does not match plugin version $VERSION" >&2
  exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
  echo "Warning: uncommitted changes are NOT included in the archive" >&2
fi

# Compiled translations must be committed and up to date
if command -v msgfmt >/dev/null; then
  tools/locales.sh compile >/dev/null
  if [ -n "$(git status --porcelain -- locales)" ]; then
    echo "locales/*.mo are out of date: run tools/locales.sh compile and commit" >&2
    exit 1
  fi
fi

mkdir -p dist
ARCHIVE="dist/$KEY-$VERSION.tar.bz2"
git archive --format=tar --prefix="$KEY/" HEAD | bzip2 -9 > "$ARCHIVE"

echo "$ARCHIVE"
