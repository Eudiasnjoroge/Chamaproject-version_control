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

// Verify user is admin of this chama
$stmt = $pdo->prepare("SELECT c.* FROM chamas c
                      JOIN chama_members cm ON c.chama_id = cm.chama_id
                      WHERE c.chama_id = ? AND cm.user_id = ? AND cm.role = 'admin' AND cm.status = 'active'");
$stmt->execute([$chama_id, $user_id]);
$chama = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chama) {
    $_SESSION['message'] = 'You are not authorized to view this report';
    redirect(SITE_URL . '/dashboard.php');
}

// Get all members and their contributions
$stmt = $pdo->prepare("SELECT 
                      u.user_id, u.username, u.full_name, u.email,
                      cm.joined_at,
                      (SELECT SUM(amount) FROM contributions 
                       WHERE chama_id = ? AND user_id = u.user_id AND status = 'verified') as total_contributed,
                      (SELECT COUNT(*) FROM contributions 
                       WHERE chama_id = ? AND user_id = u.user_id AND status = 'verified') as contribution_count
                      FROM users u
                      JOIN chama_members cm ON u.user_id = cm.user_id
                      WHERE cm.chama_id = ? AND cm.status = 'active'
                      ORDER BY total_contributed DESC");
$stmt->execute([$chama_id, $chama_id, $chama_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate expected contributions based on chama settings
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

// Get total verified contributions
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM contributions 
                      WHERE chama_id = ? AND status = 'verified'");
$stmt->execute([$chama_id]);
$total_contributions = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contribution Summary</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .report-header { margin-bottom: 20px; }
        .summary-card { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }
        .summary-item { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .progress-container { width: 100%; background-color: #e0e0e0; border-radius: 5px; }
        .progress-bar { height: 20px; background-color: #4CAF50; border-radius: 5px; }
        .good { color: green; }
        .warning { color: orange; }
        .danger { color: red; }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>Contribution Summary: <?php echo htmlspecialchars($chama['name']); ?></h1>
        <p>Period: <?php echo ucfirst($chama['contribution_period']); ?> | 
           Target: Ksh <?php echo number_format($chama['goal_amount'], 2); ?></p>
    </div>

    <div class="summary-card">
        <div>
            <h3>Group Progress</h3>
            <div class="summary-item">
                Total Collected: <strong>Ksh <?php echo number_format($total_contributions, 2); ?></strong>
            </div>
            <div class="summary-item">
                Remaining: <strong>Ksh <?php echo number_format(max(0, $chama['goal_amount'] - $total_contributions), 2); ?></strong>
            </div>
            <div class="summary-item">
                Progress:
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo min(100, ($total_contributions / $chama['goal_amount']) * 100); ?>%"></div>
                </div>
                <?php echo round(min(100, ($total_contributions / $chama['goal_amount']) * 100), 2); ?>%
            </div>
        </div>
        <div>
            <h3>Period Summary</h3>
            <div class="summary-item">
                Expected Contributions: <?php echo $expected_periods; ?>
            </div>
            <div class="summary-item">
                Per Member: Ksh <?php echo number_format($expected_periods * $chama['contribution_amount'], 2); ?>
            </div>
        </div>
    </div>

    <h2>Member Contributions</h2>
    <table>
        <thead>
            <tr>
                <th>Member</th>
                <th>Contributions</th>
                <th>Total Amount</th>
                <th>Expected</th>
                <th>Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $member): 
                $expected = $expected_periods * $chama['contribution_amount'];
                $balance = $expected - ($member['total_contributed'] ?? 0);
                $status_class = ($balance <= 0) ? 'good' : (($balance <= $chama['contribution_amount']) ? 'warning' : 'danger');
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($member['full_name'] ?: $member['username']); ?></td>
                    <td><?php echo $member['contribution_count'] ?? 0; ?></td>
                    <td>Ksh <?php echo number_format($member['total_contributed'] ?? 0, 2); ?></td>
                    <td>Ksh <?php echo number_format($expected, 2); ?></td>
                    <td class="<?php echo $status_class; ?>">
                        Ksh <?php echo number_format(max(0, $balance), 2); ?>
                    </td>
                    <td class="<?php echo $status_class; ?>">
                        <?php echo ($balance <= 0) ? 'Complete' : (($balance <= $chama['contribution_amount']) ? 'Slightly Behind' : 'Behind'); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <a href="<?php echo SITE_URL; ?>/../../chamas/view.php?id=<?php echo $chama_id; ?>">Back to Chama</a> |
        <a href="<?php echo SITE_URL; ?>/export.php?chama_id=<?php echo $chama_id; ?>" target="_blank">Export as PDF</a>
    </div>
</body>
</html>