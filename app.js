// Plain browser JavaScript: this runs directly from a GitHub Pages branch.
const app = document.querySelector('#app');
let currentPage = 'dashboard';
let selectedStudentId = null;
let flashMessage = '';

const remainingDays = (student) => Math.max(student.requiredDays - student.verifiedDays, 0);
const countStatus = (status) => students.filter((student) => student.status === status).length;
const badge = (status) => `<span class="badge ${status.toLowerCase().replaceAll(' ', '-')}">${status.replace('-', ' ')}</span>`;
const heading = (title, text, action = '') => `<div class="page-heading"><div><p class="eyebrow">ISSD OPERATIONS</p><h1>${title}</h1><p class="subtle">${text}</p></div>${action}</div>`;
const feedback = () => flashMessage ? `<div class="notice" role="status">${flashMessage}</div>` : '';

function reviewItems() {
  return students.flatMap((student) => student.records
    .map((record, recordIndex) => ({ student, record, recordIndex }))
    .filter((item) => item.record.status === 'Pending'))
    .sort((a, b) => (a.record.priority === 'High' ? -1 : 1) - (b.record.priority === 'High' ? -1 : 1));
}

function openStudent(id) {
  selectedStudentId = id;
  currentPage = 'detail';
  render();
}

function syncStudent(student) {
  const pending = student.records.some((record) => record.status === 'Pending');
  const rejected = student.records.some((record) => record.status === 'Rejected');
  const daysIssue = `${remainingDays(student)} stay days still required`;
  student.issues = student.issues.filter((issue) => !/awaiting verification|pending review|still required/i.test(issue));
  if (pending) student.issues.unshift('Travel declaration is pending review');
  if (rejected && !student.issues.some((issue) => /returned for correction/i.test(issue))) student.issues.unshift('Travel record was returned for correction');
  if (remainingDays(student) > 0) student.issues.push(daysIssue);
  if (rejected || student.issues.some((issue) => /no new travel declaration/i.test(issue))) student.status = 'non-compliant';
  else if (pending || remainingDays(student) <= 60) student.status = 'warning';
  else student.status = 'compliant';
}

function queueRow({ student, record, recordIndex }) {
  const priority = record.priority === 'High' ? 'high' : 'standard';
  return `<article class="queue-row"><div class="queue-priority ${priority}">${record.priority}</div><div class="queue-person"><strong>${student.name}</strong><span>${student.id} · ${student.programme}</span></div><div><span class="queue-label">Record</span><strong>${record.type} · ${record.date}</strong></div><div><span class="queue-label">Evidence</span><strong>${record.evidence}</strong></div><div><span class="queue-label">Submitted</span><strong>${record.submittedAt}</strong></div><button class="button queue-action" data-open="${student.id}" data-record="${recordIndex}">Review record</button></article>`;
}

