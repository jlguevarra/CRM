<?php
// Database connection (already in your config.php)
include 'config.php';

// Task functions
function getTasks($filters = []) {
    global $conn;
    
    $sql = "SELECT t.*, u.name as assigned_name, creator.name as created_by_name 
            FROM tasks t 
            JOIN users u ON t.assigned_to = u.id 
            JOIN users creator ON t.created_by = creator.id 
            WHERE 1=1";
    
    if (!empty($filters['status']) && $filters['status'] != 'all') {
        $sql .= " AND t.status = '" . $conn->real_escape_string($filters['status']) . "'";
    }
    
    if (!empty($filters['priority']) && $filters['priority'] != 'all') {
        $sql .= " AND t.priority = '" . $conn->real_escape_string($filters['priority']) . "'";
    }
    
    if (!empty($filters['due_date'])) {
        $sql .= " AND t.due_date = '" . $conn->real_escape_string($filters['due_date']) . "'";
    }
    
    $sql .= " ORDER BY t.due_date ASC";
    
    $result = $conn->query($sql);
    $tasks = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
    }
    
    return $tasks;
}

function createTask($data) {
    global $conn;
    
    $sql = "INSERT INTO tasks (title, description, due_date, priority, status, assigned_to, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssii", 
        $data['title'], 
        $data['description'], 
        $data['due_date'], 
        $data['priority'], 
        $data['status'], 
        $data['assigned_to'], 
        $data['created_by']
    );
    
    return $stmt->execute();
}

// Settings functions
function getUserSettings($user_id) {
    global $conn;
    
    $sql = "SELECT * FROM user_settings WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function updateUserSettings($user_id, $data) {
    global $conn;
    
    $sql = "UPDATE user_settings SET 
            email_notifications = ?, 
            push_notifications = ?, 
            task_reminders = ?, 
            weekly_reports = ?, 
            theme = ?, 
            language = ?, 
            timezone = ?, 
            date_format = ?, 
            time_format = ?, 
            items_per_page = ? 
            WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisssssssi", 
        $data['email_notifications'], 
        $data['push_notifications'], 
        $data['task_reminders'], 
        $data['weekly_reports'], 
        $data['theme'], 
        $data['language'], 
        $data['timezone'], 
        $data['date_format'], 
        $data['time_format'], 
        $data['items_per_page'], 
        $user_id
    );
    
    return $stmt->execute();
}

// Report functions
function generateReport($data) {
    global $conn;
    
    $sql = "INSERT INTO reports (report_type, title, description, date_range, start_date, end_date, created_by, filters) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $filters_json = json_encode($data['filters']);
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssis", 
        $data['report_type'], 
        $data['title'], 
        $data['description'], 
        $data['date_range'], 
        $data['start_date'], 
        $data['end_date'], 
        $data['created_by'], 
        $filters_json
    );
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
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

// Notification functions
function getNotifications($user_id, $unread_only = false) {
    global $conn;
    
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    
    if ($unread_only) {
        $sql .= " AND is_read = FALSE";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $notifications = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    
    return $notifications;
}

function markNotificationAsRead($notification_id) {
    global $conn;
    
    $sql = "UPDATE notifications SET is_read = TRUE WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notification_id);
    
    return $stmt->execute();
}

// Activity log functions
function logActivity($user_id, $activity_type, $description) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $sql = "INSERT INTO activity_log (user_id, activity_type, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $activity_type, $description, $ip_address, $user_agent);
    
    return $stmt->execute();
}

function getActivityLog($user_id = null, $limit = 50) {
    global $conn;
    
    $sql = "SELECT al.*, u.name as user_name 
            FROM activity_log al 
            JOIN users u ON al.user_id = u.id";
    
    if ($user_id) {
        $sql .= " WHERE al.user_id = ?";
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($user_id) {
        $stmt->bind_param("ii", $user_id, $limit);
    } else {
        $stmt->bind_param("i", $limit);
    }
    
    $stmt->execute();
    
    $result = $stmt->get_result();
    $activities = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
    }
    
    return $activities;
}

