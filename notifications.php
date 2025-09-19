<?php
require_once 'db.php';
session_start();

$userId = $_SESSION['user_id'];

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
$unread_count = 0;

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
    if ($row['is_read'] == 0) {
        $unread_count++;
    }
}

echo json_encode([
    'count' => $unread_count,
    'notifications' => $notifications
]);

