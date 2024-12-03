<?php
// getTotalPages.php
include 'models.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $itemsPerPage = isset($_GET['itemsPerPage']) ? (int)$_GET['itemsPerPage'] : 10;
    $totalApplicants = countApplicants($conn);
    $totalPages = ceil($totalApplicants / $itemsPerPage);
    echo json_encode([
        'statusCode' => 200,
        'totalPages' => $totalPages
    ]);
}
?>
