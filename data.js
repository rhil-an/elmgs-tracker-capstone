// Prototype data only. The final build can replace this file with SharePoint or API data.
// Days on a Pending or Rejected record never count until ISSD verifies the evidence.
const students = [
  {
    id: 'HELP-001', name: 'Ahmad Reza Karimi', nationality: 'Iranian',
    faculty: 'Business and Economics', programme: 'MBA Business Administration',
    requiredDays: 240, verifiedDays: 208, lastSubmission: '10 Sep 2026', status: 'warning',
    issues: ['30-day entry declaration is awaiting verification', '32 stay days still required'],
    records: [
      { type: 'Entry', date: '10 Sep 2026', status: 'Pending', days: 30, submittedAt: '10 Sep 2026, 09:18', evidence: 'Passport entry stamp + boarding pass', priority: 'High', note: 'Student has supplied both requested documents.' },
      { type: 'Exit', date: '14 Jul 2026', status: 'Verified', days: 0, submittedAt: '14 Jul 2026, 16:42', evidence: 'Exit stamp' },
      { type: 'Entry', date: '12 Aug 2026', status: 'Verified', days: 29, submittedAt: '12 Aug 2026, 11:05', evidence: 'Passport entry stamp' }
    ]
  },
  {
    id: 'HELP-002', name: 'Li Xiao Wei', nationality: 'Chinese',
    faculty: 'Computing and Digital Technology', programme: 'BSc Computer Science',
    requiredDays: 365, verifiedDays: 316, lastSubmission: '14 Sep 2026', status: 'compliant', issues: [],
    records: [
      { type: 'Entry', date: '14 Sep 2026', status: 'Verified', days: 14, submittedAt: '14 Sep 2026, 10:12', evidence: 'Passport entry stamp' },
      { type: 'Exit', date: '31 Aug 2026', status: 'Verified', days: 0, submittedAt: '31 Aug 2026, 14:20', evidence: 'Exit stamp' }
    ]
  },
  {
    id: 'HELP-003', name: 'Priya Nair Subramaniam', nationality: 'Indian',
    faculty: 'Social Sciences and Liberal Arts', programme: 'BA Psychology',
    requiredDays: 300, verifiedDays: 192, lastSubmission: '22 Jul 2026', status: 'non-compliant',
    issues: ['No new travel declaration for 64 days', '108 stay days still required'],
    records: [
      { type: 'Entry', date: '22 Jul 2026', status: 'Verified', days: 64, submittedAt: '22 Jul 2026, 08:46', evidence: 'Passport entry stamp' },
      { type: 'Exit', date: '20 May 2026', status: 'Verified', days: 0, submittedAt: '20 May 2026, 13:07', evidence: 'Exit stamp' }
    ]
  },
  {
    id: 'HELP-004', name: 'Mohammed Al-Hassan', nationality: 'Saudi Arabian',
    faculty: 'Business and Economics', programme: 'BBA Finance',
    requiredDays: 240, verifiedDays: 173, lastSubmission: '01 Sep 2026', status: 'warning',
    issues: ['Travel declaration is pending review', '67 stay days still required'],
    records: [
      { type: 'Entry', date: '01 Sep 2026', status: 'Pending', days: 29, submittedAt: '01 Sep 2026, 15:34', evidence: 'Passport entry stamp + flight itinerary', priority: 'Standard', note: 'Date matches the airline itinerary.' },
      { type: 'Exit', date: '10 Aug 2026', status: 'Verified', days: 0, submittedAt: '10 Aug 2026, 12:09', evidence: 'Exit stamp' }
    ]
  },
  {
    id: 'HELP-005', name: 'Fatima Osei Mensah', nationality: 'Ghanaian', faculty: 'Law', programme: 'LLB',
    requiredDays: 365, verifiedDays: 344, lastSubmission: '15 Sep 2026', status: 'compliant', issues: [],
    records: [
      { type: 'Entry', date: '15 Sep 2026', status: 'Verified', days: 15, submittedAt: '15 Sep 2026, 09:51', evidence: 'Passport entry stamp' },
      { type: 'Exit', date: '30 Aug 2026', status: 'Verified', days: 0, submittedAt: '30 Aug 2026, 17:14', evidence: 'Exit stamp' }
    ]
  },
  {
    id: 'HELP-006', name: 'Tomasz Kowalski', nationality: 'Polish',
    faculty: 'Computing and Digital Technology', programme: 'BSc Software Engineering',
    requiredDays: 365, verifiedDays: 201, lastSubmission: '10 Jun 2026', status: 'non-compliant',
    issues: ['Travel record was returned for correction', '164 stay days still required'],
    records: [
      { type: 'Exit', date: '10 Jun 2026', status: 'Rejected', days: 0, submittedAt: '10 Jun 2026, 10:40', evidence: 'Unreadable passport stamp', decision: 'Returned for a clearer image' },
      { type: 'Entry', date: '01 May 2026', status: 'Verified', days: 40, submittedAt: '01 May 2026, 09:16', evidence: 'Passport entry stamp' }
    ]
  }
];

const faculties = [...new Set(students.map((student) => student.faculty))];
