<?php
declare(strict_types=1);
require __DIR__.'/app/submissions.php';
$user=require_user('admin');
function e(string $v): string { return htmlspecialchars($v,ENT_QUOTES,'UTF-8'); }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();
 try {
  $id=filter_var($_POST['submission_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
  if (!$id) throw new InvalidArgumentException('Invalid submission.');
  review_submission($user,$id,submission_text($_POST,'decision',20,true),submission_text($_POST,'review_remarks',2000));
  header('Location: submission-review.php',true,303); exit;
 } catch (InvalidArgumentException|DomainException $ex) { http_response_code($ex instanceof DomainException?409:422); $error=$ex->getMessage(); }
}
$rows=demo_submissions(); usort($rows,fn($a,$b)=>($b["status"]==="Pending")<=>($a["status"]==="Pending") ?: ($b["id"]<=>$a["id"]));
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Submission Reviews | ICompliance</title><link rel="stylesheet" href="assets/styles.css"><link rel="stylesheet" href="assets/submissions.css"></head><body><div class="app-shell"><header class="topbar"><div class="topbar-inner"><a class="brand" href="dashboard.php"><span class="brand-mark">I</span><span class="brand-name">ICompliance</span></a><span class="office-name">HELP University <span>International Student Office</span></span><nav class="navigation" aria-label="Primary navigation"><a class="nav-link" href="dashboard.php">Dashboard</a><a class="nav-link" href="student-records.php">Student Records</a><a class="nav-link active" href="submission-review.php">Submission Reviews</a><?= logout_control() ?></nav></div></header><main class="content submission-page"><h1>Submission Reviews</h1><p>Before approving travel, verify the student-entered event date against the attached passport image. Reject mismatches with a reason. Travel declarations do not refresh check-ins.</p><?php if ($error): ?><p role="alert" class="error-message"><?= e($error) ?></p><?php endif; ?><?php if (!$rows): ?><p>No submissions yet.</p><?php endif; ?>
<?php foreach ($rows as $row): ?><article class="submission-panel"><h2>#<?= $row['id'] ?> · <?= e($row['student_id']) ?> · <?= e($row['status']) ?></h2><dl><?php foreach (['kind'=>'Type','location'=>'Location','country'=>'Country','location_details'=>'City / location','start_date'=>'Start date','end_date'=>'End date','submitted_at'=>'Submitted at','remarks'=>'Student remarks','resubmission_of'=>'Replaces record','reviewed_at'=>'Reviewed at','reviewer'=>'Reviewer','review_remarks'=>'Review remarks'] as $key=>$label): ?><dt><?= $label ?></dt><dd><?= e((string)($row[$key]??'—')) ?></dd><?php endforeach; ?></dl>
<?php foreach (submission_files((int)$row["id"]) as $file): ?><p><a href="evidence.php?id=<?= $file['id'] ?>">Download <?= e($file['original_name']) ?></a></p><?php endforeach; ?>
<?php if ($row['status']==='Pending'): ?><form method="post" class="submission-fields"><?= csrf_field() ?><input type="hidden" name="submission_id" value="<?= $row['id'] ?>"><label>Review remarks (required for rejection)<textarea name="review_remarks" maxlength="2000" rows="2"></textarea></label><button class="button primary" name="decision" value="Verified">Approve</button><button class="button" name="decision" value="Rejected">Reject</button></form><?php endif; ?>
<details><summary>Audit history</summary><?php foreach (submission_audit((int)$row["id"]) as $audit): ?><p><?= e($audit['created_at'].' · '.$audit['action'].' · '.$audit['email'].' · '.$audit['remarks']) ?></p><?php endforeach; ?></details></article><?php endforeach; ?></main></div></body></html>

