# ICompliance postgraduate prototype

This folder is the single active Git checkout for `https://github.com/rhil-an/elmgs-tracker-capstone`.
Run all Git push and pull commands from this folder.

Run locally with PHP:

```sh
php -S 127.0.0.1:8080 -t .
```

Open `http://127.0.0.1:8080/`. The landing page redirects to `dashboard.php`; records are at `student-records.php`. Dashboard, records, and profiles share the top navigation layout.

Dashboard alerts show five students per page, ordered by critical severity, earliest visa expiry, longest overdue check-in, then student ID. All pages warn about visas expiring within 30 days and check-ins overdue by more than 30 days. Overseas location alone does not cause an alert. Dates use Asia/Kuala_Lumpur. Send Notice remains disabled.

The prototype provides search, status/program filters, pagination, a fixed 365-day stay progress indicator, keyboard-accessible profile links, visa/check-in alerts, and labelled demonstration check-in history. Only postgraduate sample records are included. See `data/README.md` for data assumptions.

Original code snapshots are saved outside this checkout under `../backups/`. Original project documents and the Figma Make file are kept under `../references/`.
