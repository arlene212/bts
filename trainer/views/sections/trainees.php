<?php ?>
<section class="tab-content <?php echo $currentTab === 'trainees' ? 'active' : ''; ?>" id="trainees">
  <h2 class="section-header">My Trainees</h2>
  <div class="search-container"><form method="GET" action="trainer.php#trainees"><input type="hidden" name="trainee_page" value="1"><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input type="text" name="trainee_search" class="form-control" placeholder="Search trainees by name, ID, email, or course..." value="<?php echo htmlspecialchars($traineeSearch); ?>"><button type="submit" class="btn btn-primary btn-ripple"><i class="fas fa-search"></i> Search</button><?php if (!empty($traineeSearch)): ?><a href="trainer.php?trainee_page=1#trainees" class="btn btn-outline-secondary btn-ripple">Clear</a><?php endif; ?></div></form></div>
  <div class="table-responsive"><table class="table table-striped table-hover trainees-table"><thead><tr><th>Trainee Name</th><th>Email</th><th>Contact Number</th><th>Enrolled Course</th><th>Batch</th><th>Status</th><th>Last Login</th></tr></thead><tbody>
    <?php foreach ($trainees as $trainee): ?>
      <tr>
        <td><?php echo htmlspecialchars($trainee['first_name'] . ' ' . $trainee['last_name']); ?></td>
        <td><?php echo htmlspecialchars($trainee['email']); ?></td>
        <td><?php echo htmlspecialchars($trainee['contact_number'] ?: '-'); ?></td>
        <td><?php echo htmlspecialchars($trainee['course_name'] ?: '-'); ?></td>
        <td><?php echo htmlspecialchars($trainee['batch_name'] ?: 'Not Assigned'); ?></td>
        <td><span class="status-active"><?php echo htmlspecialchars($trainee['status']); ?></span></td>
        <td><?php echo $trainee['last_login'] ? date('M d, Y H:i', strtotime($trainee['last_login'])) : 'Never'; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($trainees)): ?>
      <tr><td colspan="7" style="text-align: center;">No trainees found in your courses</td></tr>
    <?php endif; ?>
  </tbody></table></div>
  <?php if ($totalTraineePages > 1): ?><div class="pagination"><?php for ($i = 1; $i <= $totalTraineePages; $i++): ?><a href="?trainee_page=<?php echo $i; ?>&trainee_search=<?php echo urlencode($traineeSearch); ?>#trainees" class="btn btn-sm <?php echo $i == $traineePage ? 'btn-primary' : 'btn-outline-primary'; ?> btn-ripple"><?php echo $i; ?></a><?php endfor; ?></div><?php endif; ?>
</section>