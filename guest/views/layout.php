<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Benguet Technical School eLMS - Guest</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/v4-shims.min.css">
  <link rel="stylesheet" href="../css/guest.css">
  <link rel="stylesheet" href="../css/utils.css">
  <link rel="icon" type="image/png" href="../images/school.png">
</head>
<body>
  <style>*{ -webkit-user-select: none; -ms-user-select: none; user-select: none; }</style>
  <div class="container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="main">
      <?php include __DIR__ . '/partials/header.php'; ?>
      <div class="content">
        <?php include __DIR__ . '/partials/alert.php'; ?>
        <div class="main-content">
          <div class="content-area <?php echo $currentTab === 'home' ? 'active' : ''; ?>" id="content-home">
            <?php include __DIR__ . '/sections/home.php'; ?>
          </div>
          <div class="content-area <?php echo $currentTab === 'courses' ? 'active' : ''; ?>" id="content-courses">
            <?php include __DIR__ . '/sections/offered.php'; ?>
          </div>
          <div class="content-area <?php echo $currentTab === 'enrolled' ? 'active' : ''; ?>" id="content-enrolled">
            <?php include __DIR__ . '/sections/enrolled.php'; ?>
          </div>
          <div class="content-area <?php echo $currentTab === 'aboutUs' ? 'active' : ''; ?>" id="content-aboutUs">
            <?php include __DIR__ . '/sections/about.php'; ?>
          </div>
          <div class="content-area <?php echo $currentTab === 'quizzes' ? 'active' : ''; ?>" id="content-quizzes">
            <?php include __DIR__ . '/sections/quizzes.php'; ?>
          </div>
        </div>
        <aside class="announcements">
          <div class="announcements-header">
            <h2><i class="fas fa-bullhorn"></i> Announcements</h2>
          </div>
          <div class="announcements-content">
            <?php if (!empty($announcements)): ?>
              <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-item">
                  <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                  <p><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>...</p>
                  <small><?php echo date('M d, Y', strtotime($announcement['date_posted'])); ?></small>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="no-announcements">
                <i class="fas fa-bell-slash"></i>
                <p>No announcements yet. Stay tuned!</p>
              </div>
            <?php endif; ?>
          </div>
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
  <script src="../js/guest/navigation.js"></script>
  <script src="../js/guest/modal_controls.js"></script>
  <script src="../js/guest/course_details.js"></script>
  <script src="../js/guest/enrollment.js"></script>
  <script src="../js/guest/activity.js"></script>
  <script src="../js/guest/profile.js"></script>
  <script src="../js/verification.js"></script>
  <script src="../js/guest/main.js"></script>
  <script>
    (function(){
      function d(e){ e.preventDefault(); return false; }
      ['copy','cut','paste','contextmenu','selectstart','dragstart'].forEach(function(ev){ document.addEventListener(ev, d, {capture:true}); });
      document.addEventListener('keydown', function(e){
        var k = String(e.key || '').toLowerCase();
        if ((e.ctrlKey || e.metaKey) && ['c','x','v','a','s','p','u'].indexOf(k) !== -1) { e.preventDefault(); }
        if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && ['i','j','c','u'].indexOf(k) !== -1)) { e.preventDefault(); }
      }, true);
    })();
  </script>
</body>
</html>
