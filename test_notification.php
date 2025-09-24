<?php
session_start();
include 'config.php';

// Test creating a notification for admin
$admin_sql = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
$admin_result = $conn->query($admin_sql);
$admin = $admin_result->fetch_assoc();

if ($admin) {
    $title = "Test Notification";
    $message = "This is a test notification for task completion";
    $task_id = 1; // Use an existing task ID
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_type, related_id, is_read, created_at) VALUES (?, ?, ?, 'success', 'task', ?, 0, NOW())");
    $stmt->bind_param("issi", $admin['id'], $title, $message, $task_id);
    
    if ($stmt->execute()) {
        echo "Test notification created successfully for admin ID: " . $admin['id'];
    } else {
        echo "Failed to create test notification: " . $stmt->error;
    }
} else {
    echo "No admin user found";
}
?>