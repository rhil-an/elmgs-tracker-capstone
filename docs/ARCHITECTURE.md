# Architecture and data flow

## PostgreSQL update — 31 October 2026

Database mode now serves accounts/profiles and workflow records through
app/database.php and app/postgres-store.php. The adapter retains the existing
PHP helper contract and progress calculator. Explicit JSON compatibility remains
available; the local JSON runtime has not been imported or changed. See
[database setup](DATABASE_SETUP.md) for tables, transaction locking, credentials,
backup recovery, local startup and remaining deployment work. The roster/runtime
description below describes the JSON compatibility path.

## No build pipeline
PHP renders authenticated pages directly. Native JS drives record-table filtering and immediate alert filter navigation. CSS is shared with scoped student/dashboard/profile files. No npm dependencies are needed.

## File map
| Area | Files |
|---|---|
| Login/session/CSRF/roles | index.php, index.html, login.php, logout.php, app/bootstrap.php |
| Local storage/sample roster | app/demo-store.php, data/students.json |
| Input validation/persisted reviews | app/submissions.php, submission-form.php, submission-review.php |
| Evidence | evidence.php, app/evidence-rendering.php |
| Admin dashboard/alerts | dashboard.php, app/dashboard.php, app/dashboard-reviews.php, assets/dashboard-filters.js, assets/dashboard.css |
| Admin records/profile | student-records.php, student-profile.php, app/profile-history.php, assets/app.js |
| Student dashboard/read-only profile | dashboard-student.php, profile-student.php, assets/student-dashboard.css, assets/student-profile.css |
| Stay calculation/rendering | app/progress.php |
| Repeatable fixtures | app/demo-seed.php, scripts/seed-demo.php, sample-entry-exit-stamp/*.jpg |
| Local serving | router.php; .htaccess for Apache (not verified equivalently) |
| Compatibility endpoint | submission-overseas.php redirects to unified form; retain because routing/auth tests cover it |
| Current regression suites | tests/*.php |

## Roster and runtime
PHP reads JSON using file_get_contents/stream_get_contents and json_decode(..., true). Roster includes id, name, nationality, faculty/programme, status, location, lastCheckIn, visaExpiry and legacy stay-start metadata. Legacy enrolment dates DO NOT calculate physical stay.

Runtime stores keyed submissions, evidence, audit, checkins, plus seed-batch metadata. Each submission stores student_id, kind, location, country, start_date/end_date, remarks, status, submitted_at, reviewer/time/remarks, notification_status and optional resubmission_of. New records omit location_details; older data may contain it and display code handles it optionally.
Evidence bytes are base64 in JSON with id/submission_id/name/MIME/size. Authenticated evidence.php checks owner/admin and returns correct inline/download MIME.
Read/write helpers take shared/exclusive file locks. Exclusive writes currently truncate/rewrite directly; partial-write durability remains a future hardening task.

## Progress
Filter student's Verified Entry/Exit records, sort event dates, close/reopen intervals, count entry-inclusive/exit-exclusive, count ongoing through today. Duplicate/overlapping entry events do not double-count. CheckIn/Pending/Rejected excluded; uncertain legacy dates flagged.
actual=verified_days; completed=min(actual,365); percentage=round(completed/365*100,1), capped at100; remaining=max(365-actual,0).
PHP sends values to HTML and admin table JS. CSS fill width receives bounded percentage. Large actual total is preserved separately, not rewritten into travel history.

## Review workflow
Student session -> form validation -> save Pending/evidence/audit -> admin dashboard or review page POST -> require admin + valid CSRF -> shared review helper -> Verified or Rejected + reason/audit -> redirect -> pages recalculate queue/history/progress. Accepted CheckIn can update roster overrides; old travel cannot. UI success follows save only.

## Seed and portability
Seed is explicit, not run during login. Fresh state: 103 synthetic Verified events across 40 students +3 Pending stamp reviews. Re-run skips seeded batch; existing verified students aren't overwritten. Git ignores runtime, so each teammate must seed locally.
Stamp dates: Entry 8 Aug 2025, Exit 10 Jul 2025. Both photos identify B0901583; pending examples target sample students and flag mismatch. These images cannot authenticate those students.
