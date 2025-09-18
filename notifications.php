<?php
require_once 'db.php';
session_start();

$userId = $_SESSION['user_id'] ?? 0;
$response = ['count' => 0, 'notifications' => []];

if ($userId) {
    // Kunin count
    $sqlCount = "
        SELECT COUNT(*) AS count
        FROM notifications n
        LEFT JOIN tasks t ON n.related_type = 'task' AND n.related_id = t.id
        WHERE n.user_id = ?
          AND n.is_read = 0
          AND (
              n.related_type != 'task' OR (t.status IS NOT NULL AND t.status != 'completed')
          )
    ";
    $stmt = $conn->prepare($sqlCount);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $response['count'] = $data['count'] ?? 0;

    // Kunin details para sa dropdown (latest 5)
    $sqlList = "
        SELECT n.id, n.message, n.created_at
        FROM notifications n
        LEFT JOIN tasks t ON n.related_type = 'task' AND n.related_id = t.id
        WHERE n.user_id = ?
          AND n.is_read = 0
          AND (
              n.related_type != 'task' OR (t.status IS NOT NULL AND t.status != 'completed')
          )
        ORDER BY n.created_at DESC
        LIMIT 5
    ";
    $stmtList = $conn->prepare($sqlList);
    $stmtList->bind_param("i", $userId);
    $stmtList->execute();
    $resultList = $stmtList->get_result();

    while ($row = $resultList->fetch_assoc()) {
        $response['notifications'][] = $row;
    }
}

echo json_encode($response);
