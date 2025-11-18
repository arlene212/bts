function initializeSearch(type, currentSearch) {
  const searchInput = document.getElementById(`${type}SearchInput`);
  const searchResults = document.getElementById(`${type}SearchResults`);
  const searchLoading = document.getElementById(`${type}SearchLoading`);
  const clearSearchBtn = document.getElementById(`${type}ClearSearch`);
  if (!searchInput || !searchResults) return;
  let searchTimeout;
  const submitSearch = (query) => {
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = window.location.pathname;
    form.style.display = 'none';
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.forEach((value, key) => {
      if (key !== `${type}_search` && key !== `${type}_page`) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
      }
    });
    const searchInputField = document.createElement('input');
    searchInputField.type = 'hidden';
    searchInputField.name = `${type}_search`;
    searchInputField.value = query;
    form.appendChild(searchInputField);
    const pageInput = document.createElement('input');
    pageInput.type = 'hidden';
    pageInput.name = `${type}_page`;
    pageInput.value = '1';
    form.appendChild(pageInput);
    document.body.appendChild(form);
    form.submit();
  };
  searchInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      const query = this.value.trim();
      submitSearch(query);
    }
  });
  searchInput.addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    if (query.length < 2) {
      searchResults.style.display = 'none';
      if (searchLoading) searchLoading.classList.add('hidden');
      return;
    }
    if (searchLoading) searchLoading.classList.remove('hidden');
    searchResults.innerHTML = '<div class="search-loading">Searching...</div>';
    searchResults.style.display = 'block';
    searchTimeout = setTimeout(() => {
      performAjaxSearch(type, query, searchResults, searchLoading);
    }, 300);
  });
  searchInput.addEventListener('focus', function () {
    if (this.value.trim().length > 0 && searchResults.children.length > 0) {
      searchResults.style.display = 'block';
    }
  });
  document.addEventListener('click', function (e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
      searchResults.style.display = 'none';
    }
  });
  searchResults.addEventListener('click', function (e) {
    const resultItem = e.target.closest('.search-result-item');
    if (resultItem) {
      const searchValue = resultItem.getAttribute('data-search-value');
      searchInput.value = searchValue;
      searchResults.style.display = 'none';
      submitSearch(searchValue);
    }
  });
  if (clearSearchBtn) {
    clearSearchBtn.addEventListener('click', (e) => {
      e.preventDefault();
      submitSearch('');
    });
  }
}

function performAjaxSearch(type, query, resultsContainer, loadingIndicator) {
  const formData = new FormData();
  formData.append('search_type', type);
  formData.append('search_query', query);
  formData.append('ajax_search', 'true');
  fetch('../admin/handlers/ajax_handlers.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => { displaySearchResults(type, query, data, resultsContainer); })
    .catch(() => { resultsContainer.innerHTML = '<div class="no-results">Search failed. Please try again.</div>'; })
    .finally(() => { if (loadingIndicator) loadingIndicator.classList.add('hidden'); });
}

function displaySearchResults(type, query, data, resultsContainer) {
  if (!data || data.length === 0) {
    resultsContainer.innerHTML = '<div class="no-results">No results found for "' + query + '"</div>';
    return;
  }
  let html = '';
  let currentSection = '';
  data.forEach(item => {
    if (item.section && item.section !== currentSection) {
      if (currentSection !== '') html += '</div>';
      html += '<div class="search-section-title">' + item.section + '</div>';
      currentSection = item.section;
    }
    let displayText = '';
    let searchValue = '';
    switch (type) {
      case 'trainer':
      case 'trainee':
      case 'guest':
        displayText = `<strong>${item.name}</strong><br><small class="text-muted">ID: ${item.id} | Email: ${item.email}</small>`;
        searchValue = item.name;
        break;
      case 'enrollment':
        displayText = `<strong>${item.trainee_name}</strong><br><small class="text-muted">Course: ${item.course_name} | Status: ${item.status}</small>`;
        searchValue = item.trainee_name;
        break;
    }
    const regex = new RegExp(`(${query})`, 'gi');
    displayText = displayText.replace(regex, '<mark>$1</mark>');
    html += `<div class="search-result-item" data-search-value="${searchValue}">${displayText}</div>`;
  });
  resultsContainer.innerHTML = html;
}

function setupAjaxPagination() {
  const setupClientSideSearch = (inputId, tableSelector) => {
    const searchInput = document.getElementById(inputId);
    const table = document.querySelector(tableSelector);
    if (!searchInput || !table) return;
    const tableRows = table.querySelectorAll('tbody tr');
    searchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase().trim();
      tableRows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        row.style.display = rowText.includes(searchTerm) ? '' : 'none';
      });
    });
  };
  setupClientSideSearch('trainerSearchInput', '#activeTrainers .trainer-table');
  setupClientSideSearch('trainerSearchInput', '#archivedTrainers .trainer-table');
  setupClientSideSearch('traineeSearchInput', '#activeTrainees .trainee-table');
  setupClientSideSearch('traineeSearchInput', '#archivedTrainees .trainee-table');
  setupClientSideSearch('guestSearchInput', '#guests .guest-table');
  setupClientSideSearch('enrollmentSearchInput', '#enrollments .enrollment-table');
  document.addEventListener('modalOpened', function(e) {
    if (e.detail.modalId === 'addCourseModal') { initializeCompetencyHandlers(); }
    if (e.detail.modalId === 'editCourseModal') { initializeEditCompetencyHandlers(); }
  });
}