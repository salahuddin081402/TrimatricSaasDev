@echo off
setlocal ENABLEDELAYEDEXPANSION
REM ==========================================
REM TrimatricSaasDev: One-click GitHub Sync + Raw Index (Final)
REM - Uploads ALL project files (adds/mods/deletes)
REM - Force-includes .env and DB folder
REM - Generates:
REM     docs/PROJECT_MAP.txt   (tree view)
REM     docs/PATHS_INDEX.md    (seed paths)
REM     docs/CODE_INDEX.md     (RAW links for reliable browsing)
REM - Adds local excludes to avoid tool-generated junk
REM ==========================================

REM >>> PRESET FOR YOUR PROJECT (no edits needed) <<<
set "PROJECT_ROOT=C:\xampp\htdocs\laravel\TrimatricSaasDev"
set "REPO_URL=https://github.com/salahuddin081402/TrimatricSaasDev.git"
set "GIT_USER_EMAIL=salahuddin081402@gmail.com"
set "GIT_USER_NAME=Salahuddin Ahmed"
REM <<< END PRESET >>>

REM --- Jump to project root ---
if not exist "%PROJECT_ROOT%\" (
  echo [ERROR] PROJECT_ROOT not found: %PROJECT_ROOT%
  exit /b 1
)
cd /d "%PROJECT_ROOT%"

REM --- Verify Git installed ---
git --version >nul 2>&1 || (echo [ERROR] Git not found. Install Git for Windows and retry.& exit /b 1)

REM --- Init repo if needed ---
if not exist ".git" (
  echo [INFO] Initializing new git repository...
  git init || (echo [ERROR] git init failed.& exit /b 1)
)

REM --- Ensure branch = main ---
for /f "delims=" %%b in ('git rev-parse --abbrev-ref HEAD 2^>nul') do set CURBR=%%b
if "%CURBR%"=="" (
  git checkout -b main
) else (
  if /i not "%CURBR%"=="main" git branch -M main
)
set "BRANCH=main"

REM --- Configure identity if missing ---
for /f "delims=" %%u in ('git config user.email 2^>nul') do set GEMAIL=%%u
if "%GEMAIL%"=="" git config user.email "%GIT_USER_EMAIL%"
for /f "delims=" %%n in ('git config user.name 2^>nul') do set GNAME=%%n
if "%GNAME%"=="" git config user.name "%GIT_USER_NAME%"

REM --- Ensure remote 'origin' matches your repo ---
set "ORIGIN_URL="
for /f "delims=" %%r in ('git remote get-url origin 2^>nul') do set ORIGIN_URL=%%r
if "%ORIGIN_URL%"=="" (
  git remote add origin "%REPO_URL%" || (echo [ERROR] Failed to add remote origin.& exit /b 1)
) else (
  if /i not "%ORIGIN_URL%"=="%REPO_URL%" git remote set-url origin "%REPO_URL%"
)

REM --- If remote branch exists, rebase onto it to avoid divergence ---
git ls-remote --exit-code --heads origin %BRANCH% >nul 2>&1
if not errorlevel 1 (
  git fetch origin %BRANCH% >nul 2>&1
  git pull --rebase origin %BRANCH% >nul 2>&1
)

REM --- Local excludes to prevent tool-generated junk from being re-added ---
if not exist ".git\info" mkdir ".git\info"
findstr /C:"system-commandline-sentinel-files/" .git\info\exclude >nul 2>&1 || (
  echo **/system-commandline-sentinel-files/>> .git\info\exclude
)
findstr /C:"/*.git/" .git\info\exclude >nul 2>&1 || (
  echo **/*.git/>> .git\info\exclude
)

REM --- Build/update docs/ (maps + seeds) ---
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

REM --- Force-include .env even if ignored ---
if exist ".env" git add -f .env

REM --- Stage everything (adds/mods/deletes), including DB/ ---
git add -A

REM --- Parse owner/repo for RAW links ---
set "OWNER="
set "REPONAME="
set "TMP=%REPO_URL:https://github.com/=%"
for /f "tokens=1,2 delims=/" %%a in ("%TMP%") do (
  set OWNER=%%a
  set REPONAME=%%b
)
if /i "!REPONAME:~-4!"==".git" set "REPONAME=!REPONAME:~0,-4!"

REM --- Generate docs/CODE_INDEX.md (RAW links I can always fetch) ---
set "INDEX=docs\CODE_INDEX.md"
> "%INDEX%" echo # Code Index ^(raw links^)
>>"%INDEX%" echo **Repo:** https://github.com/!OWNER!/!REPONAME! ^| **Branch:** !BRANCH!
>>"%INDEX%" echo _Generated: %DATE% %TIME%_
>>"%INDEX%" echo.
>>"%INDEX%" echo ^> **Tip:** Share this file's URL. I will open it and follow the raw links to read any source reliably.
>>"%INDEX%" echo.
for /f "usebackq delims=" %%F in (`git ls-files`) do call :ADDLINE "%%F"
git add "%INDEX%" >nul 2>&1

REM --- Commit if there are changes ---
for /f %%t in ('powershell -NoProfile -Command "(Get-Date).ToString(\"yyyyMMdd-HHmmss\")"') do set TS=%%t
for /f %%c in ('git diff --cached --name-only ^| find /c /v ""') do set COUNT=%%c

if "%COUNT%"=="0" (
  echo [INFO] No changes to commit.
) else (
  git commit -m "auto: sync %TS% (files: %COUNT%)"
)

REM --- Push to origin/main ---
git push -u origin %BRANCH%

REM --- Tag snapshot (ignore errors if tags disallowed) ---
git tag -f auto-%TS% >nul 2>&1
git push -f origin auto-%TS% >nul 2>&1

echo.
echo [DONE] Sync complete to: %REPO_URL%
echo [INFO] Open this in browser: https://github.com/!OWNER!/!REPONAME!/blob/!BRANCH!/docs/CODE_INDEX.md
echo       (I will use the raw links listed inside to read any file.)
echo.

endlocal
exit /b 0

:ADDLINE
set "F=%~1"
set "EXT=%~x1"
REM Include common source types (Blade is .php), plus SQL and docs
if /i "%EXT%"==".php"  goto :WRITE
if /i "%EXT%"==".sql"  goto :WRITE
if /i "%EXT%"==".js"   goto :WRITE
if /i "%EXT%"==".css"  goto :WRITE
if /i "%EXT%"==".md"   goto :WRITE
goto :EOF

:WRITE
>>"%INDEX%" echo - %F% ^(raw^) : https://raw.githubusercontent.com/!OWNER!/!REPONAME!/!BRANCH!/%F%
goto :EOF
