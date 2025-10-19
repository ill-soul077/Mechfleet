@echo off
REM Refresh MySQL schema and seed data for Mechfleet
REM Usage:
REM   scripts\refresh_db.bat            (uses default DB: mechfleet)
REM   scripts\refresh_db.bat mechfleet_db  (override DB name)
REM Requires mysql in PATH and root access without password (adjust as needed).

setlocal enabledelayedexpansion
set DB_NAME=%1
if "%DB_NAME%"=="" set DB_NAME=mechfleet
REM Optional: set MYSQL_ARGS to e.g. -p or -h host -u user
if "%MYSQL_ARGS%"=="" set MYSQL_ARGS=-u root

pushd "%~dp0.."

echo [1/2] Applying DDL (sql\ddl.sql)...
mysql %MYSQL_ARGS% < sql\ddl.sql
if errorlevel 1 (
  echo ERROR: Failed to apply DDL. Ensure MySQL is running and root has no password or adjust the command.
  popd & exit /b 1
)

echo [2/2] Seeding data into %DB_NAME% (sql\dml_seed.sql)...
mysql %MYSQL_ARGS% %DB_NAME% < sql\dml_seed.sql
if errorlevel 1 (
  echo ERROR: Failed to seed data. Ensure database '%DB_NAME%' exists and MySQL is accessible.
  popd & exit /b 1
)

echo Done. Database refreshed successfully. (DB=%DB_NAME%)
popd
endlocal
