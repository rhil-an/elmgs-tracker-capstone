<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Kuala_Lumpur');

$students = json_decode(
    file_get_contents(__DIR__ . '/data/students.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);

/*
 * Prototype student account.
 * Later, this should come from the logged-in student's session.
 */
$studentId = 'B2500004';

$student = null;

foreach ($students as $record) {
    if (($record['id'] ?? '') === $studentId) {
        $student = $record;
        break;
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$today = (new DateTimeImmutable('today'))->format('Y-m-d');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location = $_POST['current_location'] ?? '';

    if ($location === 'Local') {
        /*
         * Prototype:
         * Local submissions do not require additional evidence.
         * Later, this should save the submission before redirecting.
         */
        header('Location: dashboard-student.php');
        exit;
    }

    if ($location === 'Overseas') {
        /*
         * Pass the student information to the overseas form.
         * Later, this can be replaced with session data.
         */
        $query = http_build_query([
            'name' => $student['name'] ?? '',
            'student_id' => $student['id'] ?? '',
            'submission_date' => $today
        ]);

        header('Location: submission-overseas.php?' . $query);
        exit;
    }

    $error = 'Please select your current location.';
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Submit Proof | ICompliance</title>

    <link rel="stylesheet" href="assets/styles.css">

    <style>
        .submission-page {
            max-width: 900px;
        }

        .submission-card {
            padding: 30px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 28px rgba(34, 43, 55, .06);
        }

        .submission-card h2 {
            margin: 0 0 7px;
            color: var(--ink);
        }

        .submission-card > p {
            margin: 0 0 26px;
            color: var(--muted);
        }

        .submission-form {
            display: grid;
            gap: 20px;
        }

        .submission-field {
            display: grid;
            gap: 7px;
        }

        .submission-field label {
            color: #495567;
            font-size: .84rem;
            font-weight: 800;
        }

        .submission-field select {
            width: 100%;
            min-height: 45px;
            padding: 10px 13px;
            border: 1px solid #bfc8d2;
            border-radius: 8px;
            background: #fff;
            color: var(--ink);
            font: inherit;
        }

        .submission-field select:focus {
            outline: 3px solid rgba(23, 105, 170, .25);
            border-color: var(--focus);
        }

        .submission-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 8px;
        }

        .submission-button {
            min-height: 42px;
            padding: 9px 17px;
            border: 0;
            border-radius: 8px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
        }

        .submission-button.primary {
            background: var(--red);
            color: #fff;
        }

        .submission-button.primary:hover {
            background: var(--red-dark);
        }

        .submission-button.secondary {
            border: 1px solid #cbd5df;
            background: #fff;
            color: var(--ink);
            text-decoration: none;
        }

        .error-message {
            margin-bottom: 22px;
            padding: 14px 16px;
            border: 1px solid #f0b8b8;
            border-radius: 9px;
            background: #fff4f4;
            color: #a32929;
            font-weight: 700;
        }

        .prototype-note {
            margin-top: 22px;
            padding: 14px 16px;
            border-radius: 9px;
            background: #f3f8fc;
            color: var(--muted);
            font-size: .84rem;
        }

        @media (max-width: 600px) {
            .submission-card {
                padding: 22px 17px;
            }

            .submission-actions {
                flex-direction: column-reverse;
            }

            .submission-button {
                width: 100%;
                text-align: center;
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
                <a class="nav-link" href="dashboard-student.php">
                    My Dashboard
                </a>

                <a class="nav-link active" href="submission-form.php">
                    Submit Proof
                </a>
            </nav>
        </div>
    </header>

    <main class="content submission-page" id="main-content">

        <a class="back-link" href="dashboard-student.php">
            ← Back to My Dashboard
        </a>

        <header class="page-header">
            <div>
                <p class="eyebrow">Student Portal</p>

                <h1>Submit Proof of Location</h1>

                <p class="record-count">
                    Select your current location to continue
                </p>
            </div>

            <div class="header-accent" aria-hidden="true"></div>
        </header>

        <section class="submission-card">

            <h2>Current Location</h2>

            <p>
                Please select whether you are currently in Malaysia or overseas.
                Additional evidence is only required if you are overseas.
            </p>

            <?php if ($error !== ''): ?>
                <div class="error-message" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form
                class="submission-form"
                action="submission-form.php"
                method="post"
            >

                <div class="submission-field">
                    <label for="current-location">
                        Where are you currently?
                    </label>

                    <select
                        id="current-location"
                        name="current_location"
                        required
                    >
                        <option value="">
                            Select your current location
                        </option>

                        <option value="Local">
                            Malaysia
                        </option>

                        <option value="Overseas">
                            Overseas
                        </option>
                    </select>
                </div>

                <div class="submission-actions">

                    <a
                        class="submission-button secondary"
                        href="dashboard-student.php"
                    >
                        Cancel
                    </a>

                    <button
                        class="submission-button primary"
                        type="submit"
                    >
                        Continue
                    </button>

                </div>

            </form>

            <div class="prototype-note">
                Your student information is already associated with your
                account. You only need to select your current location.
            </div>

        </section>

    </main>

</div>
</body>
</html>
