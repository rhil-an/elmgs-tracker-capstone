(() => {
  const students = window.ISSD_STUDENTS || [];
  const pageSize = 10;
  const elements = {
    search: document.querySelector('#student-search'), status: document.querySelector('#status-filter'), faculty: document.querySelector('#faculty-filter'),
    body: document.querySelector('#student-records'), count: document.querySelector('#record-count'), empty: document.querySelector('#empty-state'),
    previous: document.querySelector('#previous-page'), next: document.querySelector('#next-page'), indicator: document.querySelector('#page-indicator')
  };
  let page = 1;
  const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;' }[char]));
  const dateText = (isoDate) => new Intl.DateTimeFormat('en-GB', { day:'2-digit', month:'short', year:'numeric' }).format(new Date(`${isoDate}T00:00:00`));
  const complianceProgress = student => ({ ...student.stay_progress, score: student.stay_progress.bar_percentage, tone: 'green' });
  const filtered = () => {
    const query = elements.search.value.trim().toLocaleLowerCase();
    return students.filter(student => (!query || [student.name, student.id, student.nationality].some(value => value.toLocaleLowerCase().includes(query))) && (!elements.status.value || student.status === elements.status.value) && (!elements.faculty.value || student.faculty === elements.faculty.value));
  };
  const render = () => {
    const matches = filtered(); const totalPages = Math.max(1, Math.ceil(matches.length / pageSize)); page = Math.min(page, totalPages);
    const visible = matches.slice((page - 1) * pageSize, page * pageSize);
    elements.body.innerHTML = visible.map(student => {
      const progress = complianceProgress(student);
      return `<tr class="data-row"><td><span class="student-name">${escapeHtml(student.name)}</span><span class="student-meta">${escapeHtml(student.id)} · ${escapeHtml(student.nationality)}</span></td><td class="faculty">${escapeHtml(student.faculty)}</td><td class="date">${dateText(student.visaExpiry)}</td><td class="date">${dateText(student.lastCheckIn)}</td><td><span class="badge badge-${student.status.toLocaleLowerCase().replace(/[^a-z]+/g,'-')}">${escapeHtml(student.status)}</span></td><td><span class="badge location-${student.currentLocation === 'Local' ? 'local' : 'overseas'}">${escapeHtml(student.currentLocation)}</span></td><td><div class="progress-cell"><div class="progress-track" role="progressbar" aria-label="Verified stay progress for ${escapeHtml(student.name)}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${progress.score}" aria-valuetext="${progress.verified_days} verified days, ${progress.required_days} required days, ${progress.remaining_days} remaining days, ${progress.percentage}%"><span class="progress-fill progress-${progress.tone}" style="width:${progress.score}%"></span></div><span class="progress-label" aria-hidden="true">${progress.percentage}% · ${progress.verified_days}/${progress.required_days} days · ${progress.remaining_days} remaining</span></div></td><td class="table-action"><a class="view-link" href="student-profile.php?id=${encodeURIComponent(student.id)}" aria-label="View profile for ${escapeHtml(student.name)}">View<span aria-hidden="true"> →</span></a></td></tr>`;
    }).join('');
    elements.count.textContent = `${matches.length} ${matches.length === 1 ? 'record' : 'records'} found`;
    elements.empty.hidden = matches.length !== 0; elements.previous.disabled = page === 1; elements.next.disabled = page === totalPages || matches.length === 0;
    elements.indicator.textContent = `Page ${page} of ${totalPages}`;
  };
  [elements.search, elements.status, elements.faculty].forEach(control => control.addEventListener('input', () => { page = 1; render(); }));
  elements.previous.addEventListener('click', () => { if (page > 1) { page--; render(); } });
  elements.next.addEventListener('click', () => { if (page < Math.ceil(filtered().length / pageSize)) { page++; render(); } });
  render();
})();
