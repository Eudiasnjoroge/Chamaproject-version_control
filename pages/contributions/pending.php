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

// Verify user is admin of this chama
$stmt = $pdo->prepare("SELECT 1 FROM chama_members 
                      WHERE chama_id = ? AND user_id = ? AND role = 'admin' AND status = 'active'");
$stmt->execute([$chama_id, $user_id]);
if (!$stmt->fetch()) {
    $_SESSION['message'] = 'Unauthorized access';
    redirect(SITE_URL . '/pages/dashboard.php');
}

// Get pending contributions
$stmt = $pdo->prepare("SELECT c.*, u.username 
                      FROM contributions c
                      JOIN users u ON c.user_id = u.user_id
                      WHERE c.chama_id = ? AND c.status = 'pending'
                      ORDER BY c.contributed_at DESC");
$stmt->execute([$chama_id]);
$contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pending Contributions</title>
</head>
<body>
    <h2>Pending Contributions</h2>
    
    <?php if (!empty($contributions)): ?>
        <table border="1">
            <tr>
                <th>Member</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Proof</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($contributions as $contribution): ?>
            <tr>
                <td><?= htmlspecialchars($contribution['username']) ?></td>
                <td>Ksh <?= number_format($contribution['amount'], 2) ?></td>
                <td><?= date('M j, Y', strtotime($contribution['contributed_at'])) ?></td>
                <td>
                    <?php if ($contribution['proof_url']): ?>
                        <a href="<?= $contribution['proof_url'] ?>" target="_blank">View</a>
                    <?php else: ?>
                        None
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= SITE_URL ?>/verify.php?id=<?= $contribution['contribution_id'] ?>&action=verify">Verify</a>
                    <a href="<?= SITE_URL ?>/verify.php?id=<?= $contribution['contribution_id'] ?>&action=reject">Reject</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No pending contributions.</p>
    <?php endif; ?>
    
    <p><a href="<?= SITE_URL ?>/view.php?id=<?= $chama_id ?>">Back to Chama</a></p>
</body>
</html>