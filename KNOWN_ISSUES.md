# Current known issues — 14 October 2026

## Open
1. Local PostgreSQL demo is integrated and tested but not deployed or approved for real student data. The compatibility adapter reads full demo snapshots and serializes writes; larger production use should use focused queries and row-level locking.
2. Admin dashboard outer mobile overflow was observed in older browser verification; student dashboard/profile layout was improved and verified. Recheck admin current layout before claiming fixed.
3. No real email notification delivery. Notification labels aren't proof of sent email.
4. JSON uses truncate/rewrite under a file lock; partial write/failed disk durability isn't atomic. This prototype is not a production persistence design.
5. Public demo passwords, no reset/account administration, synthetic roster/stored compliance statuses and 365-day rules are not final university policy. PostgreSQL checks individual hashes; original hardcoded accounts remain only in explicit JSON fallback mode.
6. Native browser PDF, Apache parity, exhaustive accessibility/screen-reader/cross-browser behavior and complete real-user browser journey are not fully verified.

## Data limitations
Fresh copy has no runtime travel history: seed explicitly to avoid all-zero demo progress. Do not calculate from enrolment dates. Samples include over-target actual verified totals: display completion capped, actual days retained.
Sample stamps identify B0901583 rather than assigned dummy students; intentional mismatch is flagged in fixture remarks. Dummy Verified history isn't authentic travel.
Original developer's reviewed runtime state is not shipped. Fresh seed has three Pending examples; observed states on separate machines may differ legitimately.

## Resolved
- Preview access bypass: anonymous GET redirects to login and student GET is denied; preview POST remains disabled. Verified in database workflow HTTP checks.
- Dashboard display-only review buttons: fixed in 62d5124; server-saved approvals/rejections now update queue/history/progress.
- Completion labels >100%: fixed in 549d744; actual totals preserved.
- Duplicate alert cards/filter bounce: grouped student alerts and live filters resolved.
- Login blocked by PostgreSQL: dependency removed; current hardcoded PHP/local JSON prototype works.
- Missing varied demo travel: explicit repeatable synthetic seed added.

See docs/VALIDATION.md for current tested scope. Historical reports describe then-current issues; they don't override this list.
