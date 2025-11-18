document.addEventListener('click', function(e) {
  if (e.target.classList.contains('approve')) {
    const id = e.target.getAttribute('data-request-id');
    const body = new URLSearchParams({ action: 'approve_enrollment', request_id: id });
    fetch('../trainer/handlers/ajax_handlers.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
      .then(r => r.json()).then(d => { alert(d.message); if (d.success) location.reload(); });
  }
  if (e.target.classList.contains('reject')) {
    const id = e.target.getAttribute('data-request-id');
    const body = new URLSearchParams({ action: 'reject_enrollment', request_id: id });
    fetch('../trainer/handlers/ajax_handlers.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
      .then(r => r.json()).then(d => { alert(d.message); if (d.success) location.reload(); });
  }
});