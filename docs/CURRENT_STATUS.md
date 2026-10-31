# Current status — 14 October 2026

## Purpose and scope
ELMGS Tracker / ICompliance supports HELP University ISSD in monitoring international postgraduate students' physical stay in Malaysia. Students submit dates and evidence; staff retain verification responsibility. The aim is reduced manual tallying, clear student progress and focused follow-up.

Base code commit: 549d744 (7 October, Add read-only student profile and redesign student dashboard). Local HEAD matched locally recorded origin/main at inspection; this handover did not fetch new remote work or claim none exists. Handover cleanup/docs are new uncommitted changes.

## Working features

PostgreSQL workflow update (31 October): local `icompliance_demo` contains 40 dummy
profiles, 40 student accounts and one administrator. Database mode now serves
login, profiles, submissions, evidence versions, reviews and audit history.
Runtime JSON is preserved as an explicit compatibility mode. Dummy seed includes
103 Verified travel events, three Pending travel examples, one rejected CheckIn
and its Pending replacement. Setup does not reset passwords or existing records.
All nine original regression suites passed (273 checks after rules/logo integration), 177 database checks
passed, 255 password helper checks passed, and 71 isolated workflow checks include all student logins and concurrent
reviews. Backup restoration matched every public table in a temporary database;
that test database was removed. No server deployment or real email. See
[DATABASE_SETUP.md](DATABASE_SETUP.md).

- Prototype student/admin login, session regeneration, logout, CSRF and page/owner access controls.
- 40 sample postgraduate profiles; student search by name/ID/nationality, status/programme filters and pagination.
- Student read-only profile: personal/academic, visa/residence, check-in and stay summary; session-only identity, no administrative audit/evidence log.
- Student dashboard with clickable profile, prominent Submit Proof, compact progress/date summaries and complete five-column history.
- Entry/Exit and CheckIn form with server date/evidence validation. Local sets Malaysia; Overseas asks country; no City/Location field.
- Saved Pending submissions, private evidence delivery, review history, audit events and linked resubmissions.
- Admin dashboard review saves Verified/Rejected through shared backend; rejection requires reason; reload updates queue, history and progress. Failed/duplicate decisions never show successful save.
- One combined alert per student; live filters without Apply Filters; severity/pagination and relevant View/action links.
- Admin profile separates merged personal/academic rows, stay progress, visa/residence and check-in; Status Guide removed.
- Verified interval calculator; percentage and completed/required display cap at 100%/365, actual verified total retained.
- MIME-aware image thumbnails and PDF document links; missing evidence displayed honestly.
- Repeatable fictional seed gives varied progress and three review examples; source stamp JPGs included.

## Not implemented / not proven
No actual SMTP/email delivery, account administration/reset, production deployment or university system integration. Real institutional programme policy and production onboarding are not finalized. Preview now requires admin authentication; JSON write durability and some mobile admin overflow remain in KNOWN_ISSUES.
Stored Warning/Compliant/Non-Compliant profile labels are sample data; they are not a fully defined dynamic risk classifier calculated from deadlines.

## Latest verified baseline
7 October combined changes: 270 tests passed across nine suites. Owners reported desktop/mobile student profile/dashboard and keyboard checks. This handover performs a new cleanup/package verification documented in VALIDATION.md. Keep results distinct; don't sum repeated runs.
Current runtime on the original machine has some reviewed seed examples; it is deliberately NOT shipped. Fresh teammate state must seed, then review examples locally. Its pending statuses can differ from the author's local state.

## Immediate direction
1. Keep a reproducible clean setup; database setup is documented and preview access bypass is fixed.
2. Verify the full browser submission -> admin decision -> student relogin journey and admin mobile overflow.
3. Confirm ISSD's real counting/assessment rules and how computed progress relates to stored compliance labels.
4. Implement email only once sender/provider, recipient testing and acceptance scope are agreed.
5. Prepare 16 October submission/evidence milestone, 30 October dashboard demo and 20 November complete-functionality demo without claiming stakeholder acceptance until it happens.
See ROADMAP.md for dates and acceptance criteria. OCR is out of MVP.
