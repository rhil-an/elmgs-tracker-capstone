<?php
$dataPath = __DIR__ . '/data/students.json';
$students = json_decode(file_get_contents($dataPath), true, 512, JSON_THROW_ON_ERROR);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
    }

    return $letters;
}

date_default_timezone_set('Asia/Kuala_Lumpur');
$today = new DateTimeImmutable('today');
$attentionAlerts = [];

foreach ($students as $student) {
    $reasons = [];
    $severity = 'warning';
    $daysUntilExpiry = PHP_INT_MAX;

    if (($student['status'] ?? '') === 'Non-Compliant') {
        $severity = 'danger';
        $reasons[] = 'Compliance status is non-compliant';
    } elseif (($student['status'] ?? '') === 'Warning') {
        $reasons[] = 'Compliance status requires attention';
    }

    if (!empty($student['visaExpiry'])) {
        $expiryDate = new DateTimeImmutable($student['visaExpiry']);
        $daysUntilExpiry = (int) $today->diff($expiryDate)->format('%r%a');

        if ($daysUntilExpiry < 0) {
            $severity = 'danger';
            $reasons[] = 'Visa has expired';
        } elseif ($daysUntilExpiry <= 30) {
            $reasons[] = 'Visa expires within 30 days';
        }
    }



    $checkinDays = max(0, (int)(new DateTimeImmutable($student['lastCheckIn']))->diff($today)->format('%r%a'));
    if ($checkinDays > 30) { $reasons[] = 'Check-in is overdue'; }

    if ($reasons) {
        $attentionAlerts[] = [
            'student' => $student,
            'severity' => $severity,
            'reasons' => $reasons,
            'visaDays' => $daysUntilExpiry ?? PHP_INT_MAX,
            'checkinDays' => $checkinDays
        ];
    }
}

usort($attentionAlerts, function ($a, $b) {
    $priority = ['danger' => 1, 'warning' => 2];
    return ($priority[$a['severity']] <=> $priority[$b['severity']])
        ?: ($a['visaDays'] <=> $b['visaDays'])
        ?: ($b['checkinDays'] <=> $a['checkinDays'])
        ?: strcmp($a['student']['id'], $b['student']['id']);
});

