<?php ?>
<section class="tab-content <?php echo $currentTab === 'requests' ? 'active' : ''; ?>" id="requests">
  <h2 class="section-header">Enrollment Requests for My Courses</h2>
  <p class="trainer-note" style="color: #6c757d; font-size: 0.9em; margin-top: -10px; margin-bottom: 20px;">
    <i class="fas fa-eye"></i> <strong>View Only:</strong> You can view enrollment requests for your assigned courses. Only admins can approve or reject enrollment requests.
  </p>
  <div class="table-responsive"><table class="table table-striped table-hover requests-table"><thead><tr><th>Course</th><th>Trainee Name</th><th>Status</th><th>Date Requested</th><th>Actions</th></tr></thead><tbody id="requests-body">
    <?php foreach ($enrollment_requests as $request): ?>
      <tr>
        <td><?php echo htmlspecialchars($request['course_name']); ?></td>
        <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
        <td><span class="status-<?php echo htmlspecialchars($request['status']); ?>"><?php echo htmlspecialchars($request['status']); ?></span></td>
        <td><?php echo date('M d, Y', strtotime($request['date_requested'])); ?></td>
        <td class="table-actions">
          <span class="status-info" style="color: #6c757d; font-size: 0.9em;">
            <i class="fas fa-info-circle"></i> Pending Admin Review
          </span>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($enrollment_requests)): ?>
      <tr><td colspan="5" style="text-align: center;">No pending enrollment requests.</td></tr>
    <?php endif; ?>
  </tbody></table></div>
</section>