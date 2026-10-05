# ICompliance simple PHP demo

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
