function setupFormValidation() {
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', function(e) {
      const requiredFields = this.querySelectorAll('[required]');
      let isValid = true;
      requiredFields.forEach(field => {
        if (!field.value.trim()) { isValid = false; field.style.borderColor = 'red'; } else { field.style.borderColor = ''; }
      });
      if (!isValid) { e.preventDefault(); alert('Please fill in all required fields.'); }
    });
  });
}