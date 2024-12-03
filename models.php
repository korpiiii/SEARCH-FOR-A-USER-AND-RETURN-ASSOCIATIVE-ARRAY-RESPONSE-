<?php
include 'dbconfig.php';

// User Registration
function registerUser($conn, $data) {
    $username = trim($data['username']);
    $email = trim($data['email']);
    $password = $data['password'];
    $confirm_password = $data['confirm_password'];

    if ($password !== $confirm_password) {
        return ['message' => 'Passwords do not match.', 'statusCode' => 400];
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
    $stmt->execute([':email' => $email, ':username' => $username]);

    if ($stmt->rowCount() > 0) {
        return ['message' => 'User already exists.', 'statusCode' => 400];
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

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
    $login = trim($data['email']);
    $password = $data['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = :login OR username = :login");
    $stmt->execute([':login' => $login]);

    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            return ['message' => 'Login successful.', 'statusCode' => 200];
        }
    }

    return ['message' => 'Invalid credentials.', 'statusCode' => 400];
}

// Check if User is Logged In
function isLoggedIn() {
    if (!isset($_SESSION)) {
        session_start();
    }
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
    $phone = isset($data['phone']) ? trim($data['phone']) : null;
    $address = isset($data['address']) ? trim($data['address']) : null;
    $position = trim($data['position_applied']);
    $resume = isset($data['resume']) ? trim($data['resume']) : null;
    $user_id = $_SESSION['user_id'];

    try {
        $stmt = $conn->prepare("INSERT INTO applicants (user_id, name, email, phone, address, position_applied, resume) 
                                VALUES (:user_id, :name, :email, :phone, :address, :position_applied, :resume)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':position_applied' => $position,
            ':resume' => $resume
        ]);

        logActivity($conn, $user_id, 'INSERT', "Added applicant: $name");
        return ['message' => 'Applicant added successfully.', 'statusCode' => 200];
    } catch (PDOException $e) {
        error_log('Error adding applicant: ' . $e->getMessage());
        return ['message' => 'Failed to add applicant.', 'statusCode' => 400];
    }
}

// Read Applicants
function getApplicants($conn, $search = '') {
    if (!isLoggedIn()) {
        return [];
    }

    $user_id = $_SESSION['user_id'];
    $search = trim($search);

    try {
        if ($search) {
            $searchParam = "%" . $search . "%";
            $stmt = $conn->prepare("SELECT * FROM applicants 
                                    WHERE user_id = :user_id 
                                    AND (name LIKE :search 
                                    OR email LIKE :search 
                                    OR phone LIKE :search 
                                    OR address LIKE :search 
                                    OR position_applied LIKE :search)");
            $stmt->execute([':user_id' => $user_id, ':search' => $searchParam]);

            logActivity($conn, $user_id, 'SEARCH', "Searched applicants with keyword: $search");
        } else {
            $stmt = $conn->prepare("SELECT * FROM applicants WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching applicants: ' . $e->getMessage());
        return [];
    }
}
function getCurrentUser($conn) {
    $userId = $_SESSION['user_id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching current user: ' . $e->getMessage());
        return false;
    }
}


// Get Applicant by ID
function getApplicantById($conn, $id) {
    try {
        $stmt = $conn->prepare('SELECT * FROM applicants WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching applicant by ID: ' . $e->getMessage());
        return false;
    }
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
                                WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':position_applied' => $position,
            ':resume' => $resume,
            ':id' => $id,
            ':user_id' => $updated_by
        ]);

        logActivity($conn, $updated_by, 'UPDATE', "Updated applicant ID: $id");
        return ['message' => 'Applicant updated successfully.', 'statusCode' => 200];
    } catch (PDOException $e) {
        error_log('Error updating applicant: ' . $e->getMessage());
        return ['message' => 'Failed to update applicant.', 'statusCode' => 400];
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
        $stmt = $conn->prepare("DELETE FROM applicants WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $id, ':user_id' => $deleted_by]);

        logActivity($conn, $deleted_by, 'DELETE', "Deleted applicant ID: $id");
        return ['message' => 'Applicant deleted successfully.', 'statusCode' => 200];
    } catch (PDOException $e) {
        error_log('Error deleting applicant: ' . $e->getMessage());
        return ['message' => 'Failed to delete applicant.', 'statusCode' => 400];
    }
}

// Count Applicants
function countApplicants($conn) {
    if (!isLoggedIn()) {
        return 0;
    }

    $user_id = $_SESSION['user_id'];
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM applicants WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    } catch (PDOException $e) {
        error_log('Error counting applicants: ' . $e->getMessage());
        return 0;
    }
}
?>
