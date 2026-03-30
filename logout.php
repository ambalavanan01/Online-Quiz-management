<?php
// logout.php
require_once 'includes/auth.php';
logout();
header("Location: login.php");
exit;
?>
