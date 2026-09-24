// Plain browser JavaScript. No framework, compiler, or server is needed.
const app = document.querySelector('#app');
let currentPage = 'dashboard';
let selectedStudentId = null;

const remainingDays = (student) => Math.max(student.requiredDays - student.verifiedDays, 0);
const countStatus = (status) => students.filter((student) => student.status === status).length;
const badge = (status) => `<span class="badge ${status.toLowerCase()}">${status.replace('-', ' ')}</span>`;
const heading = (title, text) => `<div class="page-heading"><div><h1>${title}</h1><p class="subtle">${text}</p></div></div>`;

function dashboard() {
  const total = students.length;
  const statuses = [['compliant', 'Compliant'], ['warning', 'Need attention'], ['non-compliant', 'Non-compliant']];
  const bars = statuses.map(([status, label]) => {
    const count = countStatus(status);
    return `<div class="status-row"><span>${label}</span><div class="bar ${status}"><span style="width:${count / total * 100}%"></span></div><strong>${count}</strong></div>`;
  }).join('');
  const alerts = students.filter((student) => student.status !== 'compliant').slice(0, 4);
  app.innerHTML = `${heading('Compliance overview', 'Stay-compliance status for tracked international students.')}
    <section class="stat-grid"><div class="panel"><span class="stat-value">${total}</span><span class="stat-label">Students tracked</span></div><div class="panel"><span class="stat-value">${countStatus('compliant')}</span><span class="stat-label">Compliant</span></div><div class="panel"><span class="stat-value">${countStatus('warning')}</span><span class="stat-label">Need attention</span></div><div class="panel"><span class="stat-value">${countStatus('non-compliant')}</span><span class="stat-label">Non-compliant</span></div></section>
    <section class="dashboard-grid"><div class="panel"><h2>Status distribution</h2>${bars}</div><div class="panel"><h2>Active alerts</h2><div class="alert-list">${alerts.map((student) => `<div class="alert ${student.status === 'non-compliant' ? 'danger' : ''}"><strong>${student.name}</strong><p>${student.issues[0]}</p></div>`).join('')}</div></div></section>`;
}

function studentRow(student) {
  const percent = Math.min(student.verifiedDays / student.requiredDays * 100, 100);
  return `<tr><td><span class="student-name">${student.name}</span><span class="student-meta">${student.id} · ${student.nationality}</span></td><td><span class="student-name">${student.faculty}</span><span class="student-meta">${student.programme}</span></td><td><div class="progress"><span style="width:${percent}%"></span></div><span class="progress-label">${student.verifiedDays} / ${student.requiredDays} verified days</span></td><td>${remainingDays(student)} days</td><td>${badge(student.status)}</td><td><button class="link-button" data-student="${student.id}">View</button></td></tr>`;
}

function studentsPage() {
  app.innerHTML = `${heading('Student records', 'Only verified travel records count toward official stay days.')}
    <section class="panel"><div class="toolbar"><input id="search" type="search" placeholder="Search name, ID, nationality, or programme"><select id="status"><option value="all">All statuses</option><option value="compliant">Compliant</option><option value="warning">Warning</option><option value="non-compliant">Non-compliant</option></select><select id="faculty"><option value="all">All faculties</option>${faculties.map((faculty) => `<option>${faculty}</option>`).join('')}</select></div><div class="table-wrap"><table><thead><tr><th>Student</th><th>Faculty and programme</th><th>Stay progress</th><th>Remaining</th><th>Status</th><th></th></tr></thead><tbody id="student-table"></tbody></table></div></section>`;
  const search = document.querySelector('#search');
  const status = document.querySelector('#status');
  const faculty = document.querySelector('#faculty');
  const table = document.querySelector('#student-table');
  const update = () => {
    const query = search.value.toLowerCase();
    const result = students.filter((student) => (`${student.name} ${student.id} ${student.nationality} ${student.programme}`.toLowerCase().includes(query)) && (status.value === 'all' || student.status === status.value) && (faculty.value === 'all' || student.faculty === faculty.value));
    table.innerHTML = result.length ? result.map(studentRow).join('') : '<tr><td colspan="6" class="empty">No students match these filters.</td></tr>';
    table.querySelectorAll('[data-student]').forEach((button) => button.addEventListener('click', () => { selectedStudentId = button.dataset.student; currentPage = 'detail'; render(); }));
  };
  [search, status, faculty].forEach((element) => element.addEventListener('input', update));
  update();
}

