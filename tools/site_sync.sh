#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage:
  tools/site_sync.sh pull [--config FILE] [--frontend-branch BRANCH] [--cms-branch BRANCH] [--cms-dir DIR]
  tools/site_sync.sh init-cms [--config FILE] [--cms-repo URL] [--cms-branch BRANCH] [--cms-dir DIR]

Commands:
  pull      Pull latest frontend repo and latest CMS repo (if present).
  init-cms  Clone the central CMS repo into local cms dir.

Config file:
  Default config path is tools/site_sync.conf.
  Example values:
    FRONTEND_BRANCH=main
    CMS_REPO=git@github.com:example/wccms.git
    CMS_BRANCH=main
    CMS_DIR=wccms
EOF
}

log() {
  printf '[site-sync] %s\n' "$*"
}

require_repo_root() {
  if [ ! -d .git ]; then
    echo 'Run this from the site repo root (where .git exists).' >&2
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

pull_frontend() {
  local branch="$1"
  log "Pulling frontend branch: ${branch}"
  git fetch origin
  git checkout "$branch"
  git pull --ff-only origin "$branch"
}

pull_cms() {
  local cms_dir="$1"
  local cms_branch="$2"
  if [ ! -d "$cms_dir/.git" ]; then
    log "Skipping CMS pull (${cms_dir}/.git not found)."
    return 0
  fi

  log "Pulling CMS branch: ${cms_branch}"
  git -C "$cms_dir" fetch origin
  git -C "$cms_dir" checkout "$cms_branch"
  git -C "$cms_dir" pull --ff-only origin "$cms_branch"
}

init_cms_repo() {
  local cms_repo="$1"
  local cms_branch="$2"
  local cms_dir="$3"

  if [ -d "$cms_dir/.git" ]; then
    log "CMS repo already initialized in ${cms_dir}."
    return 0
  fi

  if [ -e "$cms_dir" ] && [ "$(find "$cms_dir" -mindepth 1 -maxdepth 1 | wc -l)" -gt 0 ]; then
    echo "${cms_dir} exists and is not empty. Move/backup it first." >&2
    exit 1
  fi

  rm -rf "$cms_dir"
  log "Cloning CMS repo ${cms_repo} (${cms_branch}) into ${cms_dir}"
  git clone --branch "$cms_branch" "$cms_repo" "$cms_dir"
}

main() {
  require_repo_root

  local cmd="${1:-}"
  shift || true

  local config_file="tools/site_sync.conf"
  local frontend_branch="main"
  local cms_dir="wccms"
  local cms_repo=""
  local cms_branch="main"

  case "$cmd" in
    pull)
      while [ "$#" -gt 0 ]; do
        case "$1" in
          --config)
            config_file="$2"; shift 2 ;;
          *)
            break ;;
        esac
      done

      load_config "$config_file"
      frontend_branch="${FRONTEND_BRANCH:-$frontend_branch}"
      cms_dir="${CMS_DIR:-$cms_dir}"
      cms_branch="${CMS_BRANCH:-$cms_branch}"

      while [ "$#" -gt 0 ]; do
        case "$1" in
          --frontend-branch)
            frontend_branch="$2"; shift 2 ;;
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

      pull_frontend "$frontend_branch"
      pull_cms "$cms_dir" "$cms_branch"
      ;;

    init-cms)
      while [ "$#" -gt 0 ]; do
        case "$1" in
          --config)
            config_file="$2"; shift 2 ;;
          *)
            break ;;
        esac
      done

      load_config "$config_file"
      cms_dir="${CMS_DIR:-$cms_dir}"
      cms_repo="${CMS_REPO:-$cms_repo}"
      cms_branch="${CMS_BRANCH:-$cms_branch}"

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

      if [ -z "$cms_repo" ]; then
        echo '--cms-repo is required for init-cms.' >&2
        usage
        exit 1
      fi

      init_cms_repo "$cms_repo" "$cms_branch" "$cms_dir"
      ;;

    *)
      usage
      exit 1 ;;
  esac
}

main "$@"
