#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage:
  tools/wccms_deploy.sh init [--config FILE] [--cms-repo URL] [--cms-branch BRANCH] [--cms-dir DIR]
  tools/wccms_deploy.sh deploy [--config FILE] [--cms-branch BRANCH] [--cms-dir DIR]
  tools/wccms_deploy.sh status [--config FILE] [--cms-dir DIR]

Commands:
  init    One-time CMS clone for a site (backend only).
  deploy  Pull latest CMS branch into local cms dir (backend only).
  status  Show CMS git remote and branch status.

Config:
  Default file: tools/site_sync.conf
  Supported keys:
    CMS_REPO=git@github.com:example/wccms-backend.git
    CMS_BRANCH=main
    CMS_DIR=wccms
EOF
}

log() {
  printf '[wccms-deploy] %s\n' "$*"
}

require_repo_root() {
  if [ ! -d .git ]; then
    echo 'Run this from the site frontend repo root (where .git exists).' >&2
    exit 1
  fi
}

load_config() {
  local config_path="$1"
  if [ -f "$config_path" ]; then
    # shellcheck disable=SC1090
    source "$config_path"
    log "Loaded config: ${config_path}"
  fi
}

init_cms_repo() {
  local cms_repo="$1"
  local cms_branch="$2"
  local cms_dir="$3"

  if [ -d "$cms_dir/.git" ]; then
    log "CMS repo already initialized in ${cms_dir}."
    return 0
  fi

  if [ -e "$cms_dir" ] && [ "$(find "$cms_dir" -mindepth 1 -maxdepth 1 2>/dev/null | wc -l)" -gt 0 ]; then
    echo "${cms_dir} exists and is not empty. Move/backup it first." >&2
    exit 1
  fi

  rm -rf "$cms_dir"
  log "Cloning CMS repo ${cms_repo} (${cms_branch}) into ${cms_dir}"
  git clone --branch "$cms_branch" "$cms_repo" "$cms_dir"
}

deploy_cms() {
  local cms_dir="$1"
  local cms_branch="$2"

  if [ ! -d "$cms_dir/.git" ]; then
    echo "CMS repo not found in ${cms_dir}. Run init first." >&2
    exit 1
  fi

  log "Deploying CMS branch: ${cms_branch}"
  git -C "$cms_dir" fetch origin
  git -C "$cms_dir" checkout "$cms_branch"
  git -C "$cms_dir" pull --ff-only origin "$cms_branch"
}

status_cms() {
  local cms_dir="$1"

  if [ ! -d "$cms_dir/.git" ]; then
    echo "CMS repo not found in ${cms_dir}." >&2
    exit 1
  fi

  git -C "$cms_dir" remote -v
  git -C "$cms_dir" status -sb
}

main() {
  require_repo_root

  local cmd="${1:-}"
  shift || true

  local config_file="tools/site_sync.conf"
  local cms_repo=""
  local cms_branch="main"
  local cms_dir="wccms"

  while [ "$#" -gt 0 ]; do
    case "$1" in
      --config)
        config_file="$2"; shift 2 ;;
      *)
        break ;;
    esac
  done

  load_config "$config_file"
  cms_repo="${CMS_REPO:-$cms_repo}"
  cms_branch="${CMS_BRANCH:-$cms_branch}"
  cms_dir="${CMS_DIR:-$cms_dir}"

  while [ "$#" -gt 0 ]; do
    case "$1" in
      --cms-repo)
        cms_repo="$2"; shift 2 ;;
      --cms-branch)
        cms_branch="$2"; shift 2 ;;
      --cms-dir)
        cms_dir="$2"; shift 2 ;;
      *)
        echo "Unknown argument: $1" >&2
        usage
        exit 1 ;;
    esac
  done

  case "$cmd" in
    init)
      if [ -z "$cms_repo" ]; then
        echo '--cms-repo is required for init (or set CMS_REPO in config).' >&2
        usage
        exit 1
      fi
      init_cms_repo "$cms_repo" "$cms_branch" "$cms_dir"
      ;;
    deploy)
      deploy_cms "$cms_dir" "$cms_branch"
      ;;
    status)
      status_cms "$cms_dir"
      ;;
    *)
      usage
      exit 1 ;;
  esac
}

main "$@"
