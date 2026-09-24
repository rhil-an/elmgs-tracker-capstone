import { useState, useMemo } from 'react';
import { students, faculties, UNIVERSITY, type Student, type ComplianceStatus } from './data';

const statusConfig: Record<ComplianceStatus, { label: string; bg: string; text: string; dot: string; border: string }> = {
  compliant:     { label: 'Compliant',     bg: 'bg-jade-50',    text: 'text-jade-700',    dot: 'bg-jade-500',    border: 'border-jade-500' },
  warning:       { label: 'Warning',       bg: 'bg-amber-50',   text: 'text-amber-600',   dot: 'bg-amber-500',   border: 'border-amber-500' },
  'non-compliant': { label: 'Non-Compliant', bg: 'bg-crimson-50', text: 'text-crimson-600', dot: 'bg-crimson-500', border: 'border-crimson-500' },
  pending:       { label: 'Pending',       bg: 'bg-navy-50',    text: 'text-navy-600',    dot: 'bg-navy-400',    border: 'border-navy-400' },
};

const enrollmentConfig: Record<string, { label: string; bg: string; text: string }> = {
  active:     { label: 'Active',      bg: 'bg-jade-50',    text: 'text-jade-700' },
  'on-leave': { label: 'On Leave',   bg: 'bg-amber-50',   text: 'text-amber-600' },
  terminated: { label: 'Terminated', bg: 'bg-crimson-50', text: 'text-crimson-600' },
  graduated:  { label: 'Graduated',  bg: 'bg-navy-50',    text: 'text-navy-600' },
};

function daysBetween(a: string, b: string) {
  return Math.floor((new Date(b).getTime() - new Date(a).getTime()) / 86400000);
}

function formatDate(d: string) {
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function isExpired(d: string) {
  return new Date(d) < new Date();
}

function daysUntil(d: string) {
  return daysBetween(new Date().toISOString().slice(0, 10), d);
}

function StatusBadge({ status }: { status: ComplianceStatus }) {
  const cfg = statusConfig[status];
  return (
    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${cfg.bg} ${cfg.text}`}>
      <span className={`w-1.5 h-1.5 rounded-full ${cfg.dot}`} />
      {cfg.label}
    </span>
  );
}

function CheckInDot({ method }: { method: string }) {
  const colors: Record<string, string> = {
    'in-person': 'bg-jade-500',
    online: 'bg-navy-400',
    missed: 'bg-crimson-400',
  };
  return <span className={`inline-block w-2.5 h-2.5 rounded-full ${colors[method] ?? 'bg-slate-300'}`} title={method} />;
}

function StatCard({ value, label, sub, color }: { value: number; label: string; sub: string; color: string }) {
  return (
    <div className={`rounded-2xl p-5 flex flex-col gap-1 border-l-4 bg-white shadow-sm ${color}`}>
      <span className="text-3xl font-bold text-navy-900">{value}</span>
      <span className="text-sm font-semibold text-navy-700">{label}</span>
      <span className="text-xs text-slate-500">{sub}</span>
    </div>
  );
}

function Sidebar({ activeNav, onNav }: { activeNav: string; onNav: (v: string) => void }) {
  const navItems = [
    { id: 'dashboard', icon: '⊞', label: 'Dashboard' },
    { id: 'students', icon: '◉', label: 'Student Records' },
    { id: 'alerts', icon: '◈', label: 'Alerts & Actions' },
    { id: 'reports', icon: '▦', label: 'Reports' },
  ];
  return (
    <aside className="w-64 bg-navy-900 flex flex-col shrink-0 h-screen sticky top-0">
      <div className="px-6 py-7 border-b border-navy-700">
        <div className="flex items-center gap-2 mb-1">
          <span className="text-crimson-400 text-lg">◈</span>
          <span className="text-white text-sm font-semibold tracking-wider uppercase">iCompliance</span>
        </div>
        <p className="text-navy-300 text-xs leading-tight">HELP University<br />International Student Office</p>
      </div>

      <nav className="flex-1 px-3 py-4 flex flex-col gap-1">
        {navItems.map(item => (
          <button
            key={item.id}
            onClick={() => onNav(item.id)}
            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left text-sm transition-all ${
              activeNav === item.id
                ? 'bg-crimson-500 text-white font-medium'
                : 'text-navy-300 hover:bg-navy-800 hover:text-white'
            }`}
          >
            <span className="text-base">{item.icon}</span>
            {item.label}
          </button>
        ))}
      </nav>

      <div className="px-5 py-5 border-t border-navy-700">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-navy-600 flex items-center justify-center text-white text-xs font-bold">
            IO
          </div>
          <div>
            <p className="text-white text-xs font-medium">Int'l Office Admin</p>
            <p className="text-navy-400 text-xs">HELP University</p>
          </div>
        </div>
      </div>
    </aside>
  );
}

