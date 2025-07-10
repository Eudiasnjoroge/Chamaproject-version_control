<?php
require_once __DIR__ . '/../config/database.php';

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function addActivity($chama_id, $user_id, $activity_type, $message, $metadata = []) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO activities 
                          (chama_id, user_id, activity_type, message, metadata) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $chama_id, 
        $user_id, 
        $activity_type, 
        $message, 
        json_encode($metadata)
    ]);
}

function addNotification($user_id, $title, $message, $link = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO notifications 
                          (user_id, title, message, link) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $message, $link]);
}
?>