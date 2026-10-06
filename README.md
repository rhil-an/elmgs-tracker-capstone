# ICompliance simple PHP demo

## Task 5 — final integration acceptance, 6 October 2026

Today's tasks 1–4 passed final integration verification: 161 checks across auth (51), submissions (29), dashboard (25), progress (14), seed/rendering (23) and evidence HTTP (19). All 28 PHP files syntax-pass and whitespace checks pass. Browser checks used both real demo logins, desktop 1280x900/mobile 390x844, grouped alerts/live filters/reload/page reset, loaded stamp images, organized profile, compact 154px records progress and student dashboard/proof navigation/logout. Runtime JSON hash and three Pending stamp reviews stayed unchanged. No implementation repair was needed; no push/deployment/email.

Acceptance matrix, setup, demo steps, screenshots and next-stage limitations: [handoff #5](outputs/task-handoffs/task-5-acceptance.md). Dashboard tally buttons remain frontend-only, no real email is sent, and built-in-server preview authentication bypass remains. Native PDF viewer/Apache and full browser submission-to-saved-decision-to-relogin flow were not verified; this does not claim the original whole-stage workflow finished.

## Task 2 — grouped dashboard alerts (6 October 2026)

Alerts now show one card per student, keeping every pending submission and independent warning with its own Review submission or View link. Card severity is the highest applicable level. High/Medium filters use that highest level; Pending includes any student awaiting review even when their card is High/Medium. Totals and 5/10/20/all pagination count students. Select changes apply immediately, reset to page 1 and return to the Alerts heading; pagination and reload preserve chosen values and unrelated encoded query parameters. Apply filters was removed. Immediate filtering requires JavaScript; keyboard changes use native select events. Review buttons and stay calculations were not changed.

Checks: `php tests/dashboard.php` (25), `php tests/auth.php` (51), `php tests/submissions.php` (29), `php tests/progress.php` (14): 119 checks passed. All 28 PHP files syntax-pass and git diff --check passes. Actual admin login, Pending grouping, keyboard display change, parameter preservation, pagination/reload and mobile/desktop alert layout were browser-verified. Details/screenshots: `outputs/task-handoffs/task-2-grouped-alerts.md`. Local demo state was preserved. No database, email, push or deployment was added.

Login uses an explicit if/elseif credential check in `login.php`. No database, environment file, migrations, seeding or PostgreSQL extension is required.

## Run

```powershell
php -S 127.0.0.1:8080 -t . router.php
```

Open http://127.0.0.1:8080/. PHP 8.2+ is required; curl is used by the HTTP tests. The login page must be served through PHP rather than opened as a file. Use the supplied router to keep local data and helper files private. Existing Apache restrictions remain available in .htaccess.

## Login credentials

| Role | Email | Password |
| --- | --- | --- |
| Student | student@gmail.com | 1234 |
| Admin | admin@gmail.com | 1234 |

Student login maps to B2500004 and opens dashboard-student.php. Admin login opens dashboard.php. Edit the if/elseif conditions in login.php to change demo credentials. These hardcoded accounts are for the prototype.

Sessions, role/ownership checks, CSRF protection, session ID regeneration and POST logout are retained. Original top navigation, responsive screens, Asia/Kuala_Lumpur dates, visa warnings within 30 days and check-ins overdue after more than 30 days are preserved. Overseas alone is not a compliance warning.

## Data and existing screens

The 40 sample students are read from data/students.json. Existing submission, evidence, review and verified travel-progress screens remain available. Their demo state is stored in one ignored local file, .runtime/demo.json, created only on the first submission/review write. PHP needs write access to .runtime. Removing that file resets demo submissions, evidence, reviews and check-in overrides; the sample JSON stays unchanged. Notifications are labelled Demo only; no email is sent.

This local file is a lightweight prototype store, not a production persistence system. There is no account management or password recovery. Login itself does not write submission state or require backend setup.

## Verification — 5 October 2026

```powershell
php tests/auth.php
php tests/submissions.php
php tests/dashboard.php
php tests/progress.php
```

Results: 51 HTTP authentication/access checks, 29 submission validation/demo storage checks, 17 dashboard checks and 14 progress checks passed. Both actual credential logins and dashboards passed without any database extension. PHP syntax checks and git diff --check passed. Tests use isolated temporary demo state and do not change sample data. Browser visual verification was not performed.

Database helpers, migrations, seed scripts, database environment example and database-specific tests were removed. Previous source was backed up outside the active checkout in ../backups/before-simple-login-20261005-081517. No push or deployment was performed. All stage chats share this checkout; future work should use the JSON/demo helpers rather than reintroducing database requirements unless requested.

## Synthetic sample travel — 6 October 2026

The original 40 student records have no entry/exit declarations. Their enrolment/stay-start fields do not count as verified travel. To populate varied sample progress explicitly, run:

```powershell
php scripts/seed-demo.php
```

This repeatable seed uses the JSON submission/evidence/review helpers, under one exclusive store lock. It adds **103 synthetic Verified travel events** across all 40 students and **exactly three Pending sample travel reviews** to `.runtime/demo.json`. Dates are fixed fictional fixtures, not elapsed enrolment days. Synthetic records, remarks, reviewer address and placeholder image filenames clearly label them as demo history, not verified real travel. Existing students with any Verified entry/exit history are skipped rather than having synthetic travel mixed into that history. Existing submissions, evidence, audits, check-in overrides and unknown state fields are preserved. IDs are allocated above the highest existing key so sparse stores cannot be overwritten.

The seed records batch `synthetic-travel-2026-10-06-v1` inside the store. Re-running changes no records or audit entries and does not recreate pending rows that a user later reviews. It does not run implicitly at login or on dashboard reads. Keep the supplied `sample-entry-exit-stamp/` files in the checkout to run it. The local store was populated during this task; it is ignored by Git, so other checkouts must run the command too. No changes were made to `data/students.json` or the supplied JPEGs.

On 6 October 2026 the sample totals range from 64 to 554 verified days. Examples: B2500001 66 days (18.1%), B2500002 131 (35.9%), B2500003 554 (151.8%), B2500004 190 (52.1%). Percentages above 100 remain valid totals; only the displayed bar is capped. Entry counts, exit is excluded, ongoing stays count through today, target remains 365. Truly empty verified history still produces zero. Pending/rejected records never add official days.

The pending records in the initial populated store are:

| ID | Student | Event | Event date | Evidence |
| --- | --- | --- | --- | --- |
| 104 | B2500004 | Entry | 2025-08-08 | sample-entry-stamp.jpg |
| 105 | B2500003 | Exit | 2025-07-10 | sample-exit-stamp.jpg |
| 106 | B2500002 | Entry | 2025-08-08 | sample-entry-stamp.jpg |

The JPEGs were inspected visually: the Malaysian entry stamp reads 08 AUG 2025, and the exit stamp reads 10 JUL 2025. The handwritten permission date is not used as an entry date. Both images name B0901583, outside this sample roster, so every pending record explicitly flags the identity mismatch for review. Reusing these photos is a demonstration, not a claim of authentic sample-student travel. B2500004/B2500002 have closed verified synthetic stays ending 10 July 2025; B2500003 has an open synthetic stay beginning 1 April 2025 that the pending exit could close only after a real saved review.

Dashboard attachment rendering uses `review_attachments_html`: JPEG/PNG get a thumbnail, full-view and download links; PDF gets a labelled document tile, view and download links, never an img. Multiple attachments render independently. Missing/invalid bytes or unsupported MIME show Evidence unavailable; no attachments show No evidence submitted. All URLs use the existing authenticated `evidence.php` endpoint; owner/admin checks and inline/download MIME behavior remain intact. Source images are embedded privately in the local JSON store, not referenced directly from public thumbnail paths.

Additional checks:

```powershell
php tests/demo-seed.php
php tests/evidence.php
```

Results: 23 seed/rendering tests and 19 evidence HTTP tests passed, plus the existing 51 auth, 29 submission, 17 dashboard and 14 progress tests (153 total). All 28 PHP files passed syntax checks; git diff --check passed. Browser verification confirmed administrator login, three loaded supplied-image thumbnails, B2500004's 190-day profile/history, and contained table scrolling at mobile width. PDF tile/headers/bytes and missing/multiple attachment behavior were checked through tests; native browser PDF rendering was not visually tested. Dashboard review-button persistence and development preview authentication remain the known issues below/in KNOWN_ISSUES.md; this task did not change them. No email, database, push or deployment was added.

Handoff: `outputs/task-handoffs/task-1-travel-seed-and-evidence.md`.

## Task 3 — profile organization and shared progress, 6 October 2026

Admin profile now separates student/academic label-value rows, verified stay progress, visa/residence and check-in details; Status Guide removed, issues/history retained. Shared progress shows completed days, right-aligned percentage and secondary remaining days. Records keep the compact 154px progress cell. Calculator/local JSON unchanged. See `outputs/task-handoffs/task-3-profile-and-progress.md` for the next dashboard task's helper/classes/data contract, setup, 119 passing checks, desktop/mobile visual results and limitations. No reseed, push or deployment.

## Student dashboard layout — task 4, 6 October 2026

Removed the Welcome Back card. The session-derived student name and initials now sit at the upper right in a white area below the red header. One prominent Submit New Proof action is beside the dashboard heading (full width on mobile); the bottom duplicate was removed. Profile ID/nationality/programme, compliance/location/visa/check-in summaries and saved submission history remain. Shared verified-stay markup/calculation is reused unchanged in its own section. Page-specific layout lives in assets/student-dashboard.css. The history scroll region is keyboard focusable.

Verification: 119 checks passed (auth 51, submissions 29, dashboard 25, progress 14), PHP syntax and whitespace checks passed. Browser verified the student login, desktop 1280x900/mobile 390x844 layout, exactly one main proof action, visible keyboard focus after Tab from Logout, and Enter opening the proof form without submitting. Runtime JSON hash unchanged. Cross-browser/screen-reader and exhaustive mobile/history keyboard testing remain unverified. Existing admin issues in KNOWN_ISSUES.md remain outside this task. See outputs/task-handoffs/task-4-student-dashboard.md.

## Student history simplification — 6 October 2026

Student dashboard history now has five columns: Date submitted, Entry / Exit / Check-in, Travel / Check-in date, Country, Status. Dates use d M Y without times; submitted timestamps convert to Asia/Kuala_Lumpur while event dates use the saved start_date. Missing dates show Date unavailable; differing legacy start/end dates show the saved start with a clarification notice. Stored Rejected displays Needs resubmission and a small Resubmit link; storage is unchanged. All saved submissions sort by actual submitted timestamp descending, then ID descending. Verbose descriptions, remarks, review times, attachments and notification details are removed from this table. Existing progress/account/action content is preserved. Admin display-only badges still do not represent saved review decisions.

Checks: auth/history 58, progress 14, submissions 29 passed (101 total). Isolated history fixtures cover empty state, five headers, timezone/date separation, no times, rejection mapping/link, escaping, missing/legacy dates and ordering. Real student login and desktop/mobile table layout verified; horizontal scrolling is contained on mobile. Saved runtime hash unchanged. Exhaustive cross-browser/screen-reader and live rejected-record visual checks remain unverified. Handoff: outputs/task-handoffs/student-history-2026-10-06.md.
