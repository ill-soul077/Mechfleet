#!/usr/bin/env bash
set -euo pipefail

# Refresh MySQL schema and seed data for Mechfleet
# Usage: scripts/refresh_db.sh [DB_NAME]
# Default DB_NAME: mechfleet
# Requires: mysql CLI in PATH and root access without password (adjust as needed)

DB_NAME="${1:-mechfleet}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="${SCRIPT_DIR}/.."
cd "$REPO_ROOT"

echo "[1/2] Applying DDL (sql/ddl.sql)..."
mysql -u root < sql/ddl.sql

echo "[2/2] Seeding data into ${DB_NAME} (sql/dml_seed.sql)..."
mysql -u root "${DB_NAME}" < sql/dml_seed.sql

echo "Done. Database refreshed successfully. (DB=${DB_NAME})"
