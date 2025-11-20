<?php ?>
<section class="tab-content <?php echo $currentTab === 'enrollment-requests' ? 'active' : ''; ?>" id="enrollment-requests">
  <h1 class="section-header">My Enrollment Requests</h1>
  <div class="requests-container">
    <?php if (!empty($enrollment_requests)): ?>
      <div class="requests-grid">
        <?php foreach ($enrollment_requests as $request): ?>
          <div class="request-card">
            <div class="request-header">
              <h3 class="course-name"><?php echo htmlspecialchars($request['course_name']); ?></h3>
              <span class="status-badge <?php echo htmlspecialchars($request['status']); ?>">
                <?php echo htmlspecialchars(ucfirst($request['status'])); ?>
              </span>
            </div>
            <div class="request-details">
              <div class="detail-item">
                <i class="fas fa-calendar"></i>
                <span>Requested: <?php echo date('M d, Y', strtotime($request['date_requested'])); ?></span>
              </div>
              <?php if (!empty($request['remarks'])): ?>
                <div class="detail-item remarks">
                  <i class="fas fa-comment"></i>
                  <span>Remarks: <?php echo htmlspecialchars($request['remarks']); ?></span>
                </div>
              <?php endif; ?>
              <?php if (!empty($request['processed_date'])): ?>
                <div class="detail-item">
                  <i class="fas fa-clock"></i>
                  <span>Processed: <?php echo date('M d, Y', strtotime($request['processed_date'])); ?></span>
                </div>
              <?php endif; ?>
            </div>
            <div class="request-actions">
              <?php if ($request['status'] === 'pending'): ?>
                <button class="btn btn-outline-danger btn-cancel btn-ripple" data-request-id="<?php echo htmlspecialchars($request['id']); ?>">
                  <i class="fas fa-times"></i> Cancel Request
                </button>
              <?php else: ?>
                <span class="status-badge status-<?php echo htmlspecialchars($request['status']); ?>">
                  <i class="fas fa-lock"></i> Request <?php echo htmlspecialchars(ucfirst($request['status'])); ?>
                </span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="no-requests">
        <i class="fas fa-inbox fa-3x"></i>
        <h3>No Enrollment Requests</h3>
        <p>You haven't made any enrollment requests yet.</p>
        <a href="#" class="btn btn-primary tab-switch btn-ripple" data-tab="offered-courses">
          <i class="fas fa-plus"></i> Browse Available Courses
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>