<?php
declare(strict_types=1);

require __DIR__ . '/app/progress.php';
$student = session_student();
$studentId = $student['id'];

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
    : ($visaDaysRemaining <= 30 ? 'Expiring Soon' : 'Valid');

$visaClass = $visaDaysRemaining < 0
    ? 'visa-expired'
    : ($visaDaysRemaining <= 30 ? 'visa-warning' : 'visa-valid');

$statusClass = match ($student['status']) {
    'Non-Compliant' => 'badge-non-compliant',
    'Warning' => 'badge-warning',
    default => 'badge-compliant'
};

$submissionHistory = submission_history($studentId);
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

    <style>
        .student-dashboard {
            max-width: 1440px;
        }

        .student-welcome {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 28px;
            padding: 28px 32px;
            border: 1px solid var(--line);
            border-left: 5px solid var(--red);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 28px rgba(34, 43, 55, .06);
        }

        .student-welcome h2 {
            margin: 0 0 6px;
            color: var(--ink);
            font-size: clamp(1.5rem, 3vw, 2.1rem);
            letter-spacing: -.03em;
        }

        .student-welcome p {
            margin: 0;
            color: var(--muted);
        }

        .student-welcome-mark {
            display: grid;
            width: 58px;
            height: 58px;
            place-items: center;
            border-radius: 50%;
            background: var(--red);
            color: #fff;
            font-weight: 800;
        }

        .student-summary {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 24px;
        }

        .summary-card {
            min-height: 145px;
        }

        .summary-label {
            margin-bottom: 12px;
            color: var(--muted);
            font-size: .82rem;
            font-weight: 800;
        }

        .summary-value {
            margin: 0;
            color: var(--ink);
            font-size: 1.15rem;
            font-weight: 800;
        }

        .summary-note {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: .82rem;
        }

        .submission-section {
            margin-top: 28px;
        }

        .section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 14px;
        }

        .section-heading h2 {
            margin: 0 0 4px;
            font-size: 1.3rem;
        }

        .section-heading p {
            margin: 0;
            color: var(--muted);
            font-size: .88rem;
        }

        .submission-card {
            overflow: hidden;
            padding: 24px;
            border: 1px solid var(--line);
            border-radius: 13px;
            background: #fff;
            box-shadow: 0 8px 20px rgba(34, 43, 55, .04);
        }

        .submission-table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
        }

        .submission-table th {
            padding: 13px 15px;
            background: #f7f8fa;
            border-bottom: 1px solid var(--line);
            color: #536071;
            font-size: .74rem;
            text-align: left;
            text-transform: uppercase;
        }

        .submission-table td {
            padding: 16px 15px;
            border-bottom: 1px solid #e7ebef;
            color: #3c4857;
            font-size: .88rem;
        }

        .submission-table tr:last-child td {
            border-bottom: 0;
        }

        .submission-date {
            white-space: nowrap;
            font-weight: 800;
        }

        .submission-scroll {
            overflow-x: auto;
        }

        .student-help {
            margin-top: 24px;
            padding: 20px 24px;
            border: 1px solid #d8e4ef;
            border-radius: 12px;
            background: #f3f8fc;
        }

        .student-help h2 {
            margin: 0 0 5px;
            color: var(--ink);
            font-size: 1rem;
        }

        .student-help p {
            margin: 0;
            color: var(--muted);
            font-size: .88rem;
        }

        .student-submit-action {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
        }

        @media (max-width: 850px) {
            .student-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 620px) {
            .student-welcome {
                padding: 22px;
            }

            .student-summary {
                grid-template-columns: 1fr;
            }

            .student-welcome-mark {
                display: none;
            }
        }
    </style>
</head>

