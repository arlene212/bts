function setupClientSearch() {
  const filterInputs = document.querySelectorAll('.search-input');
  filterInputs.forEach(input => {
    input.addEventListener('input', function() {
      const term = this.value.toLowerCase().trim();
      const table = this.closest('.tab-content')?.querySelector('table');
      if (table) {
        table.querySelectorAll('tbody tr').forEach(row => {
          row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
      }
    });
  });
}
document.addEventListener('DOMContentLoaded', setupClientSearch);