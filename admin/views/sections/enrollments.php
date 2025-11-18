<?php ?>
<section class="main-content tab-content" id="enrollments">
  <div class="tab-header"><h2>Enrollment Management</h2></div>
  <div class="search-container">
    <div class="input-group">
      <span class="input-group-text"><i class="fas fa-search"></i></span>
      <input type="text" id="enrollmentSearchInput" class="form-control" placeholder="Search enrollments by trainee name, course, or ID..." value="<?php echo htmlspecialchars($enrollmentSearch); ?>">
      <div class="search-loading hidden" id="enrollmentSearchLoading"><i class="fas fa-spinner fa-spin"></i></div>
      <?php if (!empty($enrollmentSearch)): ?><a href="?enrollment_page=1" class="btn btn-link" id="enrollmentClearSearch">Clear</a><?php endif; ?>
    </div>
    <div class="search-results-dropdown" id="enrollmentSearchResults"></div>
    <div class="search-results-info" id="enrollmentSearchResultsInfo">
      <?php if (!empty($enrollmentSearch)): ?>
        Showing <?php echo count($enrollments); ?> of <?php echo $totalEnrollments; ?> enrollments matching "<?php echo htmlspecialchars($enrollmentSearch); ?>"
      <?php endif; ?>
    </div>
  </div>
  <div class="table-container">
    <table class="table table-striped table-hover enrollment-table"><thead><tr><th>Enrollment ID</th><th>Trainee Name</th><th>Course & Trainee Type</th><th>Batch</th><th>Status</th><th>Date Requested</th><th>Remarks</th><th>Actions</th></tr></thead><tbody>
      <?php if (empty($enrollments)): ?>
        <tr><td colspan="8" class="no-data">No enrollments found</td></tr>
      <?php else: ?>
        <?php foreach ($enrollments as $enrollment): ?>
          <tr>
            <td><?php echo $enrollment['id']; ?></td>
            <td><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></td>
            <td><?php echo htmlspecialchars($enrollment['course_name']); ?><br><small>(<?php echo htmlspecialchars(ucfirst($enrollment['trainee_role'])); ?>)</small></td>
            <td><?php echo htmlspecialchars($enrollment['batch_name'] ?? 'Not Assigned'); ?></td>
            <td><span class="status-badge status-<?php echo $enrollment['status']; ?>"><?php echo ucfirst($enrollment['status']); ?></span></td>
            <td><?php echo date('Y-m-d H:i', strtotime($enrollment['date_requested'])); ?></td>
            <td><?php echo htmlspecialchars($enrollment['remarks'] ?? 'No remarks'); ?></td>
            <td>
              <?php if ($enrollment['status'] == 'pending'): ?>
                <div class="table-actions enrollment-actions" data-enrollment-id="<?php echo $enrollment['id']; ?>">
                  <button class="action-btn approve btn btn-sm btn-success" title="Approve" data-action="approve"><i class="fas fa-check"></i><span class="btn-text">Approve</span></button>
                  <button class="action-btn reject btn btn-sm btn-outline-danger" title="Reject"><i class="fas fa-times"></i><span class="btn-text">Reject</span></button>
                </div>
              <?php else: ?>
                <span class="processed-text">Processed on <?php echo date('Y-m-d', strtotime($enrollment['processed_date'])); ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody></table>
  </div>
  <?php if ($totalEnrollmentPages > 1): ?>
    <div class="pagination">
      <?php if ($enrollmentPage > 1): ?>
        <a href="?enrollment_page=1&enrollment_search=<?php echo urlencode($enrollmentSearch); ?>" class="page-link first">First</a>
        <a href="?enrollment_page=<?php echo $enrollmentPage - 1; ?>&enrollment_search=<?php echo urlencode($enrollmentSearch); ?>" class="page-link prev">Previous</a>
      <?php endif; ?>
      <?php for ($i = max(1, $enrollmentPage - 2); $i <= min($totalEnrollmentPages, $enrollmentPage + 2); $i++): ?>
        <a href="?enrollment_page=<?php echo $i; ?>&enrollment_search=<?php echo urlencode($enrollmentSearch); ?>" class="page-link <?php echo $i == $enrollmentPage ? 'active' : ''; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
      <?php if ($enrollmentPage < $totalEnrollmentPages): ?>
        <a href="?enrollment_page=<?php echo $enrollmentPage + 1; ?>&enrollment_search=<?php echo urlencode($enrollmentSearch); ?>" class="page-link next">Next</a>
        <a href="?enrollment_page=<?php echo $totalEnrollmentPages; ?>&enrollment_search=<?php echo urlencode($enrollmentSearch); ?>" class="page-link last">Last</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>