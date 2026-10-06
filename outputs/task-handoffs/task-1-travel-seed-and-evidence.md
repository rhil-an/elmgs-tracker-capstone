# Handoff #1 — sample travel and submitted evidence

Completed 6 October 2026, Asia/Kuala_Lumpur. Active checkout: `elmgs-tracker-capstone`. PHP + JSON only; no database, email, push or deployment. No applicable AGENTS.md found. Initial Git state was clean apart from the supplied `sample-entry-exit-stamp/` directory; those files and original student JSON were not modified.

## Reproduce / persisted local state

Run `php scripts/seed-demo.php`, then `php -S 127.0.0.1:8080 -t . router.php`. Login remains student@gmail.com / 1234 (B2500004), admin@gmail.com / 1234. The local `.runtime/demo.json` now contains 106 submissions, 106 private evidence records and 209 audit entries: 103 synthetic Verified events and precisely 3 Pending supplied-image reviews. No check-in override was created. The runtime file is ignored; the seed reproduces it on another checkout. The seed is explicit and atomic, preserves all existing user state, skips students already having Verified travel, allocates safe IDs in sparse state and records a batch marker. Re-running is byte-identical, including after subsequent user decisions.

## Data / calculation

Original sample JSON contains no verified travel events; its enrolment/stay dates must not become official days. Calculator unchanged: verified Entry inclusive, Exit exclusive, ongoing through today, 365 target, bar only capped at 100. On 6 October totals range 64–554 days. B2500004 has Entry 2025-01-01 / Exit 2025-07-10 = 190 days (52.1%); B2500002 Entry 2025-03-01 / Exit 2025-07-10 = 131 (35.9%); B2500003 Entry 2025-04-01 ongoing = 554 (151.8%). Other students have varied closed histories plus ongoing histories for Local samples. Synthetic history uses explicit fictional dates and clearly marked tiny placeholder PNG evidence; it does not claim real verification.

Pending rows: #104 B2500004 Entry 2025-08-08; #105 B2500003 Exit 2025-07-10; #106 B2500002 Entry 2025-08-08. The entry JPEG is used for 104/106 and the exit JPEG for 105. `view_image` inspection found matching visible Malaysian stamp dates. Both photos identify B0901583, outside the roster; pending remarks flag this identity mismatch and say sample review only. These remain Pending and add no official days. Do not approve them during the next task just to demonstrate progress.

## Files changed / rendering contract

- `app/demo-store.php`: IDs allocated above maximum existing key.
- `app/submissions.php`: existing normal JSON append/decision logic extracted into shared state helpers, still wrapped by locked normal save/review operations. Role, session, validation, CSRF and ownership behavior retained.
- `app/demo-seed.php`, `scripts/seed-demo.php`: explicit repeatable synthetic fixture seeding through those same submission/evidence/audit mechanisms.
- `app/evidence-rendering.php`, `app/dashboard-reviews.php`, `assets/dashboard.css`: per-file MIME-aware rendering in Submitted image column. JPEG/PNG thumbnail + full view + download; PDF document tile + view + download (never img); multiple files individually rendered; missing bytes/unsupported MIME show unavailable; empty attachments show no evidence. All file URLs use authenticated `evidence.php`, never a direct source path.
- `tests/demo-seed.php`, `tests/evidence.php`: repeatability, preserved/sparse state, actual persisted records, calculation exclusion, mixed/missing rendering, private evidence MIME/bytes/ownership tests.
- `README.md`, `KNOWN_ISSUES.md`, this handoff and verification JPEGs.

## Verification / next-task limitations

153 automated checks pass: auth 51, submissions 29, progress 14, dashboard 17, seed/rendering 23, evidence HTTP 19. All 28 PHP files syntax-pass; git diff --check passes. Browser: actual admin login, three loaded JPEG thumbnails (1240 × 1755 source dimensions), B2500004's real profile history and 190-day progress, desktop layout and mobile contained table scrolling. Screenshot files `task-1-reviews.jpg` and `task-1-progress.jpg` accompany this handoff. PDF tile and serving are test-verified; browser-native PDF rendering and Apache remain unverified.

Existing dashboard buttons still only change frontend badges; do not infer persisted approval from those clicks. Separate submission-review.php remains the saved review route. Development `?preview=review` still bypasses admin authentication; untouched as unrelated to this assignment. Removed public sample-SVG attachment rendering from the Submitted image column: it now always follows persisted evidence, so preview-only rows have no evidence instead of pretend photos. Review preview and dashboard filters/profile design were not modified. Visa <=30 days, check-in >30 days and Overseas-alone rule are unchanged. Tests use temporary stores; the actual three Pending rows remain intact. Next task should preserve `.runtime/demo.json` and shared rendering contract and not delete/reset the local state.
