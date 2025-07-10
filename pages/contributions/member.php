<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/constants.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/auth/login.php');
}

if (!isset($_GET['chama_id'])) {
    redirect(SITE_URL . '/dashboard.php');
}

$chama_id = (int)$_GET['chama_id'];
$user_id = $_SESSION['user_id'];

// Verify user is member of this chama
$stmt = $pdo->prepare("SELECT c.*, cm.role FROM chamas c
                      JOIN chama_members cm ON c.chama_id = cm.chama_id
                      WHERE c.chama_id = ? AND cm.user_id = ? AND cm.status = 'active'");
$stmt->execute([$chama_id, $user_id]);
$chama = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chama) {
    $_SESSION['message'] = 'You are not a member of this chama';
    redirect(SITE_URL . '/dashboard.php');
}

// Get member's contributions
$stmt = $pdo->prepare("SELECT * FROM contributions 
                      WHERE chama_id = ? AND user_id = ?
                      ORDER BY contributed_at DESC");
$stmt->execute([$chama_id, $user_id]);
$contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate expected and paid amounts
$period_days = [
    'daily' => 1,
    'weekly' => 7,
    'monthly' => 30,
    'yearly' => 365
];

$period = $chama['contribution_period'];
$days_in_period = $period_days[$period] ?? 30;
$chama_age_days = max(1, (time() - strtotime($chama['created_at'])) / (60 * 60 * 24));
$expected_periods = floor($chama_age_days / $days_in_period);
$expected_amount = $expected_periods * $chama['contribution_amount'];

$total_paid = 0;
foreach ($contributions as $contribution) {
    if ($contribution['status'] === 'verified') {
        $total_paid += $contribution['amount'];
    }
}

$balance = max(0, $expected_amount - $total_paid);
$status_class = ($balance <= 0) ? 'good' : (($balance <= $chama['contribution_amount']) ? 'warning' : 'danger');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Contributions - ChamaPro</title>
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

        /* Summary Card */
        .summary-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 30px;
        }

        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-light);
        }

        .summary-header h2 {
            color: var(--primary-color);
            font-size: 1.3rem;
        }

        .status-indicator {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-indicator.good {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .status-indicator.warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }

        .status-indicator.danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .summary-item {
            padding: 15px;
            border-radius: var(--border-radius);
            background-color: var(--light-color);
        }

        .summary-label {
            font-size: 0.85rem;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .summary-value.currency {
            color: var(--primary-color);
        }

        /* Contributions Table */
        .contributions-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 30px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-light);
        }

        .card-header h2 {
            color: var(--primary-color);
            font-size: 1.3rem;
        }

        .contributions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .contributions-table th {
            text-align: left;
            padding: 12px 15px;
            background-color: var(--gray-light);
            color: var(--dark-color);
            font-weight: 600;
        }

        .contributions-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray-light);
            vertical-align: middle;
        }

        .contributions-table tr:last-child td {
            border-bottom: none;
        }

        .contributions-table tr:hover {
            background-color: rgba(74, 111, 220, 0.05);
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.verified {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .status-badge.pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }

        .status-badge.rejected {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        /* Action Links */
        .action-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            gap: 8px;
        }

        .btn:hover {
            background-color: #3a5bc7;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: rgba(74, 111, 220, 0.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--gray-light);
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

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .contributions-table {
                display: block;
                overflow-x: auto;
            }

            .action-links {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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
                <a href="<?= SITE_URL ?>/contributions/" class="active">
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
                <h1>My Contributions</h1>
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'User') ?>&background=4a6fdc&color=fff" alt="User">
                </div>
            </div>

            <a href="<?= SITE_URL ?>/chamas/view.php?id=<?= $chama_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Chama
            </a>

            <!-- Summary Card -->
            <div class="summary-card">
                <div class="summary-header">
                    <h2>Contribution Status</h2>
                    <span class="status-indicator <?= $status_class ?>">
                        <?= ($balance <= 0) ? 'Up to date' : (($balance <= $chama['contribution_amount']) ? 'Slightly behind' : 'Behind schedule') ?>
                    </span>
                </div>

                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Contribution Period</div>
                        <div class="summary-value"><?= ucfirst($chama['contribution_period']) ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">Expected Periods</div>
                        <div class="summary-value"><?= $expected_periods ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">Amount Per Period</div>
                        <div class="summary-value currency">Ksh <?= number_format($chama['contribution_amount'], 2) ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">Total Expected</div>
                        <div class="summary-value currency">Ksh <?= number_format($expected_amount, 2) ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">Total Paid</div>
                        <div class="summary-value currency">Ksh <?= number_format($total_paid, 2) ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-label">Remaining Balance</div>
                        <div class="summary-value <?= $status_class ?>">Ksh <?= number_format($balance, 2) ?></div>
                    </div>
                </div>
            </div>

            <!-- Contributions Card -->
            <div class="contributions-card">
                <div class="card-header">
                    <h2>Contribution History</h2>
                </div>

                <?php if (!empty($contributions)): ?>
                    <div style="overflow-x: auto;">
                        <table class="contributions-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Proof</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contributions as $contribution): ?>
                                <tr>
                                    <td><?= date('M j, Y', strtotime($contribution['contributed_at'])) ?></td>
                                    <td>Ksh <?= number_format($contribution['amount'], 2) ?></td>
                                    <td><?= ucfirst($contribution['payment_method']) ?></td>
                                    <td>
                                        <span class="status-badge <?= $contribution['status'] ?>">
                                            <?= ucfirst($contribution['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($contribution['proof_url']): ?>
                                            <a href="<?= $contribution['proof_url'] ?>" target="_blank" class="btn btn-outline btn-sm">
                                                <i class="fas fa-file-alt"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--secondary-color);">None</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-money-bill-wave"></i>
                        <p>No contributions yet</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="action-links">
                <a href="<?= SITE_URL ?>/contributions/make.php?chama_id=<?= $chama_id ?>" class="btn">
                    <i class="fas fa-plus"></i> Make New Contribution
                </a>
                <a href="<?= SITE_URL ?>/chamas/view.php?id=<?= $chama_id ?>" class="btn btn-outline">
                    <i class="fas fa-users"></i> Back to Chama
                </a>
            </div>
        </main>
    </div>
</body>
</html>