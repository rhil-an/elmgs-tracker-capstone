<section class="alerts-section pending-section" aria-labelledby="pending-heading">
<div class="pending-heading-row"><h2 id="pending-heading">Student submission reviews</h2></div>
<?php if ($feedback): ?><p class="<?= $feedback['ok'] ? 'success-message' : 'error-message' ?>" role="<?= $feedback['ok'] ? 'status' : 'alert' ?>"><?= e($feedback['text']) ?></p><?php endif; ?>
<div class="review-table-scroll" tabindex="0" role="region" aria-label="Student submission reviews table">
<table class="review-table">
<thead><tr><th scope="col">Student code</th><th scope="col">Submitted image</th><th scope="col">Entry / Exit Date</th><th scope="col">Tally option</th></tr></thead>
<tbody>
<?php if (!$pending): ?><tr><td colspan="4">No submissions awaiting review.</td></tr><?php endif; ?>
<?php foreach ($pending as $row): ?>
<tr id="review-<?= (int)$row['id'] ?>" data-review-row>
<td><a class="review-student-link" href="student-profile.php?id=<?= e(rawurlencode($row['student_id'])) ?>"><?= e($row['student_id']) ?></a></td>
<td>
<?php $reviewFiles=submission_files((int)$row['id']); ?>
<?php foreach ($reviewFiles as $file): ?>
<?php if (in_array($file['mime_type']??'', ['image/jpeg','image/png'], true)): ?>
<div class="review-evidence-card" data-evidence-editor data-evidence-id="<?= (int)$file['id'] ?>" data-review-id="<?= (int)$row['id'] ?>" data-image-url="evidence.php?id=<?= (int)$file['id'] ?>&amp;preview=1">
<img class="review-stamp" src="evidence.php?id=<?= (int)$file['id'] ?>&amp;preview=1" alt="Submitted evidence: <?= e($file['original_name']) ?>" loading="lazy">
<div class="evidence-actions"><button class="button secondary" type="button" data-edit-evidence>Edit image</button><form method="post" enctype="multipart/form-data" class="inline-evidence-upload"><input type="hidden" name="action" value="upload_evidence"><input type="hidden" name="submission_id" value="<?= (int)$row['id'] ?>"><?= csrf_field() ?><label class="button secondary">Upload image<input type="file" name="evidence[]" accept="image/jpeg,image/png" required onchange="this.form.submit()"></label></form><a href="evidence.php?id=<?= (int)$file['id'] ?>" target="_blank" rel="noopener">Download</a></div>
</div>
<?php else: ?><a href="evidence.php?id=<?= (int)$file['id'] ?>" target="_blank" rel="noopener">Download <?= e($file['original_name']) ?></a><?php endif; ?>
<?php endforeach; ?>
<?php if (!$reviewFiles): ?><span class="evidence-unavailable">No evidence submitted</span><form method="post" enctype="multipart/form-data" class="inline-evidence-upload"><input type="hidden" name="action" value="upload_evidence"><input type="hidden" name="submission_id" value="<?= (int)$row['id'] ?>"><?= csrf_field() ?><label class="button secondary">Upload image<input type="file" name="evidence[]" accept="image/jpeg,image/png" required onchange="this.form.submit()"></label></form><?php endif; ?>
</td>
<td>
<?php
 $eventDate=$row['start_date']??null;
 $parsed=is_string($eventDate)?DateTimeImmutable::createFromFormat('!Y-m-d',$eventDate):false;
 $label=match ($row['kind']??'') { 'Entry'=>'Entry', 'Exit'=>'Exit', 'CheckIn'=>'Observation', default=>'Event' };
 if (!$parsed || $parsed->format('Y-m-d')!==$eventDate): ?>
<span><?= e($label) ?> date unavailable</span>
<?php elseif (($row['end_date']??$eventDate)!==$eventDate): ?>
<span>Legacy date range — review required</span>
<?php else: ?>
<span><?= e($label) ?> · </span><time datetime="<?= e($eventDate) ?>"><?= e($parsed->format('d M Y')) ?></time>
<?php endif; ?>
</td>
<td><div class="review-tally">
<?php $previewRow = $reviewPreview || !empty($row['preview']); ?>
<form method="post" action="<?= e($reviewAction) ?>">
<?= csrf_field() ?><input type="hidden" name="submission_id" value="<?= (int)$row['id'] ?>">
<button type="submit" name="decision" value="Verified" class="button primary" data-decision="Approved" <?= $previewRow ? 'disabled' : '' ?>>Approved</button>
</form>
<form method="post" action="<?= e($reviewAction) ?>" class="review-rejection">
<?= csrf_field() ?><input type="hidden" name="submission_id" value="<?= (int)$row['id'] ?>">
<label for="reason-<?= (int)$row['id'] ?>">Reason for resubmission (required)</label>
<textarea id="reason-<?= (int)$row['id'] ?>" name="review_remarks" rows="2" maxlength="2000" required <?= $previewRow ? 'disabled' : '' ?>><?= e(($feedback['id'] ?? null) === (int)$row['id'] ? ($feedback['remarks'] ?? '') : '') ?></textarea>
<button type="submit" name="decision" value="Rejected" class="button secondary" data-decision="Request Resubmission" <?= $previewRow ? 'disabled' : '' ?>>Request Resubmission</button>
</form>
<?php if ($previewRow): ?><small>Preview only: decisions cannot be saved.</small><?php endif; ?>
<span class="sr-only" data-review-status role="status" aria-live="polite" aria-atomic="true"></span>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
</section>
<dialog class="evidence-editor" id="evidence-editor" aria-labelledby="evidence-editor-heading">
<form method="dialog" class="evidence-editor-shell">
<header class="evidence-editor-heading"><div><p class="eyebrow">Evidence editor</p><h2 id="evidence-editor-heading">Adjust submitted image</h2></div><button class="editor-close" value="cancel" aria-label="Close image editor">×</button></header>
<div class="editor-canvas-wrap"><canvas id="evidence-canvas"></canvas><p id="editor-hint">Choose Annotate to draw on the image, or Crop to draw a crop shape.</p></div>
<div class="editor-tools" aria-label="Image editing tools"><button type="button" class="button secondary" data-editor-rotate="-90">Rotate left</button><button type="button" class="button secondary" data-editor-rotate="90">Rotate right</button><button type="button" class="button secondary" id="editor-crop">Crop</button><select id="editor-crop-shape" class="editor-crop-shape" aria-label="Crop selection shape" hidden><option value="freehand">Free draw shape</option><option value="rectangle">Rectangular selection</option></select><button type="button" class="button secondary" id="editor-annotate">Annotate</button><button type="button" class="button secondary" id="editor-reset">Reset</button></div>
<div class="editor-actions"><button type="button" class="button" id="editor-cancel">Cancel</button><button type="button" class="button primary" id="editor-save">Save edited image</button></div>
</form>
</dialog>
<form id="edited-evidence-form" method="post" hidden><?= csrf_field() ?><input type="hidden" name="action" value="replace_evidence"><input type="hidden" name="evidence_id" id="edited-evidence-id"><input type="hidden" name="review_id" id="edited-review-id"><input type="hidden" name="edited_image" id="edited-image"></form>
