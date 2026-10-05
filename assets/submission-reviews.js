document.querySelectorAll('[data-review-row]').forEach(row => {
  const buttons = [...row.querySelectorAll('[data-decision]')];
  const status = row.querySelector('[data-review-status]');
  buttons.forEach(button => {
    button.disabled = false;
    button.addEventListener('click', () => {
      status.textContent = button.dataset.decision;
      status.className = 'badge ' + (button.dataset.decision === 'Approved' ? 'review-approved' : 'review-resubmit');
      buttons.forEach(action => action.setAttribute('aria-pressed', String(action === button)));
    });
  });
});
