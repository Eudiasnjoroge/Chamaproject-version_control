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
$stmt = $pdo->prepare("SELECT 1 FROM chama_members 
                      WHERE chama_id = ? AND user_id = ? AND role = 'admin' AND status = 'active'");
$stmt->execute([$chama_id, $user_id]);
if (!$stmt->fetch()) {
    $_SESSION['message'] = 'Unauthorized access';
    redirect(SITE_URL . '/dashboard.php');
}

// Get chama details
$stmt = $pdo->prepare("SELECT * FROM chamas WHERE chama_id = ?");
$stmt->execute([$chama_id]);
$chama = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all members and their contributions
$stmt = $pdo->prepare("SELECT 
                      u.user_id, u.username, u.full_name, u.email,
                      (SELECT SUM(amount) FROM contributions 
                       WHERE chama_id = ? AND user_id = u.user_id AND status = 'verified') as total_contributed
                      FROM users u
                      JOIN chama_members cm ON u.user_id = cm.user_id
                      WHERE cm.chama_id = ? AND cm.status = 'active'");
$stmt->execute([$chama_id, $chama_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total contributions
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM contributions 
                      WHERE chama_id = ? AND status = 'verified'");
$stmt->execute([$chama_id]);
$total_contributions = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Generate simple HTML PDF (in real app, use a library like TCPDF)
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="chama_report_'.$chama_id.'.pdf"');

$html = '<h1>Chama Contribution Report</h1>';
$html .= '<h2>'.htmlspecialchars($chama['name']).'</h2>';
$html .= '<p>Generated on: '.date('Y-m-d').'</p>';
$html .= '<h3>Total Collected: Ksh '.number_format($total_contributions, 2).'</h3>';
$html .= '<h3>Members Contributions</h3>';
$html .= '<table border="1" cellpadding="5">';
$html .= '<tr><th>Member</th><th>Amount</th></tr>';

foreach ($members as $member) {
    $html .= '<tr>';
    $html .= '<td>'.htmlspecialchars($member['full_name'] ?: $member['username']).'</td>';
    $html .= '<td>Ksh '.number_format($member['total_contributed'] ?? 0, 2).'</td>';
    $html .= '</tr>';
}

$html .= '</table>';

// In a real implementation, you would use a PDF library here
// For this example, we'll just output the HTML
echo $html;
exit;