$attentionCount = count($attentionAlerts);
$totalPages = max(1, (int)ceil($attentionCount / 5));
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = max(1, min($totalPages, $requestedPage ?: 1));
$visibleAlerts = array_slice($attentionAlerts, ($page - 1) * 5, 5);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ISSD compliance dashboard for HELP University.">
    <title>Dashboard | ICompliance</title>
    <link rel="stylesheet" href="assets/styles.css">

    <style>
        .dashboard-content {
            max-width: 1440px;
        }

        .welcome-banner {
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

        .welcome-banner h2 {
            margin: 0 0 6px;
            color: var(--ink);
            font-size: clamp(1.5rem, 3vw, 2.1rem);
            letter-spacing: -.03em;
        }

        .welcome-banner p {
            margin: 0;
            color: var(--muted);
        }

        .welcome-mark {
            display: grid;
            width: 58px;
            height: 58px;
            place-items: center;
            border-radius: 50%;
            background: var(--red);
            color: #fff;
            font-size: 1.2rem;
            font-weight: 800;
        }

        .alerts-section {
            padding: 28px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 28px rgba(34, 43, 55, .06);
        }

        .alerts-heading {
            margin-bottom: 22px;
        }

        .alerts-heading h2 {
            margin: 0 0 5px;
            color: var(--ink);
            font-size: 1.4rem;
            letter-spacing: -.02em;
        }

        .alerts-heading p {
            margin: 0;
            color: var(--muted);
        }

        .alert-list {
            display: grid;
            gap: 16px;
        }

        .alert-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 22px;
            align-items: center;
            padding: 20px 22px;
            border: 1px solid var(--line);
            border-left: 5px solid var(--amber);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 5px 16px rgba(34, 43, 55, .05);
        }

        .alert-card.danger {
            border-left-color: var(--danger);
        }

        .alert-student {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            min-width: 0;
        }

        .student-avatar {
            display: grid;
            flex: 0 0 50px;
            width: 50px;
            height: 50px;
            place-items: center;
            border-radius: 12px;
            background: #dce5ed;
            color: #34485c;
            font-weight: 800;
        }

        .alert-info {
            min-width: 0;
        }

        .alert-name-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 5px;
        }

        .alert-name {
            color: var(--ink);
            font-size: 1rem;
            font-weight: 800;
        }

        .alert-meta {
            margin-bottom: 9px;
            color: #8492a5;
            font-family: "Courier New", monospace;
            font-size: .8rem;
        }

        .alert-reasons {
            display: grid;
            gap: 4px;
            margin: 0;
            padding: 0;
            color: var(--amber);
            font-size: .86rem;
            list-style: none;
        }

        .alert-card.danger .alert-reasons {
            color: var(--danger);
        }

        .alert-reasons li::before {
            content: "•";
            margin-right: 8px;
            font-weight: 800;
        }

        .alert-actions {
            display: grid;
            gap: 9px;
            min-width: 126px;
        }

        .dashboard-button {
            display: inline-flex;
            min-height: 38px;
            align-items: center;
            justify-content: center;
            padding: 8px 13px;
            border-radius: 8px;
            font: inherit;
            font-size: .82rem;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .dashboard-button.primary {
            background: #172f5c;
            color: #fff;
        }

        .dashboard-button.primary:hover,
        .dashboard-button.primary:focus-visible {
            background: #0f2347;
        }

        .dashboard-button.secondary {
            border: 1px solid #d8e0e8;
            background: #f4f7fa;
            color: #34485c;
        }

        .dashboard-button.secondary:hover,
        .dashboard-button.secondary:focus-visible {
            background: #e9eef3;
        }

        .no-alerts {
            padding: 35px 20px;
            color: var(--muted);
            text-align: center;
        }

        @media (max-width: 700px) {
            .welcome-banner {
                padding: 22px;
            }

            .alerts-section {
                padding: 20px 16px;
            }

            .alert-card {
                grid-template-columns: 1fr;
                gap: 17px;
                padding: 18px 16px;
            }

            .alert-actions {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
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
          <a class="nav-link active" href="dashboard.php" aria-current="page">Dashboard</a>
          <a class="nav-link" href="student-records.php">Student Records</a>
        </nav>
      </div>
    </header>

    <main class="content dashboard-content" id="main-content">
        <header class="page-header">
            <div>
                <p class="eyebrow">International Student Office</p>
                <h1>Dashboard</h1>
                <p class="record-count">
                    Monitoring overview for international student compliance
                </p>
            </div>

            <div class="header-accent" aria-hidden="true"></div>
        </header>

        <section class="welcome-banner" aria-labelledby="welcome-heading">
            <div>
                <h2 id="welcome-heading">Welcome, Administrator!</h2>
                <p>Here is the latest compliance activity requiring your attention.</p>
            </div>

            <div class="welcome-mark" aria-hidden="true">A</div>
        </section>

        <section class="alerts-section" aria-labelledby="alerts-heading">
            <div class="alerts-heading">
                <h2 id="alerts-heading">Alerts &amp; Actions</h2>
                <p><?= $attentionCount ?> students require attention</p>
            </div>

            <?php if (!$visibleAlerts): ?>
                <div class="no-alerts">
                    No students currently require attention.
                </div>
            <?php else: ?>
                <div class="alert-list">
                    <?php foreach ($visibleAlerts as $alert): ?>
                        <?php
                        $student = $alert['student'];
                        $statusClass = $alert['severity'] === 'danger'
                            ? 'badge-non-compliant'
                            : 'badge-warning';
                        ?>
                        <article class="alert-card <?= e($alert['severity']) ?>">
                            <div class="alert-student">
                                <div class="student-avatar" aria-hidden="true">
                                    <?= e(initials($student['name'])) ?>
                                </div>

                                <div class="alert-info">
                                    <div class="alert-name-row">
                                        <span class="alert-name">
                                            <?= e($student['name']) ?>
                                        </span>

                                        <span class="badge <?= $statusClass ?>">
                                            <?= e($student['status']) ?>
                                        </span>
                                    </div>

                                    <div class="alert-meta">
                                        <?= e($student['id']) ?>
                                        ·
                                        <?= e($student['faculty']) ?>
                                    </div>

                                    <ul class="alert-reasons">
                                        <?php foreach ($alert['reasons'] as $reason): ?>
                                            <li><?= e($reason) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>

                            <div class="alert-actions">
                                <a
                                    class="dashboard-button primary"
                                    href="student-profile.php?id=<?= urlencode($student['id']) ?>"
                                >
                                    View Profile
                                </a>

                                <button
                                    class="dashboard-button secondary"
                                    type="button"
                                    disabled
                                    title="Notification functionality will be added later"
                                >
                                    Send Notice
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <nav class="pagination" aria-label="Alert pages">
            <?php if ($page > 1): ?><a class="button secondary" href="dashboard.php?page=<?= $page - 1 ?>#alerts-heading">Previous</a><?php else: ?><button class="button secondary" disabled>Previous</button><?php endif; ?>
            <p>Page <?= $page ?> of <?= $totalPages ?></p>
            <?php if ($page < $totalPages): ?><a class="button primary" href="dashboard.php?page=<?= $page + 1 ?>#alerts-heading">Next</a><?php else: ?><button class="button primary" disabled>Next</button><?php endif; ?>
        </nav>
    </main>
</div>
</body>
</html>
