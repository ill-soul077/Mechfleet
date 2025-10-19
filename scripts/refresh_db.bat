@echo off
REM Refresh MySQL schema and seed data for Mechfleet
REM Usage: double-click or run from a terminal. Requires mysql in PATH and root access without password.

setlocal enabledelayedexpansion
pushd "%~dp0.."

echo [1/2] Applying DDL (sql\ddl.sql)...
mysql -u root < sql\ddl.sql
if errorlevel 1 (
  echo ERROR: Failed to apply DDL. Ensure MySQL is running and root has no password or adjust the command.
  popd & exit /b 1
)

echo [2/2] Seeding data into mechfleet_db (sql\dml_seed.sql)...
mysql -u root mechfleet_db < sql\dml_seed.sql
if errorlevel 1 (
  echo ERROR: Failed to seed data. Ensure database exists and MySQL is accessible.
  popd & exit /b 1
)

echo Done. Database refreshed successfully.
popd
endlocal
