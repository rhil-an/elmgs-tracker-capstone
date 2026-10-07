# Student profile and bounded completion — 7 October 2026

Scope A completed from HEAD 62d5124, PHP + local JSON only. No applicable AGENTS.md found. Concurrent dashboard work was left untouched; no shared README/KNOWN_ISSUES edits, commit, push, deploy, email or runtime migration.

## Owned changes / contract

- `app/progress.php`: counting remains unchanged. `verified_days` is the actual verified interval total. New `completed_days=min(verified_days,required_days)` and `requirement_met=(verified_days>=required_days)`. `percentage` and `bar_percentage` both express requirement completion, 0–100; remaining_days stays nonnegative. Required days remains 365. Entry inclusive, exit excluded, ongoing through today, verified only, overlap/deduplication unchanged.
- `stay_progress_html` name and existing classes preserved. Headline uses completed_days/required_days; requirement-met message appears at target and actual verified total appears separately above target. ARIA value text retains actual verified days and capped percentage. Shared dashboards/admin profile inherit helper behavior.
- `assets/app.js`: records display capped percentage, completed-days denominator, remaining days, requirement-met state and separate over-target actual total; accessible value text retains actual verified total. No travel dates/records were clamped.
- New EXACT route `profile-student.php`, scoped `assets/student-profile.css`, allowlist addition in `router.php`: session_student() exclusively; personal/academic rows, visa/residence, check-in summary and compact progress. No editing controls, review/audit/notification metadata, submission log or evidence details. Dashboard/proof/profile navigation and CSRF logout retained. Auth/ownership checks run before method rejection: anonymous redirects, admin/cross-owner denial, own GET only; unsupported methods return 405 with Allow: GET.
- `tests/progress.php` expanded; new `tests/student-profile.php` uses isolated JSON and real credential login. No tests modify actual runtime. `tests/demo-seed.php` required no changes.

## Checks

Progress 24, profile HTTP 39, seed/rendering 23, auth 58, dashboard 25, submissions 31 checks passed (200 total). Owned PHP syntax checks and git diff --check passed. Profile tests cover 0/24/364/365/500-day completion contract/markup; real session owner, anonymous/admin/cross-owner GET/POST, GET-only methods, no edit/private metadata, boundary warnings, escaping, independent location text and unchanged isolated store.

Browser actual student profile: 1280x900 and 390x844, stacked mobile rows, no main horizontal overflow, visible keyboard focus from proof link to logout, full ARIA actual total with 100% completion. Saved student-profile-desktop-20261007.png and student-profile-mobile-20261007.png; viewport override reset. Admin records cap is code/test verified, not separately browser-verified this run. Exhaustive screen-reader/cross-browser, missing-date browser states and Apache remain unverified. Setup unchanged: existing PHP server on 127.0.0.1:8080; student@gmail.com/1234 maps B2500004, admin@gmail.com/1234.

## Fixture investigation / next steps

Actual B2500004 persisted Verified Entry 2025-01-01, Exit 2025-07-10, Entry #104 2025-08-08 produces 190 + 426 ongoing days = 616 on 7 October. This is legitimate over-target arithmetic, not a percentage-calculation reason to rewrite dates. No identifiable erroneous fixture requiring modification found. Earlier sample evidence identity mismatch still needs human review if those records are treated as real; do not silently rewrite decisions or seed/runtime to force totals below target. Preserve synthetic labels. Runtime observed SHA256 B2FE923589AE9FBC64F50829FCEC4534F861FB0CA7FE4CAB6FF931122099964F; this work only read it.

Concurrent dashboard owner should keep using the shared helper and link name/avatar to profile-student.php. Use completed_days for requirement completion, verified_days for actual travel totals, and percentage/bar_percentage for capped visuals/ARIA. Do not recompute uncapped days/365 in JavaScript. No other-chat messaging performed.
