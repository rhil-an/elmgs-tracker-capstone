# Database workflow handoff — 31 October 2026

## Delivered

- 40 student accounts and one admin, with individual password hashes. Original
  student@gmail.com / B2500004 and admin@gmail.com identities and passwords are
  preserved. Other emails use lowercase student ID @students.demo.test. Seed
  gives new accounts password 1234; no database login compares a universal password.
- Migrations 002 and 003 add submissions, final reviews, private BYTEA evidence
  versions, immutable runtime audit history, profile/check-in overrides and seed
  markers. Ownership FKs, two attachment slots, unique replacement/review,
  mandatory rejection reason and decision/payload checks protect the workflow.
- PHP storage adapter reuses existing validation/progress helpers. Database writes
  are atomic and serialized before reading to prevent simultaneous decisions.
- Database sessions recheck active account, role and ownership. Runtime has no
  account-update or history-update/delete permissions. Preview authentication
  bypass is closed; anonymous and student GET checks pass.
- Local mode selects PostgreSQL. Original runtime JSON remains byte-identical:
  SHA256 B2FE923589AE9FBC64F50829FCEC4534F861FB0CA7FE4CAB6FF931122099964F.
- Live demo started on port 8081 with scripts/start-postgres.ps1. Original student,
  original admin and one new student were verified against the actual database;
  all 40 logins were tested in the isolated workflow schema.
- Repeat setup/seed preserved all workflow records and user hashes. Initial
  database fixture contains 103 Verified, 4 Pending and 1 Rejected submission.
- Backup recovery compared every public table including hashes, evidence and
  audit in a temporary database. Recovery reapplies runtime grants and checks
  restricted app access; only the temporary recovery database was removed.

## Verification

| Suite | Checks |
|---|---:|
| auth | 58 |
| submissions | 31 |
| dashboard | 25 |
| progress | 24 |
| review-table | 40 |
| evidence | 19 |
| demo-seed | 23 |
| student-profile | 42 |
| student-dashboard | 11 |
| database-users | 255 |
| postgres baseline | 177 |
| postgres-workflow | 71 |

All listed suites passed. After integrating the upstream rules/logo commits, all 44 PHP files syntax-pass; git diff --check passed
with existing Git line-ending notices. HTTP checks exercise actual multipart
uploads, edited proof, review/relogin, rejected replacements, owner/CSRF/role
checks, disabled sessions and two separate concurrent database review processes.
These are automated HTTP/database checks, not a fresh visual/mobile/browser UAT.

Regression work found older baseline issues: dashboard fixture visa expiry was
within the existing 120-day warning despite expecting no warning; it now uses a
clearly distant date. Review tests counted upload forms as decision forms; the
selector now counts decision forms explicitly and retains its exact expectation.
Dashboard evidence rendering again uses the shared MIME/byte-aware helper while
retaining image edit/upload controls. Anticipated fopen failures are suppressed
and checked, so failed saves return feedback without PHP warnings breaking redirects.
No policy/counting changes were made.

Upstream rules/logo commits were preserved during integration. The student-profile
navigation test now checks the Rules link and verifies that proof submission is
still reachable through My Dashboard, matching that intentional navigation change.

## Ownership and remaining work

Database files: app/database*.php, app/postgres-store.php, migrations/*.sql,
scripts/setup-postgres.*, scripts/seed-postgres.php, scripts/start-postgres.ps1,
scripts/backup-postgres.*, scripts/list-demo-accounts.php, tests/postgres*.php,
tests/database-users.php and the database docs. Shared integration edits are in
login.php, app/bootstrap.php, app/demo-store.php, app/submissions.php,
dashboard.php, app/dashboard-reviews.php and local startup/check scripts.

The checkout already contained unrelated documentation cleanup and deleted
historical artifacts. Those changes were preserved; no commit/push/deploy or
real email was performed.

See DATABASE_SETUP.md for teammate setup, local credentials, account conventions,
backup and JSON compatibility. Local password/config files and backups stay
ignored. A PostgreSQL outage does not silently switch to JSON. New database
records do not appear in old JSON; merging/export for a real rollback still needs
a tested transfer. The snapshot adapter is appropriate to this demo, not large
production workloads. Account/reset UI, server deployment/configuration, scheduled
off-device backups, institutional policy, full browser/mobile UAT and remaining
known issues are future work. Server selection/credentials are still undecided.
