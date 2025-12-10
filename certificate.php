<?php
// Convenience redirect for users visiting /bts/certificate.php
$course = isset($_GET['course_code']) ? $_GET['course_code'] : '';
header('Location: guest/certificate.php?course_code=' . urlencode($course));
exit;
?>
