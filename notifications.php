<?php
require_once 'db.php';
session_start();

$userId = $_SESSION['user_id'];

$sql = "
    SELECT COUNT(*) AS count
    FROM notifications n
    LEFT JOIN tasks t ON n.related_type = 'task' AND n.related_id = t.id
    WHERE n.user_id = ?
      AND n.is_read = 0
      AND (
          n.related_type != 'task' OR (t.status IS NOT NULL AND t.status != 'completed')
      )
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode(['count' => $data['count']]);
