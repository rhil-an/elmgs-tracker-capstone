# Known issues

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
