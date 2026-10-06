<section class="alerts-section pending-section" aria-labelledby="pending-heading">
<h2 id="pending-heading">Student submission reviews</h2>
<div class="review-table-scroll" tabindex="0" role="region" aria-label="Student submission reviews table">
<table class="review-table">
<thead><tr><th scope="col">Student code</th><th scope="col">Submitted image</th><th scope="col">Entry / Exit Date</th><th scope="col">Tally option</th></tr></thead>
<tbody>
<?php if (!$pending): ?><tr><td colspan="4">No submissions awaiting review.</td></tr><?php endif; ?>
<?php foreach ($pending as $row): ?>
<tr id="review-<?= (int)$row['id'] ?>" data-review-row>
<td><a class="review-student-link" href="student-profile.php?id=<?= e(rawurlencode($row['student_id'])) ?>"><?= e($row['student_id']) ?></a></td>
<td>
<?php require_once __DIR__.'/evidence-rendering.php'; echo review_attachments_html(submission_files((int)$row['id'])); ?>
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
<button type="button" class="button primary" data-decision="Approved" disabled>Approved</button>
<button type="button" class="button secondary" data-decision="Request Resubmission" disabled>Request Resubmission</button>
<span class="sr-only" data-review-status role="status" aria-live="polite" aria-atomic="true"></span>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
</section>
