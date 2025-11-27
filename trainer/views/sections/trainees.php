<?php ?>
<section class="tab-content <?php echo $currentTab === 'trainees' ? 'active' : ''; ?>" id="trainees">
  <h2 class="section-header">My Trainees</h2>
  <div id="trainees-loading" style="display: none; text-align: center; padding: 20px;">
    <i class="fas fa-spinner fa-spin fa-2x"></i>
    <p>Loading trainees...</p>
  </div>
  <div class="search-container">
    <form method="GET" action="index.php#trainees" id="trainees-search-form">
      <input type="hidden" name="current_tab" value="trainees">
      <input type="hidden" name="trainee_page" value="1">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" name="trainee_search" class="form-control" placeholder="Search trainees by name, ID, email, or course..." value="<?php echo htmlspecialchars($traineeSearch); ?>" oninput="debouncedSearch()">
          </div>
        </div>
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-filter"></i></span>
            <select name="trainee_status" class="form-select" onchange="this.form.submit()">
              <option value="active" <?php echo $traineeStatus === 'active' ? 'selected' : ''; ?>>Active Trainees</option>
              <option value="dropped" <?php echo $traineeStatus === 'dropped' ? 'selected' : ''; ?>>Dropped Trainees</option>
              <option value="graduated" <?php echo $traineeStatus === 'graduated' ? 'selected' : ''; ?>>Graduated Trainees</option>
              <option value="all" <?php echo $traineeStatus === 'all' ? 'selected' : ''; ?>>All Trainees</option>
            </select>
          </div>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary btn-ripple w-100"><i class="fas fa-search"></i> Search</button>
        </div>
      </div>
      <?php if (!empty($traineeSearch) || $traineeStatus !== 'active'): ?>
        <a href="index.php?current_tab=trainees&trainee_page=1&trainee_status=active#trainees" class="btn btn-outline-secondary btn-ripple mt-2">Clear Filters</a>
      <?php endif; ?>
    </form>
  </div>
  <div id="trainees-table-container">
    <div class="table-responsive"><table class="table table-striped table-hover trainees-table"><thead><tr><th>Trainee Name</th><th>Email</th><th>Contact Number</th><th>Enrolled Course</th><th>Batch</th><th>Status</th><th>Last Login</th></tr></thead><tbody>
    <?php foreach ($trainees as $trainee): ?>
      <tr>
        <td><?php echo htmlspecialchars($trainee['first_name'] . ' ' . $trainee['last_name']); ?></td>
        <td><?php echo htmlspecialchars($trainee['email']); ?></td>
        <td><?php echo htmlspecialchars($trainee['contact_number'] ?: '-'); ?></td>
        <td><?php echo htmlspecialchars($trainee['course_name'] ?: '-'); ?></td>
        <td><?php echo htmlspecialchars($trainee['batch_name'] ?: 'Not Assigned'); ?></td>
        <td>
          <?php 
          $statusClass = '';
          $statusText = htmlspecialchars($trainee['status']);
          if ($trainee['status'] === 'active') {
            $statusClass = 'badge bg-success';
          } elseif ($trainee['status'] === 'dropped') {
            $statusClass = 'badge bg-danger';
          } elseif ($trainee['status'] === 'graduated') {
            $statusClass = 'badge bg-primary';
          } else {
            $statusClass = 'badge bg-secondary';
          }
          ?>
          <span class="<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
          <?php if (isset($trainee['enrollment_status']) && $trainee['enrollment_status'] !== 'approved'): ?>
            <br><small class="text-muted">(<?php echo htmlspecialchars($trainee['enrollment_status']); ?>)</small>
          <?php endif; ?>
        </td>
        <td><?php echo $trainee['last_login'] ? date('M d, Y H:i', strtotime($trainee['last_login'])) : 'Never'; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($trainees)): ?>
      <tr><td colspan="7" style="text-align: center;">
        <?php 
        $noTraineesMessage = 'No trainees found in your courses';
        if ($traineeStatus === 'dropped') {
          $noTraineesMessage = 'No dropped trainees found in your courses';
        } elseif ($traineeStatus === 'graduated') {
          $noTraineesMessage = 'No graduated trainees found in your courses';
        } elseif ($traineeStatus === 'all') {
          $noTraineesMessage = 'No trainees found in your courses';
        }
        echo $noTraineesMessage;
        ?>
      </td></tr>
    <?php endif; ?>
  </tbody></table></div>
  <?php if ($totalTraineePages > 1): ?><div class="pagination"><?php for ($i = 1; $i <= $totalTraineePages; $i++): ?><a href="index.php?current_tab=trainees&trainee_page=<?php echo $i; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>&trainee_status=<?php echo urlencode($traineeStatus); ?>#trainees" class="btn btn-sm <?php echo $i == $traineePage ? 'btn-primary' : 'btn-outline-primary'; ?> btn-ripple"><?php echo $i; ?></a><?php endfor; ?></div><?php endif; ?>
  </div>
</section>

<script>
let searchTimeout;

function debouncedSearch() {
  clearTimeout(searchTimeout);
  document.getElementById('trainees-loading').style.display = 'block';
  document.getElementById('trainees-table-container').style.display = 'none';
  
  searchTimeout = setTimeout(() => {
    document.getElementById('trainees-search-form').submit();
  }, 500); // Wait 500ms after user stops typing
}

// Hide loading indicator when page loads
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('trainees-loading').style.display = 'none';
  document.getElementById('trainees-table-container').style.display = 'block';
});
</script>
