<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Kuala_Lumpur');

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/*
 * Student information is passed from submission-form.php.
 * Later, this should come from the logged-in student's session.
 */
$name = $_GET['name'] ?? $_POST['name'] ?? 'Ahmed Reza Karimi';

$studentId = $_GET['student_id']
    ?? $_POST['student_id']
    ?? 'B2500004';

$submissionDate = $_GET['submission_date']
    ?? $_POST['submission_date']
    ?? (new DateTimeImmutable('today'))->format('Y-m-d');

$isSubmitted = false;
$uploadedCount = 0;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $country = trim($_POST['where_are_you'] ?? '');

    if ($country === '') {
        $error = 'Please select your current country.';
    }

    if (
        $error === ''
        && (
            !isset($_FILES['stamp_evidence'])
            || !is_array($_FILES['stamp_evidence']['name'])
        )
    ) {
        $error = 'Please upload at least one supporting picture.';
    }

    if ($error === '') {

        $files = $_FILES['stamp_evidence'];

        foreach ($files['name'] as $index => $fileName) {

            if (
                isset($files['error'][$index])
                && $files['error'][$index] === UPLOAD_ERR_OK
                && !empty($fileName)
            ) {
                $uploadedCount++;
            }
        }

        if ($uploadedCount === 0) {
            $error = 'Please upload at least one valid picture.';
        } else {
            /*
             * Prototype:
             * Files are not permanently stored yet.
             * Later, the files can be saved to an uploads directory
             * or stored using a database/file-management system.
             */
            $isSubmitted = true;
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Overseas Submission | ICompliance</title>

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

        .submission-field input,
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

        .submission-field input[readonly] {
            background: #f3f5f7;
            color: #5d6875;
        }

        .submission-field input[type="file"] {
            padding: 9px;
            background: #f8fafc;
        }

        .submission-field input:focus,
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

        .submitted-message {
            margin-bottom: 22px;
            padding: 15px 17px;
            border: 1px solid #bce1c8;
            border-radius: 9px;
            background: var(--green-bg);
            color: var(--green);
            font-weight: 700;
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

        .file-help {
            color: var(--muted);
            font-size: .8rem;
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

                <a
                    class="nav-link"
                    href="dashboard-student.php"
                >
                    My Dashboard
                </a>

                <a
                    class="nav-link active"
                    href="submission-form.php"
                >
                    Submit Proof
                </a>

            </nav>

        </div>
    </header>

    <main class="content submission-page" id="main-content">

        <a class="back-link" href="submission-form.php">
            ← Back to Location Selection
        </a>

        <header class="page-header">

            <div>

                <p class="eyebrow">
                    Overseas Submission
                </p>

                <h1>
                    Submit Travel Evidence
                </h1>

                <p class="record-count">
                    Provide your overseas location and supporting evidence
                </p>

            </div>

            <div
                class="header-accent"
                aria-hidden="true"
            ></div>

        </header>

        <section class="submission-card">

            <h2>Overseas Details</h2>

            <p>
                You selected Overseas. Please provide your current country
                and upload a picture of your entry or exit stamp.
            </p>

            <?php if ($isSubmitted): ?>

                <div
                    class="submitted-message"
                    role="status"
                >
                    Your overseas submission has been received with
                    <?= $uploadedCount ?>
                    picture(s).
                </div>

                <a
                    class="submission-button primary"
                    href="dashboard-student.php"
                >
                    Return to Dashboard
                </a>

            <?php else: ?>

                <?php if ($error !== ''): ?>

                    <div
                        class="error-message"
                        role="alert"
                    >
                        <?= e($error) ?>
                    </div>

                <?php endif; ?>

                <form
                    class="submission-form"
                    method="post"
                    enctype="multipart/form-data"
                    action="submission-overseas.php"
                >

                    <!-- Student information is carried forward
                         from the location selection step. -->

                    <input
                        type="hidden"
                        name="name"
                        value="<?= e($name) ?>"
                    >

                    <input
                        type="hidden"
                        name="student_id"
                        value="<?= e($studentId) ?>"
                    >

                    <input
                        type="hidden"
                        name="submission_date"
                        value="<?= e($submissionDate) ?>"
                    >

                    <div class="submission-field">

                        <label for="where-are-you">
                            Where are you currently?
                        </label>

                        <select
                            id="where-are-you"
                            name="where_are_you"
                            required
                        >

                            <option value="">
                                Select a country
                            </option>

                            <option value="Singapore">
                                Singapore
                            </option>

                            <option value="Thailand">
                                Thailand
                            </option>

                            <option value="Indonesia">
                                Indonesia
                            </option>

                            <option value="Vietnam">
                                Vietnam
                            </option>

                            <option value="China">
                                China
                            </option>

                            <option value="India">
                                India
                            </option>

                            <option value="United Kingdom">
                                United Kingdom
                            </option>

                            <option value="Australia">
                                Australia
                            </option>

                            <option value="Other">
                                Other
                            </option>

                        </select>

                    </div>

                    <div class="submission-field">

                        <label for="stamp-evidence">
                            Upload entry or exit stamp picture(s)
                        </label>

                        <input
                            id="stamp-evidence"
                            name="stamp_evidence[]"
                            type="file"
                            accept="image/png,image/jpeg,image/jpg"
                            multiple
                            required
                        >

                        <small class="file-help">
                            You may upload up to two pictures.
                        </small>

                    </div>

                    <div class="submission-actions">

                        <a
                            class="submission-button secondary"
                            href="submission-form.php"
                        >
                            Back
                        </a>

                        <button
                            class="submission-button primary"
                            type="submit"
                        >
                            Submit Evidence
                        </button>

                    </div>

                </form>

            <?php endif; ?>

            <div class="prototype-note">
                File uploads are displayed for prototype purposes only and are
                not permanently stored yet.
            </div>

        </section>

    </main>

</div>

<script>
    const stampInput = document.querySelector('#stamp-evidence');

    if (stampInput) {
        stampInput.addEventListener('change', function () {

            if (this.files.length > 2) {

                alert(
                    'You can upload a maximum of two pictures.'
                );

                this.value = '';
            }

        });
    }
</script>

</body>
</html>