function generateReportData($filters) {
    global $conn;

    $report_data = [];
    $start_date = $filters['start_date'];
    $end_date = $filters['end_date'];

    switch ($filters['report_type']) {
        case 'sales':
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as total_customers,
                        SUM(CASE WHEN DATE(created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_customers
                    FROM customers 
                    WHERE created_at BETWEEN ? AND ?
                    GROUP BY DATE(created_at) 
                    ORDER BY date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
            break;

        case 'tasks':
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as tasks_created,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as tasks_completed
                    FROM tasks 
                    WHERE created_at BETWEEN ? AND ?
                    GROUP BY DATE(created_at) 
                    ORDER BY date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            break;

        default:
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as total_customers
                    FROM customers 
                    WHERE created_at BETWEEN ? AND ?
                    GROUP BY DATE(created_at) 
                    ORDER BY date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            break;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($filters['report_type'] === 'sales') {
                $row['conversion_rate'] = $row['total_customers'] > 0 ?
                    round(($row['new_customers'] / $row['total_customers']) * 100, 2) : 0;
            }
            $report_data[] = $row;
        }
    }

    return $report_data;
}

/**
 * Calculate statistics from report data
 */
function calculateStats($report_data) {
    $stats = [
        'total_customers' => 0,
        'new_customers' => 0,
        'tasks_completed' => 0,
        'conversion_rate' => 0
    ];
    
    if (empty($report_data)) {
        return $stats;
    }
    
    foreach ($report_data as $data) {
        $stats['total_customers'] += $data['total_customers'] ?? 0;
        $stats['new_customers'] += $data['new_customers'] ?? 0;
        $stats['tasks_completed'] += $data['tasks_completed'] ?? 0;
    }
    
    // Calculate average conversion rate
    if ($stats['total_customers'] > 0) {
        $stats['conversion_rate'] = round(($stats['new_customers'] / $stats['total_customers']) * 100, 2);
    }
    
    // Calculate task completion percentage if we have task data
    $total_tasks = $stats['total_customers']; // This would need adjustment for actual task data
    if ($total_tasks > 0) {
        $stats['tasks_completed'] = round(($stats['tasks_completed'] / $total_tasks) * 100, 2);
    }
    
    return $stats;
}

/**
 * Save report configuration
 */
function saveReport($data) {
    global $conn;
    
    $sql = "INSERT INTO reports (report_type, title, description, date_range, start_date, end_date, created_by, filters) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $filters_json = json_encode($data['filters']);
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssis", 
        $data['report_type'], 
        $data['title'], 
        $data['description'], 
        $data['date_range'], 
        $data['start_date'], 
        $data['end_date'], 
        $data['created_by'], 
        $filters_json
    );
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

// Add these functions to your existing functions.php file

/**
 * Get user details
 */
function getUserDetails($user_id) {
    global $conn;
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Update user profile
 */
function updateUserProfile($user_id, $data) {
    global $conn;
    
    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, position = ?, bio = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    // Combine first and last name
    $full_name = $data['first_name'] . ' ' . $data['last_name'];
    
    $stmt->bind_param("sssssi", 
        $full_name,
        $data['email'],
        $data['phone'],
        $data['position'],
        $data['bio'],
        $user_id
    );
    
    return $stmt->execute();
}

/**
 * Update password
 */
function updatePassword($user_id, $current_password, $new_password) {
    global $conn;
    
    // Verify current password
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($current_password, $user['password'])) {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        return $stmt->execute();
    }
    
    return false;
}

/**
 * Get all users (admin only)
 */
function getAllUsers() {
    global $conn;
    
    $sql = "SELECT id, name, email, role FROM users ORDER BY name";
    $result = $conn->query($sql);
    
    $users = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    return $users;
}

/**
 * Get system settings
 */
function getSystemSettings() {
    global $conn;
    
    $sql = "SELECT * FROM system_settings ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // Return default settings if none exist
    return [
        'company_name' => 'Your Company Inc.',
        'auto_backup' => 1,
        'backup_frequency' => 'weekly',
        'backup_location' => 'local',
        'email_notifications' => 1,
        'timezone' => 'UTC',
        'date_format' => 'mm/dd/yyyy',
        'time_format' => '12',
        'items_per_page' => '25'
    ];
}

/**
 * Update system settings
 */
function updateSystemSettings($data) {
    global $conn;
    
    // Check if settings already exist
    $check_sql = "SELECT id FROM system_settings ORDER BY id DESC LIMIT 1";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        // Update existing settings
        $sql = "UPDATE system_settings SET 
                company_name = ?, 
                auto_backup = ?, 
                backup_frequency = ?, 
                backup_location = ?, 
                email_notifications = ?, 
                timezone = ?, 
                date_format = ?, 
                time_format = ?, 
                items_per_page = ? 
                ORDER BY id DESC LIMIT 1";
    } else {
        // Insert new settings
        $sql = "INSERT INTO system_settings 
                (company_name, auto_backup, backup_frequency, backup_location, email_notifications, timezone, date_format, time_format, items_per_page) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sississss", 
        $data['company_name'],
        $data['auto_backup'],
        $data['backup_frequency'],
        $data['backup_location'],
        $data['email_notifications'],
        $data['timezone'],
        $data['date_format'],
        $data['time_format'],
        $data['items_per_page']
    );
    
    return $stmt->execute();
}





function updateTask($data) {
    global $conn;
    
    $sql = "UPDATE tasks SET 
            title = ?, 
            description = ?, 
            due_date = ?, 
            priority = ?, 
            status = ?, 
            assigned_to = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssii", 
        $data['title'], 
        $data['description'], 
        $data['due_date'], 
        $data['priority'], 
        $data['status'], 
        $data['assigned_to'], 
        $data['task_id']
    );
    
    return $stmt->execute();
}

function deleteTask($task_id) {
    global $conn;
    
    // Check if user has permission to delete this task
    $user_id = $_SESSION['user_id'];
    $check_sql = "SELECT id FROM tasks WHERE id = ? AND (created_by = ? OR assigned_to = ?)";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iii", $task_id, $user_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        return false; // User doesn't have permission
    }
    
    $sql = "DELETE FROM tasks WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $task_id);
    
    return $stmt->execute();
}

function updateTaskStatus($task_id, $status) {
    global $conn;
    
    // Check if user has permission to update this task
    $user_id = $_SESSION['user_id'];
    $check_sql = "SELECT id FROM tasks WHERE id = ? AND (created_by = ? OR assigned_to = ?)";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iii", $task_id, $user_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        return false; // User doesn't have permission
    }
    
    $sql = "UPDATE tasks SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $task_id);
    
    return $stmt->execute();
}

function fetchUnreadNotifications($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, title, message, type, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}




?>