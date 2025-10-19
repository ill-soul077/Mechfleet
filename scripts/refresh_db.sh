#!/usr/bin/env bash
set -euo pipefail

# Refresh MySQL schema and seed data for Mechfleet
# Usage: scripts/refresh_db.sh [DB_NAME]
# Default DB_NAME: mechfleet
# Requires: mysql CLI in PATH and root access without password (adjust as needed)

DB_NAME="${1:-mechfleet}"
MYSQL_ARGS=${MYSQL_ARGS:-"-u root"}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="${SCRIPT_DIR}/.."
cd "$REPO_ROOT"

echo "[1/2] Applying DDL (sql/ddl.sql)..."
mysql $MYSQL_ARGS < sql/ddl.sql

echo "[2/2] Seeding data into ${DB_NAME} (sql/dml_seed.sql)..."
mysql $MYSQL_ARGS "${DB_NAME}" < sql/dml_seed.sql

echo "Done. Database refreshed successfully. (DB=${DB_NAME})"
