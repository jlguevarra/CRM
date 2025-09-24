<?php
session_start();
include 'config.php';
include 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'] ?? 0;
    $status = $_POST['status'] ?? 'pending';
    $user_id = $_SESSION['user_id'];
    
    error_log("=== TASK STATUS UPDATE STARTED ===");
    error_log("Task ID: $task_id, Status: $status, User ID: $user_id");
    
    if (updateTaskStatus($task_id, $status)) {
        error_log("Task status updated successfully");
        
        // 🔔 If task is completed, notify the admin
        if ($status === 'completed') {
            error_log("Task completed - creating admin notifications");
            
            // Get task details
            $task_sql = "SELECT t.*, u.name as assigned_name, creator.name as created_by_name 
                        FROM tasks t 
                        JOIN users u ON t.assigned_to = u.id 
                        JOIN users creator ON t.created_by = creator.id 
                        WHERE t.id = ?";
            $task_stmt = $conn->prepare($task_sql);
            $task_stmt->bind_param("i", $task_id);
            $task_stmt->execute();
            $task = $task_stmt->get_result()->fetch_assoc();
            
            if ($task) {
                error_log("Task found: " . $task['title'] . " assigned to: " . $task['assigned_name']);
                
                // Notify all admins about task completion
                $admin_sql = "SELECT id FROM users WHERE role = 'admin'";
                $admin_result = $conn->query($admin_sql);
                
                $admin_count = 0;
                while ($admin = $admin_result->fetch_assoc()) {
                    $title = "Task Completed";
                    $message = "Task '{$task['title']}' has been completed by {$task['assigned_name']}";
                    
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_type, related_id, is_read, created_at) VALUES (?, ?, ?, 'success', 'task', ?, 0, NOW())");
                    if ($notif_stmt) {
                        $notif_stmt->bind_param("issi", $admin['id'], $title, $message, $task_id);
                        if ($notif_stmt->execute()) {
                            $admin_count++;
                            error_log("Notification created for admin ID: " . $admin['id']);
                        } else {
                            error_log("Failed to create notification for admin: " . $notif_stmt->error);
                        }
                    } else {
                        error_log("Failed to prepare notification statement: " . $conn->error);
                    }
                }
                error_log("Notifications created for $admin_count admins");
            } else {
                error_log("Task not found with ID: $task_id");
            }
        }
        
        error_log("=== TASK STATUS UPDATE COMPLETED ===");
    } else {
        error_log("Failed to update task status");
    }
    
    // Redirect back to the previous page
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
    exit();
}

header("Location: dashboard.php");
exit();
?>