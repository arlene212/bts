<?php
require_once 'SessionManager.php';
SessionManager::logout();
header("Location: ../landingpage.php");
exit();
?>