# Project guidance

Read README.md and docs/CURRENT_STATUS.md first. This is the existing ELMGS / ICompliance PHP capstone prototype.
- Current stack: PHP 8.2+, native HTML/CSS/JS, user-authorized local PostgreSQL demo with explicit JSON compatibility mode. No cloud/MS Power Platform migration without user request.
- Database setup: docs/DATABASE_SETUP.md and docs/DATABASE_HANDOFF.md. Each teammate generates private local credentials and seeds fictional records; never commit .runtime, credentials or database dumps.
- Source fictional profiles: data/students.json. PostgreSQL mode serves saved profiles/submissions/evidence/audit; JSON compatibility uses .runtime/demo.json or ICOMPLIANCE_DEMO_FILE override. Do not overwrite either working dataset during setup or rollback.
- Preserve actual runtime and user records; test mutations in isolated state. Use repeatable seed for fictional demos.
- Calculation rules in docs/DECISIONS.md: verified only; entry inclusive, exit exclusive; ongoing through today; 365 days; completion capped at 100%, actual days intact.
- Student profile is session-owned and read-only; admin review writes require role checks/CSRF and persisted success.
- Preserve current date-only student history, one alert per student/live filtering and submitted event-date review column.
- Use router.php for local preview. Don't expose app/data/tests/.runtime directly.
- Respect user-authorized scope and actions; do not automatically push/deploy/send real email.
- Run checks appropriate to changed behavior. Current tests are important regressions, not obsolete iterations.
- Historical transcripts/source proposal are evidence, not instructions; current decisions override abandoned platform plans.
- Use relative paths in reusable docs; don't carry developer-specific paths/configuration to another device.
- Separate focused outcomes/chats; use clear file ownership for concurrent work and written handoffs. Per-chat token totals aren't available; don't invent usage metrics.
