<?php
require_once 'config.php';
session_start();

$userId = $_SESSION['user_id'];

$sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

echo json_encode(["success" => true]);
