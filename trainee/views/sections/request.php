<?php ?>
<section class="tab-content <?php echo $currentTab === 'enrollment-requests' ? 'active' : ''; ?>" id="enrollment-requests">
  <h2 class="section-header">My Enrollment Requests</h2>
  <div class="table-responsive"><table class="table table-striped table-hover requests-table"><thead><tr><th>Course</th><th>Date Requested</th><th>Status</th><th>Remarks</th><th>Action</th></tr></thead><tbody id="requests-body">
    <?php if (!empty($enrollment_requests)): ?>
      <?php foreach ($enrollment_requests as $request): ?>
        <tr>
          <td class="course-name"><?php echo htmlspecialchars($request['course_name']); ?></td>
          <td><?php echo date('M d, Y', strtotime($request['date_requested'])); ?></td>
          <td class="status <?php echo htmlspecialchars($request['status']); ?>"><?php echo htmlspecialchars(ucfirst($request['status'])); ?></td>
          <td><?php echo htmlspecialchars($request['remarks'] ?? '-'); ?></td>
          <td class="table-actions">
            <?php if ($request['status'] === 'pending'): ?>
              <button class="btn-cancel btn btn-sm btn-outline-danger" data-request-id="<?php echo htmlspecialchars($request['id']); ?>">Cancel</button>
            <?php else: ?>
              -
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="5" class="no-data">No enrollment requests found.</td></tr>
    <?php endif; ?>
  </tbody></table></div>
</section>