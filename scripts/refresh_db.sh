#!/usr/bin/env bash
set -euo pipefail

# Refresh MySQL schema and seed data for Mechfleet
# Requires: mysql CLI in PATH and root access without password (adjust as needed)

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="${SCRIPT_DIR}/.."
cd "$REPO_ROOT"

echo "[1/2] Applying DDL (sql/ddl.sql)..."
mysql -u root < sql/ddl.sql

echo "[2/2] Seeding data into mechfleet_db (sql/dml_seed.sql)..."
mysql -u root mechfleet_db < sql/dml_seed.sql

echo "Done. Database refreshed successfully."
