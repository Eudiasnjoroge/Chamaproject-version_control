<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/constants.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/auth/login.php');
}

if (!isset($_GET['chama_id'])) {
    redirect(SITE_URL . '/pages/dashboard.php');
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
    $_SESSION['message'] = 'You are not a member of this chama or it does not exist.';
    redirect(SITE_URL . '/pages/dashboard.php');
}

// Get all contributions with verification options
$stmt = $pdo->prepare("SELECT con.*, u.username, u.full_name 
                      FROM contributions con
                      JOIN users u ON con.user_id = u.user_id
                      WHERE con.chama_id = ?
                      ORDER BY con.contributed_at DESC");
$stmt->execute([$chama_id]);
$contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contribution History - ChamaPro</title>
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

        /* Message Styles */
        .message {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            display: flex;
            align-items: center;
            gap: 10px;
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
            font-size: 1.5rem;
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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: var(--border-radius);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        .btn-success {
            background-color: var(--success-color);
            color: var(--white);
            border: none;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: var(--white);
            border: none;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: rgba(74, 111, 220, 0.1);
        }

        /* Proof Link */
        .proof-link {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .proof-link:hover {
            text-decoration: underline;
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

            .contributions-table {
                display: block;
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .btn-sm {
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
                <a href="<?= SITE_URL ?>/../../chamas/">
                    <i class="fas fa-users"></i> My Chamas
                </a>
                <a href="<?= SITE_URL ?>/make.php">
                    <i class="fas fa-hand-holding-usd"></i> Contributions
                </a>
       <a href="<?= SITE_URL ?>/../../reports/summary.php">
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
                <h1>Contribution History</h1>
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'User') ?>&background=4a6fdc&color=fff" alt="User">
                </div>
            </div>

            <a href="<?= SITE_URL ?>/chamas/view.php?id=<?= $chama_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Chama
            </a>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="message">
                    <i class="fas fa-check-circle"></i>
                    <span><?= $_SESSION['message'] ?></span>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <div class="contributions-card">
                <div class="card-header">
                    <h2>Contributions for <?= htmlspecialchars($chama['name']) ?></h2>
                </div>

                <?php if (count($contributions) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="contributions-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Proof</th>
                                    <?php if ($chama['role'] === 'admin'): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contributions as $contribution): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($contribution['full_name'] ?: $contribution['username']) ?></td>
                                        <td>Ksh <?= number_format($contribution['amount'], 2) ?></td>
                                        <td><?= date('M j, Y g:i a', strtotime($contribution['contributed_at'])) ?></td>
                                        <td>
                                            <span class="status-badge <?= $contribution['status'] ?>">
                                                <?= ucfirst($contribution['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($contribution['proof_url']): ?>
                                                <a href="<?= $contribution['proof_url'] ?>" class="proof-link" target="_blank">
                                                    <i class="fas fa-file-alt"></i> View
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--secondary-color);">No proof</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($chama['role'] === 'admin'): ?>
                                            <td>
                                                <?php if ($contribution['status'] === 'pending'): ?>
                                                    <div class="action-buttons">
                                                        <a href="<?= SITE_URL ?>/verify.php?id=<?= $contribution['contribution_id'] ?>&action=verify" class="btn btn-success btn-sm">
                                                            <i class="fas fa-check"></i> Verify
                                                        </a>
                                                        <a href="<?= SITE_URL ?>/verify.php?id=<?= $contribution['contribution_id'] ?>&action=reject" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-times"></i> Reject
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: var(--secondary-color);">Verified</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-money-bill-wave"></i>
                        <p>No contributions found for this chama</p>
                        <a href="<?= SITE_URL ?>/make.php?chama_id=<?= $chama_id ?>" class="btn btn-outline">
                            <i class="fas fa-plus"></i> Make First Contribution
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>