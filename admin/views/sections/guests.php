<?php ?>
<section class="main-content tab-content" id="guests">
  <div class="tab-header"><h2>Guest Management</h2></div>
  <div class="search-container">
    <div class="input-group">
      <span class="input-group-text"><i class="fas fa-search"></i></span>
      <input type="text" id="guestSearchInput" class="form-control" placeholder="Search guests by name, ID, or email..." value="<?php echo htmlspecialchars($guestSearch); ?>">
      <div class="search-loading hidden" id="guestSearchLoading"><i class="fas fa-spinner fa-spin"></i></div>
      <?php if (!empty($guestSearch)): ?><a href="?guest_page=1" class="btn btn-link" id="guestClearSearch">Clear</a><?php endif; ?>
    </div>
    <div class="search-results-dropdown" id="guestSearchResults"></div>
    <div class="search-results-info" id="guestSearchResultsInfo">
      <?php if (!empty($guestSearch)): ?>
        Showing <?php echo count($guests); ?> of <?php echo $totalGuests; ?> guests matching "<?php echo htmlspecialchars($guestSearch); ?>"
      <?php endif; ?>
    </div>
  </div>
  <div class="table-container">
    <table class="table table-striped table-hover guest-table"><thead><tr><th>User ID</th><th>User Name</th><th>Email</th><th>Contact Number</th><th>Enrolled Courses</th><th>Date Created</th></tr></thead><tbody>
      <?php if (empty($guests)): ?>
        <tr><td colspan="6" class="no-data">No guests found</td></tr>
      <?php else: ?>
        <?php foreach ($guests as $guest): ?>
          <tr>
            <td><?php echo htmlspecialchars($guest['user_id']); ?></td>
            <td><?php echo htmlspecialchars($guest['last_name'] . ', ' . $guest['first_name'] . ($guest['middle_name'] ? ' ' . $guest['middle_name'] . '.' : '')); ?></td>
            <td><?php echo htmlspecialchars($guest['email']); ?></td>
            <td><?php echo htmlspecialchars($guest['contact_number']); ?></td>
            <td>
              <?php
              $guestEnrollments = array_filter($enrollments, function ($e) use ($guest) { return $e['trainee_id'] == $guest['user_id'] && $e['status'] == 'approved'; });
              if (!empty($guestEnrollments)) { echo implode(', ', array_map(function ($e) { return $e['course_code']; }, $guestEnrollments)); } else { echo 'No courses enrolled'; }
              ?>
            </td>
            <td><?php echo date('Y-m-d', strtotime($guest['date_created'])); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody></table>
  </div>
  <?php if ($totalGuestPages > 1): ?>
    <div class="pagination">
      <?php if ($guestPage > 1): ?>
        <a href="?guest_page=1&guest_search=<?php echo urlencode($guestSearch); ?>" class="page-link first">First</a>
        <a href="?guest_page=<?php echo $guestPage - 1; ?>&guest_search=<?php echo urlencode($guestSearch); ?>" class="page-link prev">Previous</a>
      <?php endif; ?>
      <?php for ($i = max(1, $guestPage - 2); $i <= min($totalGuestPages, $guestPage + 2); $i++): ?>
        <a href="?guest_page=<?php echo $i; ?>&guest_search=<?php echo urlencode($guestSearch); ?>" class="page-link <?php echo $i == $guestPage ? 'active' : ''; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
      <?php if ($guestPage < $totalGuestPages): ?>
        <a href="?guest_page=<?php echo $guestPage + 1; ?>&guest_search=<?php echo urlencode($guestSearch); ?>" class="page-link next">Next</a>
        <a href="?guest_page=<?php echo $totalGuestPages; ?>&guest_search=<?php echo urlencode($guestSearch); ?>" class="page-link last">Last</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>