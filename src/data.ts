export type ComplianceStatus = 'compliant' | 'warning' | 'non-compliant' | 'pending';

export interface CheckIn {
  date: string;
  location: string;
  method: 'in-person' | 'online' | 'missed';
}

export interface Student {
  id: string;
  name: string;
  passportNo: string;
  nationality: string;
  faculty: string;
  program: string;
  visaType: 'Student Pass' | 'Dependent Pass';
  visaExpiry: string;
  enrollmentStatus: 'active' | 'on-leave' | 'terminated' | 'graduated';
  residenceAddress: string;
  addressVerified: boolean;
  lastCheckIn: string;
  checkIns: CheckIn[];
  overallStatus: ComplianceStatus;
  issues: string[];
  photo: string;
  courseLoad: number;
  minCourseLoad: number;
  gpa: number;
  entryDate: string;
  permitNo: string;
}

export const UNIVERSITY = 'HELP University';
export const INT_OFFICE = 'HELP University International Office';

export const students: Student[] = [
  {
    id: 'HELP-001',
    name: 'Ahmad Reza Karimi',
    passportNo: 'IR8834512',
    nationality: 'Iranian',
    faculty: 'Faculty of Business, Economics & Accounting',
    program: 'MBA Business Administration',
    visaType: 'Student Pass',
    visaExpiry: '2025-03-15',
    enrollmentStatus: 'active',
    residenceAddress: 'No. 12, Jalan Pantai Baru, 59200 Kuala Lumpur',
    addressVerified: true,
    lastCheckIn: '2026-09-10',
    checkIns: [
      { date: '2026-09-10', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-08-12', location: 'Online Portal', method: 'online' },
      { date: '2026-07-08', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-06-10', location: 'Online Portal', method: 'missed' },
    ],
    overallStatus: 'warning',
    issues: ['Visa expiry within 6 months', 'Missed June check-in'],
    photo: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&h=80&fit=crop',
    courseLoad: 12,
    minCourseLoad: 9,
    gpa: 3.7,
    entryDate: '2022-09-01',
    permitNo: 'SP/2022/HELP/04821',
  },
  {
    id: 'HELP-002',
    name: 'Li Xiao Wei',
    passportNo: 'CN6621904',
    nationality: 'Chinese',
    faculty: 'School of Computing & Digital Technology',
    program: 'BSc Computer Science',
    visaType: 'Student Pass',
    visaExpiry: '2027-01-20',
    enrollmentStatus: 'active',
    residenceAddress: 'Blok B, KL Sentral Residences, 50470 Kuala Lumpur',
    addressVerified: true,
    lastCheckIn: '2026-09-14',
    checkIns: [
      { date: '2026-09-14', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-08-14', location: 'Online Portal', method: 'online' },
      { date: '2026-07-12', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-06-13', location: 'Online Portal', method: 'online' },
    ],
    overallStatus: 'compliant',
    issues: [],
    photo: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=80&h=80&fit=crop',
    courseLoad: 15,
    minCourseLoad: 12,
    gpa: 3.9,
    entryDate: '2025-01-15',
    permitNo: 'SP/2025/HELP/00312',
  },
  {
    id: 'HELP-003',
    name: 'Priya Nair Subramaniam',
    passportNo: 'IN7745220',
    nationality: 'Indian',
    faculty: 'Faculty of Social Sciences & Liberal Arts',
    program: 'BA Psychology',
    visaType: 'Student Pass',
    visaExpiry: '2028-06-30',
    enrollmentStatus: 'active',
    residenceAddress: 'No. 88, Jalan Duta, 50480 Kuala Lumpur',
    addressVerified: false,
    lastCheckIn: '2026-07-22',
    checkIns: [
      { date: '2026-07-22', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-06-18', location: 'Online Portal', method: 'online' },
      { date: '2026-05-14', location: INT_OFFICE, method: 'missed' },
      { date: '2026-04-10', location: INT_OFFICE, method: 'missed' },
    ],
    overallStatus: 'non-compliant',
    issues: ['Address not verified', 'Missed 2 consecutive check-ins', 'Overdue for current check-in'],
    photo: 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=80&h=80&fit=crop',
    courseLoad: 18,
    minCourseLoad: 18,
    gpa: 3.2,
    entryDate: '2023-07-01',
    permitNo: 'SP/2023/HELP/07744',
  },
  {
    id: 'HELP-004',
    name: 'Mohammed Al-Hassan',
    passportNo: 'SA4412893',
    nationality: 'Saudi Arabian',
    faculty: 'Faculty of Business, Economics & Accounting',
    program: 'BBA Finance',
    visaType: 'Student Pass',
    visaExpiry: '2026-12-01',
    enrollmentStatus: 'on-leave',
    residenceAddress: 'Lot 5, Jalan Ampang, 50450 Kuala Lumpur',
    addressVerified: true,
    lastCheckIn: '2026-09-01',
    checkIns: [
      { date: '2026-09-01', location: 'Online Portal', method: 'online' },
      { date: '2026-08-04', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-07-02', location: 'Online Portal', method: 'online' },
      { date: '2026-06-05', location: INT_OFFICE, method: 'in-person' },
    ],
    overallStatus: 'warning',
    issues: ['Student currently on approved leave', 'Must maintain check-in schedule during leave'],
    photo: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&h=80&fit=crop',
    courseLoad: 0,
    minCourseLoad: 0,
    gpa: 3.4,
    entryDate: '2023-01-10',
    permitNo: 'SP/2023/HELP/01185',
  },
  {
    id: 'HELP-005',
    name: 'Fatima Osei Mensah',
    passportNo: 'GH2298741',
    nationality: 'Ghanaian',
    faculty: 'Faculty of Law',
    program: 'LLB',
    visaType: 'Student Pass',
    visaExpiry: '2027-08-15',
    enrollmentStatus: 'active',
    residenceAddress: 'No. 45, Jalan Bangsar, 59000 Kuala Lumpur',
    addressVerified: true,
    lastCheckIn: '2026-09-15',
    checkIns: [
      { date: '2026-09-15', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-08-16', location: 'Online Portal', method: 'online' },
      { date: '2026-07-14', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-06-12', location: 'Online Portal', method: 'online' },
    ],
    overallStatus: 'compliant',
    issues: [],
    photo: 'https://images.unsplash.com/photo-1489424731084-a5d8b219a5bb?w=80&h=80&fit=crop',
    courseLoad: 15,
    minCourseLoad: 12,
    gpa: 3.8,
    entryDate: '2024-09-01',
    permitNo: 'SP/2024/HELP/09903',
  },
  {
    id: 'HELP-006',
    name: 'Tomasz Kowalski',
    passportNo: 'PL9988123',
    nationality: 'Polish',
    faculty: 'School of Computing & Digital Technology',
    program: 'BSc Software Engineering',
    visaType: 'Student Pass',
    visaExpiry: '2026-04-30',
    enrollmentStatus: 'active',
    residenceAddress: 'No. 22, Jalan Semantan, 50490 Kuala Lumpur',
    addressVerified: true,
    lastCheckIn: '2026-06-10',
    checkIns: [
      { date: '2026-06-10', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-05-12', location: 'Online Portal', method: 'online' },
      { date: '2026-04-08', location: INT_OFFICE, method: 'missed' },
      { date: '2026-03-10', location: 'Online Portal', method: 'missed' },
    ],
    overallStatus: 'non-compliant',
    issues: ['Visa expired — renewal pending', 'No check-in for 3+ months', 'Missed 2 check-ins'],
    photo: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=80&h=80&fit=crop',
    courseLoad: 9,
    minCourseLoad: 9,
    gpa: 2.9,
    entryDate: '2024-05-01',
    permitNo: 'SP/2024/HELP/03341',
  },
  {
    id: 'HELP-007',
    name: 'Yuki Tanaka',
    passportNo: 'JP5512834',
    nationality: 'Japanese',
    faculty: 'Faculty of Social Sciences & Liberal Arts',
    program: 'BA Communication & Media',
    visaType: 'Student Pass',
    visaExpiry: '2027-09-01',
    enrollmentStatus: 'active',
    residenceAddress: 'Residensi Bukit Jalil, 57000 Kuala Lumpur',
    addressVerified: true,
    lastCheckIn: '2026-09-12',
    checkIns: [
      { date: '2026-09-12', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-08-10', location: 'Online Portal', method: 'online' },
      { date: '2026-07-14', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-06-09', location: 'Online Portal', method: 'online' },
    ],
    overallStatus: 'compliant',
    issues: [],
    photo: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=80&h=80&fit=crop',
    courseLoad: 18,
    minCourseLoad: 15,
    gpa: 3.6,
    entryDate: '2023-09-01',
    permitNo: 'SP/2023/HELP/08821',
  },
  {
    id: 'HELP-008',
    name: 'Amara Diallo',
    passportNo: 'SN3312009',
    nationality: 'Senegalese',
    faculty: 'Faculty of Business, Economics & Accounting',
    program: 'BBA International Business',
    visaType: 'Student Pass',
    visaExpiry: '2025-11-20',
    enrollmentStatus: 'active',
    residenceAddress: 'No. 7, Jalan Masjid India, 50100 Kuala Lumpur',
    addressVerified: false,
    lastCheckIn: '2026-08-28',
    checkIns: [
      { date: '2026-08-28', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-07-30', location: 'Online Portal', method: 'online' },
      { date: '2026-06-28', location: INT_OFFICE, method: 'in-person' },
      { date: '2026-05-29', location: 'Online Portal', method: 'online' },
    ],
    overallStatus: 'warning',
    issues: ['Visa expired — must renew immediately', 'Address verification pending'],
    photo: 'https://images.unsplash.com/photo-1539701938214-0d9736e1c16b?w=80&h=80&fit=crop',
    courseLoad: 15,
    minCourseLoad: 12,
    gpa: 3.3,
    entryDate: '2022-07-15',
    permitNo: 'SP/2022/HELP/06612',
  },
];

export const faculties = [
  'All Faculties',
  'Faculty of Business, Economics & Accounting',
  'School of Computing & Digital Technology',
  'Faculty of Social Sciences & Liberal Arts',
  'Faculty of Law',
];
