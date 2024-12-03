<?php
// models.php
include 'dbconfig.php';

// User Registration
function registerUser($conn, $data) {
    $username = trim($data['username']);
    $email = trim($data['email']);
    $password = $data['password'];
    $confirm_password = $data['confirm_password'];

    // Basic validation
    if ($password !== $confirm_password) {
        return ['message' => 'Passwords do not match.', 'statusCode' => 400];
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
    $stmt->execute([':email' => $email, ':username' => $username]);

    if ($stmt->rowCount() > 0) {
        return ['message' => 'User already exists.', 'statusCode' => 400];
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert the new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
    $result = $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => $hashedPassword
    ]);

    if ($result) {
        return ['message' => 'Registration successful.', 'statusCode' => 200];
    } else {
        return ['message' => 'Registration failed.', 'statusCode' => 400];
    }
}

// User Login
function loginUser($conn, $data) {
    $email = trim($data['email']);
    $password = $data['password'];

    // Fetch user data
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);

    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            return ['message' => 'Login successful.', 'statusCode' => 200];
        }
    }

    return ['message' => 'Invalid credentials.', 'statusCode' => 400];
}

// Check if User is Logged In
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Log Activity
function logActivity($conn, $user_id, $action, $details = '') {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (:user_id, :action, :details)");
    $stmt->execute([
        ':user_id' => $user_id,
        ':action' => $action,
        ':details' => $details
    ]);
}

// Create Applicant
function insertApplicant($conn, $data) {
    if (!isLoggedIn()) {
        return ['message' => 'Unauthorized.', 'statusCode' => 400];
    }

    $name = trim($data['name']);
    $email = trim($data['email']);
    $phone = trim($data['phone']);
    $address = trim($data['address']);
    $position = trim($data['position_applied']);
    $resume = trim($data['resume']);
    $created_by = $_SESSION['user_id'];

    try {
        $stmt = $conn->prepare("INSERT INTO applicants (name, email, phone, address, position_applied, resume, created_by) 
                                VALUES (:name, :email, :phone, :address, :position_applied, :resume, :created_by)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':position_applied' => $position,
            ':resume' => $resume,
            ':created_by' => $created_by
        ]);

        logActivity($conn, $created_by, 'INSERT', "Added applicant: $name");
        return ['message' => 'Applicant added successfully.', 'statusCode' => 200];
    } catch (PDOException $e) {
        return ['message' => 'Failed to add applicant: ' . $e->getMessage(), 'statusCode' => 400];
    }
}

// Read Applicants
function getApplicants($conn, $search = '') {
    $user_id = $_SESSION['user_id'];
    $search = trim($search);

    if ($search) {
        $searchParam = "%" . $search . "%";
        $stmt = $conn->prepare("SELECT * FROM applicants 
                                WHERE name LIKE :search 
                                   OR email LIKE :search 
                                   OR phone LIKE :search 
                                   OR address LIKE :search 
                                   OR position_applied LIKE :search");
        $stmt->execute([':search' => $searchParam]);

        // Log search activity
        logActivity($conn, $user_id, 'SEARCH', "Searched applicants with keyword: $search");
    } else {
        $stmt = $conn->prepare("SELECT * FROM applicants");
        $stmt->execute();
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Update Applicant
function updateApplicant($conn, $data) {
    if (!isLoggedIn()) {
        return ['message' => 'Unauthorized.', 'statusCode' => 400];
    }

    $id = intval($data['id']);
    $name = trim($data['name']);
    $email = trim($data['email']);
    $phone = trim($data['phone']);
    $address = trim($data['address']);
    $position = trim($data['position_applied']);
    $resume = trim($data['resume']);
    $updated_by = $_SESSION['user_id'];

    try {
        $stmt = $conn->prepare("UPDATE applicants 
                                SET name = :name, email = :email, phone = :phone, 
                                    address = :address, position_applied = :position_applied, 
                                    resume = :resume 
                                WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':position_applied' => $position,
            ':resume' => $resume,
            ':id' => $id
        ]);

        logActivity($conn, $updated_by, 'UPDATE', "Updated applicant ID: $id");
        return ['message' => 'Applicant updated successfully.', 'statusCode' => 200];
    } catch (PDOException $e) {
        return ['message' => 'Failed to update applicant: ' . $e->getMessage(), 'statusCode' => 400];
    }
}

// Delete Applicant
function deleteApplicant($conn, $id) {
    if (!isLoggedIn()) {
        return ['message' => 'Unauthorized.', 'statusCode' => 400];
    }

    $id = intval($id);
    $deleted_by = $_SESSION['user_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM applicants WHERE id = :id");
        $stmt->execute([':id' => $id]);

        logActivity($conn, $deleted_by, 'DELETE', "Deleted applicant ID: $id");
        return ['message' => 'Applicant deleted successfully.', 'statusCode' => 200];
    } catch (PDOException $e) {
        return ['message' => 'Failed to delete applicant: ' . $e->getMessage(), 'statusCode' => 400];
    }
}

// Count Applicants
function countApplicants($conn) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM applicants");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    } catch (PDOException $e) {
        return 0;
    }
}
?>
