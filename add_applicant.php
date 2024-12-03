<?php
// add_applicant.php
include 'models.php';

if (!isLoggedIn()) {
    echo json_encode(['statusCode' => 400, 'message' => 'Unauthorized']);
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input data
    $requiredFields = ['name', 'email', 'position_applied'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['statusCode' => 400, 'message' => "{$field} is required"]);
            exit;
        }
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['statusCode' => 400, 'message' => 'Invalid email format']);
        exit;
    }

    // Add the user_id to the data (logged-in user's ID)
    session_start();
    $_POST['user_id'] = $_SESSION['user_id'];

    // Insert the applicant
    $response = insertApplicant($conn, $_POST);
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
