<?php ?>
<?php if (isset($_SESSION['success_message'])): ?>
  <div class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
  <div class="error-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
<?php endif; ?>