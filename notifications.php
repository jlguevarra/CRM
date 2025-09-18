<?php
require_once 'db.php';
session_start();

$userId = $_SESSION['user_id'];

// Fetch unread count
$sql = "
    SELECT n.id, n.message, n.is_read, n.created_at,
           t.title AS task_title, t.status AS task_status
    FROM notifications n
    LEFT JOIN tasks t ON n.related_type = 'task' AND n.related_id = t.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 10
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode($notifications);
