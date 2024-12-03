<?php
// dashboard.php
include 'models.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Handle AJAX Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (isset($_POST['addApplicant'])) {
        $response = insertApplicant($conn, $_POST);
        echo json_encode([
            'statusCode' => $response['statusCode'],
            'message' => $response['statusCode'] === 200 ? 'Applicant added successfully' : $response['message'],
            'querySet' => null
        ]);
        exit;
    }

    if (isset($_POST['updateApplicant'])) {
        $response = updateApplicant($conn, $_POST);
        echo json_encode([
            'statusCode' => $response['statusCode'],
            'message' => $response['statusCode'] === 200 ? 'Applicant updated successfully' : $response['message'],
            'querySet' => null
        ]);
        exit;
    }

    if (isset($_POST['deleteApplicant'])) {
        $response = deleteApplicant($conn, $_POST['id']);
        echo json_encode([
            'statusCode' => $response['statusCode'],
            'message' => $response['statusCode'] === 200 ? 'Applicant deleted successfully' : $response['message'],
            'querySet' => null
        ]);
        exit;
    }

    if (isset($_POST['getApplicant'])) {
        $applicant = getApplicantById($conn, $_POST['id']);
        if ($applicant) {
            echo json_encode([
                'statusCode' => 200,
                'applicant' => $applicant
            ]);
        } else {
            echo json_encode([
                'statusCode' => 404,
                'message' => 'Applicant not found'
            ]);
        }
        exit;
    }

    if (isset($_POST['search'])) {
        $applicants = getApplicants($conn, $_POST['search']);
        echo json_encode([
            'statusCode' => 200,
            'message' => 'Search successful',
            'querySet' => $applicants
        ]);
        exit;
    }
}

// Fetch initial data
$totalApplicants = countApplicants($conn);
$applicants = getApplicants($conn);
$currentUser = getCurrentUser($conn); // Assuming a function to fetch current user details
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Job Management System</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Custom Styles -->
    <style>

        /* Title Styling */
        h1 {
            color: #9f7aea; /* Lighter purple color */
            font-weight: 800; /* Extra bold for emphasis */
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5); /* Subtle shadow for contrast */
        }

        /* Global Styles */
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
            animation: GradientAnimation 15s ease infinite;
            z-index: -1;
        }

        @keyframes GradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        header, footer {
            background-color: rgba(31, 31, 31, 0.8); /* Semi-transparent dark */
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
            transition: opacity 0.5s;
            font-weight: 600;
        }

        .notification.show {
            display: block;
            opacity: 1;
        }

        .notification.success {
            background-color: #6d28d9; /* Purple-600 */
            color: #f9fafb;
        }

        .notification.error {
            background-color: #ef4444; /* Red-500 */
            color: #f9fafb;
        }

        /* Container fade-in animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s forwards;
        }

        /* Responsive table */
        @media (max-width: 768px) {
            table thead {
                display: none;
            }
            table, table tbody, table tr, table td {
                display: block;
                width: 100%;
            }
            table tr {
                margin-bottom: 1rem;
                background-color: rgba(31, 31, 31, 0.9);
                border-radius: 8px;
                padding: 10px;
                transition: transform 0.3s;
            }
            table tr:hover {
                transform: scale(1.02);
            }
            table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
                padding: 10px 5px;
            }
            table td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 45%;
                padding-left: 10px;
                font-weight: bold;
                text-align: left;
                color: #a1a1aa; /* Gray-400 */
            }
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: rgba(31, 31, 31, 0.9);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color: #2c2c2c; /* Darker header */
            color: #e0e0e0;
            font-weight: 700;
            border-bottom: 2px solid #3f3f46; /* Gray-700 */
        }

        tr:nth-child(even) {
            background-color: rgba(31, 31, 31, 0.8);
        }

        tr:hover {
            background-color: #3f3f46; /* Darker on hover */
            transform: scale(1.01);
            transition: transform 0.2s;
        }

        /* Buttons */
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
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(119, 102, 255, 0.6); /* Purple-400 */
        }

        /* Modal Animations */
        .modal {
            display: none;
            transition: opacity 0.25s ease;
        }

        .modal.show {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Total Applicants Styling */
        .total-applicants {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #1e1e1e; /* Dark Gray */
            color: #ffffff; /* White text */
            border: 3px solid #333333; /* Darker Gray Border */
            border-radius: 16px; /* Rounded box */
            width: 200px; /* Width for text alignment */
            height: 100px; /* Height for proper layout */
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.5); /* Shadow for depth */
            text-align: center; /* Center-align text */
            margin-top: 30px; /* Added margin-top for spacing */
            transition: transform 0.3s;
        }

        .total-applicants:hover {
            transform: scale(1.05);
            background-color: #252525; /* Slightly lighter gray on hover */
        }

        /* Header and Footer Centering */
        header .content, footer .content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Remove Scroll from Table */
        .table-container {
            overflow-x: hidden;
        }

        /* Logout Button Positioning */
        #logoutBtn {
            position: fixed;
            bottom: 80px; /* Adjusted to account for footer height */
            right: 20px;
            z-index: 1000;
            background-color: #e53e3e; /* Dark Red */
            color: white;
            padding: 10px 20px;
            border-radius: 9999px; /* Fully rounded button */
            transition: transform 0.3s;
        }

        #logoutBtn:hover {
            background-color: #c53030; /* Slightly darker red on hover */
            transform: translateY(-2px);
        }

        /* Improved Modal Input Styles */
        .modal form input,
        .modal form textarea {
            font-family: 'Poppins', sans-serif;
        }

        /* Add smooth transition to the background overlay */
        body::before {
            transition: background 15s ease;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #6d28d9, #b83280);
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
        }
        
        header {
            background-color: rgba(31, 31, 31, 0.8);
            padding: 20px;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            text-align: center;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
        }

        header h1 {
            color: #9f7aea;
            font-weight: 800;
            margin: 0;
            font-size: 2rem;
        }

        header p {
            color: #bfbfbf;
            font-size: 0.9rem;
        }

        #welcomeMessage {
            font-size: 2rem;
            font-weight: 600;
            color: #ffffff;
            text-align: center;
            margin-top: 120px;
            margin-bottom: -120px;
            white-space: nowrap;
            overflow: hidden;
            animation: typing 5s steps(40) 1 normal both, blink 0.7s infinite step-end;
        }

        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }

        @keyframes blink {
            from, to { border-color: transparent; }
            50% { border-color: rgba(255, 255, 255, 0.75); }
        }
    </style>


    
    <p id="welcomeMessage" >Welcome, <?= isset($currentUser['username']) ? htmlspecialchars($currentUser['username']) : 'User' ?>!</p>