function dashboard() {
  const total = students.length;
  const queue = reviewItems();
  const actionRequired = countStatus('non-compliant');
  const nearTarget = students.filter((student) => remainingDays(student) <= 60 && student.status !== 'non-compliant').length;
  const statuses = [['compliant', 'On track'], ['warning', 'Need attention'], ['non-compliant', 'Action required']];
  const bars = statuses.map(([status, label]) => {
    const count = countStatus(status);
    return `<div class="status-row"><span>${label}</span><div class="bar ${status}"><span style="width:${count / total * 100}%"></span></div><strong>${count}</strong></div>`;
  }).join('');
  const alerts = students.filter((student) => student.status !== 'compliant').slice(0, 3);
  app.innerHTML = `${heading('Today’s compliance desk', 'Start with the records that can change a student’s official stay total today.', `<button class="button" id="open-queue">Open review queue <span class="button-count">${queue.length}</span></button>`)}${feedback()}
    <section class="focus-card"><div><p class="eyebrow">DAILY FOCUS</p><h2>${queue.length ? `${queue.length} travel record${queue.length === 1 ? '' : 's'} waiting for a decision` : 'The review queue is clear'}</h2><p>${queue.length ? 'Verify the evidence first. Verified entry days are added automatically; records sent back do not count.' : 'Use the student list to monitor stay targets and follow up on outstanding declarations.'}</p><div class="focus-actions"><button class="button" id="focus-queue">${queue.length ? 'Review next record' : 'View student records'}</button><span>${actionRequired} student${actionRequired === 1 ? '' : 's'} require${actionRequired === 1 ? 's' : ''} follow-up</span></div></div><img src="assets/compliance-orbit.png" alt="Globe, passport and verified document illustration" /></section>
    <section class="stat-grid"><div class="panel stat-card"><span class="stat-value">${total}</span><span class="stat-label">Students tracked</span></div><div class="panel stat-card"><span class="stat-value amber">${queue.length}</span><span class="stat-label">Records to review</span></div><div class="panel stat-card"><span class="stat-value red">${actionRequired}</span><span class="stat-label">Action required</span></div><div class="panel stat-card"><span class="stat-value blue">${nearTarget}</span><span class="stat-label">Within 60 stay days</span></div></section>
    <section class="dashboard-grid"><div class="panel"><div class="section-heading"><div><h2>Today’s review queue</h2><p class="subtle">Evidence waiting for an ISSD decision.</p></div><button class="link-button" id="view-all-queue">View all</button></div><div class="queue-list compact">${queue.length ? queue.slice(0, 2).map(queueRow).join('') : '<p class="empty">No pending travel records. Nice work.</p>'}</div></div><div class="panel"><h2>Compliance distribution</h2>${bars}<hr><h2 class="minor-heading">Active follow-up</h2><div class="alert-list">${alerts.map((student) => `<button class="alert ${student.status === 'non-compliant' ? 'danger' : ''}" data-open="${student.id}"><strong>${student.name}</strong><p>${student.issues[0]}</p></button>`).join('')}</div></div></section>`;
  document.querySelector('#open-queue').addEventListener('click', () => { currentPage = 'review'; render(); });
  document.querySelector('#focus-queue').addEventListener('click', () => { currentPage = queue.length ? 'review' : 'students'; render(); });
  document.querySelector('#view-all-queue').addEventListener('click', () => { currentPage = 'review'; render(); });
  bindOpenStudent();
}

function bindOpenStudent() {
  document.querySelectorAll('[data-open]').forEach((button) => button.addEventListener('click', () => openStudent(button.dataset.open)));
}

function reviewPage() {
  const queue = reviewItems();
  app.innerHTML = `${heading('Review queue', 'Check the travel evidence, make a decision, then let the tracker update the official stay total.')}${feedback()}
    <section class="workflow-strip"><span><b>1</b> Check evidence</span><span><b>2</b> Verify or return</span><span><b>3</b> Tracker updates</span><span><b>4</b> Follow up if needed</span></section>
    <section class="panel"><div class="section-heading"><div><h2>${queue.length ? `${queue.length} pending decision${queue.length === 1 ? '' : 's'}` : 'No records waiting'}</h2><p class="subtle">Only a verified record contributes stay days.</p></div><span class="status-key">High priority is reviewed first</span></div><div class="queue-list">${queue.length ? queue.map(queueRow).join('') : '<p class="empty">The queue is clear. Review returned records in Alerts and actions when a student resubmits.</p>'}</div></section>`;
  bindOpenStudent();
}

function studentRow(student) {
  const percent = Math.min(student.verifiedDays / student.requiredDays * 100, 100);
  return `<tr><td><span class="student-name">${student.name}</span><span class="student-meta">${student.id} · ${student.nationality}</span></td><td><span class="student-name">${student.faculty}</span><span class="student-meta">${student.programme}</span></td><td><div class="progress"><span style="width:${percent}%"></span></div><span class="progress-label">${student.verifiedDays} / ${student.requiredDays} verified days</span></td><td>${remainingDays(student)} days</td><td>${badge(student.status)}</td><td><button class="link-button" data-open="${student.id}">Open</button></td></tr>`;
}

