<?php declare(strict_types=1);

require __DIR__ . '/app/submissions.php';

$student = session_student();
$user = current_user();

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$parent = filter_var(
    $_POST['resubmission_of'] ?? $_GET['resubmit'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
) ?: null;

$values = [
    'kind' => 'CheckIn',
    'location' => 'Local',
    'country' => 'Malaysia',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d'),
    'remarks' => ''
];

$error = '';

if ($parent) {
    $old = owned_submission($parent, $user);

    if (!$old || $old['status'] !== 'Rejected') {
        http_response_code(404);
        exit('Rejected submission not found.');
    }

    foreach ($values as $key => $value) {
        if (isset($old[$key])) {
            $values[$key] = $old[$key];
        }
    }

    if ($values['kind'] === 'CheckIn') {
        $values['start_date'] = $values['end_date'] = date('Y-m-d');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        if (
            isset($_POST['resubmission_of']) &&
            $_POST['resubmission_of'] !== '' &&
            !$parent
        ) {
            throw new InvalidArgumentException(
                'Invalid resubmission reference.'
            );
        }

        $data = validate_submission($_POST);

        $files = validate_evidence(
            $_FILES['evidence'] ?? [],
            $data['kind'] !== 'CheckIn' || $data['location'] === 'Overseas',
            $data['kind'] !== 'CheckIn'
        );

        save_submission($user, $data, $files, $parent);

        header(
            'Location: dashboard-student.php#submissions',
            true,
            303
        );
        exit;
    } catch (InvalidArgumentException $ex) {
        http_response_code(422);
        $error = $ex->getMessage();
    }

    foreach ($values as $key => $value) {
        if (is_string($_POST[$key] ?? null)) {
            $values[$key] = $_POST[$key];
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submit Proof | ICompliance</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="assets/submissions.css">
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <div class="topbar-inner">
                <a class="brand" href="dashboard-student.php">
                    <span class="brand-mark">I</span>
                    <span class="brand-name">
                        ICompliance
                    </span>
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
                    <?= logout_control() ?>
                </nav>
            </div>
        </header>

        <main class="content submission-page">
            <header class="page-header">
                <div>
                    <p class="eyebrow">
                        Student Portal
                    </p>
                    <h1>
                        <?= $parent ? 'Resubmit Proof' : 'Submit Proof' ?>
                    </h1>
                    <p>
                        Location check-ins describe today. Entry/exit declarations record one historical event date. The admin verifies your entered date against the attached passport image.
                    </p>
                </div>
            </header>

            <section class="submission-panel">
                <?php if ($error): ?>
                    <p role="alert" class="error-message">
                        <?= e($error) ?>
                    </p>
                <?php endif; ?>

                <?php if ($parent): ?>
                    <p>
                        Replacement for rejected record #<?= $parent ?>. The original record and evidence remain in your history.
                    </p>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="submission-fields">
                    <?= csrf_field() ?>

                    <input type="hidden" name="resubmission_of" value="<?= $parent ?? '' ?>">

                    <label>
                        Declaration type
                        <select name="kind" required>
                            <?php
                            $declarationTypes = [
                                'CheckIn' => 'Location check-in',
                                'Entry' => 'Entry into Malaysia',
                                'Exit' => 'Exit from Malaysia'
                            ];
                            ?>
                            <?php foreach ($declarationTypes as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $values['kind'] === $key ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Location
                        <select name="location" id="location" required>
                            <option value="Local" <?= $values['location'] === 'Local' ? 'selected' : '' ?>>
                                Malaysia
                            </option>
                            <option value="Overseas" <?= $values['location'] === 'Overseas' ? 'selected' : '' ?>>
                                Overseas
                            </option>
                        </select>
                    </label>

                    <label id="country-field" style="<?= $values['location'] === 'Overseas' ? '' : 'display: none;' ?>">
                        Country
                        <input type="text" name="country" id="country" value="<?= e($values['country']) ?>" maxlength="100" <?= $values['location'] === 'Overseas' ? 'required' : '' ?> placeholder="Enter the country you are in">
                    </label>

                    <label>
                        Entry / exit / check-in date
                        <input name="start_date" type="date" value="<?= e($values['start_date']) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>" required>
                    </label>

                    <label>
                        Remarks
                        <textarea name="remarks" maxlength="2000" rows="3" placeholder="Optional"><?= e($values['remarks']) ?></textarea>
                    </label>

                    <label>
                        Passport stamp image / check-in evidence
                        <input type="file" name="evidence[]" accept="image/jpeg,image/png,application/pdf" multiple>
                    </label>

                    <p>
                        Maximum two files, 5 MiB each. Entry/exit requires JPEG or PNG images showing the entered event date for admin verification. Check-ins accept JPEG, PNG or PDF. Evidence is required for overseas check-ins. Local check-ins may omit it. All submissions await admin review.
                    </p>

                    <button class="button primary" type="submit">
                        Submit for review
                    </button>
                    <a href="dashboard-student.php">Cancel</a>
                </form>
            </section>
        </main>
    </div>

    <script>
        const locationSelect = document.getElementById('location');
        const countryField = document.getElementById('country-field');
        const countryInput = document.getElementById('country');

        function updateCountryField() {
            if (locationSelect.value === 'Overseas') {
                countryField.style.display = '';
                countryInput.required = true;
            } else {
                countryField.style.display = '';
                countryField.style.display = 'none';
                countryInput.required = false;
                countryInput.value = '';
            }
        }

        locationSelect.addEventListener(
            'change',
            updateCountryField
        );

        updateCountryField();
    </script>
</body>
</html>