#!/usr/bin/env bash
# Extract translatable strings, update the .po files and compile them to .mo.
#
# Usage: tools/locales.sh            extract + update + compile
#        tools/locales.sh compile    compile only
set -euo pipefail

cd "$(dirname "$0")/.."

DOMAIN=assetqr
LANGS=(de_DE en_GB es_ES fr_FR it_IT)
POT="locales/$DOMAIN.pot"

# Only calls that pass the plugin domain (last argument) are extracted;
# core strings such as __('Print') are translated by GLPI itself.
KEYWORDS=(
  --keyword=__:1,2t
  --keyword=_x:1c,2,3t
  --keyword=_sx:1c,2,3t
  --keyword=_n:1,2,4t
  --keyword=_nx:1c,2,3,5t
)

mkdir -p locales

if [ "${1:-}" != "compile" ]; then
  common=(--from-code=UTF-8 --no-wrap --sort-by-file --package-name="$DOMAIN" "${KEYWORDS[@]}" -o "$POT")

  find . -name '*.php' -not -path './vendor/*' -not -path './.devcontainer/*' | sort \
    | xargs xgettext --language=PHP "${common[@]}"
  # xgettext has no Twig parser; its Python parser understands the __('…', 'domain') calls
  find templates -name '*.twig' | sort \
    | xargs xgettext --language=Python --join-existing "${common[@]}"

  for lang in "${LANGS[@]}"; do
    po="locales/$lang.po"
    if [ -f "$po" ]; then
      msgmerge --quiet --update --backup=none --no-wrap "$po" "$POT"
    else
      msginit --no-translator --no-wrap --locale="$lang.UTF-8" --input="$POT" --output-file="$po"
    fi
  done
fi

for lang in "${LANGS[@]}"; do
  msgfmt --check --output-file="locales/$lang.mo" "locales/$lang.po"
done

echo "Locales compiled: ${LANGS[*]}"
