<?php // Included by the authenticated admin profile; values are escaped by e(). ?>
<section class="profile-card history-card" aria-labelledby="history-heading"><h2 id="history-heading">Saved Submissions and Reviews</h2><p>Times shown in Asia/Kuala_Lumpur. Notification Pending means queued, not delivered.</p>
<?php if (!$history): ?><p>No saved submissions for this student.</p><?php endif; ?>
<?php foreach ($history as $entry): ?><article class="saved-history"><h3>Submission #<?= (int)$entry['id'] ?> · <?= e($entry['kind']) ?> <span class="badge submission-<?= strtolower($entry['status']) ?>"><?= e($entry['status']) ?></span></h3><dl class="detail-list">
<div><dt>Submitted time</dt><dd><?= e((new DateTimeImmutable($entry['submitted_at']))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('d M Y H:i:s T')) ?></dd></div>
<div><dt>Travel / observation dates</dt><dd><?= e(date_label($entry['start_date']).' – '.date_label($entry['end_date'])) ?></dd></div>
<div><dt>Location</dt><dd><span class="badge location-<?= strtolower($entry['location']) ?>"><?= e($entry['location']) ?></span> <?= e($entry['country'].(!empty($entry['location_details']) ? ' · '.$entry['location_details'] : '')) ?></dd></div>
<div><dt>Student remarks</dt><dd><?= e($entry['remarks']?:'None') ?></dd></div>
<div><dt>Evidence</dt><dd><?php if (!$entry['evidence']): ?>None<?php endif; ?><?php foreach ($entry['evidence'] as $file): ?><a class="history-evidence" href="evidence.php?id=<?= (int)$file['id'] ?>">Download <?= e($file['original_name']) ?></a><?php endforeach; ?></dd></div>
<div><dt>Reviewer</dt><dd><?= e($entry['reviewer']??'Awaiting review') ?></dd></div>
<div><dt>Reviewed time</dt><dd><?= $entry['reviewed_at'] ? e((new DateTimeImmutable($entry['reviewed_at']))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('d M Y H:i:s T')) : 'Awaiting review' ?></dd></div>
<div><dt>Review remarks</dt><dd><?= e($entry['review_remarks']??'Awaiting review') ?></dd></div>
<div><dt>Notification delivery</dt><dd><?= e($entry['notification_status']??'No decision notification') ?></dd></div>
<?php if ($entry['resubmission_of']): ?><div><dt>Replaces submission</dt><dd>#<?= (int)$entry['resubmission_of'] ?></dd></div><?php endif; ?>
</dl></article><?php endforeach; ?></section>
