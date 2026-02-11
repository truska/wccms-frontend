#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage:
  tools/new_site_bootstrap.sh --target-dir DIR --frontend-repo URL --cms-repo URL [options]

Options:
  --frontend-branch BRANCH   Frontend branch (default: main)
  --cms-branch BRANCH        CMS branch (default: main)
  --cms-dir DIR              CMS folder name (default: wccms)
  --profile PROFILE          Branch profile: live|dev (default: live)
EOF
}

log() {
  printf '[bootstrap] %s\n' "$*"
}

TARGET_DIR=""
FRONTEND_REPO=""
FRONTEND_BRANCH="main"
CMS_REPO=""
CMS_BRANCH="main"
CMS_DIR="wccms"
PROFILE="live"

while [ "$#" -gt 0 ]; do
  case "$1" in
    --target-dir)
      TARGET_DIR="$2"; shift 2 ;;
    --frontend-repo)
      FRONTEND_REPO="$2"; shift 2 ;;
    --frontend-branch)
      FRONTEND_BRANCH="$2"; shift 2 ;;
    --cms-repo)
      CMS_REPO="$2"; shift 2 ;;
    --cms-branch)
      CMS_BRANCH="$2"; shift 2 ;;
    --cms-dir)
      CMS_DIR="$2"; shift 2 ;;
    --profile)
      PROFILE="$2"; shift 2 ;;
    *)
      echo "Unknown argument: $1" >&2
      usage
      exit 1 ;;
  esac
done

if [ -z "$TARGET_DIR" ] || [ -z "$FRONTEND_REPO" ] || [ -z "$CMS_REPO" ]; then
  usage
  exit 1
fi

case "$PROFILE" in
  live)
    : "${CMS_BRANCH:=main}" ;;
  dev)
    CMS_BRANCH="staging" ;;
  *)
    echo "Unknown profile: $PROFILE (expected live or dev)" >&2
    exit 1 ;;
esac

if [ -e "$TARGET_DIR" ] && [ "$(find "$TARGET_DIR" -mindepth 1 -maxdepth 1 2>/dev/null | wc -l)" -gt 0 ]; then
  echo "Target dir is not empty: $TARGET_DIR" >&2
  exit 1
fi

mkdir -p "$TARGET_DIR"
log "Cloning frontend repo ${FRONTEND_REPO} (${FRONTEND_BRANCH})"
git clone --branch "$FRONTEND_BRANCH" "$FRONTEND_REPO" "$TARGET_DIR"

cat > "$TARGET_DIR/tools/site_sync.conf" <<EOF
FRONTEND_BRANCH=${FRONTEND_BRANCH}
CMS_REPO=${CMS_REPO}
CMS_BRANCH=${CMS_BRANCH}
CMS_DIR=${CMS_DIR}
EOF

log "Created sync config: $TARGET_DIR/tools/site_sync.conf"
log "Initializing CMS repo ${CMS_REPO} (${CMS_BRANCH})"
(
  cd "$TARGET_DIR"
  ./tools/site_sync.sh init-cms
)

log 'Bootstrap complete.'
