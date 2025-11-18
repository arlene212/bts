function setupDynamicBatchLoading() {
  const traineeCourseSelect = document.getElementById('trainee_course');
  const traineeBatchSelect = document.getElementById('trainee_batch');
  if (traineeCourseSelect && traineeBatchSelect) {
    traineeCourseSelect.addEventListener('change', function() {
      const courseCode = this.value;
      traineeBatchSelect.innerHTML = '<option value="">Select a batch</option>';
      if (courseCode) {
        fetch('../admin/handlers/get_batches.php?course_code=' + encodeURIComponent(courseCode))
          .then(response => { if (!response.ok) throw new Error('Network response was not ok'); return response.json(); })
          .then(batches => {
            if (batches && batches.length > 0) {
              batches.forEach(batch => {
                const option = document.createElement('option');
                const name = batch.batch_name || batch;
                option.value = name;
                option.textContent = name;
                traineeBatchSelect.appendChild(option);
              });
            } else {
              const option = document.createElement('option');
              option.value = '';
              option.textContent = 'No batches available';
              traineeBatchSelect.appendChild(option);
            }
          })
          .catch(() => {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Error loading batches';
            traineeBatchSelect.appendChild(option);
          });
      }
    });
  }
}