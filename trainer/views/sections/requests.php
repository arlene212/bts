<?php ?>
<section class="tab-content <?php echo $currentTab === 'requests' ? 'active' : ''; ?>" id="requests">
  <h2 class="section-header">Enrollment Requests for My Courses</h2>
  <div class="table-responsive"><table class="table table-striped table-hover requests-table"><thead><tr><th>Course</th><th>Trainee Name</th><th>Status</th><th>Date Requested</th><th>Actions</th></tr></thead><tbody id="requests-body">
    <?php foreach ($enrollment_requests as $request): ?>
      <tr>
        <td><?php echo htmlspecialchars($request['course_name']); ?></td>
        <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
        <td><span class="status-<?php echo htmlspecialchars($request['status']); ?>"><?php echo htmlspecialchars($request['status']); ?></span></td>
        <td><?php echo date('M d, Y', strtotime($request['date_requested'])); ?></td>
        <td class="table-actions">
          <button class="action-btn approve btn btn-sm btn-success btn-ripple" title="Approve" data-request-id="<?php echo $request['id']; ?>"><i class="fas fa-check"></i><span class="btn-text">Approve</span></button>
          <button class="action-btn reject btn btn-sm btn-outline-danger btn-ripple" title="Reject" data-request-id="<?php echo $request['id']; ?>"><i class="fas fa-times"></i><span class="btn-text">Reject</span></button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($enrollment_requests)): ?>
      <tr><td colspan="5" style="text-align: center;">No pending enrollment requests.</td></tr>
    <?php endif; ?>
  </tbody></table></div>
</section>