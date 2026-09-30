# Local data source

`students.json` contains 40 demonstration postgraduate student records originally converted from the sample workbook, sheet `Students`, rows 5–44. Program labels have been adapted to postgraduate programs for this prototype; they are sample labels, not a verified university course catalogue. The archived source workbook is not read by the application.

| Spreadsheet column | Prototype property |
| --- | --- |
| Student Name | `name` |
| Student ID | `id` |
| Nationality | `nationality` |
| Faculty/Program | `faculty` |
| Status | `status` |
| Current Location | `currentLocation` |
| Last Check In | `lastCheckIn` |
| Visa Expiry | `visaExpiry` |

Each record also includes `postgraduateStayStartDate` and `postgraduateStayRequiredCompletionDate`. Compliance progress uses elapsed calendar days since the start date divided by 365, clamped between 0% and 100%, for every student. It does not calculate physical presence or determine the stored compliance status. The completion date is sample metadata; the progress calculation uses the fixed 365-day requirement.

To refresh the prototype, export postgraduate-only records with the mapped fields and stay dates. Profile alerts derive from visa expiry, last check-in, and stored status. Check-in history is generated demonstration data and is labelled accordingly. SharePoint is not connected.
