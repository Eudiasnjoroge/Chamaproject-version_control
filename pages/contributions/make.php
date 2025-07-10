<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/constants.php';

// Redirect if user is not logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/auth/login.php');
}

// Check if chama_id is provided
if (!isset($_GET['chama_id'])) {
    redirect(SITE_URL . '/pages/dashboard.php');
}

$chama_id = (int)$_GET['chama_id'];
$user_id = $_SESSION['user_id'];

// Verify user is a member of the chama
$stmt = $pdo->prepare("
    SELECT c.* FROM chamas c
    JOIN chama_members cm ON c.chama_id = cm.chama_id
    WHERE c.chama_id = ? AND cm.user_id = ? AND cm.status = 'active'
");
$stmt->execute([$chama_id, $user_id]);
$chama = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chama) {
    $_SESSION['message'] = 'You are not a member of this chama or it does not exist.';
    redirect(SITE_URL . '/pages/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $payment_method = sanitizeInput($_POST['payment_method']);

    // Handle file upload
    $proof_url = null;
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = UPLOAD_PATH . 'contributions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES['proof']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['proof']['tmp_name'], $target_file)) {
            $proof_url = 'assets/uploads/contributions/' . $file_name;
        }
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO contributions (chama_id, user_id, amount, payment_method, proof_url)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$chama_id, $user_id, $amount, $payment_method, $proof_url]);

        addActivity(
            $chama_id,
            $user_id,
            'contribution_made',
            "Made a contribution of Ksh " . number_format($amount, 2)
        );

        $_SESSION['message'] = 'Contribution submitted successfully! Waiting for verification.';
        redirect(SITE_URL . '/../../chamas/view.php?id=' . $chama_id);
    } catch (PDOException $e) {
        $error = "Failed to record contribution: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Contribution - ChamaPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6fdc;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --white: #ffffff;
            --gray-light: #e9ecef;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        /* Sidebar Styles (consistent with other pages) */
        .sidebar {
            background-color: var(--white);
            box-shadow: var(--box-shadow);
            padding: 20px 0;
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 100;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid var(--gray-light);
        }

        .sidebar-header h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--dark-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .sidebar-menu a:hover {
            background-color: var(--gray-light);
            color: var(--primary-color);
        }

        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .sidebar-menu a.active {
            background-color: rgba(74, 111, 220, 0.1);
            color: var(--primary-color);
            border-left: 3px solid var(--primary-color);
        }

        /* Main Content Styles */
        .main-content {
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-light);
        }

        .header h1 {
            color: var(--dark-color);
            font-size: 1.8rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .back-link i {
            margin-right: 8px;
        }

        /* Contribution Form */
        .contribution-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
        }

        .contribution-header {
            margin-bottom: 25px;
            text-align: center;
        }

        .contribution-header h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .contribution-header p {
            color: var(--secondary-color);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 111, 220, 0.2);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px 12px;
        }

        /* File Upload Styles */
        .file-upload {
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            border: 2px dashed var(--gray-light);
            border-radius: var(--border-radius);
            text-align: center;
            transition: var(--transition);
        }

        .file-upload-label:hover {
            border-color: var(--primary-color);
            background-color: rgba(74, 111, 220, 0.05);
        }

        .file-upload-label i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .file-upload-label span {
            display: block;
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }

        .file-name {
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }

        /* Input Group Styles */
        .input-group {
            position: relative;
        }

        .input-group .prefix {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            font-weight: 600;
        }

        .input-group .form-control {
            padding-left: 50px;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            gap: 8px;
            width: 100%;
        }

        .btn:hover {
            background-color: #3a5bc7;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Error Message */
        .error-message {
            color: var(--danger-color);
            background-color: rgba(220, 53, 69, 0.1);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message i {
            font-size: 1.2rem;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                height: auto;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }

            .contribution-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar (consistent with other pages) -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>ChamaPro</h2>
                <p>Manage your groups</p>
            </div>
            <nav class="sidebar-menu">
                <a href="<?= SITE_URL ?>/../../dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="<?= SITE_URL ?>/../../chamas/create.php">
                    <i class="fas fa-plus-circle"></i> Create Chama
                </a>
                <a href="<?= SITE_URL ?>/../../chamas/my_chamas.php" class="active">
                    <i class="fas fa-users"></i> My Chamas
                </a>
                <a href="<?= SITE_URL ?>/contributions/">
                    <i class="fas fa-hand-holding-usd"></i> Contributions
                </a>
                <a href="<?= SITE_URL ?>/reports/">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="<?= SITE_URL ?>/../../settings/settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="<?= SITE_URL ?>/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Make Contribution</h1>
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'User') ?>&background=4a6fdc&color=fff" alt="User">
                </div>
            </div>

            <a href="<?= SITE_URL ?>/chamas/view.php?id=<?= $chama_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Chama
            </a>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <div class="contribution-card">
                <div class="contribution-header">
                    <h2>Submit Your Contribution</h2>
                    <p>Fill in the details below to contribute to <?= htmlspecialchars($chama['name']) ?></p>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <!-- Amount Field -->
                    <div class="form-group">
                        <label for="amount">Contribution Amount</label>
                        <div class="input-group">
                            <span class="prefix">Ksh</span>
                            <input type="number" id="amount" name="amount" class="form-control" 
                                   min="1" step="0.01" value="<?= htmlspecialchars($chama['contribution_amount']) ?>" required>
                        </div>
                    </div>

                    <!-- Payment Method Field -->
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select id="payment_method" name="payment_method" class="form-control" required>
                            <option value="mpesa">M-Pesa</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Proof of Payment Field -->
                    <div class="form-group">
                        <label>Proof of Payment</label>
                        <div class="file-upload">
                            <input type="file" id="proof" name="proof" class="file-upload-input" accept="image/*,.pdf">
                            <label for="proof" class="file-upload-label">
                                <div>
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Click to upload or drag and drop</p>
                                    <span>Supports JPG, PNG, or PDF (Max 5MB)</span>
                                </div>
                            </label>
                            <div id="file-name" class="file-name"></div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn">
                        <i class="fas fa-paper-plane"></i> Submit Contribution
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Update file name display when file is selected
        document.getElementById('proof').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file selected';
            document.getElementById('file-name').textContent = fileName;
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value);
            if (amount <= 0) {
                e.preventDefault();
                alert('Please enter a valid contribution amount');
            }
        });
    </script>
</body>
</html>