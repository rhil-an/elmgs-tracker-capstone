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
  const filtered = () => {
    const query = elements.search.value.trim().toLocaleLowerCase();
    return students.filter(student => (!query || [student.name, student.id, student.nationality].some(value => value.toLocaleLowerCase().includes(query))) && (!elements.status.value || student.status === elements.status.value) && (!elements.faculty.value || student.faculty === elements.faculty.value));
  };
  const render = () => {
    const matches = filtered(); const totalPages = Math.max(1, Math.ceil(matches.length / pageSize)); page = Math.min(page, totalPages);
    const visible = matches.slice((page - 1) * pageSize, page * pageSize);
    elements.body.innerHTML = visible.map(student => `<tr class="data-row" tabindex="0" aria-label="${escapeHtml(student.name)}, ${escapeHtml(student.status)}"><td><span class="student-name">${escapeHtml(student.name)}</span><span class="student-meta">${escapeHtml(student.id)} · ${escapeHtml(student.nationality)}</span></td><td class="faculty">${escapeHtml(student.faculty)}</td><td class="date">${dateText(student.visaExpiry)}</td><td class="date">${dateText(student.lastCheckIn)}</td><td><span class="badge badge-${student.status.toLocaleLowerCase().replace(/[^a-z]+/g,'-')}">${escapeHtml(student.status)}</span></td><td class="location">${escapeHtml(student.currentLocation)}</td></tr>`).join('');
    elements.count.textContent = `${matches.length} ${matches.length === 1 ? 'record' : 'records'} found`;
    elements.empty.hidden = matches.length !== 0; elements.previous.disabled = page === 1; elements.next.disabled = page === totalPages || matches.length === 0;
    elements.indicator.textContent = `Page ${page} of ${totalPages}`;
  };
  [elements.search, elements.status, elements.faculty].forEach(control => control.addEventListener('input', () => { page = 1; render(); }));
  elements.previous.addEventListener('click', () => { if (page > 1) { page--; render(); } });
  elements.next.addEventListener('click', () => { if (page < Math.ceil(filtered().length / pageSize)) { page++; render(); } });
  render();
})();
