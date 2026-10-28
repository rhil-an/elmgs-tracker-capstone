<?php
declare(strict_types=1);
require __DIR__.'/app/submissions.php';

$user = require_user('admin');

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$error = '';
$feedback = $_SESSION['submission_review_feedback'] ?? null;
unset($_SESSION['submission_review_feedback']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : 'review';
        $id = filter_var($_POST['submission_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($action === 'upload_evidence') {
            if (!$id) throw new InvalidArgumentException('Select a student submission.');
            add_admin_evidence($user, $id, $_FILES['evidence'] ?? []);
            $_SESSION['submission_review_feedback'] = ['ok' => true, 'text' => 'Evidence uploaded to submission #' . $id . '.'];
            header('Location: submission-review.php#review-' . $id, true, 303);
            exit;
        }

        if ($action === 'replace_evidence') {
            $evidenceId = filter_var($_POST['evidence_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$evidenceId) throw new InvalidArgumentException('Invalid evidence file.');
            replace_admin_evidence($user, $evidenceId, submission_text($_POST, 'edited_image', 8000000, true));
            $_SESSION['submission_review_feedback'] = ['ok' => true, 'text' => 'Edited evidence saved.'];
            header('Location: submission-review.php#review-' . (int)($_POST['review_id'] ?? 0), true, 303);
            exit;
        }

        if (!$id) throw new InvalidArgumentException('Invalid submission.');
        review_submission($user, $id, submission_text($_POST, 'decision', 20, true), submission_text($_POST, 'review_remarks', 2000));
        $_SESSION['submission_review_feedback'] = ['ok' => true, 'text' => 'Submission #' . $id . ' reviewed successfully.'];
        header('Location: submission-review.php#review-' . $id, true, 303);
        exit;
    } catch (InvalidArgumentException|DomainException $ex) {
        http_response_code($ex instanceof DomainException ? 409 : 422);
        $error = $ex->getMessage();
    }
}

$rows = demo_submissions();
usort(
    $rows,
    fn($a, $b) =>
        ($b["status"] === "Pending") <=> ($a["status"] === "Pending")
        ?: ($b["id"] <=> $a["id"])
);
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Submission Reviews | ICompliance</title>
        <link rel="stylesheet" href="assets/styles.css">
        <link rel="stylesheet" href="assets/submissions.css">
    </head>
    <body>
        <div class="app-shell">
            <header class="topbar">
                <div class="topbar-inner">
                    <a class="brand" href="dashboard.php">
                        <span class="brand-mark">I</span>
                        <span class="brand-name">ICompliance</span>
                    </a>
                    <span class="office-name">HELP University <span>International Student Tracking &amp; Records</span></span>
                    <nav class="navigation" aria-label="Primary navigation">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                        <a class="nav-link" href="student-records.php">Student Records</a>
                        <a class="nav-link active" href="submission-review.php">Submission Reviews</a>
                        <?= logout_control() ?>
                    </nav>
                </div>
            </header>
            <main class="content submission-page">
                <header class="page-header review-page-heading">
                    <div>
                        <p class="eyebrow">International Student Tracking &amp; Records</p>
                        <h1>Submission Reviews</h1>
                        <p>Review the student's Malaysia entry or exit date against the attached travel evidence.</p>
                    </div>
                </header>
                <?php if ($error): ?>
                    <p role="alert" class="error-message"><?= e($error) ?></p>
                <?php endif; ?>
                <?php if ($feedback): ?>
                    <p class="<?= $feedback['ok'] ? 'success-message' : 'error-message' ?>" role="status"><?= e($feedback['text']) ?></p>
                <?php endif; ?>
                <?php if (!$rows): ?>
                    <p>No submissions yet.</p>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <article class="submission-panel" id="review-<?= (int)$row['id'] ?>">
                        <h2>#<?= $row['id'] ?> · <?= e($row['student_id']) ?> · <?= e($row['status']) ?></h2>
                        <dl>
                            <?php foreach (['kind' => 'Type', 'location' => 'Location', 'country' => 'Country', 'location_details' => 'City / location', 'start_date' => 'Start date', 'end_date' => 'End date', 'submitted_at' => 'Submitted at', 'remarks' => 'Student remarks', 'resubmission_of' => 'Replaces record', 'reviewed_at' => 'Reviewed at', 'reviewer' => 'Reviewer', 'review_remarks' => 'Review remarks'] as $key => $label): ?>
                                <dt><?= $label ?></dt>
                                <dd><?= e((string)($row[$key] ?? '—')) ?></dd>
                            <?php endforeach; ?>
                        </dl>
                        <?php $reviewFiles = submission_files((int)$row['id']); foreach ($reviewFiles as $file): ?>
                            <?php if (in_array($file['mime_type'] ?? '', ['image/jpeg', 'image/png'], true)): ?>
                                <div class="review-evidence-card" data-evidence-editor data-evidence-id="<?= (int)$file['id'] ?>" data-review-id="<?= (int)$row['id'] ?>" data-image-url="evidence.php?id=<?= (int)$file['id'] ?>&amp;preview=1">
                                    <img class="review-stamp" src="evidence.php?id=<?= (int)$file['id'] ?>&amp;preview=1" alt="Submitted evidence: <?= e($file['original_name']) ?>" loading="lazy">
                                    <div class="evidence-actions"><button class="button secondary" type="button" data-edit-evidence>Edit image</button><?php if ($row['status'] === 'Pending'): ?><form method="post" enctype="multipart/form-data" class="inline-evidence-upload"><input type="hidden" name="action" value="upload_evidence"><input type="hidden" name="submission_id" value="<?= (int)$row['id'] ?>"><?= csrf_field() ?><label class="button secondary">Upload image<input type="file" name="evidence[]" accept="image/jpeg,image/png" required onchange="this.form.submit()"></label></form><?php endif; ?><a href="evidence.php?id=<?= (int)$file['id'] ?>" target="_blank" rel="noopener">Download</a></div>
                                </div>
                            <?php else: ?>
                                <p><a href="evidence.php?id=<?= $file['id'] ?>">Download <?= e($file['original_name']) ?></a></p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (!$reviewFiles && $row['status'] === 'Pending'): ?><p><span class="evidence-unavailable">No evidence submitted</span></p><form method="post" enctype="multipart/form-data" class="inline-evidence-upload"><input type="hidden" name="action" value="upload_evidence"><input type="hidden" name="submission_id" value="<?= (int)$row['id'] ?>"><?= csrf_field() ?><label class="button secondary">Upload image<input type="file" name="evidence[]" accept="image/jpeg,image/png" required onchange="this.form.submit()"></label></form><?php endif; ?>
                        <?php if ($row['status'] === 'Pending'): ?>
                            <form method="post" class="submission-fields">
                                <?= csrf_field() ?>
                                <input type="hidden" name="submission_id" value="<?= $row['id'] ?>">
                                <label>
                                    Review remarks (required for rejection)
                                    <textarea name="review_remarks" maxlength="2000" rows="2"></textarea>
                                </label>
                                <button class="button primary" name="decision" value="Verified">Approve</button>
                                <button class="button" name="decision" value="Rejected">Reject</button>
                            </form>
                        <?php endif; ?>
                        <details>
                            <summary>Audit history</summary>
                            <?php foreach (submission_audit((int)$row["id"]) as $audit): ?>
                                <p><?= e($audit['created_at'].' · '.$audit['action'].' · '.$audit['email'].' · '.$audit['remarks']) ?></p>
                            <?php endforeach; ?>
                        </details>
                    </article>
                <?php endforeach; ?>
            </main>
        </div>
        <dialog class="evidence-editor" id="evidence-editor" aria-labelledby="evidence-editor-heading">
            <form method="dialog" class="evidence-editor-shell">
                <header class="evidence-editor-heading"><div><p class="eyebrow">Evidence editor</p><h2 id="evidence-editor-heading">Adjust submitted image</h2></div><button class="editor-close" value="cancel" aria-label="Close image editor">×</button></header>
                <div class="editor-canvas-wrap"><canvas id="evidence-canvas"></canvas><p id="editor-hint">Choose Annotate to draw on the image, or Crop to draw a crop shape.</p></div>
                <div class="editor-tools" aria-label="Image editing tools"><button type="button" class="button secondary" data-editor-rotate="-90">Rotate left</button><button type="button" class="button secondary" data-editor-rotate="90">Rotate right</button><button type="button" class="button secondary" id="editor-crop">Crop</button><select id="editor-crop-shape" class="editor-crop-shape" aria-label="Crop selection shape" hidden><option value="freehand">Free draw shape</option><option value="rectangle">Rectangular selection</option></select><button type="button" class="button secondary" id="editor-annotate">Annotate</button><button type="button" class="button secondary" id="editor-reset">Reset</button></div>
                <div class="editor-actions"><button type="button" class="button" value="cancel" id="editor-cancel">Cancel</button><button type="button" class="button primary" id="editor-save">Save edited image</button></div>
            </form>
        </dialog>
        <form id="edited-evidence-form" method="post" hidden><?= csrf_field() ?><input type="hidden" name="action" value="replace_evidence"><input type="hidden" name="evidence_id" id="edited-evidence-id"><input type="hidden" name="review_id" id="edited-review-id"><input type="hidden" name="edited_image" id="edited-image"></form>
        <script src="assets/evidence-editor.js"></script>
    </body>
</html>