function detailPage() {
  const student = students.find((item) => item.id === selectedStudentId);
  if (!student) { currentPage = 'students'; render(); return; }
  const percent = Math.min(student.verifiedDays / student.requiredDays * 100, 100);
  app.innerHTML = `<button class="button secondary" id="back">← Back to student records</button><section class="panel" style="margin-top:16px"><div class="page-heading"><div><h1>${student.name}</h1><p class="subtle">${student.id} · ${student.nationality} · ${student.programme}</p></div>${badge(student.status)}</div><div class="detail-grid"><div><h2>Stay compliance</h2><div class="progress"><span style="width:${percent}%"></span></div><p><strong>${student.verifiedDays}</strong> verified stay days of <strong>${student.requiredDays}</strong> required days.</p><p class="subtle">${remainingDays(student)} days remain. Pending and rejected records do not count.</p></div><div><h2>Current information</h2><div class="key-values"><div><span>Faculty</span><strong>${student.faculty}</strong></div><div><span>Last submission</span><strong>${student.lastSubmission}</strong></div><div><span>Current status</span><strong>${student.status.replace('-', ' ')}</strong></div><div><span>Travel records</span><strong>${student.records.length}</strong></div></div></div></div></section><section class="detail-grid" style="margin-top:20px"><div class="panel"><h2>Travel records</h2><div class="table-wrap"><table><thead><tr><th>Type</th><th>Date</th><th>Status</th><th>Days added</th></tr></thead><tbody>${student.records.map((record) => `<tr><td>${record.type}</td><td>${record.date}</td><td>${badge(record.status)}</td><td>${record.days}</td></tr>`).join('')}</tbody></table></div></div><div class="panel"><h2>Items requiring attention</h2>${student.issues.length ? `<ul class="issue-list">${student.issues.map((issue) => `<li>${issue}</li>`).join('')}</ul>` : '<p class="empty">No current issues. This student is on track.</p>'}</div></section>`;
  document.querySelector('#back').addEventListener('click', () => { currentPage = 'students'; render(); });
}

function alertsPage() {
  const alertStudents = students.filter((student) => student.issues.length);
  app.innerHTML = `${heading('Alerts and actions', 'Students who need an ISSD follow-up or travel-record review.')}<section class="panel"><div class="alert-list">${alertStudents.map((student) => `<div class="alert ${student.status === 'non-compliant' ? 'danger' : ''}"><strong>${student.name}</strong> ${badge(student.status)}<ul class="issue-list">${student.issues.map((issue) => `<li>${issue}</li>`).join('')}</ul><button class="button secondary" data-notice="${student.id}">Prepare notice</button></div>`).join('')}</div><p id="notice"></p></section>`;
  document.querySelectorAll('[data-notice]').forEach((button) => button.addEventListener('click', () => { const student = students.find((item) => item.id === button.dataset.notice); document.querySelector('#notice').className = 'notice'; document.querySelector('#notice').textContent = `Notice prepared for ${student.name}. In the final system, Power Automate sends it after ISSD approval.`; }));
}

function reportsPage() {
  app.innerHTML = `${heading('Reports', 'Generate a simple CSV report from this prototype data.')}<section class="panel"><div class="page-heading"><div><h2>Compliance summary</h2><p class="subtle">This demonstrates a report export; it does not use live student data.</p></div><button class="button" id="download">Download CSV</button></div><div class="table-wrap"><table><thead><tr><th>Student</th><th>Verified</th><th>Required</th><th>Remaining</th><th>Status</th></tr></thead><tbody>${students.map((student) => `<tr><td><span class="student-name">${student.name}</span><span class="student-meta">${student.id}</span></td><td>${student.verifiedDays}</td><td>${student.requiredDays}</td><td>${remainingDays(student)}</td><td>${badge(student.status)}</td></tr>`).join('')}</tbody></table></div></section>`;
  document.querySelector('#download').addEventListener('click', () => { const rows = students.map((student) => `${student.id},${student.name},${student.verifiedDays},${student.requiredDays},${remainingDays(student)},${student.status}`).join('\n'); const link = document.createElement('a'); link.href = URL.createObjectURL(new Blob([`ID,Name,Verified Days,Required Days,Remaining Days,Status\n${rows}`], { type: 'text/csv' })); link.download = 'elmgs-compliance-report.csv'; link.click(); URL.revokeObjectURL(link.href); });
}

function render() { document.querySelectorAll('.nav-link').forEach((button) => button.classList.toggle('is-active', button.dataset.page === currentPage)); if (currentPage === 'dashboard') dashboard(); if (currentPage === 'students') studentsPage(); if (currentPage === 'detail') detailPage(); if (currentPage === 'alerts') alertsPage(); if (currentPage === 'reports') reportsPage(); }
document.querySelectorAll('.nav-link').forEach((button) => button.addEventListener('click', () => { currentPage = button.dataset.page; selectedStudentId = null; render(); }));
render();
