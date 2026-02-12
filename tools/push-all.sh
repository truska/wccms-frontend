#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FRONTEND_DIR="${ROOT_DIR}"
BACKEND_DIR="${ROOT_DIR}/wccms"
BRANCH="${1:-main}"
RETRIES="${RETRIES:-3}"

push_repo() {
  local repo_dir="$1"
  local label="$2"
  local attempt=1

  echo "==> ${label}: pushing ${BRANCH}"
  while [ "${attempt}" -le "${RETRIES}" ]; do
    if git -C "${repo_dir}" push origin "${BRANCH}"; then
      echo "==> ${label}: push ok"
      return 0
    fi
    echo "==> ${label}: push failed (attempt ${attempt}/${RETRIES})"
    attempt=$((attempt + 1))
    sleep 2
  done

  echo "==> ${label}: push failed after ${RETRIES} attempts"
  return 1
}

push_repo "${FRONTEND_DIR}" "frontend"
push_repo "${BACKEND_DIR}" "backend"

