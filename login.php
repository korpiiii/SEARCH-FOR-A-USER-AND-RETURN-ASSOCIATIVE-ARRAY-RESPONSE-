<?php
// login.php
include_once 'models.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $response = loginUser($conn, $_POST);
    if ($response['statusCode'] === 200) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = $response['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Job Management System</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('BG.png') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            color: #e0e0e0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Dark Overlay with Gradient Animation */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(270deg, rgba(26, 42, 108, 0.7), rgba(178, 31, 31, 0.7), rgba(253, 187, 45, 0.7));
            background-size: 600% 600%;
            animation: GradientAnimation 20s ease infinite;
            z-index: -1;
        }

        @keyframes GradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            display: none;
            z-index: 1100;
            opacity: 0;
            transition: opacity 0.5s, transform 0.5s;
            font-weight: 600;
        }

        .notification.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .notification.hide {
            opacity: 0;
            transform: translateY(-20px);
        }

        .notification.success {
            background-color: #6d28d9; /* Purple-600 */
            color: #f9fafb;
        }

        .notification.error {
            background-color: #ef4444; /* Red-500 */
            color: #f9fafb;
        }

        /* Header and Footer Styling */
        header, footer {
            background-color: rgba(31, 31, 31, 0.8); /* Semi-transparent dark */
        }

        /* Header and Footer Content Centering */
        header .content, footer .content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Button Styling */
        .btn {
            border-radius: 9999px; /* Full rounded */
            padding: 10px 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s;
            font-weight: 600;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(119, 102, 255, 0.6); /* Purple-400 */
        }

        /* Form Entrance Animation */
        .form-container {
            animation: fadeInUp 0.8s ease-out forwards;
            opacity: 0;
            transform: translateY(50px);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Input Focus Animation */
        .form-input:focus {
            transform: scale(1.02);
            box-shadow: 0 0 0 2px rgba(128, 90, 213, 0.6); /* Purple-400 */
        }

        .form-input {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <header class="fixed top-0 left-0 right-0 flex justify-center items-center px-6 py-4 shadow-lg z-50">
        <div class="content text-center">
            <h1 class="text-4xl font-extrabold">Job Management System</h1>
            <p class="text-sm text-gray-400">Manage job applicants and positions effectively.</p>
        </div>
    </header>

     <!-- Notification Area -->
     <div id="notification" class="notification"></div>

   <!-- Main Content -->
   <main class="flex-grow flex items-center justify-center">
        <div class="bg-gray-800 bg-opacity-80 p-8 rounded-lg shadow-lg w-full max-w-md relative form-container">
            <h2 class="text-2xl mb-6 text-center text-purple-400">Login</h2>
            <?php if(isset($error)): ?>
                <div class="mb-4 text-red-500"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" id="loginForm" class="space-y-4">
                <input type="text" name="email" placeholder="Username or Email" required aria-label="Email or Username" class="form-input w-full px-4 py-2 bg-gray-700 text-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-300">
                <input type="password" name="password" placeholder="Password" required aria-label="Password" class="form-input w-full px-4 py-2 bg-gray-700 text-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-300">
                <button type="submit" name="login" class="btn bg-purple-600 hover:bg-purple-700 text-white w-full">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login
                </button>
            </form>
            <p class="mt-4 text-center">Don't have an account? <a href="register.php" class="text-blue-500">Register</a></p>
        </div>
    </main>
    <footer class="fixed bottom-0 left-0 right-0 flex justify-center items-center py-4 bg-gray-800 text-gray-400">
        <div class="content">
            <p>&copy; <?= date('Y'); ?> Job Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>