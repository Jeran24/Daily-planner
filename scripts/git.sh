#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
export GIT_DIR="$repo_root/.git-meta"
export GIT_WORK_TREE="$repo_root"

exec git "$@"
