<?php
declare(strict_types=1);
require __DIR__ . '/app/progress.php';

require_user('admin');

$students = all_students();
$id = trim((string)($_GET['id'] ?? ''));
require_owner($id);

$student = null;
foreach ($students as $record) {
    if (($record['id'] ?? '') === $id) {
        $student = $record;
        break;
    }
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function date_label(string $date): string {
    return (new DateTimeImmutable($date))->format('d M Y');
}

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    return strtoupper(implode('', array_map(static fn($part) => substr($part, 0, 1), array_slice($parts, 0, 2))));
}

$today = new DateTimeImmutable('today');

if ($student) {
    $history = profile_submission_history($student['id']);
    $visa = new DateTimeImmutable($student['visaExpiry']);
    $checkin = new DateTimeImmutable($student['lastCheckIn']);
    $visaDays = (int)$today->diff($visa)->format('%r%a');
    $checkinDays = max(0, (int)$checkin->diff($today)->format('%r%a'));
    $visaState = $visaDays < 0 ? 'Expired' : ($visaDays <= 30 ? 'Expiring within 30 days' : 'Valid');
    $visaClass = $visaDays < 0 ? 'visa-expired' : ($visaDays <= 30 ? 'visa-warning' : 'visa-valid');
    $alerts = [];

    if ($student['status'] === 'Non-Compliant') {
        $alerts[] = ['critical', 'Non-compliance requires attention', 'This student is currently marked Non-Compliant. Review their outstanding compliance requirements.'];
    } elseif ($student['status'] === 'Warning') {
        $alerts[] = ['warning', 'Compliance status requires attention', 'This student is currently marked Warning. Review their compliance requirements.'];
    }

    if ($visaDays < 0) {
        $alerts[] = ['critical', 'Visa has expired', 'Visa expiry was ' . date_label($student['visaExpiry']) . '. Immediate follow-up is required.'];
    } elseif ($visaDays <= 30) {
        $alerts[] = ['warning', 'Visa expiry is approaching', 'Visa expires on ' . date_label($student['visaExpiry']) . ' (' . $visaDays . ' days remaining).'];
    }

    if ($checkinDays > 30) {
        $alerts[] = ['warning', 'Check-in is overdue', 'The last recorded check-in was ' . $checkinDays . ' days ago on ' . date_label($student['lastCheckIn']) . '.'];
    }
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Student compliance profile for HELP University International Student Office.">
        <title><?= $student ? e($student['name']) . ' | ICompliance' : 'Student Not Found | ICompliance' ?></title>
        <link rel="stylesheet" href="assets/styles.css">
    </head>
    <body>
        <div class="app-shell">
            <header class="topbar">
                <div class="topbar-inner">
                    <a class="brand" href="dashboard.php" aria-label="ICompliance dashboard">
                        <span class="brand-mark" aria-hidden="true">I</span>
                        <span class="brand-name">ICompliance</span>
                    </a>
                    <span class="office-name">HELP University <span>International Student Office</span></span>
                    <nav class="navigation" aria-label="Primary navigation">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                        <a class="nav-link active" href="student-records.php" aria-current="page">Student Records</a>
                        <a class="nav-link" href="submission-review.php">Submission Reviews</a>
                        <?php echo logout_control(); ?>
                    </nav>
                </div>
            </header>
            <main class="content" id="main-content">
                <a class="back-link" href="student-records.php#records">← Back to Student Records</a>
                <?php if (!$student): ?>
                    <section class="not-found" aria-labelledby="not-found-title">
                        <p class="eyebrow">Student profile</p>
                        <h1 id="not-found-title">Student record not found</h1>
                        <p>The profile link may be incomplete, or this student is not included in the student records.</p>
                        <a class="button primary" href="student-records.php#records">Back to Student Records</a>
                    </section>
                <?php else: ?>
                    <header class="page-header profile-page-heading"><div><p class="eyebrow">International Student Office</p><h1 id="student-name">Student Profile</h1><p>Student information, verified stay progress and submission history.</p></div></header>
                    <section class="profile-card student-information" aria-labelledby="student-information-heading">
                        <h2 id="student-information-heading">Student &amp; Academic Information</h2>
                        <dl class="detail-list profile-information-rows">
                            <div><dt>Full name</dt><dd><?= e($student['name']) ?></dd></div>
                            <div><dt>Student ID</dt><dd><?= e($student['id']) ?></dd></div>
                            <div><dt>Nationality</dt><dd><?= e($student['nationality']) ?></dd></div>
                            <div><dt>University</dt><dd>HELP University</dd></div>
                            <div><dt>Faculty / Programme</dt><dd><?= e($student['faculty']) ?></dd></div>
                            <div><dt>Compliance status</dt><dd><span class="badge badge-<?= strtolower(str_replace(' ', '-', $student['status'])) ?>"><?= e($student['status']) ?></span></dd></div>
                        </dl>
                    </section>
                    <?= stay_progress_html(student_stay_progress($student['id'])) ?>
                    <section class="issues-section" aria-labelledby="issues-heading">
                        <div class="section-intro">
                            <div>
                                <h2 id="issues-heading">Issues Requiring Attention</h2>
                                <p>Visa and check-in alerts use the current record; compliance flags remain cached sample values.</p>
                            </div>
                        </div>
                        <div class="alerts">
                            <?php if (!$alerts): ?>
                                <div class="alert alert-ok" role="status">
                                    <span class="alert-icon" aria-hidden="true">✓</span>
                                    <div>
                                        <h3>No immediate compliance issues</h3>
                                        <p>This record has no expired or upcoming visa alert, overdue check-in, or non-compliance flag.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($alerts as [$severity, $title, $message]): ?>
                                    <div class="alert alert-<?= e($severity) ?>" role="<?= $severity === 'critical' ? 'alert' : 'status' ?>">
                                        <span class="alert-icon" aria-hidden="true">!</span>
                                        <div>
                                            <h3><?= e($title) ?></h3>
                                            <p><?= e($message) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                    <div class="profile-grid">
<section class="profile-card" aria-labelledby="visa-heading">
                            <h2 id="visa-heading">Visa &amp; Residence</h2>
                            <dl class="detail-list">
                                <div><dt>Visa Expiry</dt><dd><?= e(date_label($student['visaExpiry'])) ?></dd></div>
                                <div><dt>Visa Status</dt><dd class="<?= e($visaClass) ?>"><?= e($visaState) ?></dd></div>
                                <div><dt>Current Location</dt><dd><span class="badge location-<?= strtolower($student['currentLocation']) ?>"><?= e($student['currentLocation']) ?></span></dd></div>
                            </dl>
                        </section>
                        <section class="profile-card" aria-labelledby="checkin-heading">
                            <h2 id="checkin-heading">Check-in Summary</h2>
                            <dl class="detail-list">
                                <div><dt>Last Check-in</dt><dd><?= e(date_label($student['lastCheckIn'])) ?></dd></div>
                                <div><dt>Days since check-in</dt><dd><?= $checkinDays ?> days</dd></div>
                            <div><dt>Check-in status</dt><dd><?= $checkinDays > 30 ? 'Overdue' : 'Within 30 days' ?></dd></div></dl>
                        </section>
<?php require __DIR__.'/app/profile-history.php'; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </body>
</html>