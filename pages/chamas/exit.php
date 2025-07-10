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

$chama_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verify user is member of this chama
$stmt = $pdo->prepare("SELECT c.*, cm.member_id, cm.role FROM chamas c
                      JOIN chama_members cm ON c.chama_id = cm.chama_id
                      WHERE c.chama_id = ? AND cm.user_id = ? AND cm.status = 'active'");
$stmt->execute([$chama_id, $user_id]);
$chama = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chama) {
    $_SESSION['message'] = 'You are not a member of this chama or it does not exist.';
    redirect(SITE_URL . '/pages/dashboard.php');
}

// Check if user is the last admin
if ($chama['role'] === 'admin') {
    $stmt = $pdo->prepare("SELECT COUNT(*) as admin_count FROM chama_members 
                          WHERE chama_id = ? AND role = 'admin' AND status = 'active'");
    $stmt->execute([$chama_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['admin_count'];
    
    if ($count <= 1) {
        $_SESSION['message'] = 'You are the last admin. Please assign another admin before leaving.';
        redirect(SITE_URL . '/pages/chamas/manage.php?id=' . $chama_id);
    }
}

// Handle exit confirmation
if (isset($_GET['confirm']) && $_GET['confirm'] === 'true') {
    try {
        // Soft delete (set status to inactive)
        $stmt = $pdo->prepare("UPDATE chama_members 
                              SET status = 'inactive' 
                              WHERE member_id = ?");
        $stmt->execute([$chama['member_id']]);
        
        addActivity($chama_id, $user_id, 'member_left', 
                   $_SESSION['username'] . " left the chama");
        
        $_SESSION['message'] = 'You have left the chama successfully.';
        redirect(SITE_URL . '/pages/dashboard.php');
    } catch (PDOException $e) {
        $error = "Failed to leave chama: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exit Chama</title>
</head>
<body>
    <h2>Exit Chama: <?= $chama['name'] ?></h2>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>
    
    <p>Are you sure you want to leave this chama?</p>
    
    <?php if ($chama['role'] === 'admin'): ?>
        <p><strong>Warning:</strong> You are an admin of this chama. If you leave, your admin privileges will be removed.</p>
    <?php endif; ?>
    
    <a href="<?= SITE_URL ?>/pages/chamas/exit.php?id=<?= $chama_id ?>&confirm=true">Yes, I want to leave</a>
    <a href="<?= SITE_URL ?>/pages/chamas/view.php?id=<?= $chama_id ?>">No, take me back</a>
</body>
</html>