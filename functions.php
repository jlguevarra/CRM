<?php
// This file should be included after config.php

// --- Notification Functions ---

function createNotification($user_id, $title, $message, $related_type, $related_id) {
    global $conn;
    $sql = "INSERT INTO notifications (user_id, title, message, type, related_type, related_id, is_read, created_at) 
            VALUES (?, ?, ?, 'task', ?, ?, 0, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $user_id, $title, $message, $related_type, $related_id);
    return $stmt->execute();
}


// --- Task Functions ---

function getTasks() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    $sql = "SELECT t.*, u.name as assigned_name, creator.name as created_by_name 
            FROM tasks t 
            JOIN users u ON t.assigned_to = u.id 
            JOIN users creator ON t.created_by = creator.id";

    if ($role === 'user') {
        $sql .= " WHERE t.assigned_to = ?";
    }

    $sql .= " ORDER BY t.id DESC"; // Sorts by the newest task first

    $stmt = $conn->prepare($sql);

    if ($role === 'user') {
        $stmt->bind_param("i", $user_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    return $tasks;
}

function createTask($data) {
    global $conn;
    $sql = "INSERT INTO tasks (title, description, due_date, priority, status, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssii", 
        $data['title'], $data['description'], $data['due_date'], 
        $data['priority'], $data['status'], $data['assigned_to'], $data['created_by']
    );
    
    if ($stmt->execute()) {
        $task_id = $conn->insert_id;
        $message = "You have been assigned a new task: \"" . htmlspecialchars($data['title']) . "\"";
        createNotification($data['assigned_to'], "New Task Assigned", $message, 'task', $task_id);
        return true;
    }
    return false;
}

function updateTask($data, $admin_edit = false) {
    global $conn;
    
    // Admin edit from modal does not include status
    if ($admin_edit) {
        $sql = "UPDATE tasks SET title = ?, description = ?, due_date = ?, priority = ?, assigned_to = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii",
            $data['title'], $data['description'], $data['due_date'],
            $data['priority'], $data['assigned_to'], $data['task_id']
        );
    } else {
        // Full update, including status
        $sql = "UPDATE tasks SET title = ?, description = ?, due_date = ?, priority = ?, status = ?, assigned_to = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssii",
            $data['title'], $data['description'], $data['due_date'],
            $data['priority'], $data['status'], $data['assigned_to'], $data['task_id']
        );
    }
    
    if ($stmt->execute()) {
        $message = "Your assigned task has been updated: \"" . htmlspecialchars($data['title']) . "\"";
        createNotification($data['assigned_to'], "Task Updated", $message, 'task', $data['task_id']);
        return true;
    }
    return false;
}

// MODIFIED: This function now sends a notification upon a successful deletion.
function deleteTask($task_id) {
    global $conn;

    // First, get the task details before deleting it
    $details_stmt = $conn->prepare("SELECT title, assigned_to FROM tasks WHERE id = ?");
    $details_stmt->bind_param("i", $task_id);
    $details_stmt->execute();
    $result = $details_stmt->get_result();
    $task_details = $result->fetch_assoc();
    $details_stmt->close();

    // Now, delete the task
    $sql = "DELETE FROM tasks WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $task_id);
    
    if ($stmt->execute()) {
        // If deletion was successful and we have the task details, create a notification
        if ($task_details) {
            $message = "A task assigned to you was deleted: \"" . htmlspecialchars($task_details['title']) . "\"";
            createNotification($task_details['assigned_to'], "Task Deleted", $message, 'task', $task_id);
        }
        return true;
    }
    return false;
}

function updateTaskStatus($task_id, $status) {
    global $conn;
    $sql = "UPDATE tasks SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $task_id);
    
    if ($stmt->execute()) {
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
            $task_details_stmt = $conn->prepare("SELECT title FROM tasks WHERE id = ?");
            $task_details_stmt->bind_param("i", $task_id);
            $task_details_stmt->execute();
            $task_details = $task_details_stmt->get_result()->fetch_assoc();
            $task_title = $task_details['title'] ?? 'a task';
            $user_name = $_SESSION['name'];
            $message = "$user_name updated task status to '$status': \"" . htmlspecialchars($task_title) . "\"";
            $admin_result = $conn->query("SELECT id FROM users WHERE role = 'admin'");
            while ($admin = $admin_result->fetch_assoc()) {
                createNotification($admin['id'], "Task Progress Update", $message, 'task', $task_id);
            }
        }
        return true;
    }
    return false;
}


// --- User & Settings Functions ---

function getAllUsers() {
    global $conn;
    $sql = "SELECT id, name, email, role, phone, position FROM users ORDER BY name ASC";
    $result = $conn->query($sql);
    $users = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    return $users;
}

function getUserDetails($user_id) {
    global $conn;
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $name_parts = explode(' ', $user['name'], 2);
        $user['first_name'] = $name_parts[0] ?? '';
        $user['last_name'] = $name_parts[1] ?? '';
        return $user;
    }
    return null;
}

function updateUserProfile($user_id, $data) {
    global $conn;
    $full_name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, position = ?, bio = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $full_name, $data['email'], $data['phone'], $data['position'], $data['bio'], $user_id);
    return $stmt->execute();
}

function updatePassword($user_id, $current_password, $new_password) {
    global $conn;
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            return $update_stmt->execute();
        }
    }
    return false;
}

function getUserSettings($user_id) {
    global $conn;
    $sql = "SELECT * FROM user_settings WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = $result->fetch_assoc();
    if (!$settings) {
        return [
            'email_notifications' => 1, 'push_notifications' => 1, 'task_reminders' => 1,
            'weekly_reports' => 0, 'theme' => 'light', 'language' => 'en', 'timezone' => 'UTC',
            'date_format' => 'mm/dd/yyyy', 'time_format' => '12', 'items_per_page' => 25
        ];
    }
    return $settings;
}

function getReports($user_id) {
    global $conn;
    $sql = "SELECT * FROM reports WHERE created_by = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reports = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
    }
    return $reports;
}

function logActivity($user_id, $activity_type, $description) {
    global $conn;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $sql = "INSERT INTO activity_log (user_id, activity_type, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $activity_type, $description, $ip_address, $user_agent);
    return $stmt->execute();
}

function getActivityLog($user_id = null, $limit = 5) {
    global $conn;
    $sql = "SELECT al.*, u.name as user_name FROM activity_log al JOIN users u ON al.user_id = u.id";
    $params = [];
    $types = "";
    if ($user_id !== null) {
        $sql .= " WHERE al.user_id = ?";
        $params[] = $user_id;
        $types .= "i";
    }
    $sql .= " ORDER BY al.created_at DESC LIMIT ?";
    $params[] = $limit;
    $types .= "i";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $activities = [];
    while($row = $result->fetch_assoc()) {
        $row['formatted_description'] = $row['user_name'] . " - " . $row['description'];
        $activities[] = $row;
    }
    return $activities;
}
?>