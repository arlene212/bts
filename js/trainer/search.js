document.addEventListener('DOMContentLoaded', function() {
  const guestSearch = document.getElementById('guestSearch');
  const guestsBody = document.getElementById('guestsBody');
  if (guestSearch && guestsBody) {
    guestSearch.addEventListener('input', function() {
      const term = this.value.toLowerCase().trim();
      guestsBody.querySelectorAll('tr').forEach(row => { row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none'; });
    });
  }
});