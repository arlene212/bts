<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Benguet Technical School eLMS - Trainer</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/trainer.css">
  <link rel="stylesheet" href="../css/utils.css">
  <link rel="icon" type="image/png" href="../images/school.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/v4-shims.min.css">
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
          <?php include __DIR__ . '/sections/course.php'; ?>
          <?php include __DIR__ . '/sections/requests.php'; ?>
          <?php include __DIR__ . '/sections/trainees.php'; ?>
          <?php include __DIR__ . '/sections/guest.php'; ?>
        </div>
      </div>
    </main>
  </div>
  <?php include __DIR__ . '/partials/modal.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    window.currentTab = '<?php echo addslashes($currentTab ?? "home"); ?>';
  </script>
  <script src="../js/trainer/main.js"></script>
  <script src="../js/trainer/ajax.js"></script>
  <script src="../js/trainer/forms.js"></script>
  <script src="../js/trainer/modals.js"></script>
  <script src="../js/trainer/search.js"></script>
  <script src="../js/trainer/course_details.js"></script>
</body>
</html>