function Dashboard() {
  const compliant = students.filter(s => s.overallStatus === 'compliant').length;
  const warning = students.filter(s => s.overallStatus === 'warning').length;
  const nonCompliant = students.filter(s => s.overallStatus === 'non-compliant').length;
  const expiredVisas = students.filter(s => isExpired(s.visaExpiry)).length;

  const recentAlerts = students
    .filter(s => s.overallStatus !== 'compliant')
    .flatMap(s => s.issues.map(issue => ({ student: s.name, issue, status: s.overallStatus, id: s.id })))
    .slice(0, 8);

  return (
    <div className="flex flex-col gap-7">
      <div>
        <h1 className="text-2xl font-bold text-navy-900" style={{ fontFamily: 'DM Serif Display, serif' }}>
          Compliance Overview
        </h1>
        <p className="text-slate-500 text-sm mt-1">
          As of {new Date().toLocaleDateString('en-GB', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })} — Malaysia Standard Time
        </p>
      </div>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard value={compliant} label="Compliant" sub="No action required" color="border-jade-500" />
        <StatCard value={warning} label="Warnings" sub="Attention needed" color="border-amber-500" />
        <StatCard value={nonCompliant} label="Non-Compliant" sub="Immediate action" color="border-crimson-500" />
        <StatCard value={expiredVisas} label="Expired Visas" sub="Renewal overdue" color="border-navy-400" />
      </div>

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div className="lg:col-span-2 bg-white rounded-2xl shadow-sm p-5">
          <h2 className="text-sm font-semibold text-navy-800 mb-4 uppercase tracking-wider">Status Distribution</h2>
          <div className="space-y-3">
            {(['compliant', 'warning', 'non-compliant'] as ComplianceStatus[]).map(status => {
              const count = students.filter(s => s.overallStatus === status).length;
              const pct = Math.round((count / students.length) * 100);
              const cfg = statusConfig[status];
              return (
                <div key={status} className="flex items-center gap-3">
                  <span className={`text-xs font-medium w-28 ${cfg.text}`}>{cfg.label}</span>
                  <div className="flex-1 bg-slate-100 rounded-full h-2">
                    <div className={`h-2 rounded-full ${cfg.dot}`} style={{ width: `${pct}%` }} />
                  </div>
                  <span className="text-xs font-mono text-slate-500 w-12 text-right">{count} / {students.length}</span>
                </div>
              );
            })}
          </div>

          <div className="mt-6 pt-5 border-t border-slate-100">
            <h3 className="text-xs font-semibold text-navy-700 uppercase tracking-wider mb-3">By Faculty</h3>
            <div className="grid grid-cols-1 gap-2">
              {['Faculty of Business, Economics & Accounting', 'School of Computing & Digital Technology', 'Faculty of Social Sciences & Liberal Arts', 'Faculty of Law'].map(faculty => {
                const fStudents = students.filter(s => s.faculty === faculty);
                const fCompliant = fStudents.filter(s => s.overallStatus === 'compliant').length;
                const shortName = faculty.replace('Faculty of ', '').replace('School of ', '');
                return (
                  <div key={faculty} className="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2">
                    <span className="text-xs text-slate-600 truncate">{shortName}</span>
                    <span className="text-xs font-mono font-medium text-navy-700 ml-2">{fCompliant}/{fStudents.length}</span>
                  </div>
                );
              })}
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm p-5">
          <h2 className="text-sm font-semibold text-navy-800 mb-4 uppercase tracking-wider">Active Alerts</h2>
          <div className="space-y-3">
            {recentAlerts.map((alert, i) => {
              const cfg = statusConfig[alert.status as ComplianceStatus];
              return (
                <div key={i} className={`rounded-xl p-3 ${cfg.bg} border-l-2 ${cfg.border}`}>
                  <p className={`text-xs font-semibold ${cfg.text}`}>{alert.student}</p>
                  <p className="text-xs text-slate-600 mt-0.5">{alert.issue}</p>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}

function StudentList({ onSelectStudent }: { onSelectStudent: (s: Student) => void }) {
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState<ComplianceStatus | 'all'>('all');
  const [filterFaculty, setFilterFaculty] = useState('All Faculties');
  const [sortField, setSortField] = useState<'name' | 'visaExpiry' | 'lastCheckIn'>('name');
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

  const filtered = useMemo(() => {
    let list = [...students];
    if (search) {
      const q = search.toLowerCase();
      list = list.filter(s =>
        s.name.toLowerCase().includes(q) ||
        s.passportNo.toLowerCase().includes(q) ||
        s.nationality.toLowerCase().includes(q) ||
        s.id.toLowerCase().includes(q)
      );
    }
    if (filterStatus !== 'all') list = list.filter(s => s.overallStatus === filterStatus);
    if (filterFaculty !== 'All Faculties') list = list.filter(s => s.faculty === filterFaculty);

    list.sort((a, b) => {
      const va = a[sortField];
      const vb = b[sortField];
      return sortDir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
    });
    return list;
  }, [search, filterStatus, filterFaculty, sortField, sortDir]);

  function toggleSort(field: typeof sortField) {
    if (sortField === field) setSortDir(d => d === 'asc' ? 'desc' : 'asc');
    else { setSortField(field); setSortDir('asc'); }
  }

  return (
    <div className="flex flex-col gap-5">
      <div>
        <h1 className="text-2xl font-bold text-navy-900" style={{ fontFamily: 'DM Serif Display, serif' }}>
          Student Records
        </h1>
        <p className="text-slate-500 text-sm mt-1">{filtered.length} of {students.length} records shown</p>
      </div>

      <div className="flex flex-wrap gap-3">
        <input
          type="text"
          placeholder="Search name, passport, nationality..."
          value={search}
          onChange={e => setSearch(e.target.value)}
          className="flex-1 min-w-56 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-navy-900 placeholder-slate-400 outline-none focus:border-navy-400 focus:ring-2 focus:ring-navy-100 transition"
        />
        <select
          value={filterStatus}
          onChange={e => setFilterStatus(e.target.value as ComplianceStatus | 'all')}
          className="px-3 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-navy-800 outline-none focus:border-navy-400 cursor-pointer"
        >
          <option value="all">All Statuses</option>
          <option value="compliant">Compliant</option>
          <option value="warning">Warning</option>
          <option value="non-compliant">Non-Compliant</option>
        </select>
        <select
          value={filterFaculty}
          onChange={e => setFilterFaculty(e.target.value)}
          className="px-3 py-2.5 rounded-xl border border-slate-200 bg-white text-sm text-navy-800 outline-none focus:border-navy-400 cursor-pointer"
        >
          {faculties.map(f => <option key={f}>{f}</option>)}
        </select>
      </div>

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-navy-950 text-navy-200">
                <th className="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider">Student</th>
                <th className="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider">Faculty / Program</th>
                <th className="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider cursor-pointer hover:text-white" onClick={() => toggleSort('visaExpiry')}>
                  Visa Expiry {sortField === 'visaExpiry' ? (sortDir === 'asc' ? '↑' : '↓') : ''}
                </th>
                <th className="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider cursor-pointer hover:text-white" onClick={() => toggleSort('lastCheckIn')}>
                  Last Check-in {sortField === 'lastCheckIn' ? (sortDir === 'asc' ? '↑' : '↓') : ''}
                </th>
                <th className="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider">Enrollment</th>
                <th className="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider">Status</th>
                <th className="px-4 py-3.5"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filtered.map(student => {
                const expired = isExpired(student.visaExpiry);
                const daysSinceCheckIn = daysBetween(student.lastCheckIn, new Date().toISOString().slice(0, 10));
                const enrollCfg = enrollmentConfig[student.enrollmentStatus];
                return (
                  <tr key={student.id} className="hover:bg-navy-50 transition-colors">
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-3">
                        <img src={student.photo} alt={student.name} className="w-9 h-9 rounded-full object-cover bg-slate-200 shrink-0" />
                        <div>
                          <p className="font-semibold text-navy-900">{student.name}</p>
                          <p className="text-xs text-slate-400 font-mono">{student.id} · {student.nationality}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-4">
                      <p className="text-navy-800 font-medium text-xs">{student.faculty.replace('Faculty of ', '').replace('School of ', '')}</p>
                      <p className="text-xs text-slate-400">{student.program}</p>
                    </td>
                    <td className="px-4 py-4">
                      <span className={`font-mono text-xs ${expired ? 'text-crimson-500 font-semibold' : 'text-navy-700'}`}>
                        {formatDate(student.visaExpiry)}
                      </span>
                      {expired && <p className="text-xs text-crimson-400">Expired</p>}
                      {!expired && daysUntil(student.visaExpiry) <= 180 && (
                        <p className="text-xs text-amber-500">{daysUntil(student.visaExpiry)}d remaining</p>
                      )}
                    </td>
                    <td className="px-4 py-4">
                      <span className="font-mono text-xs text-navy-700">{formatDate(student.lastCheckIn)}</span>
                      <p className={`text-xs ${daysSinceCheckIn > 45 ? 'text-crimson-400' : 'text-slate-400'}`}>
                        {daysSinceCheckIn}d ago
                      </p>
                    </td>
                    <td className="px-4 py-4">
                      <span className={`inline-block px-2 py-0.5 rounded-md text-xs font-medium ${enrollCfg.bg} ${enrollCfg.text}`}>
                        {enrollCfg.label}
                      </span>
                    </td>
                    <td className="px-4 py-4">
                      <StatusBadge status={student.overallStatus} />
                    </td>
                    <td className="px-4 py-4">
                      <button
                        onClick={() => onSelectStudent(student)}
                        className="text-navy-500 hover:text-navy-800 text-xs font-semibold underline underline-offset-2 transition"
                      >
                        View →
                      </button>
                    </td>
                  </tr>
                );
              })}
              {filtered.length === 0 && (
                <tr>
                  <td colSpan={7} className="px-5 py-12 text-center text-slate-400 text-sm">
                    No students match your search criteria.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

function StudentDetail({ student, onBack }: { student: Student; onBack: () => void }) {
  const expired = isExpired(student.visaExpiry);
  const daysSince = daysBetween(student.lastCheckIn, new Date().toISOString().slice(0, 10));
  const cfg = statusConfig[student.overallStatus];
  const enrollCfg = enrollmentConfig[student.enrollmentStatus];

  return (
    <div className="flex flex-col gap-6">
      <div className="flex items-center gap-3">
        <button onClick={onBack} className="text-sm text-navy-500 hover:text-navy-900 transition flex items-center gap-1">
          ← Back to Records
        </button>
      </div>

      <div className="bg-navy-900 rounded-2xl p-6 flex items-start gap-6">
        <img src={student.photo} alt={student.name} className="w-20 h-20 rounded-2xl object-cover bg-navy-700 shrink-0" />
        <div className="flex-1 min-w-0">
          <div className="flex items-start justify-between gap-4 flex-wrap">
            <div>
              <h1 className="text-2xl font-bold text-white" style={{ fontFamily: 'DM Serif Display, serif' }}>
                {student.name}
              </h1>
              <p className="text-navy-300 text-sm mt-0.5">{student.nationality} · {student.passportNo}</p>
            </div>
            <StatusBadge status={student.overallStatus} />
          </div>
          <div className="mt-3 flex flex-wrap gap-x-6 gap-y-1">
            {[
              { label: 'Permit No.', value: student.permitNo },
              { label: 'Entry Date', value: formatDate(student.entryDate) },
              { label: 'Visa Type', value: student.visaType },
            ].map(item => (
              <div key={item.label}>
                <span className="text-navy-400 text-xs">{item.label}: </span>
                <span className="text-navy-100 text-xs font-medium font-mono">{item.value}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {student.issues.length > 0 && (
        <div className={`rounded-2xl p-4 ${cfg.bg} border ${cfg.border}`}>
          <p className={`text-xs font-bold uppercase tracking-wider mb-2 ${cfg.text}`}>Issues Requiring Attention</p>
          <ul className="space-y-1.5">
            {student.issues.map((issue, i) => (
              <li key={i} className={`text-sm flex items-start gap-2 ${cfg.text}`}>
                <span className="mt-0.5">•</span>{issue}
              </li>
            ))}
          </ul>
        </div>
      )}

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div className="bg-white rounded-2xl shadow-sm p-5">
          <h2 className="text-xs font-bold uppercase tracking-wider text-navy-700 mb-4">Academic Details</h2>
          <div className="space-y-3">
            {[
              { label: 'University', value: UNIVERSITY },
              { label: 'Faculty', value: student.faculty },
              { label: 'Program', value: student.program },
              { label: 'Enrollment', value: enrollCfg.label },
              { label: 'Course Load', value: `${student.courseLoad} / ${student.minCourseLoad} credits (min)` },
              { label: 'CGPA', value: student.gpa.toFixed(2) },
            ].map(item => (
              <div key={item.label} className="flex justify-between gap-2">
                <span className="text-xs text-slate-500">{item.label}</span>
                <span className="text-xs font-medium text-navy-800 text-right">{item.value}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm p-5">
          <h2 className="text-xs font-bold uppercase tracking-wider text-navy-700 mb-4">Visa & Residence</h2>
          <div className="space-y-3">
            {[
              { label: 'Visa Type', value: student.visaType },
              { label: 'Expiry Date', value: formatDate(student.visaExpiry) },
              { label: 'Status', value: expired ? 'EXPIRED' : `${daysUntil(student.visaExpiry)} days left` },
              { label: 'Address', value: student.residenceAddress },
              { label: 'Address Verified', value: student.addressVerified ? 'Yes' : 'No' },
            ].map(item => (
              <div key={item.label} className="flex justify-between gap-2">
                <span className="text-xs text-slate-500 shrink-0">{item.label}</span>
                <span className={`text-xs font-medium text-right ${
                  item.label === 'Status' && expired ? 'text-crimson-500 font-bold' :
                  item.label === 'Address Verified' && !student.addressVerified ? 'text-amber-600' :
                  'text-navy-800'
                }`}>{item.value}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm p-5">
          <h2 className="text-xs font-bold uppercase tracking-wider text-navy-700 mb-4">Check-in Summary</h2>
          <div className="space-y-2 mb-4">
            <div className="flex justify-between">
              <span className="text-xs text-slate-500">Last Check-in</span>
              <span className="text-xs font-medium text-navy-800">{formatDate(student.lastCheckIn)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-xs text-slate-500">Days Since</span>
              <span className={`text-xs font-medium ${daysSince > 45 ? 'text-crimson-500' : 'text-navy-800'}`}>{daysSince} days</span>
            </div>
            <div className="flex justify-between">
              <span className="text-xs text-slate-500">Total Records</span>
              <span className="text-xs font-medium text-navy-800">{student.checkIns.length}</span>
            </div>
          </div>
          <div className="flex gap-3 mb-2">
            {(['in-person', 'online', 'missed'] as const).map(m => (
              <div key={m} className="flex items-center gap-1.5">
                <CheckInDot method={m} />
                <span className="text-xs text-slate-500 capitalize">{m}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="bg-white rounded-2xl shadow-sm p-5">
        <h2 className="text-xs font-bold uppercase tracking-wider text-navy-700 mb-4">Check-in History</h2>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-slate-400 uppercase tracking-wider">
                <th className="pb-3 pr-4">Date</th>
                <th className="pb-3 pr-4">Location</th>
                <th className="pb-3">Method</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {student.checkIns.map((ci, i) => (
                <tr key={i}>
                  <td className="py-3 pr-4 font-mono text-xs text-navy-700">{formatDate(ci.date)}</td>
                  <td className="py-3 pr-4 text-xs text-slate-600">{ci.location}</td>
                  <td className="py-3">
                    <span className={`inline-flex items-center gap-1.5 text-xs px-2 py-0.5 rounded-md font-medium ${
                      ci.method === 'in-person' ? 'bg-jade-50 text-jade-700' :
                      ci.method === 'online' ? 'bg-navy-50 text-navy-600' :
                      'bg-crimson-50 text-crimson-600'
                    }`}>
                      <CheckInDot method={ci.method} />
                      {ci.method}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <div className="bg-white rounded-2xl shadow-sm p-5">
        <h2 className="text-xs font-bold uppercase tracking-wider text-navy-700 mb-4">Actions</h2>
        <div className="flex flex-wrap gap-3">
          {[
            { label: 'Record Check-in', color: 'bg-navy-800 text-white hover:bg-navy-700' },
            { label: 'Issue Compliance Notice', color: 'bg-crimson-500 text-white hover:bg-crimson-600' },
            { label: 'Request Visa Renewal', color: 'bg-amber-500 text-white hover:bg-amber-600' },
            { label: 'Verify Address', color: 'bg-jade-600 text-white hover:bg-jade-700' },
            { label: 'Generate Report', color: 'bg-slate-100 text-slate-700 hover:bg-slate-200' },
          ].map(action => (
            <button
              key={action.label}
              className={`px-4 py-2 rounded-xl text-sm font-medium transition-colors ${action.color}`}
            >
              {action.label}
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}

function Alerts() {
  const flagged = students.filter(s => s.overallStatus !== 'compliant');

  return (
    <div className="flex flex-col gap-5">
      <div>
        <h1 className="text-2xl font-bold text-navy-900" style={{ fontFamily: 'DM Serif Display, serif' }}>
          Alerts & Required Actions
        </h1>
        <p className="text-slate-500 text-sm mt-1">{flagged.length} students require attention</p>
      </div>
      <div className="grid gap-4">
        {flagged.map(student => {
          const cfg = statusConfig[student.overallStatus];
          const expired = isExpired(student.visaExpiry);
          return (
            <div key={student.id} className={`bg-white rounded-2xl shadow-sm p-5 border-l-4 ${cfg.border}`}>
              <div className="flex items-start gap-4">
                <img src={student.photo} alt={student.name} className="w-12 h-12 rounded-xl object-cover bg-slate-200 shrink-0" />
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <p className="font-semibold text-navy-900">{student.name}</p>
                    <StatusBadge status={student.overallStatus} />
                    {expired && (
                      <span className="text-xs bg-crimson-50 text-crimson-600 px-2 py-0.5 rounded-md font-medium">Visa Expired</span>
                    )}
                  </div>
                  <p className="text-xs text-slate-400 mt-0.5 font-mono">{student.id} · {student.faculty.replace('Faculty of ', '').replace('School of ', '')} · {student.program}</p>
                  <ul className={`mt-2 space-y-1 ${cfg.text}`}>
                    {student.issues.map((issue, i) => (
                      <li key={i} className="text-xs flex items-start gap-1.5">
                        <span>•</span>{issue}
                      </li>
                    ))}
                  </ul>
                </div>
                <div className="flex flex-col gap-2 shrink-0">
                  <button className="text-xs px-3 py-1.5 bg-navy-800 text-white rounded-lg hover:bg-navy-700 transition">
                    Take Action
                  </button>
                  <button className="text-xs px-3 py-1.5 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition">
                    Send Notice
                  </button>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

function Reports() {
  const now = new Date();
  const expiringSoon = students.filter(s => !isExpired(s.visaExpiry) && daysUntil(s.visaExpiry) <= 180);
  const expired = students.filter(s => isExpired(s.visaExpiry));
  const overdueCheckIn = students.filter(s => daysBetween(s.lastCheckIn, now.toISOString().slice(0, 10)) > 30);

  return (
    <div className="flex flex-col gap-6">
      <div>
        <h1 className="text-2xl font-bold text-navy-900" style={{ fontFamily: 'DM Serif Display, serif' }}>
          Reports & Analytics
        </h1>
        <p className="text-slate-500 text-sm mt-1">Generated: {now.toLocaleDateString('en-GB', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
      </div>

      <div className="grid gap-5 lg:grid-cols-2">
        <div className="bg-white rounded-2xl shadow-sm p-5">
          <h2 className="text-xs font-bold uppercase tracking-wider text-navy-700 mb-1">Visa Expiring Within 6 Months</h2>
          <p className="text-xs text-slate-400 mb-4">{expiringSoon.length} students</p>
          {expiringSoon.length === 0 && <p className="text-sm text-slate-400">None at this time.</p>}
          {expiringSoon.map(s => (
            <div key={s.id} className="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
              <div className="flex items-center gap-2">
                <img src={s.photo} alt={s.name} className="w-7 h-7 rounded-full object-cover bg-slate-200" />
                <span className="text-sm font-medium text-navy-800">{s.name}</span>
              </div>
              <span className="text-xs font-mono text-amber-600">{daysUntil(s.visaExpiry)}d · {formatDate(s.visaExpiry)}</span>
            </div>
          ))}
        </div>

        <div className="bg-white rounded-2xl shadow-sm p-5">
          <h2 className="text-xs font-bold uppercase tracking-wider text-navy-700 mb-1">Expired Visas</h2>
          <p className="text-xs text-slate-400 mb-4">{expired.length} students</p>
          {expired.length === 0 && <p className="text-sm text-slate-400">None at this time.</p>}
          {expired.map(s => (
            <div key={s.id} className="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
              <div className="flex items-center gap-2">
                <img src={s.photo} alt={s.name} className="w-7 h-7 rounded-full object-cover bg-slate-200" />
                <span className="text-sm font-medium text-navy-800">{s.name}</span>
              </div>
              <span className="text-xs font-mono text-crimson-500">{formatDate(s.visaExpiry)}</span>
            </div>
          ))}
        </div>

        <div className="bg-white rounded-2xl shadow-sm p-5">
          <h2 className="text-xs font-bold uppercase tracking-wider text-navy-700 mb-1">Overdue Check-ins (&gt;30 days)</h2>
          <p className="text-xs text-slate-400 mb-4">{overdueCheckIn.length} students</p>
          {overdueCheckIn.map(s => {
            const days = daysBetween(s.lastCheckIn, now.toISOString().slice(0, 10));
            return (
              <div key={s.id} className="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                <div className="flex items-center gap-2">
                  <img src={s.photo} alt={s.name} className="w-7 h-7 rounded-full object-cover bg-slate-200" />
                  <span className="text-sm font-medium text-navy-800">{s.name}</span>
                </div>
                <span className={`text-xs font-mono ${days > 60 ? 'text-crimson-500' : 'text-amber-600'}`}>{days} days ago</span>
              </div>
            );
          })}
        </div>

        <div className="bg-white rounded-2xl shadow-sm p-5">
          <h2 className="text-xs font-bold uppercase tracking-wider text-navy-700 mb-4">Enrollment Distribution</h2>
          {(['active', 'on-leave', 'terminated', 'graduated'] as const).map(status => {
            const count = students.filter(s => s.enrollmentStatus === status).length;
            const pct = Math.round((count / students.length) * 100);
            const ecfg = enrollmentConfig[status];
            return (
              <div key={status} className="flex items-center gap-3 mb-3">
                <span className={`text-xs font-medium w-24 ${ecfg.text}`}>{ecfg.label}</span>
                <div className="flex-1 bg-slate-100 rounded-full h-2">
                  <div className={`h-2 rounded-full ${ecfg.bg.replace('bg-', 'bg-').replace('-50', '-400')}`} style={{ width: `${pct || 5}%` }} />
                </div>
                <span className="text-xs font-mono text-slate-500 w-8 text-right">{count}</span>
              </div>
            );
          })}
        </div>
      </div>

      <div className="bg-white rounded-2xl shadow-sm p-5">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xs font-bold uppercase tracking-wider text-navy-700">Full Compliance Report</h2>
          <button className="text-xs px-4 py-2 bg-navy-800 text-white rounded-xl hover:bg-navy-700 transition">
            Export PDF
          </button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-xs">
            <thead>
              <tr className="text-left text-slate-400 uppercase tracking-wider border-b border-slate-100">
                <th className="pb-2 pr-4">ID</th>
                <th className="pb-2 pr-4">Name</th>
                <th className="pb-2 pr-4">University</th>
                <th className="pb-2 pr-4">Visa Expiry</th>
                <th className="pb-2 pr-4">Last Check-in</th>
                <th className="pb-2 pr-4">Enrollment</th>
                <th className="pb-2">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50">
              {students.map(s => (
                <tr key={s.id}>
                  <td className="py-2 pr-4 font-mono text-slate-400">{s.id}</td>
                  <td className="py-2 pr-4 font-medium text-navy-800">{s.name}</td>
                  <td className="py-2 pr-4 text-slate-500">{s.faculty.replace('Faculty of ', '').replace('School of ', '')}</td>
                  <td className={`py-2 pr-4 font-mono ${isExpired(s.visaExpiry) ? 'text-crimson-500' : 'text-slate-600'}`}>{formatDate(s.visaExpiry)}</td>
                  <td className="py-2 pr-4 font-mono text-slate-600">{formatDate(s.lastCheckIn)}</td>
                  <td className="py-2 pr-4 text-slate-500">{enrollmentConfig[s.enrollmentStatus].label}</td>
                  <td className="py-2"><StatusBadge status={s.overallStatus} /></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

export default function App() {
  const [activeNav, setActiveNav] = useState('dashboard');
  const [selectedStudent, setSelectedStudent] = useState<Student | null>(null);

  function handleNav(nav: string) {
    setActiveNav(nav);
    setSelectedStudent(null);
  }

  function handleSelectStudent(s: Student) {
    setSelectedStudent(s);
    setActiveNav('students');
  }

  function renderContent() {
    if (activeNav === 'students' && selectedStudent) {
      return <StudentDetail student={selectedStudent} onBack={() => setSelectedStudent(null)} />;
    }
    switch (activeNav) {
      case 'dashboard': return <Dashboard />;
      case 'students': return <StudentList onSelectStudent={handleSelectStudent} />;
      case 'alerts': return <Alerts />;
      case 'reports': return <Reports />;
      default: return <Dashboard />;
    }
  }

  return (
    <div className="flex min-h-screen bg-navy-50">
      <Sidebar activeNav={activeNav} onNav={handleNav} />
      <main className="flex-1 p-8 overflow-y-auto max-w-full">
        <div className="max-w-6xl mx-auto">
          {renderContent()}
        </div>
      </main>
    </div>
  );
}
