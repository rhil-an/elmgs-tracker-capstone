<?php
declare(strict_types=1);
require __DIR__.'/app/progress.php';
$student=session_student();
if (($_SERVER['REQUEST_METHOD']??'GET')!=='GET') { header('Allow: GET'); http_response_code(405); exit('This profile is read-only. Use GET.'); }
function e(string $v): string {return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
function profile_date(?string $v): string {return $v?(new DateTimeImmutable($v))->format('d M Y'):'Not recorded';}
$today=new DateTimeImmutable('today');
$visaDays=!empty($student['visaExpiry'])?(int)$today->diff(new DateTimeImmutable($student['visaExpiry']))->format('%r%a'):null;
$checkinDays=!empty($student['lastCheckIn'])?max(0,(int)(new DateTimeImmutable($student['lastCheckIn']))->diff($today)->format('%r%a')):null;
$visaState=$visaDays===null?'Not recorded':($visaDays<0?'Expired':($visaDays<=30?'Expiring within 30 days':'Valid'));
$visaClass=$visaDays===null?'':($visaDays<0?'visa-expired':($visaDays<=30?'visa-warning':'visa-valid'));
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My Profile | ICompliance</title><link rel="stylesheet" href="assets/styles.css"><link rel="stylesheet" href="assets/student-profile.css"></head><body class="student-profile-page"><div class="app-shell">
<header class="topbar"><div class="topbar-inner"><a class="brand" href="dashboard-student.php" aria-label="Student dashboard"><span class="brand-mark" aria-hidden="true">I</span><span class="brand-name">ICompliance</span></a><span class="office-name">HELP University <span>Student Portal</span></span><nav class="navigation" aria-label="Student navigation"><a class="nav-link" href="dashboard-student.php">My Dashboard</a><a class="nav-link active" href="profile-student.php" aria-current="page">My Profile</a><a class="nav-link" href="submission-form.php">Submit Proof</a><?= logout_control() ?></nav></div></header>
<main class="content" id="main-content"><a class="back-link" href="dashboard-student.php">← Back to My Dashboard</a><header class="page-header"><div><p class="eyebrow">Student Portal</p><h1>My Profile</h1><p>Your personal, academic and residence information. Contact the International Student Office to request a correction.</p></div></header>
<section class="profile-card" aria-labelledby="information-heading"><h2 id="information-heading">Student &amp; Academic Information</h2><dl class="detail-list student-profile-details">
<?php foreach(['name'=>'Full name','id'=>'Student ID','nationality'=>'Nationality','faculty'=>'Faculty / Programme','status'=>'Compliance status'] as $key=>$label): ?><div><dt><?= $label ?></dt><dd><?= e($student[$key]) ?></dd></div><?php endforeach; ?><div><dt>University</dt><dd>HELP University</dd></div></dl></section>
<?= stay_progress_html(student_stay_progress($student['id'])) ?>
<div class="profile-grid"><section class="profile-card" aria-labelledby="visa-heading"><h2 id="visa-heading">Visa &amp; Residence</h2><dl class="detail-list"><div><dt>Visa expiry</dt><dd><?= e(profile_date($student['visaExpiry']??null)) ?></dd></div><div><dt>Visa status</dt><dd class="<?= $visaClass ?>"><?= e($visaState) ?></dd></div><div><dt>Current location</dt><dd><span class="badge location-<?= $student['currentLocation']==='Local'?'local':'overseas' ?>"><?= e($student['currentLocation']) ?></span></dd></div></dl><p class="profile-note">Location is separate from compliance status. Overseas alone is not a compliance warning.</p></section>
<section class="profile-card" aria-labelledby="checkin-heading"><h2 id="checkin-heading">Check-in Summary</h2><dl class="detail-list"><div><dt>Last check-in</dt><dd><?= e(profile_date($student['lastCheckIn']??null)) ?></dd></div><div><dt>Days since check-in</dt><dd><?= $checkinDays===null?'Not recorded':$checkinDays.' days' ?></dd></div><div><dt>Check-in status</dt><dd><?= $checkinDays===null?'No recorded check-in':($checkinDays>30?'Overdue':'Within 30 days') ?></dd></div></dl><p class="profile-note">Check-ins are overdue after more than 30 days. Verified travel does not refresh your check-in.</p></section></div></main></div></body></html>
