# Known issues

## Final integration acceptance — 6 October 2026

Task 5 verified today's seed/evidence/layout/filter/progress changes with 161 passing automated checks and desktop/mobile browser checks. No new integration defect was found; local runtime and Pending reviews remained unchanged. Dashboard frontend-only tally buttons, no real email delivery and built-in-server preview bypass remain unchanged. Native PDF viewer, Apache, exhaustive accessibility/cross-browser behavior and full browser submission-to-saved-decision-to-relogin flow remain unverified. Details: `outputs/task-handoffs/task-5-acceptance.md`.

## Dashboard alert grouping/filter follow-up — 6 October 2026

Resolved in task 2: duplicate student alert cards, action-based pagination counts, filter navigation returning to the top and pagination dropping unrelated query parameters. Each card retains all issue destinations; severity filters use highest severity, while Pending matches any outstanding submission. Browser keyboard select changes and desktop/mobile layout were verified. Immediate select filtering depends on JavaScript; there is no Apply filters button. Existing display-only review decisions and preview authorization issues below remain unresolved and outside task 2.

Recorded 5 October 2026 during the pre-push review. These issues remain unresolved in this prototype.

## Dashboard review buttons do not persist decisions

`assets/submission-reviews.js` enables the dashboard review buttons and changes the displayed badge when clicked. It does not submit the decision to PHP. Refreshing loses the displayed decision, and the submission status, audit history, and verified stay progress remain unchanged.

Fix: connect the controls to the authenticated, CSRF-protected server review handler and update the display only after a successful save. Alternatively, keep the controls disabled or explicitly label them as a display-only preview. The separate `submission-review.php` page already provides server-side approval and rejection.

Verification: approve and reject from the dashboard, reload, and confirm that the persisted status, audit history, pending queue, and relevant progress/check-in values reflect the decision. Failed saves must not display success.

## Dashboard preview bypasses administrator authentication

`dashboard.php` permits `?preview=review` without administrator authentication when served by PHP's built-in server (`PHP_SAPI === 'cli-server'`). The preview displays sample student data and demonstration reviews. POST saves are blocked, but the normal administrator access requirement is bypassed.

Fix: require administrator authentication for the preview, or explicitly enable it through a development-only configuration that defaults to off. Do not use the server type alone as permission to bypass authentication.

Verification: anonymous requests and student sessions must not access the administrator preview under default configuration, including when using PHP's built-in server. Verify any explicitly enabled development preview remains display-only.

## Deployment scope

This is a demo repository. Hardcoded, documented student and administrator credentials use password `1234`; they are not suitable for public deployment or real student data. Existing automated tests pass but do not establish that the two issues above are resolved.

## Sample history/evidence limitation — 6 October 2026

The all-zero sample progress caused by absent verified travel has been addressed with the explicit, repeatable `php scripts/seed-demo.php` fixture seed. Empty unseeded state still correctly has zero verified days; other checkouts must run the command because `.runtime/demo.json` is ignored. This is synthetic history, not authentic verified student travel.

The three persisted sample-image Pending reviews use the visible 8 August 2025 entry / 10 July 2025 exit dates. The images identify B0901583 rather than the assigned demo students, and their remarks flag that mismatch. Do not treat these sample reviews as authentic evidence. PDF document tiles, MIME/download headers, missing attachments and ownership were tested; native browser PDF rendering and Apache runtime behavior were not visually verified. Existing dashboard-button and preview-authentication issues above remain unchanged.
