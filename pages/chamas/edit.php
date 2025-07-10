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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $goal_amount = (float)$_POST['goal_amount'];
    $contribution_period = sanitizeInput($_POST['contribution_period']);
    $contribution_amount = (float)$_POST['contribution_amount'];
    
    try {
        $stmt = $pdo->prepare("UPDATE chamas 
                              SET name = ?, description = ?, goal_amount = ?,
                                  contribution_period = ?, contribution_amount = ?
                              WHERE chama_id = ?");
        $stmt->execute([
            $name, $description, $goal_amount,
            $contribution_period, $contribution_amount, $chama_id
        ]);
        
        addActivity($chama_id, $user_id, 'chama_updated', 
                  "Updated chama details");
        
        $_SESSION['message'] = 'Chama updated successfully!';
        redirect(SITE_URL . '/pages/chamas/view.php?id=' . $chama_id);
    } catch (PDOException $e) {
        $error = "Failed to update chama: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Chama</title>
</head>
<body>
    <h2>Edit Chama: <?= $chama['name'] ?></h2>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>
    
    <form method="POST">
        <div>
            <label>Chama Name:</label>
            <input type="text" name="name" value="<?= $chama['name'] ?>" required>
        </div>
        <div>
            <label>Description:</label>
            <textarea name="description" required><?= $chama['description'] ?></textarea>
        </div>
        <div>
            <label>Goal Amount (Ksh):</label>
            <input type="number" name="goal_amount" min="1" step="0.01" 
                   value="<?= $chama['goal_amount'] ?>" required>
        </div>
        <div>
            <label>Contribution Period:</label>
            <select name="contribution_period" required>
                <option value="daily" <?= $chama['contribution_period'] === 'daily' ? 'selected' : '' ?>>Daily</option>
                <option value="weekly" <?= $chama['contribution_period'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="monthly" <?= $chama['contribution_period'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="custom" <?= $chama['contribution_period'] === 'custom' ? 'selected' : '' ?>>Custom</option>
            </select>
        </div>
        <div>
            <label>Contribution Amount (Ksh):</label>
            <input type="number" name="contribution_amount" min="1" step="0.01" 
                   value="<?= $chama['contribution_amount'] ?>" required>
        </div>
        <button type="submit">Update Chama</button>
    </form>
    
    <h3>Danger Zone</h3>
    <form method="POST" action="<?= SITE_URL ?>/pages/chamas/delete.php" onsubmit="return confirm('Are you sure you want to delete this chama? This cannot be undone!');">
        <input type="hidden" name="chama_id" value="<?= $chama_id ?>">
        <button type="submit" style="color: red;">Delete Chama</button>
    </form>
</body>
</html>