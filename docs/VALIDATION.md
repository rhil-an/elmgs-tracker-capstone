# Validation — 14 October 2026

## Database integration — 31 October 2026

Latest verification: 273 checks across nine original regression suites after rules/logo integration, 255
password-helper checks, 177 database baseline checks and 71 isolated workflow
checks passed. Backup recovery matched all public table data and checked restored
runtime permissions. Setup/seed repetition preserved records and password hashes.
See [database handoff](DATABASE_HANDOFF.md) for scope and limitations. The dated
handover results below describe the earlier JSON-only baseline, not the current
database stage.

## Current working-tree verification
All nine current suites passed after handover cleanup: auth58, submissions31, dashboard25, progress24, review-table40, evidence19, demo-seed23, student-profile39, student-dashboard11 = **270 checks**.
All 32 PHP files passed syntax checks; both remaining native JS files passed node --check. git diff --check passed after documentation newline cleanup. Local runtime SHA256 stayed B2FE923589AE9FBC64F50829FCEC4534F861FB0CA7FE4CAB6FF931122099964F.
Initial sandbox run couldn't connect to spawned loopback PHP servers; authorized outside-sandbox rerun passed. This was an execution-permission limitation, not a claimed app fix.
Pattern scan found no common private-key, GitHub-token, AWS access-key or sk-token patterns in included text; this is a limited scan, not proof of exhaustive secret detection.

## Reproduce
From project root:
```powershell
php tests/auth.php
php tests/submissions.php
php tests/dashboard.php
php tests/progress.php
php tests/review-table.php
php tests/evidence.php
php tests/demo-seed.php
php tests/student-profile.php
php tests/student-dashboard.php
```
Or run scripts/run-checks.ps1. Fixtures use independent temp state and temporary servers; don't substitute the developer runtime file.

## What these checks cover
Credential login/session/CSRF/roles; student profile owner/read-only behavior; submitted vs event dates/history sorting/status; real server-saved dashboard approval/rejection, reason/duplicate/failure; warning grouping/boundaries; interval overlap/365/excess-day completion; seed idempotence/state preservation; MIME/access/download of evidence; dashboard markup/navigation.

## Visual and scope limits
Prior owner evidence on 7 October covered actual student profile/dashboard desktop/mobile and keyboard navigation. This packaging task doesn't claim new visual acceptance. Native PDF browser behavior, Apache parity, exhaustive screen-reader/cross-browser and full user-facing browser journey remain unverified. Known dev-preview bypass persists.

## Packaged copy
Package manifest lists relative paths, bytes and SHA256 for included files except the manifest itself. ZIP checks and fresh-extracted-copy results are appended below after the actual run.

Fresh extracted ZIP verification succeeded: all nine suites / 270 checks passed again. Fresh seed created 103 synthetic Verified events plus 3 Pending reviews; all 40 sample students had nonzero verified days, and none displayed completion above 100%. Re-running seed left its runtime hash identical. Original developer runtime remained unchanged. Source-byte SHA256 verification passed for every included entry. Final ZIP was regenerated only to include these recorded results; no source behavior changed after verification.
