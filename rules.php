<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
$student = session_student();

function rules_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$programmes = [
    [
        'id' => 'dba',
        'short' => 'DBA',
        'name' => 'Doctor of Business Administration (DBA)',
        'image' => 'assets/roadmap-dba.png',
        'alt' => 'DBA Learner’s Journey roadmap from enrolment to viva voce',
    ],
    [
        'id' => 'phd',
        'short' => 'PhD',
        'name' => 'Doctor of Philosophy (PhD)',
        'image' => 'assets/roadmap-phd.png',
        'alt' => 'PhD roadmap from Year 1 to Year 3',
    ],
    [
        'id' => 'mphil',
        'short' => 'MPhil',
        'name' => 'Master of Philosophy (MPhil)',
        'image' => 'assets/roadmap-mphil.png',
        'alt' => 'MPhil roadmap from Year 1 to Year 2',
    ],
];

$currentProgramme = strtoupper(trim((string)($student['programme'] ?? '')));
if ($currentProgramme === '') {
    $currentProgramme = 'DBA';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Programme rules and guidance for HELP University postgraduate students.">
    <title>Rules &amp; Guidance | ICompliance</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="assets/student-dashboard.css">
    <link rel="stylesheet" href="assets/rules.css">
</head>
<body class="student-portal-page">
<div class="app-shell">
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="dashboard-student.php" aria-label="Student dashboard">
                <span class="brand-mark" aria-hidden="true">I</span>
                <span class="brand-name">ICompliance</span>
            </a>
            <span class="office-name">HELP University <span>Student Tracking Portal</span></span>
            <nav class="navigation" aria-label="Student navigation">
                <a class="nav-link" href="dashboard-student.php">My Dashboard</a>
                <a class="nav-link active" href="rules.php" aria-current="page">Rules &amp; Guidance</a>
                <a class="nav-link" href="profile-student.php">My Profile</a>
                <a class="nav-link" href="submission-form.php">Submit Proof</a>
                <?= logout_control() ?>
            </nav>
        </div>
    </header>

    <main class="content rules-page" id="main-content">
        <a class="back-link" href="dashboard-student.php">← Back to My Dashboard</a>

        <header class="page-header rules-page-header">
            <div>
                <p class="eyebrow">Student Resources</p>
                <h1>Rules &amp; Guidance</h1>
                <p class="record-count">Review the candidature extension, deferment and postgraduate journey information for each programme.</p>
            </div>
            <span class="rules-header-mark" aria-hidden="true">i</span>
        </header>

        <section class="rules-intro" aria-labelledby="rules-intro-heading">
            <div>
                <p class="eyebrow">Important information</p>
                <h2 id="rules-intro-heading">Plan ahead and keep your programme record up to date</h2>
                <p>These rules are provided as a student reference. If you are approaching your maximum candidature period or need clarification, contact the ELM Graduate School immediately.</p>
            </div>
            <div class="rules-current-programme">
                <span>Your programme</span>
                <strong><?= rules_e($currentProgramme) ?></strong>
            </div>
        </section>

        <nav class="rules-jump" aria-label="Programme rules">
            <?php foreach ($programmes as $programme): ?>
                <a href="#<?= rules_e($programme['id']) ?>"><?= rules_e($programme['short']) ?> rules <span aria-hidden="true">↓</span></a>
            <?php endforeach; ?>
        </nav>

        <div class="rules-list">
            <?php foreach ($programmes as $programme): ?>
                <article class="rules-programme" id="<?= rules_e($programme['id']) ?>" aria-labelledby="<?= rules_e($programme['id']) ?>-heading">
                    <header class="rules-programme-heading">
                        <div>
                            <p class="eyebrow"><?= rules_e($programme['short']) ?> programme</p>
                            <h2 id="<?= rules_e($programme['id']) ?>-heading"><?= rules_e($programme['name']) ?></h2>
                        </div>
                        <?php if ($currentProgramme === $programme['short']): ?>
                            <span class="rules-current-badge">Your programme</span>
                        <?php endif; ?>
                    </header>

                    <figure class="roadmap-figure">
                        <img src="<?= rules_e($programme['image']) ?>" alt="<?= rules_e($programme['alt']) ?>" loading="lazy">
                        <figcaption>Postgraduate journey roadmap</figcaption>
                    </figure>

                    <div class="rules-sections">
                        <section class="rule-section">
                            <h3>Extension of Candidature</h3>
                            <p>Students who are unable to complete their dissertation or thesis within the maximum candidature period must apply for an extension no later than <strong>three (3) months before the period expires</strong>.</p>
                            <p>Students who exceed the maximum candidature period without an approved extension will be automatically dropped from the programme, and their names will be removed from the Registry.</p>
                            <h4>Please note</h4>
                            <ul>
                                <li>Applications must be supported by <strong>strong justification</strong> and <strong>relevant supporting documents</strong>.</li>
                                <li>All applications require <strong>endorsement from your Supervisor or Departmental Head/Dean</strong> before submission to the Dissertation Committee, Academic Board and other stakeholders for approval.</li>
                                <li>If your maximum candidature is approaching its expiry date, contact the <strong>ELM Graduate School</strong> immediately for guidance and support.</li>
                            </ul>
                        </section>

                        <section class="rule-section deferment-section">
                            <h3>Deferment</h3>
                            <div class="deferment-columns">
                                <div>
                                    <h4>For Malaysian Students</h4>
                                    <ol>
                                        <li>Any student who wishes to defer their studies must inform the Registry in writing, stating the reason for deferment.</li>
                                        <li>Refer to the University Handbook for more information.</li>
                                        <li>Any approved period of deferment shall be included in the calculation of the student’s maximum candidature period.</li>
                                    </ol>
                                </div>
                                <div>
                                    <h4>For International Students</h4>
                                    <ol>
                                        <li>A maximum of <strong>two (2) deferments / two (2) semesters</strong> is allowed for the entire duration of the programme.</li>
                                        <li>Refer to the University Handbook or ISSD for more information.</li>
                                        <li>Any approved period of deferment shall be included in the calculation of the student’s maximum candidature period.</li>
                                    </ol>
                                </div>
                            </div>
                        </section>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <aside class="rules-contact">
            <strong>Need help?</strong>
            <span>Contact the ELM Graduate School before submitting an extension or deferment request.</span>
        </aside>
    </main>
</div>
</body>
</html>
