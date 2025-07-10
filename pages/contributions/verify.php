<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/constants.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/../auth/login.php');
}

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    $_SESSION['message'] = 'Invalid request';
    redirect(SITE_URL . '/../dashboard.php');
}

$contribution_id = (int)$_GET['id'];
$action = $_GET['action'];
$user_id = $_SESSION['user_id'];

// Get contribution details
$stmt = $pdo->prepare("SELECT con.*, c.chama_id, c.name as chama_name 
                      FROM contributions con
                      JOIN chamas c ON con.chama_id = c.chama_id
                      WHERE con.contribution_id = ?");
$stmt->execute([$contribution_id]);
$contribution = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contribution) {
    $_SESSION['message'] = 'Contribution not found';
    redirect(SITE_URL . '/../dashboard.php');
}

// Verify user is admin of this chama
$stmt = $pdo->prepare("SELECT * FROM chama_members 
                      WHERE chama_id = ? AND user_id = ? AND role = 'admin' AND status = 'active'");
$stmt->execute([$contribution['chama_id'], $user_id]);
$is_admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$is_admin) {
    $_SESSION['message'] = 'You are not authorized to perform this action';
    redirect(SITE_URL . '/../dashboard.php');
}

// Process verification
$valid_status = '';
if ($action === 'verify') {
    $valid_status = 'verified';
    $message = "Contribution of Ksh " . number_format($contribution['amount'], 2) . " verified";
} elseif ($action === 'reject') {
    $valid_status = 'rejected';
    $message = "Contribution of Ksh " . number_format($contribution['amount'], 2) . " rejected";
}

if ($valid_status) {
    try {
        $pdo->beginTransaction();
        
        // Update contribution status
        $stmt = $pdo->prepare("UPDATE contributions 
                              SET status = ?, verified_by = ?, verified_at = NOW()
                              WHERE contribution_id = ?");
        $stmt->execute([$valid_status, $user_id, $contribution_id]);
        
        // Add to activity log
        addActivity($contribution['chama_id'], $user_id, 'contribution_' . $valid_status, $message);
        
        // Notify the member
        addNotification(
            $contribution['user_id'], 
            'Contribution ' . $valid_status, 
            "Your contribution of Ksh " . number_format($contribution['amount'], 2) . " has been " . $valid_status,
            SITE_URL . '/../chamas/view.php?id=' . $contribution['chama_id']
        );
        
        $pdo->commit();
        $_SESSION['message'] = "Contribution successfully " . $valid_status;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['message'] = "Error: " . $e->getMessage();
    }
} else {
    $_SESSION['message'] = 'Invalid action';
}

redirect(SITE_URL . '/history.php?chama_id=' . $contribution['chama_id']);
?>