function studentsPage() {
  app.innerHTML = `${heading('Student records', 'Search the student register. Only verified travel records count toward official stay days.')}<section class="panel"><div class="toolbar"><input id="search" type="search" placeholder="Search name, ID, nationality, or programme" aria-label="Search student records"><select id="status" aria-label="Filter by status"><option value="all">All statuses</option><option value="compliant">Compliant</option><option value="warning">Need attention</option><option value="non-compliant">Action required</option></select><select id="faculty" aria-label="Filter by faculty"><option value="all">All faculties</option>${faculties.map((faculty) => `<option>${faculty}</option>`).join('')}</select></div><div class="table-wrap"><table><thead><tr><th>Student</th><th>Faculty and programme</th><th>Stay progress</th><th>Remaining</th><th>Status</th><th><span class="sr-only">Open record</span></th></tr></thead><tbody id="student-table"></tbody></table></div></section>`;
  const search = document.querySelector('#search');
  const status = document.querySelector('#status');
  const faculty = document.querySelector('#faculty');
  const table = document.querySelector('#student-table');
  const update = () => {
    const query = search.value.toLowerCase();
    const result = students.filter((student) => (`${student.name} ${student.id} ${student.nationality} ${student.programme}`.toLowerCase().includes(query)) && (status.value === 'all' || student.status === status.value) && (faculty.value === 'all' || student.faculty === faculty.value));
    table.innerHTML = result.length ? result.map(studentRow).join('') : '<tr><td colspan="6" class="empty">No students match these filters.</td></tr>';
    bindOpenStudent();
  };
  [search, status, faculty].forEach((element) => element.addEventListener('input', update));
  update();
}

function detailPage() {
  const student = students.find((item) => item.id === selectedStudentId);
  if (!student) { currentPage = 'students'; render(); return; }
  const percent = Math.min(student.verifiedDays / student.requiredDays * 100, 100);
  const pending = student.records.find((record) => record.status === 'Pending');
  const pendingIndex = student.records.indexOf(pending);
  const travelRows = student.records.map((record, index) => `<tr><td><strong>${record.type}</strong><span class="student-meta">${record.evidence}</span></td><td>${record.date}<span class="student-meta">Submitted ${record.submittedAt}</span></td><td>${badge(record.status)}${record.decision ? `<span class="student-meta">${record.decision}</span>` : ''}</td><td>${record.status === 'Verified' ? record.days : '—'}</td><td>${record.status === 'Pending' ? `<button class="link-button" data-review="${index}">Review</button>` : '—'}</td></tr>`).join('');
  const reviewPanel = pending ? `<section class="panel review-panel"><div class="section-heading"><div><p class="eyebrow">DECISION REQUIRED</p><h2>Review ${pending.type.toLowerCase()} record · ${pending.date}</h2><p class="subtle">${pending.evidence} · ${pending.days} potential stay days</p></div>${badge(pending.priority)}</div><p class="evidence-note"><strong>Admin check:</strong> ${pending.note || 'Compare the submitted date and image with the student’s travel details.'}</p><label for="decision-note">Decision note <span>(saved with the record)</span></label><textarea id="decision-note" rows="3" placeholder="Optional for verification; required in the final system if returning a record."></textarea><div class="decision-actions"><button class="button" data-decision="verify" data-record="${pendingIndex}">Verify & count ${pending.days} days</button><button class="button danger-button" data-decision="reject" data-record="${pendingIndex}">Return for correction</button></div></section>` : '<section class="panel review-panel complete-panel"><strong>No pending evidence for this student.</strong><span>Review completed records below, or follow up on any remaining stay-day requirement.</span></section>';
  app.innerHTML = `<button class="button secondary" id="back">← Back to student records</button>${feedback()}<section class="panel student-header"><div class="page-heading"><div><p class="eyebrow">STUDENT RECORD</p><h1>${student.name}</h1><p class="subtle">${student.id} · ${student.nationality} · ${student.programme}</p></div>${badge(student.status)}</div><div class="detail-grid"><div><h2>Official stay progress</h2><div class="progress large"><span style="width:${percent}%"></span></div><p><strong>${student.verifiedDays}</strong> verified stay days of <strong>${student.requiredDays}</strong> required days.</p><p class="subtle">${remainingDays(student)} days remain. Pending and returned records never count.</p></div><div><h2>At a glance</h2><div class="key-values"><div><span>Faculty</span><strong>${student.faculty}</strong></div><div><span>Last submission</span><strong>${student.lastSubmission}</strong></div><div><span>Travel records</span><strong>${student.records.length}</strong></div><div><span>Pending decisions</span><strong>${student.records.filter((record) => record.status === 'Pending').length}</strong></div></div></div></div></section>${reviewPanel}<section class="detail-grid records-grid"><div class="panel"><h2>Travel records</h2><div class="table-wrap"><table><thead><tr><th>Record</th><th>Date and submission</th><th>Status</th><th>Days counted</th><th></th></tr></thead><tbody>${travelRows}</tbody></table></div></div><div class="panel"><h2>Items requiring attention</h2>${student.issues.length ? `<ul class="issue-list">${student.issues.map((issue) => `<li>${issue}</li>`).join('')}</ul>` : '<p class="empty">No current issues. This student is on track.</p>'}</div></section>`;
  document.querySelector('#back').addEventListener('click', () => { currentPage = 'students'; render(); });
  document.querySelectorAll('[data-review]').forEach((button) => button.addEventListener('click', () => document.querySelector('.review-panel').scrollIntoView({ behavior: 'smooth' })));
  document.querySelectorAll('[data-decision]').forEach((button) => button.addEventListener('click', () => decideRecord(student, Number(button.dataset.record), button.dataset.decision)));
}

