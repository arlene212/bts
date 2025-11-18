<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Benguet Technical School eLMS - Guest</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/v4-shims.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/guest.css">
  <link rel="stylesheet" href="../css/utils.css">
  <link rel="icon" type="image/png" href="../images/school.png">
</head>
<body>
  <div class="container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="main">
      <?php include __DIR__ . '/partials/header.php'; ?>
      <div class="content">
        <div class="main-section">
          <?php include __DIR__ . '/sections/home.php'; ?>
          <?php include __DIR__ . '/sections/offered.php'; ?>
          <?php include __DIR__ . '/sections/enrolled.php'; ?>
          <?php include __DIR__ . '/sections/about.php'; ?>
        </div>
        <aside class="announcements">
          <h2>Announcements</h2>
          <ul>
            <?php if (!empty($announcements)): ?>
              <?php foreach ($announcements as $announcement): ?>
                <li>
                  <strong><?php echo htmlspecialchars($announcement['title']); ?></strong><br>
                  <?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>...<br>
                  <small><?php echo date('M d, Y', strtotime($announcement['date_posted'])); ?></small>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li>No announcements yet. Stay tuned!</li>
            <?php endif; ?>
          </ul>
        </aside>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/modal.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    window.currentTab = '<?php echo addslashes($currentTab ?? "home"); ?>';
  </script>
  <script src="../js/guest/core.js"></script>
  <script src="../js/guest/modal_controls.js"></script>
  <script src="../js/guest/navigation.js"></script>
  <script src="../js/guest/enrollment.js"></script>
  <script src="../js/guest/activity.js"></script>
  <script src="../js/guest/profile.js"></script>
  <script src="../js/guest/course_details.js"></script>
  <script src="../js/guest/main.js"></script>
</body>
</html>