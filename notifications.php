<?php
session_start();
include 'config.php';
include 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch notifications for the current user
$sql = "SELECT n.*, t.title as task_title 
        FROM notifications n 
        LEFT JOIN tasks t ON n.related_id = t.id AND n.related_type = 'task'
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC 
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
$unread_count = 0;

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
    if (!$row['is_read']) {
        $unread_count++;
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'count' => $unread_count,
    'notifications' => $notifications
]);
?>