</html>

    </style>

<body>
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 flex justify-center items-center px-6 py-4 shadow-lg z-50">
        <div class="content text-center">
            <h1 class="text-4xl font-extrabold">Job Management System</h1>
            <p class="text-sm text-gray-400">Manage job applicants and positions effectively.</p>
        </div>
    </header>

    <!-- Logout Button -->
    <a href="logout.php" id="logoutBtn" class="btn bg-red-600 hover:bg-red-700 text-white">
        <i class="fas fa-sign-out-alt mr-2"></i> Logout
    </a>

    <!-- Main Content -->
    <main class="pt-24 pb-16 px-6 md:px-12 lg:px-24 flex-grow">
        <!-- Notification Area -->
        <div id="notification" class="notification"></div>

        <!-- Dashboard Section -->
        <section class="flex flex-col md:flex-row justify-center md:justify-between items-center mb-8 animate-fadeIn">
            <div class="w-full md:w-1/4 flex justify-center md:justify-start mb-4 md:mb-0">
                <div class="total-applicants">
                    <p class="text-lg mb-2">Total Applicants</p>
                    <p class="text-4xl" id="totalApplicants"><?= htmlspecialchars($totalApplicants) ?></p>
                </div>
            </div>
            
            <button class="btn bg-purple-600 hover:bg-purple-700 text-white" id="showAddModal">
                <i class="fas fa-user-plus mr-2"></i> Add Applicant
            </button>
        </section>

        <!-- Search and Table Section -->
        <section class="animate-fadeIn">
            <!-- Search Bar -->
            <div class="mb-6">
                <div class="relative">
                    <input type="text" id="search" placeholder="Search for applicants..." class="w-full px-4 py-3 bg-gray-800 text-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-300">
                    <i class="fas fa-search absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <!-- Applicants Table -->
            <div id="results-container" class="table-container bg-gray-800 rounded-lg shadow-lg">
                <table class="w-full table-auto">
                    <thead>
                        <tr>
                            <th class="py-3 px-4">ID</th>
                            <th class="py-3 px-4">Name</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4">Phone</th>
                            <th class="py-3 px-4">Address</th>
                            <th class="py-3 px-4">Position Applied</th>
                            <th class="py-3 px-4">Resume</th>
                            <th class="py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($applicants) > 0): ?>
                            <?php foreach ($applicants as $applicant): ?>
                                <tr>
                                    <td class="py-2 px-4" data-label="ID"><?= htmlspecialchars($applicant['id']) ?></td>
                                    <td class="py-2 px-4" data-label="Name"><?= htmlspecialchars($applicant['name']) ?></td>
                                    <td class="py-2 px-4" data-label="Email"><?= htmlspecialchars($applicant['email']) ?></td>
                                    <td class="py-2 px-4" data-label="Phone"><?= htmlspecialchars($applicant['phone']) ?></td>
                                    <td class="py-2 px-4" data-label="Address"><?= htmlspecialchars($applicant['address']) ?></td>
                                    <td class="py-2 px-4" data-label="Position Applied"><?= htmlspecialchars($applicant['position_applied']) ?></td>
                                    <td class="py-2 px-4" data-label="Resume"><?= htmlspecialchars($applicant['resume']) ?></td>
                                    <td class="py-2 px-4 space-x-2" data-label="Actions">
                                        <button class="edit-btn btn bg-yellow-500 hover:bg-yellow-600 text-white" data-id="<?= htmlspecialchars($applicant['id']) ?>">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>
                                        <button class="delete-btn btn bg-red-500 hover:bg-red-600 text-white" data-id="<?= htmlspecialchars($applicant['id']) ?>">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No applicants found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="fixed bottom-0 left-0 right-0 flex justify-center items-center py-4 bg-gray-800 text-gray-400">
        <div class="content">
            <p>&copy; <?= date('Y'); ?> Job Management System. All rights reserved.</p>
        </div>
    </footer>

    <!-- Add Applicant Modal -->
    <div id="addApplicantModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-gray-700 rounded-lg shadow-lg w-full max-w-md p-6 relative modal-content">
            <span class="close-btn absolute top-4 right-4 text-gray-300 hover:text-white cursor-pointer text-2xl">&times;</span>
            <h2 class="text-2xl mb-4 font-bold text-purple-400">Add Applicant</h2>
            <form id="addApplicantForm" class="space-y-4">
                <input type="text" name="name" placeholder="Enter Name" required class="w-full px-4 py-2 bg-gray-600 text-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-300">
                <input type="email" name="email" placeholder="Enter Email" required class="w-full px-4 py-2 bg-gray-600 text-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-300">
                <input type="text" name="phone" placeholder="Enter Phone" class="w-full px-4 py-2 bg-gray-600 text-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-300">
                <textarea name="address" placeholder="Enter Address" class="w-full px-4 py-2 bg-gray-600 text-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-300"></textarea>
                <input type="text" name="position_applied" placeholder="Enter Position" required class="w-full px-4 py-2 bg-gray-600 text-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-300">
                <textarea name="resume" placeholder="Enter Resume" class="w-full px-4 py-2 bg-gray-600 text-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors duration-300"></textarea>
                <button type="submit" class="btn bg-purple-600 hover:bg-purple-700 text-white w-full">
                    <i class="fas fa-paper-plane mr-2"></i> Submit
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Applicant Modal -->
    <div id="editApplicantModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-gray-700 rounded-lg shadow-lg w-full max-w-md p-6 relative modal-content">
            <span class="close-btn absolute top-4 right-4 text-gray-300 hover:text-white cursor-pointer text-2xl">&times;</span>
            <h2 class="text-2xl mb-4 font-bold text-yellow-400">Edit Applicant</h2>
            <form id="editApplicantForm" class="space-y-4">
                <input type="hidden" name="id" id="edit-id">
                <input type="text" name="name" id="edit-name" placeholder="Enter Name" required class="w-full px-4 py-2 bg-gray-600 text-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors duration-300">
                <input type="email" name="email" id="edit-email" placeholder="Enter Email" required class="w-full px-4 py-2 bg-gray-600 text-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors duration-300">
                <input type="text" name="phone" id="edit-phone" placeholder="Enter Phone" class="w-full px-4 py-2 bg-gray-600 text-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors duration-300">
                <textarea name="address" id="edit-address" placeholder="Enter Address" class="w-full px-4 py-2 bg-gray-600 text-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors duration-300"></textarea>
                <input type="text" name="position_applied" id="edit-position" placeholder="Enter Position" required class="w-full px-4 py-2 bg-gray-600 text-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors duration-300">
                <textarea name="resume" id="edit-resume" placeholder="Enter Resume" class="w-full px-4 py-2 bg-gray-600 text-gray-100 rounded-full focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors duration-300"></textarea>
                <button type="submit" class="btn bg-yellow-500 hover:bg-yellow-600 text-white w-full">
                    <i class="fas fa-sync mr-2"></i> Update
                </button>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        $(document).ready(function () {
            // Initialize modals
            const addModal = $('#addApplicantModal');
            const editModal = $('#editApplicantModal');
            const notification = $('#notification');

            // Ensure modals are hidden on page load
            addModal.hide();
            editModal.hide();

            // Show Add Modal
            $('#showAddModal').on('click', () => addModal.fadeIn());

            // Close Modals
            $('.close-btn').on('click', () => {
                addModal.fadeOut();
                editModal.fadeOut();
            });

            // Close modal when clicking outside the modal content
            $(window).on('click', function(event) {
                if ($(event.target).is(addModal)) {
                    addModal.fadeOut();
                }
                if ($(event.target).is(editModal)) {
                    editModal.fadeOut();
                }
            });

            // Notification function
            function showNotification(message, type = 'success') {
                notification.removeClass('success error');
                if (type === 'success') {
                    notification.addClass('success');
                } else {
                    notification.addClass('error');
                }
                notification.text(message).addClass('show');
                setTimeout(() => {
                    notification.removeClass('show');
                }, 3000);
            }

            // Load applicants and total count
            function loadApplicants(query = '') {
                $.ajax({
                    url: 'dashboard.php',
                    method: 'POST',
                    data: { search: query },
                    dataType: 'json',
                    success: function (data) {
                        if (data.statusCode === 200) {
                            let tableRows = '';
                            if (data.querySet.length > 0) {
                                data.querySet.forEach(applicant => {
                                    tableRows += `
                                        <tr>
                                            <td class="py-2 px-4" data-label="ID">${applicant.id}</td>
                                            <td class="py-2 px-4" data-label="Name">${applicant.name}</td>
                                            <td class="py-2 px-4" data-label="Email">${applicant.email}</td>
                                            <td class="py-2 px-4" data-label="Phone">${applicant.phone}</td>
                                            <td class="py-2 px-4" data-label="Address">${applicant.address}</td>
                                            <td class="py-2 px-4" data-label="Position Applied">${applicant.position_applied}</td>
                                            <td class="py-2 px-4" data-label="Resume">${applicant.resume}</td>
                                            <td class="py-2 px-4 space-x-2" data-label="Actions">
                                                <button class="edit-btn btn bg-yellow-500 hover:bg-yellow-600 text-white" data-id="${applicant.id}">
                                                    <i class="fas fa-edit mr-1"></i>Edit
                                                </button>
                                                <button class="delete-btn btn bg-red-500 hover:bg-red-600 text-white" data-id="${applicant.id}">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                                });
                            } else {
                                tableRows = `
                                    <tr>
                                        <td colspan="8" class="text-center py-4">No applicants found.</td>
                                    </tr>
                                `;
                            }
                            $('#results-container').html(`
                                <table class="w-full table-auto">
                                    <thead>
                                        <tr>
                                            <th class="py-3 px-4">ID</th>
                                            <th class="py-3 px-4">Name</th>
                                            <th class="py-3 px-4">Email</th>
                                            <th class="py-3 px-4">Phone</th>
                                            <th class="py-3 px-4">Address</th>
                                            <th class="py-3 px-4">Position Applied</th>
                                            <th class="py-3 px-4">Resume</th>
                                            <th class="py-3 px-4">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${tableRows}
                                    </tbody>
                                </table>
                            `);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching applicant data:', xhr.responseText, status, error);
                        showNotification('Failed to load applicants.', 'error');
                    }
                });
            }

            function loadTotalApplicants() {
                $.ajax({
                    url: 'countApplicants.php',
                    method: 'GET',
                    success: function (data) {
                        $('#totalApplicants').text(data);
                    },
                    error: function () {
                        $('#totalApplicants').text('Error');
                        showNotification('Failed to load total applicants.', 'error');
                    }
                });
            }

            // Submit new applicant
            $('#addApplicantForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: 'dashboard.php',
                    method: 'POST',
                    data: { 
                        addApplicant: true, 
                        name: $('input[name="name"]').val(),
                        email: $('input[name="email"]').val(),
                        phone: $('input[name="phone"]').val(),
                        address: $('textarea[name="address"]').val(),
                        position_applied: $('input[name="position_applied"]').val(),
                        resume: $('textarea[name="resume"]').val()
                    },
                    dataType: 'json',
                    success: function (data) {
                        if (data.statusCode === 200) {
                            addModal.fadeOut();
                            $('#addApplicantForm')[0].reset();
                            loadApplicants();
                            loadTotalApplicants();
                            showNotification(data.message, 'success');
                        } else {
                            showNotification(data.message, 'error');
                        }
                    },
                    error: function () {
                        showNotification('An error occurred while adding the applicant.', 'error');
                    }
                });
            });

            // Submit edit applicant
            $('#editApplicantForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: 'dashboard.php',
                    method: 'POST',
                    data: { 
                        updateApplicant: true,
                        id: $('#edit-id').val(),
                        name: $('#edit-name').val(),
                        email: $('#edit-email').val(),
                        phone: $('#edit-phone').val(),
                        address: $('#edit-address').val(),
                        position_applied: $('#edit-position').val(),
                        resume: $('#edit-resume').val()
                    },
                    dataType: 'json',
                    success: function (data) {
                        if (data.statusCode === 200) {
                            editModal.fadeOut();
                            $('#editApplicantForm')[0].reset();
                            loadApplicants();
                            loadTotalApplicants();
                            showNotification(data.message, 'success');
                        } else {
                            showNotification(data.message, 'error');
                        }
                    },
                    error: function () {
                        showNotification('An error occurred while updating the applicant.', 'error');
                    }
                });
            });

            // Delete Applicant
            $(document).on('click', '.delete-btn', function () {
                if (!confirm('Are you sure you want to delete this applicant?')) return;
                const id = $(this).data('id');
                $.ajax({
                    url: 'dashboard.php',
                    method: 'POST',
                    data: { id: id, deleteApplicant: true },
                    dataType: 'json',
                    success: function (data) {
                        if (data.statusCode === 200) {
                            loadApplicants();
                            loadTotalApplicants();
                            showNotification(data.message, 'success');
                        } else {
                            showNotification(data.message, 'error');
                        }
                    },
                    error: function () {
                        showNotification('An error occurred while deleting the applicant.', 'error');
                    }
                });
            });

            // Open Edit Modal and populate data
            $(document).on('click', '.edit-btn', function () {
                const id = $(this).data('id');
                // Fetch applicant data
                $.ajax({
                    url: 'dashboard.php',
                    method: 'POST',
                    data: { getApplicant: true, id: id },
                    dataType: 'json',
                    success: function (data) {
                        if (data.statusCode === 200 && data.applicant) {
                            $('#edit-id').val(data.applicant.id);
                            $('#edit-name').val(data.applicant.name);
                            $('#edit-email').val(data.applicant.email);
                            $('#edit-phone').val(data.applicant.phone);
                            $('#edit-address').val(data.applicant.address);
                            $('#edit-position').val(data.applicant.position_applied);
                            $('#edit-resume').val(data.applicant.resume);
                            editModal.fadeIn();
                        } else {
                            showNotification('Applicant data not found.', 'error');
                        }
                    },
                    error: function () {
                        showNotification('Failed to fetch applicant data.', 'error');
                    }
                });
            });

            // Live search with debounce
            let debounceTimer;
            $('#search').on('keyup', function () {
                const query = $(this).val();
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    loadApplicants(query);
                }, 300); // Delay of 300ms
            });

            // Initial load
            loadApplicants();
            loadTotalApplicants();
        });
    </script>
</body>
</html>