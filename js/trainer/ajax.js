document.addEventListener('click', function(e) {
  const approveBtn = e.target.closest('.approve');
  if (approveBtn) {
    const id = approveBtn.getAttribute('data-request-id');
    const body = new URLSearchParams({ action: 'approve_enrollment', request_id: id });
    fetch('../trainer/handlers/ajax_handlers.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
      .then(r => r.json()).then(d => { alert(d.message); if (d.success) location.reload(); });
    return;
  }
  const rejectBtn = e.target.closest('.reject');
  if (rejectBtn) {
    const id = rejectBtn.getAttribute('data-request-id');
    const body = new URLSearchParams({ action: 'reject_enrollment', request_id: id });
    fetch('../trainer/handlers/ajax_handlers.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
      .then(r => r.json()).then(d => { alert(d.message); if (d.success) location.reload(); });
  }
});