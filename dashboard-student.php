<?php
declare(strict_types=1);

require __DIR__ . '/app/progress.php';
$student = session_student();
$studentId = $student['id'];
$showStayNotice = !empty($_SESSION['show_stay_notice']);
unset($_SESSION['show_stay_notice']);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function date_label(string $date): string
{
    return (new DateTimeImmutable($date))->format('d M Y');
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
    }

    return $letters;
}

$today = new DateTimeImmutable('today');

$visaDate = new DateTimeImmutable($student['visaExpiry']);
$lastCheckIn = new DateTimeImmutable($student['lastCheckIn']);

$visaDaysRemaining = (int) $today
    ->diff($visaDate)
    ->format('%r%a');

$daysSinceCheckIn = max(
    0,
    (int) $lastCheckIn
        ->diff($today)
        ->format('%r%a')
);

$visaStatus = $visaDaysRemaining < 0
    ? 'Expired'
    : ($visaDaysRemaining <= 120 ? 'Expiring Soon' : 'Valid');

$visaClass = $visaDaysRemaining < 0
    ? 'visa-expired'
    : ($visaDaysRemaining <= 120 ? 'visa-warning' : 'visa-valid');

$statusClass = match ($student['status']) {
    'Non-Compliant' => 'badge-non-compliant',
    'Warning' => 'badge-warning',
    default => 'badge-compliant'
};

$submissionHistory = submission_history($studentId);
usort($submissionHistory, static function (array $a, array $b): int {
    $timestamp = static fn(array $row): int => !empty($row['submitted_at']) ? (new DateTimeImmutable($row['submitted_at']))->getTimestamp() : PHP_INT_MIN;
    return ($timestamp($b) <=> $timestamp($a)) ?: ((int)$b['id'] <=> (int)$a['id']);
});

function history_event_date(array $submission): string
{
    $start = (string)($submission['start_date'] ?? '');
    $end = (string)($submission['end_date'] ?? '');
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $start);
    if (!$date || $date->format('Y-m-d') !== $start) return 'Date unavailable';
    if ($end !== '' && $end !== $start) return $date->format('d M Y') . ' (legacy range; needs clarification)';
    return $date->format('d M Y');
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta
        name="description"
        content="Student dashboard for HELP University international students."
    >

    <title>My Dashboard | ICompliance</title>

    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="assets/student-dashboard.css">

</head>

<body class="student-portal-page">
<div class="app-shell">

    <header class="topbar">
        <div class="topbar-inner">
            <a
                class="brand"
                href="dashboard-student.php"
                aria-label="Student dashboard"
            >
                <img class="brand-mark" src="assets/help-logo.png" alt="HELP University">
                <span class="brand-name">ICompliance</span>
            </a>

            <span class="office-name">
                HELP University
                <span>Student Tracking Portal</span>
            </span>

            <nav class="navigation" aria-label="Student navigation">
                <a
                    class="nav-link active"
                    href="dashboard-student.php"
                    aria-current="page"
                >
                    My Dashboard
                </a>

                <a
                    class="nav-link"
                    href="dashboard-student.php#submissions"
                >
                    Submission History
                </a>
                <a class="nav-link" href="rules.php">Rules &amp; Guidance</a>
                <a class="nav-link" href="profile-student.php">My Profile</a>
                <?php echo logout_control(); ?></nav>
        </div>
    </header>

    <main class="content student-dashboard" id="main-content">
        <section class="student-welcome-banner" aria-labelledby="student-welcome-heading">
            <div>
                <p class="eyebrow">Student Tracking Portal</p>
                <h2 id="student-welcome-heading">Keep your travel record up to date</h2>
                <p>Submit your Malaysia entry or exit proof whenever you travel. Your submission will appear below after review.</p>
            </div>
            <span class="student-welcome-mark" aria-hidden="true">✓</span>
        </section>
        <a class="student-account" href="profile-student.php" aria-label="My Profile">
            <span class="student-account-avatar" aria-hidden="true"><?= e(initials($student['name'])) ?></span>
            <span class="student-account-details"><span class="student-account-name"><?= e($student['name']) ?></span><span class="student-account-label">My Profile <span aria-hidden="true">↗</span></span></span></a>

        <header class="page-header">
            <div>
                <p class="eyebrow">Student Portal</p>
                <h1>My Dashboard</h1>
                <p class="record-count">
                    Your stay progress, important dates and submitted proof
                </p>
            </div>

            <a class="button primary student-main-action" href="submission-form.php"><span aria-hidden="true">＋</span> Submit Proof</a>
        </header>

