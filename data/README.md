# Local data source

`students.json` is a static, local conversion of `../student-sample-data.xlsx`, sheet `Students`, rows 5–44. The source workbook is not read by the browser and has not been modified.

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

To refresh the prototype later, re-export the same mapped fields to this JSON structure. SharePoint is deliberately not connected in this local prototype.
