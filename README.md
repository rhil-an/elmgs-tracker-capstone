# ELMGS Tracker

A plain HTML, CSS, and JavaScript frontend prototype for HELP University's ELMGS Tracker. It is designed to deploy directly from the `main` branch with GitHub Pages.

## Run it

Open `index.html` in a browser, or publish the repository from GitHub Pages using **Deploy from a branch → main → /(root)**. No package installation or build command is needed.

## Files to edit

- `index.html` — page structure and navigation
- `styles.css` — styling and responsive layout rules
- `data.js` — dummy student and travel-record data
- `app.js` — dashboard, filtering, details, alerts, and CSV export behaviour

## Current functionality

- Dashboard summary and compliance distribution
- Search and filtering of student records
- Individual student compliance and travel-record views
- Alerts with a prototype notice action
- CSV download of the mock compliance report

All displayed data is dummy data. Replace `data.js` with SharePoint or Power Apps data when the backend is ready.
