<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/auth/login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Handle dismiss actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['dismiss_notification']) && isset($_POST['notification_id'])) {
        $notification_id = (int)$_POST['notification_id'];
        $pdo->prepare("DELETE FROM notifications WHERE notification_id = ? AND user_id = ?")
           ->execute([$notification_id, $user_id]);
        $_SESSION['message'] = 'Notification dismissed';
        redirect(SITE_URL . '/dashboard.php');
    }
    
    if (isset($_POST['dismiss_activity'])) {
        $_SESSION['dismissed_activities'] = $_SESSION['dismissed_activities'] ?? [];
        $_SESSION['dismissed_activities'][] = $_POST['activity_id'];
        redirect(SITE_URL . '/dashboard.php');
    }
}

// Get DISTINCT chamas to avoid duplicates
$stmt = $pdo->prepare("SELECT DISTINCT c.*, cm.role 
                      FROM chamas c
                      JOIN chama_members cm ON c.chama_id = cm.chama_id
                      WHERE cm.user_id = ? AND cm.status = 'active'");
$stmt->execute([$user_id]);
$chamas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get chama IDs for filtering
$chama_ids = array_column($chamas, 'chama_id');

// Get recent activities (only for active chamas)
$activities = [];
if (!empty($chama_ids)) {
    $placeholders = implode(',', array_fill(0, count($chama_ids), '?'));
    $stmt = $pdo->prepare("SELECT a.*, c.name as chama_name 
                          FROM activities a
                          JOIN chamas c ON a.chama_id = c.chama_id
                          WHERE a.chama_id IN ($placeholders)
                          AND NOT (a.activity_type = 'member_removed' AND a.user_id = ?)
                          ORDER BY a.created_at DESC LIMIT 10");
    $params = array_merge($chama_ids, [$user_id]);
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Filter out dismissed activities
$filtered_activities = [];
foreach ($activities as $activity) {
    if (!isset($_SESSION['dismissed_activities']) || !in_array($activity['activity_id'], $_SESSION['dismissed_activities'])) {
        $filtered_activities[] = $activity;
    }
}

// Get user notifications
$stmt = $pdo->prepare("SELECT * FROM notifications 
                      WHERE user_id = ? AND is_read = FALSE
                      ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark notifications as read when viewing dashboard
$pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?")
   ->execute([$user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Chama Management</title>
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

        /* Sidebar Styles */
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
            padding: 20px;
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

        .user-profile .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-profile .user-name {
            font-weight: 600;
        }

        .user-profile .user-role {
            font-size: 0.8rem;
            color: var(--secondary-color);
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

        .card-header .card-actions {
            display: flex;
            gap: 10px;
        }

        .card-header .btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
        }

        .btn-primary:hover {
            background-color: #3a5bc7;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: rgba(74, 111, 220, 0.1);
        }

        /* Chama List Styles */
        .chama-list {
            width: 100%;
            border-collapse: collapse;
        }

        .chama-list th {
            text-align: left;
            padding: 12px 15px;
            background-color: var(--gray-light);
            color: var(--dark-color);
            font-weight: 600;
        }

        .chama-list td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray-light);
            vertical-align: middle;
        }

        .chama-list tr:last-child td {
            border-bottom: none;
        }

        .chama-list tr:hover {
            background-color: rgba(74, 111, 220, 0.05);
        }

        .role-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-badge.admin {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .role-badge.member {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .action-links {
            display: flex;
            gap: 10px;
        }

        .action-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .action-links a:hover {
            text-decoration: underline;
        }

        /* Activity and Notification Styles */
        .activity-item, .notification-item {
            padding: 15px;
            margin-bottom: 15px;
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            position: relative;
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .notification-item {
            border-left-color: var(--info-color);
        }

        .activity-item:hover, .notification-item:hover {
            transform: translateX(5px);
        }

        .dismiss-btn {
            position: absolute;
            right: 15px;
            top: 15px;
            background: var(--danger-color);
            color: var(--white);
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .dismiss-btn:hover {
            background: #c82333;
        }

        .activity-item small, .notification-item small {
            display: block;
            margin-top: 5px;
            color: var(--secondary-color);
            font-size: 0.8rem;
        }

        /* Message Styles */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            background-color: #d4edda;
            color: #155724;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message .close-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: inherit;
        }

        /* Empty State Styles */
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

        .empty-state p {
            margin-bottom: 15px;
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
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .user-profile {
                margin-top: 10px;
            }

            .chama-list {
                display: block;
                overflow-x: auto;
            }
        }

        /* Animation for dismiss */
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; transform: translateX(20px); }
        }

        .fade-out {
            animation: fadeOut 0.3s forwards;
        }

        /* Progress bar styles */
        .progress-container {
            margin: 15px 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>ChamaPro</h2>
                <p>Manage your groups</p>
            </div>
            <nav class="sidebar-menu">
                <a href="<?= SITE_URL ?>/dashboard.php" class="active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="<?= SITE_URL ?>/chamas/create.php">
                    <i class="fas fa-plus-circle"></i> Create Chama
                </a>
                <a href="<?= SITE_URL ?>/chamas/my_chamas.php">
                    <i class="fas fa-users"></i> My Chamas
                </a>
                <a href="<?= SITE_URL ?>/contributions/">
                    <i class="fas fa-hand-holding-usd"></i> Contributions
                </a>
                <a href="<?= SITE_URL ?>/reports/">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="<?= SITE_URL ?>/settings/settings.php">
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
                <h1>Dashboard Overview</h1>
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&background=4a6fdc&color=fff" alt="User">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($user['username']) ?></span>
                        <span class="user-role">Member</span>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="message">
                    <?= $_SESSION['message'] ?>
                    <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <!-- Dashboard Stats -->
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Your Chamas</h3>
                        <span class="badge"><?= count($chamas) ?></span>
                    </div>
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Active Groups</span>
                            <span><?= count($chamas) ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= min(count($chamas)*10, 100) ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Recent Activities</h3>
                        <span class="badge"><?= count($filtered_activities) ?></span>
                    </div>
                    <p>Stay updated with your groups</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Notifications</h3>
                        <span class="badge"><?= count($notifications) ?></span>
                    </div>
                    <p>Important updates and reminders</p>
                </div>
            </div>

            <!-- Chamas Section -->
            <div class="card">
                <div class="card-header">
                    <h3>Your Chamas</h3>
                    <div class="card-actions">
                        <a href="<?= SITE_URL ?>/chamas/create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Chama
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($chamas)): ?>
                    <div style="overflow-x: auto;">
                        <table class="chama-list">
                            <thead>
                                <tr>
                                    <th>Chama Name</th>
                                    <th>Description</th>
                                    <th>Your Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($chamas as $chama): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($chama['name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($chama['description'] ?? 'No description') ?></td>
                                    <td>
                                        <span class="role-badge <?= $chama['role'] ?>">
                                            <?= ucfirst($chama['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <a href="<?= SITE_URL ?>/chamas/view.php?id=<?= $chama['chama_id'] ?>">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($chama['role'] === 'admin'): ?>
                                                <a href="<?= SITE_URL ?>/chamas/manage.php?id=<?= $chama['chama_id'] ?>">
                                                    <i class="fas fa-cog"></i> Manage
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= SITE_URL ?>/contributions/make.php?chama_id=<?= $chama['chama_id'] ?>">
                                                <i class="fas fa-money-bill-wave"></i> Contribute
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/contributions/history.php?chama_id=<?php echo $chama['chama_id']; ?>">History</a>

                                            <a href="<?php echo SITE_URL; ?>/contributions/member.php?chama_id=<?php echo $chama['chama_id']; ?>">My Contributions</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <p>You're not part of any chama yet</p>
                        <a href="<?= SITE_URL ?>/chamas/create.php" class="btn btn-primary">
                            Create Your First Chama
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Activities and Notifications Grid -->
            <div class="dashboard-grid" style="margin-top: 20px;">
                <!-- Activities Section -->
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Activities</h3>
                    </div>
                    
                    <?php if (!empty($filtered_activities)): ?>
                        <div>
                            <?php foreach ($filtered_activities as $activity): ?>
                                <div class="activity-item">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="dismiss_activity" value="1">
                                        <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                        <button type="submit" class="dismiss-btn" title="Dismiss">×</button>
                                    </form>
                                    <strong><?= htmlspecialchars($activity['chama_name']) ?>:</strong>
                                    <?= htmlspecialchars($activity['message']) ?>
                                    <small><?= date('M j, Y g:i a', strtotime($activity['created_at'])) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <p>No recent activities in your chamas</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Notifications Section -->
                <div class="card">
                    <div class="card-header">
                        <h3>Notifications</h3>
                    </div>
                    
                    <?php if (!empty($notifications)): ?>
                        <div>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="dismiss_notification" value="1">
                                        <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                                        <button type="submit" class="dismiss-btn" title="Dismiss">×</button>
                                    </form>
                                    <strong><?= htmlspecialchars($notification['title']) ?>:</strong>
                                    <?= htmlspecialchars($notification['message']) ?>
                                    <?php if ($notification['link']): ?>
                                        <a href="<?= $notification['link'] ?>">View</a>
                                    <?php endif; ?>
                                    <small><?= date('M j, Y g:i a', strtotime($notification['created_at'])) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p>You're all caught up!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Add smooth dismiss animation
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.dismiss-btn').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    var item = this.closest('.activity-item, .notification-item');
                    item.classList.add('fade-out');
                    
                    setTimeout(function() {
                        item.closest('form').submit();
                    }, 300);
                });
            });
        });
    </script>
</body>
</html>