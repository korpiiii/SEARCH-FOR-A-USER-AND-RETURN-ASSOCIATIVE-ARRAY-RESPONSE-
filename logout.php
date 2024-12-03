<?php
// logout.php
include 'dbconfig.php';
session_destroy();
header('Location: login.php');
exit;
?>
