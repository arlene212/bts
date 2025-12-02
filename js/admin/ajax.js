function ajaxRequest(action, data) {
  return fetch('../admin/handlers/ajax_handlers.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ ajax_action: action, ...data })
  })
  .then(response => response.json())
  .catch(() => ({ success: false, message: 'Network error occurred' }));
}

document.querySelectorAll('.archive-form').forEach(form => {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const userId = form.querySelector('input[name="user_id"]').value;
    showConfirm('Archive User', 'Are you sure you want to archive this user?', async () => {
      const result = await ajaxRequest('archive_user', { user_id: userId });
      if (result.success) { showAlert('success', 'Success', result.message); }
      else { showAlert('error', 'Error', result.message); }
    });
  });
});

document.querySelectorAll('.reset-password-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const userId = btn.dataset.userId;
    const userName = btn.dataset.userName;
    showConfirm('Reset Password', `Are you sure you want to reset the password for <strong>${userName}</strong>?`, async () => {
      const result = await ajaxRequest('reset_password', { user_id: userId });
      if (result.success) {
        showAlert('success', 'Password Reset Successful', `New temporary password: <br><br><div style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 16px; text-align: center;">${result.data.temp_password}</div><br><small>Please save this password as it will not be shown again.</small>`);
      } else { showAlert('error', 'Error', result.message); }
    });
  });
});

// Course archive/restore confirmation using modal instead of browser prompt
document.querySelectorAll('form.course-archive-form').forEach(form => {
  form.addEventListener('submit', function(e){
    e.preventDefault();
    showConfirm('Archive Course', 'Are you sure you want to archive this course?', function(){ form.submit(); });
  });
});

document.querySelectorAll('form.course-restore-form').forEach(form => {
  form.addEventListener('submit', function(e){
    e.preventDefault();
    showConfirm('Restore Course', 'Restore this course?', function(){ form.submit(); });
  });
});

// Restore user confirmation
document.querySelectorAll('form.unarchive-form').forEach(form => {
  form.addEventListener('submit', function(e){
    e.preventDefault();
    const userId = form.querySelector('input[name="user_id"]').value;
    showConfirm('Restore User', 'Restore this user account?', async function(){
      const result = await ajaxRequest('unarchive_user', { user_id: userId });
      if (result.success) { showAlert('success', 'Success', result.message); }
      else { showAlert('error', 'Error', result.message); }
    });
  });
});