<body>
<div class="app-shell">

    <header class="topbar">
        <div class="topbar-inner">
            <a
                class="brand"
                href="dashboard-student.php"
                aria-label="Student dashboard"
            >
                <span class="brand-mark" aria-hidden="true">I</span>
                <span class="brand-name">ICompliance</span>
            </a>

            <span class="office-name">
                HELP University
                <span>Student Portal</span>
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
                    href="#submissions"
                >
                    Submission History
                </a>
            <?php echo logout_control(); ?></nav>
        </div>
    </header>

    <main class="content student-dashboard" id="main-content">

        <header class="page-header">
            <div>
                <p class="eyebrow">Student Portal</p>
                <h1>My Dashboard</h1>
                <p class="record-count">
                    View your profile and compliance submissions
                </p>
            </div>

            <div class="header-accent" aria-hidden="true"></div>
        </header>

        <section
            class="student-welcome"
            aria-labelledby="welcome-heading"
        >
            <div>
                <h2 id="welcome-heading">
                    Welcome back, <?= e($student['name']) ?>!
                </h2>

                <p>
                    Keep your student information and compliance submissions up to date.
                </p>
            </div>

            <div class="student-welcome-mark" aria-hidden="true">
                <?= e(initials($student['name'])) ?>
            </div>
        </section>

        <section
            class="profile-hero"
            aria-labelledby="student-profile-heading"
        >
            <div
                class="profile-avatar"
                aria-label="Student initials"
            >
                <?= e(initials($student['name'])) ?>
            </div>

            <div>
                <p class="eyebrow">My Student Profile</p>

                <h1 id="student-profile-heading">
                    <?= e($student['name']) ?>
                </h1>

                <p class="profile-id">
                    <?= e($student['id']) ?>
                    ·
                    <?= e($student['nationality']) ?>
                </p>

                <p class="profile-meta">
                    <span>
                        <strong>Faculty / Programme:</strong>
                        <?= e($student['faculty']) ?>
                    </span>

                    <span>
                        <strong>Current location:</strong>
                        <?= e($student['currentLocation']) ?>
                    </span>
                </p>
            </div>

            <span class="badge profile-badge <?= $statusClass ?>">
                <?= e($student['status']) ?>
            </span>
        <?= stay_progress_html(student_stay_progress($studentId)) ?>
</section>

        <section
            class="profile-grid student-summary"
            aria-label="Student summary"
        >
            <article class="profile-card summary-card">
                <p class="summary-label">Compliance Status</p>

                <p class="summary-value">
                    <span class="badge <?= $statusClass ?>">
                        <?= e($student['status']) ?>
                    </span>
                </p>

                <p class="summary-note">
                    Current compliance result
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
                    <?= $daysSinceCheckIn ?> days ago
                </p>
            </article>
        </section>

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
                        View the status of your previous submissions.
                    </p>
                </div>
            </div>

            <div class="submission-card">
                <div class="submission-scroll">
                    <table class="submission-table">
                        <caption class="sr-only">
                            Student submission history
                        </caption>

                        <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Submission Type</th>
                            <th scope="col">Description</th>
                            <th scope="col">Status</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php if (!$submissionHistory): ?><tr><td colspan="4">No submissions yet.</td></tr><?php endif; ?>
<?php foreach ($submissionHistory as $submission): ?>
                            <tr>
                                <td class="submission-date">
                                    <?= e((new DateTimeImmutable($submission['submitted_at']))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('d M Y H:i')) ?>
                                </td>

                                <td>
                                    <?= e(match ($submission['kind']) { 'CheckIn'=>'Location check-in', 'Entry'=>'Entry declaration', default=>'Exit declaration' } . ' · '.$submission['start_date'].' → '.$submission['end_date']) ?>
                                </td>

                                <td>
                                    <?= e($submission['country'].' · '.$submission['location_details'].' · '.$submission['remarks'].' · '.($submission['review_remarks'] ?? 'Awaiting review')) ?>
<?php if ($submission['reviewed_at']): ?><p>Reviewed <?= e((new DateTimeImmutable($submission['reviewed_at']))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('d M Y H:i')) ?></p><?php endif; ?>
<?php if ($submission['resubmission_of']): ?><p>Replaces #<?= (int)$submission['resubmission_of'] ?></p><?php endif; ?>
<?php foreach (submission_files((int)$submission["id"]) as $file): ?><p><a href="evidence.php?id=<?= $file['id'] ?>">Download <?= e($file['original_name']) ?></a></p><?php endforeach; ?>
<?php if ($submission['status']==='Rejected'): ?><p><a href="submission-form.php?resubmit=<?= $submission['id'] ?>">Resubmit proof</a></p><?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge <?= e(match ($submission['status']) { 'Verified'=>'badge-compliant', 'Rejected'=>'badge-non-compliant', default=>'badge-warning' }) ?>">
                                        <?= e($submission['status']) ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="student-submit-action">
                <a
                    class="button primary"
                    href="submission-form.php"
                >
                    Submit New Proof
                </a>
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
                Submit the required documents or contact the International Student Office
                if any information on your profile is incorrect.
            </p>
        </section>

    </main>
</div>
</body>
</html>
