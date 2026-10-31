# Local PostgreSQL demo

The website uses PostgreSQL for accounts, student profiles, submissions, proof
files, reviews, check-in updates and audit history. This is a local demo, not a
deployed service. The original JSON runtime is preserved independently.

## Accounts

There are 40 student accounts and one administrator. New dummy accounts receive
password `1234` through the seed only. Login checks the saved hash and active flag;
changing a hash changes the accepted password. Setup never resets passwords.

| Account | Email | Initial demo password |
|---|---|---|
| Student B2500004 (unchanged) | student@gmail.com | 1234 |
| Administrator (unchanged) | admin@gmail.com | 1234 |
| Other students | lowercase student ID followed by @students.demo.test | 1234 |

For example, B2500001 uses `b2500001@students.demo.test`. List every account
without exposing hashes with `php -d extension=pdo_pgsql scripts/list-demo-accounts.php`.

## Teammate setup

1. Install PostgreSQL and PHP 8.2+ locally. Enable `pdo_pgsql` and `fileinfo` in
   PHP; enable `curl` for HTTP tests. Windows startup scripts enable database and
   file-info extensions for their own process, without editing global `php.ini`.
2. Create `.runtime` in the project folder if needed. Use Notepad to create
   `.runtime/postgres.pgpass` with Save as type **All files**, without `.txt`.
   Its line is `127.0.0.1:5432:*:postgres:YOUR_LOCAL_PASSWORD`. Replace the password
   locally. Escape a password's literal backslashes as `\\` and colons as `\:`.
3. Run these commands from the project folder:

```powershell
./scripts/setup-postgres.ps1
php -d extension=pdo_pgsql -d extension=fileinfo scripts/seed-postgres.php
./scripts/start-postgres.ps1
```

Open `http://127.0.0.1:8081/`. `-Port` selects a different port. Always use
`router.php` for local serving so private files cannot be fetched from the web.

Setup creates `icompliance_demo`, schema-owner `icompliance_migrator` and runtime
account `icompliance_app`. Local passwords are randomly generated into ignored
`.runtime/database.local.json`. Setup applies SQL migrations once, checking their
checksums on later runs. It inserts missing students/accounts without overwriting
profiles, passwords or decisions. Conflicting database/role names from another
setup cause it to stop. Never edit applied migrations; add a new migration.

Optional setup parameters are `-DatabaseHost`, `-DatabasePort`, `-Administrator`.
Only local hosts are supported, and the password-file entry must match.

Protect both credential files with Windows permissions limited to your account;
the author's local files have these restrictions. Never commit credentials,
private runtime, evidence exports or backups. Teammates share fictional source
fixtures and setup code, not working records. Their databases do not synchronize.

## Dummy records

Profiles come from tracked fictional `data/students.json`. Explicit database seed
creates 103 Verified fictional travel declarations, three Pending travel examples,
and a rejected CheckIn with its Pending replacement. Re-running does not recreate
reviewed examples or duplicate records. Verified placeholders are fictional, not
passports. Supplied stamp photos identify B0901583 rather than assigned students;
fixture remarks retain that identity mismatch. No private JSON runtime is imported.

## PHP connection and tables

`app/database.php` opens PDO connections using private local configuration.
Web requests use `icompliance_app`, which cannot change accounts/migration history,
delete records or rewrite audits, reviews or proof versions. Maintenance scripts
use the schema owner. `app/database-permissions.php` reapplies grants after setup
or recovery. Deployment should keep maintenance secrets separate from the web
process's configuration entirely.

`app/postgres-store.php` converts relational records into the existing helper
contract, retaining shared validation and progress rules. A write obtains a
transaction lock before reading, then commits submission/evidence/review/audit
changes together. A second reviewer sees the first saved decision and cannot
overwrite it. Failed writes roll back. This simple demo adapter reads full
snapshots and serializes writes; production scale should use focused queries
and row locks.

| Table | Purpose |
|---|---|
| students | Baseline personal/academic profiles |
| users | Individual password hashes, roles, active flags and student link |
| submissions | Student-owned declarations, dates, status and replacement link |
| submission_reviews | One final decision with administrator and reason |
| evidence_files | Attachment identity, student/submission link, slot 1 or 2 |
| evidence_versions | Original and edited bytes, version number, checksum and actor |
| audit_events | Append-only actions with actor, time and relevant before/after details |
| student_overrides | Academic edits and accepted check-in observations |
| seed_batches | Applied fictional fixture batches |
| schema_migrations | Applied SQL versions and checksums |

Submission JSONB payload preserves existing helper fields. Relational columns
support ownership/status/date constraints, and database checks require their
values to agree with the payload. Proof is stored as BYTEA; base64 exists only
at the compatibility boundary. Edited proof retains its original version.

Progress uses the existing PHP calculator: Verified travel only, entry included,
exit excluded, ongoing through today in Malaysia, duplicates do not double-count,
365-day target, display capped while actual days remain. Refreshing tomorrow
updates an ongoing count without a daily database write.

## Startup and JSON compatibility

On the author machine, ignored `.runtime/storage.json` selects
`{"storage":"postgres"}`. `./scripts/start-local.ps1` reads that setting and starts
the correct server on port 8080. `./scripts/start-postgres.ps1` explicitly selects
PostgreSQL on port 8081 on any configured machine. Sessions, CSRF and owner/role
checks still apply. Disabled accounts lose existing session access. Preview GET
requires admin authentication; preview writes remain disabled.

Inspect the preserved JSON version separately with:

```powershell
./scripts/start-local.ps1 -Storage json -Port 8082
```

`ICOMPLIANCE_STORAGE=json|postgres` overrides the saved mode. An explicit
`ICOMPLIANCE_DEMO_FILE` forces isolated JSON storage for regression tests.
Database failure never silently switches storage. PostgreSQL edits are not copied
back to the old JSON: switching modes changes datasets. Back up PostgreSQL before
rollback. Exporting/merging newer records needs a tested transfer rather than
overwriting the original JSON file.

## Verification

```powershell
./scripts/run-checks.ps1
php tests/database-users.php
php -d extension=pdo_pgsql tests/postgres.php
php -d extension=pdo_pgsql -d extension=fileinfo -d extension=curl tests/postgres-workflow.php
```

Original suites use private JSON files. Database baseline test mutations roll
back. Workflow tests create a random schema, clone only dummy profiles/accounts,
exercise real HTTP login/upload/review/relogin/resubmission and concurrent review
requests, then remove only that schema. These tests need local HTTP listeners and
PostgreSQL access. The ignored `.runtime/php.ini`, if present, is a local test
configuration, not a shipped machine-specific configuration. Teammates should
enable required extensions in their PHP configuration for child test processes.

## Backup and recovery

```powershell
./scripts/backup-postgres.ps1 -PostgresBin 'YOUR_POSTGRES_BIN_FOLDER' -VerifyRestore
```

Omit `-PostgresBin` if PostgreSQL tools are on PATH. Without `-VerifyRestore`, only
the backup is created. Backups/logs stay private under `.runtime/backups`. A
consistent snapshot includes accounts, proof bytes and audits. Restore verification
uses the local default `postgres` administrator password file, creates a separate
temporary database, compares every public table, reapplies runtime grants, checks
application access, then removes only that temporary database. The demo source
is never restored over or deleted.

For real recovery, restore into a separate replacement database using pg_restore,
recreate required roles, reapply `database_apply_permissions()` as schema owner,
verify checks and only then change private connection configuration. Never restore
over a live database without an agreed recovery procedure. Scheduled backups,
retention, off-device storage, remote TLS settings and server deployment remain
future work.
