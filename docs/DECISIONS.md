# Current decisions — authoritative context

| Topic | Agreed behavior |
|---|---|
| Platform | PHP + native HTML/CSS/JavaScript; local PostgreSQL demo with explicit JSON compatibility mode |
| Removed platforms | Power Apps, SharePoint and Power Automate explicitly excluded |
| PostgreSQL | User authorizes local database workflow, dummy seed, website integration, audit/evidence history, concurrent-review and backup checks. Current runtime JSON is preserved separately; deployment remains future work. |
| Accounts | 40 database student logins plus admin; new demo accounts receive hashed 1234 only at seed. Preserve student@gmail.com (B2500004) and admin@gmail.com. Other student emails use lowercase ID @students.demo.test. Runtime checks saved hashes and active flags; JSON fallback retains original two accounts. |
| Data scope | 40 synthetic postgraduate profiles; programme names not a verified university catalogue |
| Requirement | 365 days for all programmes for demo, pending institutional confirmation |
| Travel day counting | Verified Entry included; Exit excluded; unclosed verified stay through today; overlaps/repeated events do not double-count |
| Calculation exclusions | Pending/Rejected, CheckIn records, future/ambiguous legacy travel do not count |
| Display | completed_days=min(actual,365); percentage<=100; remaining>=0; keep actual verified total |
| Check-in | Today's location observation; verified old travel doesn't reset recent check-in |
| Alerts | Visa expired or within 30 days; check-in overdue only >30; location Overseas alone isn't warning |
| Student profile | Own session data only, read-only; personal/academic/visa/check-in, no admin metadata |
| History | Date submitted / Entry-Exit-Check-in / event date / Country / Status; no times/descriptions; Rejected displays Needs resubmission; conditional Resubmit |
| Admin review | Event date from student start_date, not submitted_at; no initial blue Pending badge; saved Verified/Rejected, mandatory rejection reason |
| Evidence | Entry/Exit requires JPEG/PNG passport image; check-in permits JPEG/PNG/PDF, overseas proof required; at most two files, 5 MiB each |
| Form location | Local uses Malaysia automatically; Overseas country required; City/Location removed |
| Branding/layout | HELP red, top navigation, mobile-friendly; student dashboard inspired by LMS hierarchy rather than adding LMS features |
| Email | Planned, not implemented or delivered |
| Dates | Asia/Kuala_Lumpur; submission time distinct from actual event date |
| Collaboration | Project Manager plans and assigns scoped tasks, named execution chats implement, QA verifies, Organizer commits/pushes when authorized |
| History management | Fresh focused chats + file handoffs when history grows repetitive; no measurable per-chat token threshold |
| Reporting | Individual reports distinguish own work/assistance from teammate commits; maintain last cutoff; never invent meeting/demo dates |

Original proposal uses outdated MS platform choices. Source documents are preserved for provenance, not current technical instructions. User instructions override these docs when explicitly changed.
