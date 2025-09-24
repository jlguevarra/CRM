<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Missing required data.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$notification_id = $_POST['id'];

// Mark a single notification as read for the current user
$sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $notification_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
}

$stmt->close();
$conn->close();
?>