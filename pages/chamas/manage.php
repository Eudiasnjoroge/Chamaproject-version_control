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
    $_SESSION['message'] = 'No chama specified';
    redirect(SITE_URL . '/pages/dashboard.php');
}

$chama_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verify user is admin of this chama
$stmt = $pdo->prepare("SELECT c.* FROM chamas c
                      JOIN chama_members cm ON c.chama_id = cm.chama_id
                      WHERE c.chama_id = ? AND cm.user_id = ? AND cm.role = 'admin' AND cm.status = 'active'");
$stmt->execute([$chama_id, $user_id]);
$chama = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chama) {
    $_SESSION['message'] = 'You are not an admin of this chama or it does not exist.';
    redirect(SITE_URL . '/pages/dashboard.php');
}

// Handle all member actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        if (isset($_POST['add_member'])) {
            $username = sanitizeInput($_POST['username']);
            
            // Find user by username or email
            $stmt = $pdo->prepare("SELECT user_id, username, email FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $new_member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($new_member) {
                // Check if already a member
                $stmt = $pdo->prepare("SELECT * FROM chama_members 
                                      WHERE chama_id = ? AND user_id = ?");
                $stmt->execute([$chama_id, $new_member['user_id']]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    if ($existing['status'] === 'inactive') {
                        $stmt = $pdo->prepare("UPDATE chama_members 
                                              SET status = 'active' 
                                              WHERE member_id = ?");
                        $stmt->execute([$existing['member_id']]);
                        $message = "Re-activated member " . $new_member['username'];
                    } else {
                        $message = "User is already a member";
                    }
                } else {
                    $stmt = $pdo->prepare("INSERT INTO chama_members 
                                          (chama_id, user_id, role) 
                                          VALUES (?, ?, 'member')");
                    $stmt->execute([$chama_id, $new_member['user_id']]);
                    $message = "Added new member " . $new_member['username'];
                    
                    // Add to activity log
                    addActivity($chama_id, $user_id, 'member_added', $message);
                    
                    // Send notification to new member
                    addNotification(
                        $new_member['user_id'], 
                        'Added to Chama', 
                        "You've been added to chama: " . $chama['name'],
                        SITE_URL . '/view.php?id=' . $chama_id
                    );
                }
                
                $_SESSION['message'] = $message;
            } else {
                $_SESSION['message'] = 'User not found.';
            }
        }
        elseif (isset($_POST['remove_member'])) {
            $member_id = (int)$_POST['member_id'];
            
            // Verify member belongs to this chama
            $stmt = $pdo->prepare("SELECT cm.*, u.username FROM chama_members cm
                                  JOIN users u ON cm.user_id = u.user_id
                                  WHERE cm.member_id = ? AND cm.chama_id = ?");
            $stmt->execute([$member_id, $chama_id]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($member) {
                if ($member['role'] === 'admin') {
                    $_SESSION['message'] = 'Cannot remove another admin.';
                } else {
                    // COMPLETELY remove the member (not just soft delete)
                    $stmt = $pdo->prepare("DELETE FROM chama_members WHERE member_id = ?");
                    $stmt->execute([$member_id]);
                    
                    // Remove all their pending contributions
                    $pdo->prepare("DELETE FROM contributions 
                                  WHERE chama_id = ? AND user_id = ? AND status = 'pending'")
                       ->execute([$chama_id, $member['user_id']]);
                    
                    $message = "Permanently removed member " . $member['username'];
                    addActivity($chama_id, $user_id, 'member_removed', $message);
                    
                    // Send notification to removed member
                    addNotification(
                        $member['user_id'], 
                        'Removed from Chama', 
                        "You've been removed from chama: " . $chama['name'],
                        SITE_URL . '/../dashboard.php'
                    );
                    
                    $_SESSION['message'] = $message;
                }
            } else {
                $_SESSION['message'] = 'Member not found in this chama.';
            }
        }
        elseif (isset($_POST['promote_member'])) {
            $member_id = (int)$_POST['member_id'];
            
            // Verify member belongs to this chama
            $stmt = $pdo->prepare("SELECT cm.*, u.username FROM chama_members cm
                                  JOIN users u ON cm.user_id = u.user_id
                                  WHERE cm.member_id = ? AND cm.chama_id = ? AND cm.role = 'member'");
            $stmt->execute([$member_id, $chama_id]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($member) {
                $stmt = $pdo->prepare("UPDATE chama_members 
                                      SET role = 'admin' 
                                      WHERE member_id = ?");
                $stmt->execute([$member_id]);
                
                $message = "Promoted " . $member['username'] . " to admin";
                addActivity($chama_id, $user_id, 'member_promoted', $message);
                addNotification(
                    $member['user_id'], 
                    'Promoted to Admin', 
                    "You've been promoted to admin in chama: " . $chama['name'],
                    SITE_URL . '/view.php?id=' . $chama_id
                );
                
                $_SESSION['message'] = $message;
            } else {
                $_SESSION['message'] = 'Member not found or already an admin.';
            }
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['message'] = 'Error: ' . $e->getMessage();
    }
    
    // Always redirect after POST to prevent form resubmission
    redirect(SITE_URL . '/manage.php?id=' . $chama_id);
}

// Get all active members (updated in real-time)
$stmt = $pdo->prepare("SELECT cm.member_id, cm.role, cm.joined_at, 
                      u.user_id, u.username, u.full_name, u.email
                      FROM chama_members cm
                      JOIN users u ON cm.user_id = u.user_id
                      WHERE cm.chama_id = ? AND cm.status = 'active'
                      ORDER BY cm.role DESC, cm.joined_at");
$stmt->execute([$chama_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Chama Members - ChamaPro</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .message.error {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        /* Card Styles */
        .card {
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

        .card-header h3 {
            font-size: 1.3rem;
            color: var(--dark-color);
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

        /* Button Styles */
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

        .btn:active {
            transform: translateY(0);
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: rgba(74, 111, 220, 0.1);
        }

        .btn-danger {
            background-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        /* Members Table */
        .members-table {
            width: 100%;
            border-collapse: collapse;
        }

        .members-table th {
            text-align: left;
            padding: 12px 15px;
            background-color: var(--gray-light);
            color: var(--dark-color);
            font-weight: 600;
        }

        .members-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray-light);
            vertical-align: middle;
        }

        .members-table tr:last-child td {
            border-bottom: none;
        }

        .members-table tr:hover {
            background-color: rgba(74, 111, 220, 0.05);
        }

        /* Role Badges */
        .role-badge {
            display: inline-block;
            padding: 4px 10px;
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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
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

            .members-table {
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
                <a href="<?= SITE_URL ?>/create.php">
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
                <h1>Manage Members</h1>
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'User') ?>&background=4a6fdc&color=fff" alt="User">
                </div>
            </div>

            <a href="<?= SITE_URL ?>/view.php?id=<?= $chama_id ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Chama
            </a>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="message <?= strpos($_SESSION['message'], 'Error') === 0 ? 'error' : 'success' ?>">
                    <i class="fas <?= strpos($_SESSION['message'], 'Error') === 0 ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                    <span><?= $_SESSION['message'] ?></span>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <!-- Add Member Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Add New Member</h3>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Enter username or email" required>
                    </div>
                    <button type="submit" name="add_member" class="btn">
                        <i class="fas fa-user-plus"></i> Add Member
                    </button>
                </form>
            </div>

            <!-- Members List Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Current Members (<?= count($members) ?>)</h3>
                </div>
                
                <?php if (count($members) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="members-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($member['full_name'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($member['username']) ?></td>
                                        <td><?= htmlspecialchars($member['email']) ?></td>
                                        <td>
                                            <span class="role-badge <?= $member['role'] ?>">
                                                <?= ucfirst($member['role']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($member['joined_at'])) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($member['role'] === 'member'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="member_id" value="<?= $member['member_id'] ?>">
                                                        <button type="submit" name="promote_member" class="btn btn-sm btn-outline">
                                                            <i class="fas fa-user-shield"></i> Promote
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if ($member['user_id'] !== $user_id): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="member_id" value="<?= $member['member_id'] ?>">
                                                        <button type="submit" name="remove_member" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('Are you sure you want to permanently remove this member?')">
                                                            <i class="fas fa-user-minus"></i> Remove
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
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
                        <p>No members found in this chama</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>