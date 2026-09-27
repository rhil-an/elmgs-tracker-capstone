<?php
$dataPath = __DIR__ . '/data/students.json';
$students = json_decode(file_get_contents($dataPath), true, 512, JSON_THROW_ON_ERROR);
$faculties = array_values(array_unique(array_column($students, 'faculty')));
sort($faculties, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="ISSD Compliance Tracker student records for HELP University International Student Office.">
  <title>Student Records | ICompliance</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar" aria-label="Application navigation">
      <a class="brand" href="index.php" aria-label="ICompliance home">
        <span class="brand-mark" aria-hidden="true">I</span>
        <span>ICompliance</span>
      </a>
      <div class="office-name">
        <strong>HELP University</strong>
        <span>International Student Office</span>
      </div>
      <nav class="navigation" aria-label="Primary navigation">
        <a class="nav-link active" href="index.php" aria-current="page"><span aria-hidden="true">▦</span> Student Records</a>
        <a class="nav-link" href="#records"><span aria-hidden="true">◷</span> Check-ins</a>
        <a class="nav-link" href="#records"><span aria-hidden="true">◫</span> Visa Monitoring</a>
      </nav>
      <p class="sidebar-note">Local prototype<br><span>Student records only</span></p>
    </aside>

    <main class="content" id="main-content">
      <header class="page-header">
        <div>
          <p class="eyebrow">International Student Office</p>
          <h1>Student Records</h1>
          <p class="record-count" id="record-count" aria-live="polite">Loading records…</p>
        </div>
        <div class="header-accent" aria-hidden="true"></div>
      </header>

      <section class="records-panel" aria-labelledby="filters-heading">
        <div class="panel-heading">
          <div>
            <h2 id="filters-heading">Find student records</h2>
            <p>On smaller screens, scroll horizontally to view additional table columns.</p>
          </div>
        </div>
        <form class="filters" id="filters" novalidate>
          <div class="field search-field">
            <label for="student-search">Search students</label>
            <input id="student-search" name="search" type="search" placeholder="Search by name, student ID, or nationality" autocomplete="off">
          </div>
          <div class="field status-field">
            <label for="status-filter">Compliance Status</label>
            <select id="status-filter" name="status">
              <option value="">All Statuses</option>
              <option value="Compliant">Compliant</option>
              <option value="Warning">Warning</option>
              <option value="Non-Compliant">Non-Compliant</option>
            </select>
          </div>
          <div class="field faculty-field">
            <label for="faculty-filter">Faculty / Program</label>
            <select id="faculty-filter" name="faculty">
              <option value="">All Faculties</option>
              <?php foreach ($faculties as $faculty): ?>
                <option value="<?= htmlspecialchars($faculty, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($faculty, ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>

        <div class="table-scroll" id="records" tabindex="0" aria-label="Student records table. Scroll horizontally to see all columns.">
          <table>
            <caption class="sr-only">Student compliance records</caption>
            <thead><tr>
              <th scope="col">Student</th><th scope="col">Faculty / Program</th><th scope="col">Visa Expiry</th><th scope="col">Last Check-in</th><th scope="col">Status</th><th scope="col">Current Location</th>
            </tr></thead>
            <tbody id="student-records"></tbody>
          </table>
        </div>
        <div class="empty-state" id="empty-state" hidden>
          <strong>No student records found.</strong><span>Try clearing or changing your search and filters.</span>
        </div>
        <nav class="pagination" aria-label="Student records pages">
          <button class="button secondary" id="previous-page" type="button">Previous</button>
          <p id="page-indicator" aria-live="polite">Page 1 of 1</p>
          <button class="button primary" id="next-page" type="button">Next</button>
        </nav>
      </section>
    </main>
  </div>
  <script>window.ISSD_STUDENTS = <?= json_encode($students, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;</script>
  <script src="assets/app.js"></script>
</body>
</html>