function decideRecord(student, recordIndex, decision) {
  const record = student.records[recordIndex];
  const note = document.querySelector('#decision-note')?.value.trim();
  if (decision === 'verify') {
    record.status = 'Verified';
    record.decision = note || `Verified by ISSD on 24 Sep 2026`;
    student.verifiedDays += record.days;
    flashMessage = `${student.name}’s ${record.type.toLowerCase()} record was verified. ${record.days} days are now counted.`;
  } else {
    record.status = 'Rejected';
    record.decision = note || 'Please submit a clearer supporting document.';
    flashMessage = `${student.name}’s record was returned for correction. No stay days were added.`;
  }
  syncStudent(student);
  render();
}

function alertsPage() {
  const alertStudents = students.filter((student) => student.issues.length);
  app.innerHTML = `${heading('Alerts and actions', 'Follow up on missing declarations and records returned to students.')} ${feedback()}<section class="panel"><div class="alert-list">${alertStudents.map((student) => `<article class="alert ${student.status === 'non-compliant' ? 'danger' : ''}"><div><strong>${student.name}</strong> ${badge(student.status)}<ul class="issue-list">${student.issues.map((issue) => `<li>${issue}</li>`).join('')}</ul></div><div class="alert-actions"><button class="button secondary" data-notice="${student.id}">Prepare notice</button><button class="link-button" data-open="${student.id}">Open record</button></div></article>`).join('')}</div><p id="notice"></p></section>`;
  document.querySelectorAll('[data-notice]').forEach((button) => button.addEventListener('click', () => { const student = students.find((item) => item.id === button.dataset.notice); const notice = document.querySelector('#notice'); notice.className = 'notice'; notice.textContent = `Notice prepared for ${student.name}. In the final system, an ISSD officer approves it before Power Automate sends it.`; }));
  bindOpenStudent();
}

function reportsPage() {
  app.innerHTML = `${heading('Reports', 'Export the current prototype state for a simple compliance handover.')}<section class="panel"><div class="section-heading"><div><h2>Compliance summary</h2><p class="subtle">This demonstrates a report export; it does not use live student data.</p></div><button class="button" id="download">Download CSV</button></div><div class="table-wrap"><table><thead><tr><th>Student</th><th>Verified</th><th>Required</th><th>Remaining</th><th>Status</th></tr></thead><tbody>${students.map((student) => `<tr><td><span class="student-name">${student.name}</span><span class="student-meta">${student.id}</span></td><td>${student.verifiedDays}</td><td>${student.requiredDays}</td><td>${remainingDays(student)}</td><td>${badge(student.status)}</td></tr>`).join('')}</tbody></table></div></section>`;
  document.querySelector('#download').addEventListener('click', () => {
    const rows = students.map((student) => `${student.id},${student.name},${student.verifiedDays},${student.requiredDays},${remainingDays(student)},${student.status}`).join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([`ID,Name,Verified Days,Required Days,Remaining Days,Status\n${rows}`], { type: 'text/csv' }));
    link.download = 'elmgs-compliance-report.csv';
    link.click();
    setTimeout(() => URL.revokeObjectURL(link.href), 0);
  });
}

function render() {
  const queueCount = reviewItems().length;
  document.querySelector('#queue-count').textContent = queueCount;
  document.querySelectorAll('.nav-link').forEach((button) => button.classList.toggle('is-active', button.dataset.page === currentPage));
  if (currentPage === 'dashboard') dashboard();
  if (currentPage === 'review') reviewPage();
  if (currentPage === 'students') studentsPage();
  if (currentPage === 'detail') detailPage();
  if (currentPage === 'alerts') alertsPage();
  if (currentPage === 'reports') reportsPage();
  flashMessage = '';
}

document.querySelectorAll('.nav-link').forEach((button) => button.addEventListener('click', () => {
  currentPage = button.dataset.page;
  selectedStudentId = null;
  render();
}));
render();
