<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/constants.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['chama_id'])) {
    redirect(SITE_URL . '/pages/dashboard.php');
}

$chama_id = (int)$_POST['chama_id'];
$user_id = $_SESSION['user_id'];

// Verify user is admin of this chama
$stmt = $pdo->prepare("SELECT * FROM chamas c
                      JOIN chama_members cm ON c.chama_id = cm.chama_id
                      WHERE c.chama_id = ? AND cm.user_id = ? AND cm.role = 'admin' AND cm.status = 'active'");
$stmt->execute([$chama_id, $user_id]);
$chama = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chama) {
    $_SESSION['message'] = 'You are not an admin of this chama or it does not exist.';
    redirect(SITE_URL . '/pages/dashboard.php');
}

try {
    // Soft delete chama by marking all members as inactive
    $pdo->beginTransaction();
    
    // Mark all members as inactive
    $stmt = $pdo->prepare("UPDATE chama_members SET status = 'inactive' WHERE chama_id = ?");
    $stmt->execute([$chama_id]);
    
    // Mark chama as inactive (if you add a status field to chamas table)
    // $stmt = $pdo->prepare("UPDATE chamas SET status = 'inactive' WHERE chama_id = ?");
    // $stmt->execute([$chama_id]);
    
    // OR actually delete the chama (be careful with this)
    $stmt = $pdo->prepare("DELETE FROM chamas WHERE chama_id = ?");
    $stmt->execute([$chama_id]);
    
    addActivity($chama_id, $user_id, 'chama_deleted', "Deleted chama: " . $chama['name']);
    
    // Notify all members
    $stmt = $pdo->prepare("SELECT user_id FROM chama_members WHERE chama_id = ?");
    $stmt->execute([$chama_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($members as $member) {
        addNotification($member['user_id'], 'Chama Deleted', 
                      "The chama '" . $chama['name'] . "' has been deleted by the admin",
                      SITE_URL . '/pages/dashboard.php');
    }
    
    $pdo->commit();
    
    $_SESSION['message'] = 'Chama deleted successfully.';
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['message'] = 'Failed to delete chama: ' . $e->getMessage();
}

redirect(SITE_URL . '/pages/dashboard.php');
?>