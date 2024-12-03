<?php
// update_applicant.php
include 'models.php';

if (!isLoggedIn()) {
    echo json_encode(['statusCode' => 400, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = updateApplicant($conn, $_POST);
    echo json_encode([
        'statusCode' => $response['statusCode'],
        'message' => $response['message']
    ]);
    exit;
} else {
    echo json_encode(['statusCode' => 400, 'message' => 'Invalid request method']);
    exit;
}
?>