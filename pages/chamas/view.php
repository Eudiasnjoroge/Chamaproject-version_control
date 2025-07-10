<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/constants.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/auth/login.php');
}

if (!isset($_GET['id'])) {
    redirect(SITE_URL . '/pages/dashboard.php');
}

$chama_id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'];

// Verify user is member of this chama
$stmt = $pdo->prepare("SELECT c.*, cm.role 
                      FROM chamas c
                      JOIN chama_members cm ON c.chama_id = cm.chama_id
                      WHERE c.chama_id = ? AND cm.user_id = ? AND cm.status = 'active'");
$stmt->execute([$chama_id, $user_id]);
$chama = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chama) {
    $_SESSION['message'] = 'You are not a member of this chama or it does not exist.';
    redirect(SITE_URL . '/pages/dashboard.php');
}

// Get chama members
$stmt = $pdo->prepare("SELECT u.user_id, u.username, u.full_name, cm.role, cm.joined_at
                      FROM chama_members cm
                      JOIN users u ON cm.user_id = u.user_id
                      WHERE cm.chama_id = ? AND cm.status = 'active'");
$stmt->execute([$chama_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get contributions
$stmt = $pdo->prepare("SELECT con.*, u.username 
                      FROM contributions con
                      JOIN users u ON con.user_id = u.user_id
                      WHERE con.chama_id = ?
                      ORDER BY con.contributed_at DESC");
$stmt->execute([$chama_id]);
$contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total contributions
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM contributions 
                      WHERE chama_id = ? AND status = 'verified'");
$stmt->execute([$chama_id]);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get recent activities
$stmt = $pdo->prepare("SELECT a.*, u.username 
                      FROM activities a
                      LEFT JOIN users u ON a.user_id = u.user_id
                      WHERE a.chama_id = ?
                      ORDER BY a.created_at DESC LIMIT 5");
$stmt->execute([$chama_id]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($chama['name']) ?> - ChamaPro</title>
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

        /* Chama Header */
        .chama-header {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
        }

        .chama-header h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .chama-header p {
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .chama-actions {
            position: absolute;
            top: 25px;
            right: 25px;
            display: flex;
            gap: 10px;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Card Styles */
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gray-light);
        }

        .card-header h3 {
            font-size: 1.2rem;
            color: var(--dark-color);
        }

        /* Progress Bar Styles */
        .progress-container {
            margin: 20px 0;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .progress-amount {
            font-weight: 600;
            color: var(--dark-color);
        }

        .progress-goal {
            color: var(--secondary-color);
        }

        .progress-bar {
            height: 10px;
            background-color: var(--gray-light);
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--success-color);
            border-radius: 5px;
            transition: width 0.5s ease;
        }

        /* Members List */
        .members-list {
            list-style: none;
        }

        .member-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .member-item:last-child {
            border-bottom: none;
        }

        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: 600;
        }

        .member-info {
            flex: 1;
        }

        .member-name {
            font-weight: 600;
            margin-bottom: 3px;
        }

        .member-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--secondary-color);
        }

        .member-role {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .member-role.admin {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .member-role.member {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        /* Contributions Table */
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

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.verified {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .status-badge.pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }

        /* Activities List */
        .activities-list {
            list-style: none;
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-message {
            margin-bottom: 5px;
        }

        .activity-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--secondary-color);
        }

        /* Button Styles */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: rgba(74, 111, 220, 0.1);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 2.5rem;
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

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }

            .chama-header {
                padding: 20px;
            }

            .chama-actions {
                position: static;
                margin-top: 15px;
                justify-content: flex-end;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
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
                <a href="<?= SITE_URL ?>/chamas/create.php">
                    <i class="fas fa-plus-circle"></i> Create Chama
                </a>
                <a href="<?= SITE_URL ?>/chamas/">
                    <i class="fas fa-users"></i> My Chamas
                </a>
                <a href="<?= SITE_URL ?>/contributions/">
                    <i class="fas fa-hand-holding-usd"></i> Contributions
                </a>
                <a href="<?= SITE_URL ?>/reports/">
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
                <h1><?= htmlspecialchars($chama['name']) ?></h1>
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'User') ?>&background=4a6fdc&color=fff" alt="User">
                </div>
            </div>

            <!-- Chama Header -->
            <div class="chama-header">
                <h2><?= htmlspecialchars($chama['name']) ?></h2>
                <p><?= htmlspecialchars($chama['description']) ?></p>
                
                <div class="progress-container">
                    <div class="progress-info">
                        <span class="progress-amount">Ksh <?= number_format($total, 2) ?> collected</span>
                        <span class="progress-goal">Goal: Ksh <?= number_format($chama['goal_amount'], 2) ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min(($total / $chama['goal_amount']) * 100, 100) ?>%"></div>
                    </div>
                </div>
                
                <div class="chama-actions">
                    <a href="<?= SITE_URL ?>/../../contributions/make.php?chama_id=<?= $chama_id ?>" class="btn">
                        <i class="fas fa-money-bill-wave"></i> Make Contribution
                    </a>
                    <?php if ($chama['role'] === 'admin'): ?>
                        <a href="<?= SITE_URL ?>manage.php?id=<?= $chama_id ?>" class="btn btn-outline">
                            <i class="fas fa-cog"></i> Manage Chama
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Members Card -->
                <div class="card">
                    <div class="card-header">
                        <h3>Members (<?= count($members) ?>)</h3>
                        <?php if ($chama['role'] === 'admin'): ?>
                            <a href="<?= SITE_URL ?>manage.php?id=<?= $chama_id ?>" class="btn btn-sm btn-outline">
                                <i class="fas fa-user-plus"></i> Manage
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($members)): ?>
                        <ul class="members-list">
                            <?php foreach ($members as $member): ?>
                                <li class="member-item">
                                    <div class="member-avatar">
                                        <?= strtoupper(substr($member['full_name'] ?: $member['username'], 0, 1)) ?>
                                    </div>
                                    <div class="member-info">
                                        <div class="member-name">
                                            <?= htmlspecialchars($member['full_name'] ?: $member['username']) ?>
                                        </div>
                                        <div class="member-meta">
                                            <span>Joined <?= date('M Y', strtotime($member['joined_at'])) ?></span>
                                            <span class="member-role <?= $member['role'] ?>">
                                                <?= ucfirst($member['role']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users-slash"></i>
                            <p>No members found</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Activities Card -->
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Activities</h3>
                    </div>
                    
                    <?php if (!empty($activities)): ?>
                        <ul class="activities-list">
                            <?php foreach ($activities as $activity): ?>
                                <li class="activity-item">
                                    <div class="activity-message">
                                        <?php if ($activity['username']): ?>
                                            <strong><?= htmlspecialchars($activity['username']) ?>:</strong>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($activity['message']) ?>
                                    </div>
                                    <div class="activity-meta">
                                        <span><?= date('M j, Y', strtotime($activity['created_at'])) ?></span>
                                        <span><?= date('g:i a', strtotime($activity['created_at'])) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <p>No recent activities</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contributions Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Contributions</h3>
                    <a href="<?= SITE_URL ?>/../../contributions/make.php?chama_id=<?= $chama_id ?>" class="btn btn-sm">
                        <i class="fas fa-plus"></i> New Contribution
                    </a>
                </div>
                
                <?php if (!empty($contributions)): ?>
                    <div style="overflow-x: auto;">
                        <table class="contributions-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contributions as $contribution): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($contribution['username']) ?></td>
                                        <td>Ksh <?= number_format($contribution['amount'], 2) ?></td>
                                        <td><?= date('M j, Y', strtotime($contribution['contributed_at'])) ?></td>
                                        <td>
                                            <span class="status-badge <?= $contribution['status'] ?>">
                                                <?= ucfirst($contribution['status']) ?>
                                            </span>
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
                        <a href="<?= SITE_URL ?>/../../contributions/make.php?chama_id=<?= $chama_id ?>" class="btn btn-sm">
                            Make First Contribution
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>