<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/constants.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $goal_amount = (float) $_POST['goal_amount'];
    $contribution_period = sanitizeInput($_POST['contribution_period']);
    $contribution_amount = (float) $_POST['contribution_amount'];
    
    try {
        $pdo->beginTransaction();
        
        // Create chama
        $stmt = $pdo->prepare("INSERT INTO chamas 
                             (name, description, goal_amount, created_by, contribution_period, contribution_amount) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name, 
            $description, 
            $goal_amount, 
            $_SESSION['user_id'], 
            $contribution_period, 
            $contribution_amount
        ]);
        
        $chama_id = $pdo->lastInsertId();
        
        // Add creator as admin
        $stmt = $pdo->prepare("INSERT INTO chama_members 
                             (chama_id, user_id, role) 
                             VALUES (?, ?, 'admin')");
        $stmt->execute([$chama_id, $_SESSION['user_id']]);
        
        // Add activity
        addActivity($chama_id, $_SESSION['user_id'], 'chama_created', 
                  "Chama {$name} was created", ['goal_amount' => $goal_amount]);
        
        $pdo->commit();
        
        $_SESSION['message'] = 'Chama created successfully!';
        redirect(SITE_URL . '/view.php?id=' . $chama_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to create chama: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Chama</title>
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
            padding: 0;
        }

        .container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        /* Sidebar Styles (same as dashboard) */
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

        /* Form Styles */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .form-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .form-header h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--secondary-color);
        }

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

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .btn:hover {
            background-color: #3a5bc7;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

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

            .form-container {
                padding: 20px;
            }
        }

        /* Form Grid Layout */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        /* Input with prefix */
        .input-group {
            position: relative;
        }

        .input-group .prefix {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }

        .input-group .form-control {
            padding-left: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar (same as dashboard) -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>ChamaPro</h2>
                <p>Manage your groups</p>
            </div>
            <nav class="sidebar-menu">
                <a href="<?= SITE_URL ?>/../../dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="<?= SITE_URL ?>/chamas/create.php" class="active">
                    <i class="fas fa-plus-circle"></i> Create Chama
                </a>
                <a href="<?= SITE_URL ?>/chamas/">
                    <i class="fas fa-users"></i> My Chamas
                </a>
                <a href="<?= SITE_URL ?>/contributions/">
                    <i class="fas fa-hand-holding-usd"></i> Contributions
                </a>
                <a href="<?= SITE_URL ?>/../reports/summary.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="<?= SITE_URL ?>/settings/">
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
                <h1>Create New Chama</h1>
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'User') ?>&background=4a6fdc&color=fff" alt="User">
                </div>
            </div>

            <div class="form-container">
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <div class="form-header">
                    <h2>Start a New Chama Group</h2>
                    <p>Fill in the details below to create your chama and start managing contributions</p>
                </div>

                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Chama Name</label>
                            <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Family Investment Group" required>
                        </div>

                        <div class="form-group">
                            <label for="contribution_period">Contribution Period</label>
                            <select id="contribution_period" name="contribution_period" class="form-control" required>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly" selected>Monthly</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" placeholder="Describe the purpose of this chama..." required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="goal_amount">Goal Amount</label>
                            <div class="input-group">
                                <span class="prefix">Ksh</span>
                                <input type="number" id="goal_amount" name="goal_amount" class="form-control" min="1" step="0.01" placeholder="100000" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contribution_amount">Contribution Amount</label>
                            <div class="input-group">
                                <span class="prefix">Ksh</span>
                                <input type="number" id="contribution_amount" name="contribution_amount" class="form-control" min="1" step="0.01" placeholder="2000" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 30px;">
                        <button type="submit" class="btn btn-block">
                            <i class="fas fa-users"></i> Create Chama
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Simple client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const goalAmount = parseFloat(document.getElementById('goal_amount').value);
            const contributionAmount = parseFloat(document.getElementById('contribution_amount').value);
            
            if (contributionAmount >= goalAmount) {
                alert('Contribution amount should be less than the goal amount');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>