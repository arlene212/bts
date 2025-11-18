<?php ?>
<section class="tab-content <?php echo $currentTab === 'guests' ? 'active' : ''; ?>" id="guests">
  <h2 class="section-header">Guest Users in My Courses</h2>
  <div class="table-controls"><div class="input-group mb-2"><span class="input-group-text"><i class="fas fa-search"></i></span><input type="text" id="guestSearch" class="form-control" placeholder="Search guests by name, email, or phone..."></div><div class="guests-count">Total Guests: <span id="totalGuests"><?php echo count($guests); ?></span></div></div>
  <div class="table-responsive"><table class="table table-striped table-hover guests-table"><thead><tr><th>Guest Name</th><th>Email</th><th>Contact Number</th><th>Course</th><th>Registration Date</th><th>Last Login</th></tr></thead><tbody id="guestsBody">
    <?php
      $guests_with_courses_query = "SELECT DISTINCT u.*, e.course_name FROM users u JOIN enrollments e ON u.user_id = e.trainee_id JOIN course_assignments ca ON e.course_code = ca.course_code WHERE u.role = 'guest' AND u.status = 'active' AND e.status = 'approved' AND ca.trainer_id = ?";
      $guests_stmt = $db->prepare($guests_with_courses_query);
      $guests_stmt->execute([$trainer_id]);
      $guests_with_courses = $guests_stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <?php foreach ($guests_with_courses as $guest): ?>
      <tr>
        <td><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></td>
        <td><?php echo htmlspecialchars($guest['email']); ?></td>
        <td><?php echo htmlspecialchars($guest['contact_number'] ?: '-'); ?></td>
        <td><?php echo htmlspecialchars($guest['course_name'] ?: '-'); ?></td>
        <td><?php echo date('M d, Y', strtotime($guest['date_created'])); ?></td>
        <td><?php echo $guest['last_login'] ? date('M d, Y H:i', strtotime($guest['last_login'])) : 'Never'; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($guests_with_courses)): ?>
      <tr><td colspan="6" style="text-align: center;">No guests found in your courses</td></tr>
    <?php endif; ?>
  </tbody></table></div>
</section>