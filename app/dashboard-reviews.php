<section class="alerts-section pending-section" aria-labelledby="pending-heading">
<h2 id="pending-heading">Student submission reviews</h2>
<div class="review-table-scroll" tabindex="0" role="region" aria-label="Student submission reviews table">
<table class="review-table">
<thead><tr><th scope="col">Student code</th><th scope="col">Submitted image</th><th scope="col">Date submitted</th><th scope="col">Tally option</th></tr></thead>
<tbody>
<?php if (!$pending): ?><tr><td colspan="4">No submissions awaiting review.</td></tr><?php endif; ?>
<?php foreach ($pending as $row): ?>
<tr id="review-<?= (int)$row['id'] ?>" data-review-row>
<td><a class="review-student-link" href="student-profile.php?id=<?= e(rawurlencode($row['student_id'])) ?>"><?= e($row['student_id']) ?></a></td>
<td>
<?php if (!empty($row['preview'])): ?>
<a href="assets/review-sample.svg" target="_blank" rel="noopener" aria-label="View passport stamp for <?= e($row['student_id']) ?>"><img class="review-stamp" src="assets/review-sample.svg" alt="Passport stamp for <?= e($row['student_id']) ?>"></a>
<?php else:
 $files=submission_files((int)$row["id"]);
 if (!$files): ?><span>No image submitted</span><?php endif;
 foreach ($files as $file): ?>
<a href="evidence.php?id=<?= (int)$file['id'] ?>&amp;preview=1" target="_blank" rel="noopener" aria-label="View <?= e($file['original_name']) ?>">
<?php if ($file['mime_type'] !== 'application/pdf'): ?><img class="review-stamp" src="evidence.php?id=<?= (int)$file['id'] ?>&amp;preview=1" alt="Passport stamp for <?= e($row['student_id']) ?>"><?php else: ?>View submitted PDF<?php endif; ?></a>
<?php endforeach; endif; ?>
</td>
<td><time datetime="<?= e((new DateTimeImmutable($row['submitted_at']))->format('c')) ?>"><?= e((new DateTimeImmutable($row['submitted_at']))->format('d M Y, h:i A')) ?></time></td>
<td><div class="review-tally">
<button type="button" class="button primary" data-decision="Approved" disabled>Approved</button>
<button type="button" class="button secondary" data-decision="Request Resubmission" disabled>Request Resubmission</button>
<span class="badge badge-pending" data-review-status role="status">Pending review</span>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
</section>
