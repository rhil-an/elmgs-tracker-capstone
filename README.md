# ELMGS Tracker Capstone

Frontend prototype for HELP University's ELMGS Tracker, a system that helps the International Student Services Department monitor international-student travel submissions and stay-compliance progress.

## Current prototype

The React app currently models the administrator experience: a compliance dashboard, searchable student records, student-detail views, alerts, and reporting. It uses local dummy data only. The next product work is to align the model with the ELMGS MVP: entry and exit submissions, evidence upload, verification, verified stay-day totals, remaining required days, and a student-facing portal.

## Development

```powershell
pnpm install --frozen-lockfile
pnpm dev
pnpm run build
```

## Repository layout

```text
src/              React components, styles, and dummy data
.figma/           Figma Make project metadata
package.json       Scripts and dependencies
vite.config.ts     Vite, React, Tailwind, and Figma Make configuration
```

## Repository scope

This repository contains implementation source and developer-facing project information only. Original Word documents and Figma Make exports are deliberately kept in the local-only `elmgs-tracker-local` folder beside this repository. The `.gitignore` prevents those binary artifacts from being added by mistake.
