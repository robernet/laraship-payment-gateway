#!/usr/bin/env bash
# Spawn a new project from this template with a clean, secret-free export.
#
# Usage:
#   scripts/make-project.sh <slug> "<Display Name>" [--dest DIR] [--keep-apps pos,customer]
#
# It exports ONLY git-tracked files (git archive honors .gitignore), so vendor/,
# .env, and auth.json never travel. Run it from the template repo root.

set -euo pipefail

SLUG="${1:-}"; NAME="${2:-}"
if [ -z "$SLUG" ] || [ -z "$NAME" ]; then
  echo "usage: scripts/make-project.sh <slug> \"<Display Name>\" [--dest DIR] [--keep-apps a,b]"
  exit 1
fi
shift 2

DEST="../$SLUG"; KEEP=""
while [ $# -gt 0 ]; do
  case "$1" in
    --dest)      DEST="$2"; shift 2;;
    --keep-apps) KEEP="$2"; shift 2;;
    *) echo "unknown option: $1"; exit 1;;
  esac
done

# 1. Must be a git repo with at least one commit (so .gitignore is in force).
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || {
  echo "ERROR: not a git repo. Initialize the template first:"
  echo "  git init && git add -A && git commit -m 'template'"
  echo "(the provided .gitignore keeps vendor/, .env, and auth.json out of the commit)"
  exit 1; }
git rev-parse HEAD >/dev/null 2>&1 || {
  echo "ERROR: no commits yet. Run: git add -A && git commit -m 'template'"; exit 1; }

# 2. Refuse if a secret is tracked.
if git ls-files --error-unmatch backend/.env backend/auth.json >/dev/null 2>&1; then
  echo "ERROR: a secret file is tracked (backend/.env or backend/auth.json). Untrack it first:"
  echo "  git rm --cached backend/.env backend/auth.json && git commit -m 'stop tracking secrets'"
  exit 1; fi

# 3. Clean export — git archive packs ONLY tracked files.
[ -e "$DEST" ] && { echo "ERROR: $DEST already exists."; exit 1; }
mkdir -p "$DEST"
git archive --format=tar HEAD | tar -x -C "$DEST"
echo "Exported clean tree to $DEST"

cd "$DEST"

# 4. Rename substitutions (portable sed).
[ -f CLAUDE.md ]            && { sed -i.bak "s/<project>/$NAME/g" CLAUDE.md;                     rm -f CLAUDE.md.bak; }
[ -f melos.yaml ]          && { sed -i.bak "s/^name: .*/name: $SLUG/" melos.yaml;               rm -f melos.yaml.bak; }
[ -f backend/.env.example ]&& { sed -i.bak "s/^APP_NAME=.*/APP_NAME=\"$NAME\"/" backend/.env.example; rm -f backend/.env.example.bak; }

# 5. Drop the stale backend-only graph (regenerate at root later).
rm -rf backend/graphify-out

# 6. Prune unused app stubs (keep _app-template + the --keep-apps list).
if [ -n "$KEEP" ]; then
  IFS=',' read -ra K <<< "$KEEP"
  for d in apps/*/; do
    a="$(basename "$d")"
    [ "$a" = "_app-template" ] && continue
    keep=false; for k in "${K[@]}"; do [ "$a" = "$k" ] && keep=true; done
    $keep || { echo "removing unused app: $a"; rm -rf "$d"; }
  done
  echo "NOTE: update the app list in CLAUDE.md to match the kept apps."
fi

# 7. Fresh git history.
git init -q && git add -A && git commit -qm "init $SLUG from laraship-template"

cat <<STEPS

Done -> $DEST
Next steps:
  1. backend:   cp backend/.env.example backend/.env && (cd backend && composer install && php artisan key:generate)
  2. workspace: melos bootstrap                 # links packages/core into the apps
  3. UI kit:    vendor a kit into reference/<kit>/ (lib + pubspec + assets only),
                then point apps/<app>/ui-kit.md at it
  4. graph:     graphify install && /graphify .  # at ROOT; .graphifyignore excludes vendor/reference/build
  5. per app:   /ui-catalog <app>
  6. fill in:   CLAUDE.md <project> app list + each apps/<app>/CLAUDE.md role
STEPS
