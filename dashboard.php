<?php
require __DIR__ . '/app/dashboard.php';
$reviewPreview = PHP_SAPI === 'cli-server' && ($_GET['preview'] ?? '') === 'review';
$user = $reviewPreview ? ['role'=>'admin'] : require_user('admin');
$feedback = $_SESSION['dashboard_feedback'] ?? null;
unset($_SESSION['dashboard_feedback']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if ($reviewPreview) { http_response_code(405); exit('Frontend preview does not save decisions.'); }
 verify_csrf();
 try {
  $id=filter_var($_POST['submission_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
  if (!$id) throw new InvalidArgumentException('Invalid submission.');
  review_submission($user,$id,submission_text($_POST,'decision',20,true),submission_text($_POST,'review_remarks',2000));
  $_SESSION['dashboard_feedback']=['ok'=>true,'text'=>'Submission #'.$id.' reviewed successfully.'];
 } catch (InvalidArgumentException|DomainException $ex) {
  $_SESSION['dashboard_feedback']=['ok'=>false,'text'=>$ex->getMessage()];
 } catch (RuntimeException $ex) {
  error_log((string)$ex);
  $_SESSION['dashboard_feedback']=['ok'=>false,'text'=>'Review could not be saved. Reload the queue and try again.'];
 }
 header('Location: dashboard.php#pending-heading',true,303); exit;
}
$students = $reviewPreview ? json_decode(file_get_contents(__DIR__.'/data/students.json'),true,512,JSON_THROW_ON_ERROR) : all_students();

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

$pending = $reviewPreview ? array_map(function ($student, $index) {
 return ['id'=>9001+$index,'name'=>$student['name'],'student_id'=>$student['id'],'faculty'=>$student['faculty'],'kind'=>$index ? 'Exit' : 'Entry','location'=>'Local','country'=>'Malaysia','location_details'=>'Kuala Lumpur','submitted_at'=>'2026-10-04 09:15:00','start_date'=>'2026-10-03','end_date'=>'2026-10-03','remarks'=>'Please review my passport stamp and event date.','preview'=>true];
},array_slice($students,0,2),[0,1]) : pending_reviews();
$attentionAlerts = dashboard_alerts($students, $pending, new DateTimeImmutable('today'));

extract(dashboard_alert_page($attentionAlerts, $_GET), EXTR_SKIP);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ISSD compliance dashboard for HELP University.">
    <title>Dashboard | ICompliance</title>
    <link rel="stylesheet" href="assets/styles.css"><link rel="stylesheet" href="assets/submissions.css"><link rel="stylesheet" href="assets/dashboard.css">

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

        .alert-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 18px;
        }

        .filter-control {
            display: grid;
            gap: 6px;
            color: var(--muted);
            font-size: .78rem;
            font-weight: 800;
        }

        .select-wrap {
            position: relative;
            display: inline-block;
        }

        .styled-select {
            min-width: 165px;
            min-height: 40px;
            appearance: none;
            padding: 8px 38px 8px 13px;
            border: 1px solid #cbd5df;
            border-radius: 8px;
            background: #f8fafc;
            color: var(--ink);
            font: inherit;
            font-size: .84rem;
            font-weight: 700;
            cursor: pointer;
        }

        .styled-select:hover {
            border-color: var(--red);
            background: #fff;
        }

        .styled-select:focus-visible {
            outline: 3px solid rgba(23, 105, 170, .25);
            border-color: var(--focus);
        }

        .select-wrap::after {
            content: "";
            position: absolute;
            top: 50%;
            right: 15px;
            width: 7px;
            height: 7px;
            border-right: 2px solid #657384;
            border-bottom: 2px solid #657384;
            pointer-events: none;
            transform: translateY(-65%) rotate(45deg);
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
        <a class="nav-link" href="submission-review.php">Submission Reviews</a><?php echo logout_control(); ?></nav>
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

        <?php require __DIR__.'/app/dashboard-reviews.php'; ?>
<section class="alerts-section" aria-labelledby="alerts-heading">
            <div class="alerts-heading">
                <h2 id="alerts-heading">Alerts</h2>
                <p><?= $attentionCount ?> matching students of <?= count($attentionAlerts) ?> students requiring attention</p>
                <form class="alert-filters" method="get" action="dashboard.php#alerts-heading">
                    <?php
// Retain nested query values safely through normal URL encoding and escaped HTML.
foreach ($_GET as $key=>$value) {
 if (in_array($key,['page','per_page','level'],true)) continue;
 $encoded=http_build_query([$key=>$value]);
 foreach (explode('&',$encoded) as $pair) {
  [$name,$item]=array_pad(explode('=',$pair,2),2,'');
  echo '<input type="hidden" name="'.e(urldecode($name)).'" value="'.e(urldecode($item)).'">';
 }
}
?>
                    <label class="filter-control">
                        <span>Display</span>

                        <span class="select-wrap">
                            <select
                                class="styled-select"
                                name="per_page"
                                aria-describedby="filter-help"
                            >
                                <option value="5" <?= $perPage === 5 ? 'selected' : '' ?>>
                                    5 students
                                </option>

                                <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>
                                    10 students
                                </option>

                                <option value="20" <?= $perPage === 20 ? 'selected' : '' ?>>
                                    20 students
                                </option>

                                <option value="0" <?= $perPage === 0 ? 'selected' : '' ?>>
                                    All students
                                </option>
                            </select>
                        </span>
                    </label>

                    <label class="filter-control">
                        <span>Alert level</span>

                        <span class="select-wrap">
                            <select
                                class="styled-select"
                                name="level"
                                aria-describedby="filter-help"
                            >
                                <option value="all" <?= $level === 'all' ? 'selected' : '' ?>>
                                    All alerts
                                </option>

                                <option value="medium" <?= $level === 'medium' ? 'selected' : '' ?>>
                                    Medium / Yellow
                                </option>

                                <option value="pending" <?= $level === 'pending' ? 'selected' : '' ?>>Pending reviews / Blue</option><option value="high" <?= $level === 'high' ? 'selected' : '' ?>>
                                    High / Red
                                </option>
                            </select>
                        </span>
                    </label>

                    <input type="hidden" name="page" value="1">
                    <p id="filter-help">Filters apply immediately. High/Medium use the highest severity per student; Pending includes every student awaiting review.</p>
                </form>
            </div>

            <?php if (!$visibleAlerts): ?>
                <div class="no-alerts">
                    No alerts match this filter.
                </div>
            <?php else: ?>
                <div class="alert-list">
                    <?php foreach ($visibleAlerts as $alert): ?>
                        <?php
                        $student = $alert['student'];
                        $statusClass = $alert['severity'] === 'danger'
                            ? 'badge-non-compliant'
                            : ($alert['severity'] === 'info' ? 'badge-pending' : 'badge-warning');
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
                                            <?= e(['danger'=>'High','warning'=>'Medium','info'=>'Pending review'][$alert['severity']]) ?>
                                        </span>
                                    </div>

                                    <div class="alert-meta">
                                        <?= e($student['id']) ?>
                                        ·
                                        <?= e($student['faculty']) ?>
                                    </div>

                                    <ul class="alert-reasons">
                                        <?php foreach ($alert['issues'] as $issue): ?>
                                            <li><?= e($issue['reason']) ?> <a href="<?= e($issue['url']) ?>" aria-label="<?= e(($issue['severity']==='info' ? 'Review: ' : 'View: ').$issue['reason'].' for '.$student['name']) ?>"><?= $issue['severity']==='info' ? 'Review submission' : 'View' ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>

                            <div class="alert-actions">
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
            <?php if ($page > 1): ?>
                <a
                    class="button secondary"
                    href="<?= e(dashboardPageUrl($page - 1, $perPage, $level)) ?>"
                >
                    Previous
                </a>
            <?php else: ?>
                <button class="button secondary" disabled>
                    Previous
                </button>
            <?php endif; ?>

            <p>
                Page <?= $page ?> of <?= $totalPages ?>
            </p>

            <?php if ($page < $totalPages): ?>
                <a
                    class="button primary"
                    href="<?= e(dashboardPageUrl($page + 1, $perPage, $level)) ?>"
                >
                    Next
                </a>
            <?php else: ?>
                <button class="button primary" disabled>
                    Next
                </button>
            <?php endif; ?>
        </nav>
    </main>
</div>
<script src="assets/submission-reviews.js"></script><script src="assets/dashboard-filters.js"></script>
</body>
</html>
