@echo off
setlocal enabledelayedexpansion
REM ==========================================
REM TrimatricSaasDev: One-click GitHub Sync
REM - Includes .env and DB folder
REM - Uses your explicit repo + path
REM - Creates docs/PROJECT_MAP.txt each run
REM ==========================================

REM >>> EDIT ONLY IF YOU MOVE THE PROJECT OR CHANGE REPO <<<
set "PROJECT_ROOT=C:\xampp\htdocs\laravel\TrimatricSaasDev"
set "REPO_URL=https://github.com/salahuddin081402/TrimatricSaasDev.git"
set "GIT_USER_EMAIL=salahuddin081402@gmail.com"
set "GIT_USER_NAME=Salahuddin Ahmed"
REM <<< END EDIT SECTION <<<

REM Jump to project root
if not exist "%PROJECT_ROOT%\" (
  echo [ERROR] PROJECT_ROOT not found: %PROJECT_ROOT%
  exit /b 1
)
cd /d "%PROJECT_ROOT%"

REM Verify Git
git --version >nul 2>&1 || (echo [ERROR] Git not found. Install Git for Windows and retry.& exit /b 1)

REM Init repo if needed
if not exist ".git" (
  echo [INFO] Initializing new git repository...
  git init || (echo [ERROR] git init failed.& exit /b 1)
)

REM Ensure current branch is main
for /f "delims=" %%b in ('git rev-parse --abbrev-ref HEAD 2^>nul') do set CURBR=%%b
if "%CURBR%"=="" (
  git checkout -b main
) else (
  if /i not "%CURBR%"=="main" git branch -M main
)

REM Configure identity if missing
for /f "delims=" %%u in ('git config user.email 2^>nul') do set GEMAIL=%%u
if "%GEMAIL%"=="" git config user.email "%GIT_USER_EMAIL%"
for /f "delims=" %%n in ('git config user.name 2^>nul') do set GNAME=%%n
if "%GNAME%"=="" git config user.name "%GIT_USER_NAME%"

REM Ensure remote 'origin' matches your repo
set "ORIGIN_URL="
for /f "delims=" %%r in ('git remote get-url origin 2^>nul') do set ORIGIN_URL=%%r
if "%ORIGIN_URL%"=="" (
  git remote add origin "%REPO_URL%" || (echo [ERROR] Failed to add remote origin.& exit /b 1)
) else (
  if /i not "%ORIGIN_URL%"=="%REPO_URL%" git remote set-url origin "%REPO_URL%"
)

REM If remote branch exists, rebase onto it to avoid divergence
git ls-remote --exit-code --heads origin main >nul 2>&1
if not errorlevel 1 (
  git fetch origin main >nul 2>&1
  git pull --rebase origin main >nul 2>&1
)

REM Build/update a quick project map
if not exist "docs" mkdir docs 2>nul
tree /F /A > "docs\PROJECT_MAP.txt"
if not exist "docs\PATHS_INDEX.md" (
  (
    echo # Paths Index (seed)
    echo app/Providers/AppServiceProvider.php
    echo app/Providers/ViewServiceProvider.php
    echo config/header.php
    echo routes/Backend
    echo resources/views/backend/layouts
    echo resources/views/backend/modules
    echo DB
  ) > "docs\PATHS_INDEX.md"
)

REM Force-include .env even if ignored
if exist ".env" git add -f .env

REM Stage everything (adds/mods/deletes), including DB/
git add -A
git add "docs\PROJECT_MAP.txt" "docs\PATHS_INDEX.md" >nul 2>&1

REM Timestamp + staged file count
for /f %%t in ('powershell -NoProfile -Command "(Get-Date).ToString(\"yyyyMMdd-HHmmss\")"') do set TS=%%t
for /f %%c in ('git diff --cached --name-only ^| find /c /v ""') do set COUNT=%%c

if "%COUNT%"=="0" (
  echo [INFO] No changes to commit.
) else (
  git commit -m "auto: sync %TS% (files: %COUNT%)"
)

REM Push to origin/main
git push -u origin main

REM Tag snapshot (ignore errors if tags disallowed)
git tag -f auto-%TS% >nul 2>&1
git push -f origin auto-%TS% >nul 2>&1

echo [DONE] Sync complete to: %REPO_URL%
endlocal
