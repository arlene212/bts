<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Benguet Technical School eLMS - Trainee</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/v4-shims.min.css">
  <link rel="icon" type="image/png" href="../images/school.png">
  <link rel="stylesheet" href="../css/trainee.css">
  <link rel="stylesheet" href="../css/utils.css">
</head>
<body>
  <div class="container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="main">
      <?php include __DIR__ . '/partials/header.php'; ?>
      <div class="content">
        <?php include __DIR__ . '/partials/alert.php'; ?>
        <div class="main-content">
          <?php include __DIR__ . '/sections/home.php'; ?>
          <?php include __DIR__ . '/sections/courses.php'; ?>
          <?php include __DIR__ . '/sections/offered.php'; ?>
          <?php include __DIR__ . '/sections/request.php'; ?>
        </div>
        <aside class="announcements">
          <h2>Announcements</h2>
          <ul>
            <?php if (!empty($announcements)): ?>
              <?php foreach ($announcements as $announcement): ?>
                <li><strong><?php echo htmlspecialchars($announcement['title']); ?></strong><br><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>...<br><small><?php echo date('M d, Y', strtotime($announcement['date_posted'])); ?></small></li>
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
  <script src="../js/trainee/main.js"></script>
  <script src="../js/trainee/ajax.js"></script>
  <script src="../js/trainee/forms.js"></script>
  <script src="../js/trainee/modals.js"></script>
  <script src="../js/trainee/search.js"></script>
  <script src="../js/trainee/course_details.js"></script>
</body>
</html>