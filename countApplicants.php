<?php
// countApplicants.php
include 'models.php';

if (!isLoggedIn()) {
    echo "0";
    exit;
}

echo countApplicants($conn);
?>