<div class="student-overview">
<?= stay_progress_html(student_stay_progress($studentId)) ?>

        <section
            class="profile-grid student-summary"
            aria-label="Compliance and important dates"
        >
            <article class="profile-card summary-card">
                <p class="summary-label">Compliance Status</p>

                <p class="summary-value">
                    <span class="badge <?= $statusClass ?>">
                        <?= e($student['status']) ?>
                    </span>
                </p>

                <p class="summary-note">
                    Recorded compliance result
                </p>
            </article>

            <article class="profile-card summary-card">
                <p class="summary-label">Current Location</p>

                <p class="summary-value">
                    <?= e($student['currentLocation']) ?>
                </p>

                <p class="summary-note">
                    Based on your latest verified check-in
                </p>
            </article>

            <article class="profile-card summary-card">
                <p class="summary-label">Visa Status</p>

                <p class="summary-value <?= e($visaClass) ?>">
                    <?= e($visaStatus) ?>
                </p>

                <p class="summary-note">
                    Expires <?= e(date_label($student['visaExpiry'])) ?>
                </p>
            </article>

            <article class="profile-card summary-card">
                <p class="summary-label">Last Check-in</p>

                <p class="summary-value">
                    <?= e(date_label($student['lastCheckIn'])) ?>
                </p>

                <p class="summary-note">
                    <?= $daysSinceCheckIn ?> days ago · <?= $daysSinceCheckIn > 30 ? 'Overdue' : 'Within 30 days' ?>
                </p>
            </article>
        </section>

        </div>
         <section
            class="submission-section"
            id="submissions"
            aria-labelledby="submission-heading"
        >
            <div class="section-heading">
                <div>
                    <h2 id="submission-heading">
                        Submission History
                    </h2>

                    <p>
                        All submissions · newest first
                    </p>
                </div>
            </div>

            <div class="submission-card">
                <div class="submission-scroll" tabindex="0" role="region" aria-label="Submission history table">
                    <table class="submission-table">
                        <caption class="sr-only">
                            Student submission history
                        </caption>

                        <thead>
                        <tr>
                            <th scope="col">Date submitted</th>
                            <th scope="col">Entry / Exit / Check-in</th>
                            <th scope="col">Travel / Check-in date</th>
                            <th scope="col">Country</th>
                            <th scope="col">Status</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php if (!$submissionHistory): ?><tr><td colspan="5">No submissions yet.</td></tr><?php endif; ?>
<?php foreach ($submissionHistory as $submission): ?>
                            <tr>
                                <td class="submission-date">
                                    <?= e(!empty($submission['submitted_at']) ? (new DateTimeImmutable($submission['submitted_at']))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('d M Y') : 'Date unavailable') ?>
                                </td>

                                <td>
                                    <?= e(match ($submission['kind']) { 'CheckIn'=>'Check-in', 'Entry'=>'Entry', 'Exit'=>'Exit', default=>(string)$submission['kind'] }) ?>
                                </td>

                                <td>
                                    <?= e(history_event_date($submission)) ?>
                                </td>
                                <td><?= e((string)($submission['country'] ?? '')) ?></td>
                                <td>
                                    <span class="badge <?= e(match ($submission['status']) { 'Verified'=>'badge-compliant', 'Rejected'=>'badge-non-compliant', default=>'badge-warning' }) ?>">
                                        <?= e($submission['status'] === 'Rejected' ? 'Needs resubmission' : $submission['status']) ?>
                                    </span>
                                    <?php if ($submission['status'] === 'Rejected'): ?><a class="history-resubmit" href="submission-form.php?resubmit=<?= (int)$submission['id'] ?>">Resubmit</a><?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
</section>

        <section
            class="student-help"
            aria-labelledby="help-heading"
        >
            <h2 id="help-heading">
                Need to update your information?
            </h2>

            <p>
                View <a href="profile-student.php">My Profile</a> or contact Student Tracking &amp; Records
                if any information on your profile is incorrect.
            </p>
        </section>

    </main>
</div>
<?php if ($showStayNotice): ?>
<div class="stay-notice-backdrop" id="stay-notice" role="presentation">
    <section class="stay-notice" role="dialog" aria-modal="true" aria-labelledby="stay-notice-heading">
        <p class="eyebrow">Important reminder</p>
        <h2 id="stay-notice-heading">Your stay requirement</h2>
        <p>As an international student, you are required to maintain verified presence in Malaysia for <strong>one-third (1/3) of your approved study period</strong>.</p>
        <button class="button primary" type="button" id="close-stay-notice">Continue</button>
    </section>
</div>
<script>
    const stayNotice = document.getElementById('stay-notice');
    const closeStayNotice = document.getElementById('close-stay-notice');
    closeStayNotice?.addEventListener('click', () => stayNotice?.remove());
    stayNotice?.addEventListener('click', event => { if (event.target === stayNotice) stayNotice.remove(); });
</script>
<?php endif; ?>
</body>
</html>
