<?php
// Database connection (already in your config.php)
include 'config.php';



// User Management Functions
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
        
        // Split full name into first and last name for the form
        $name_parts = explode(' ', $user['name'], 2);
        $user['first_name'] = $name_parts[0] ?? '';
        $user['last_name'] = $name_parts[1] ?? '';
        
        return $user;
    }
    
    return null;
}

function updateUserProfile($user_id, $data) {
    global $conn;
    
    // Combine first and last name
    $full_name = trim($data['first_name'] . ' ' . ($data['last_name'] ?? ''));
    
    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, position = ?, bio = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
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

function updatePassword($user_id, $current_password, $new_password) {
    global $conn;
    
    // Verify current password
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
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
    }
    
    return false;
}

function createUser($data) {
    global $conn;
    
    // Check if email already exists
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $data['email']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        return false; // Email already exists
    }
    
    $sql = "INSERT INTO users (name, email, password, role, phone, position, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    // Hash password
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", 
        $data['name'],
        $data['email'],
        $hashed_password,
        $data['role'],
        $data['phone'],
        $data['position']
    );
    
    return $stmt->execute();
}

function updateUser($user_id, $data) {
    global $conn;
    
    $sql = "UPDATE users SET 
            name = ?, 
            email = ?, 
            role = ?, 
            phone = ?, 
            position = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", 
        $data['name'],
        $data['email'],
        $data['role'],
        $data['phone'],
        $data['position'],
        $user_id
    );
    
    return $stmt->execute();
}

function deleteUser($user_id) {
    global $conn;
    
    // Prevent deleting the last admin
    $check_sql = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
    $check_result = $conn->query($check_sql);
    $admin_count = $check_result->fetch_assoc()['admin_count'];
    
    $user_sql = "SELECT role FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_role = $user_result->fetch_assoc()['role'];
    
    if ($user_role === 'admin' && $admin_count <= 1) {
        return false; // Cannot delete the last admin
    }
    
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    return $stmt->execute();
}

// Settings Functions
function getUserSettings($user_id) {
    global $conn;
    
    $sql = "SELECT * FROM user_settings WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $settings = $result->fetch_assoc();
    
    // Return default settings if none exist
    if (!$settings) {
        return [
            'email_notifications' => 1,
            'push_notifications' => 1,
            'task_reminders' => 1,
            'weekly_reports' => 0,
            'theme' => 'light',
            'language' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'mm/dd/yyyy',
            'time_format' => '12',
            'items_per_page' => 25
        ];
    }
    
    return $settings;
}

function updateUserPreferences($user_id, $data) {
    global $conn;
    
    // Check if settings already exist
    $check_sql = "SELECT id FROM user_settings WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    // Set default values for missing fields
    $email_notifications = isset($data['email_notifications']) ? 1 : 0;
    $push_notifications = isset($data['push_notifications']) ? 1 : 0;
    $task_reminders = isset($data['task_reminders']) ? 1 : 0;
    $weekly_reports = isset($data['weekly_reports']) ? 1 : 0;
    $theme = $data['theme'] ?? 'light';
    $language = $data['language'] ?? 'en';
    $timezone = $data['timezone'] ?? 'UTC';
    $date_format = $data['date_format'] ?? 'mm/dd/yyyy';
    $time_format = $data['time_format'] ?? '12';
    $items_per_page = $data['items_per_page'] ?? 25;
    
    if ($check_result->num_rows > 0) {
        // Update existing settings
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
                items_per_page = ?,
                updated_at = NOW()
                WHERE user_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiisssssii", 
            $email_notifications,
            $push_notifications,
            $task_reminders,
            $weekly_reports,
            $theme,
            $language,
            $timezone,
            $date_format,
            $time_format,
            $items_per_page,
            $user_id
        );
    } else {
        // Insert new settings
        $sql = "INSERT INTO user_settings 
                (user_id, email_notifications, push_notifications, task_reminders, weekly_reports, 
                 theme, language, timezone, date_format, time_format, items_per_page) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiissssssi", 
            $user_id,
            $email_notifications,
            $push_notifications,
            $task_reminders,
            $weekly_reports,
            $theme,
            $language,
            $timezone,
            $date_format,
            $time_format,
            $items_per_page
        );
    }
    
    return $stmt->execute();
}

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
        'items_per_page' => 25
    ];
}

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
                items_per_page = ?,
                updated_at = NOW()
                ORDER BY id DESC LIMIT 1";
    } else {
        // Insert new settings
        $sql = "INSERT INTO system_settings 
                (company_name, auto_backup, backup_frequency, backup_location, email_notifications, 
                 timezone, date_format, time_format, items_per_page) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sississsi", 
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

// Task functions
function getTasks($filters = []) {
    global $conn;

    $user_id = $_SESSION['user_id'];
    $user = getUserDetails($user_id);
    $role = $user['role'];

    $sql = "SELECT t.*, u.name as assigned_name, creator.name as created_by_name 
            FROM tasks t 
            JOIN users u ON t.assigned_to = u.id 
            JOIN users creator ON t.created_by = creator.id 
            WHERE 1=1";

    // 🔐 Restrict for managers
    if ($role === 'user') {
        $sql .= " AND t.assigned_to = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    } else {
        // Admin sees all
        $stmt = $conn->prepare($sql);
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

function getActivityLog($user_id = null, $limit = 5) {
    global $conn;
    
    try {
        $sql = "SELECT al.*, u.name as user_name 
                FROM activity_log al 
                JOIN users u ON al.user_id = u.id";
        
        $params = [];
        $types = "";
        
        if ($user_id) {
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
            // Format the description to be more user-friendly
            $row['formatted_description'] = formatActivityDescription($row);
            $activities[] = $row;
        }
        
        return $activities;
        
    } catch (Exception $e) {
        error_log("Error fetching activity log: " . $e->getMessage());
        return [];
    }
}

// Helper function to format activity descriptions
function formatActivityDescription($activity) {
    $description = $activity['description'];
    $user_name = $activity['user_name'];
    
    // Add user name to the description for better context
    return $user_name . " - " . $description;
}

function generateReportData($filters) {
    global $conn;

    $report_data = [];
    
    // Set default dates
    $date_range = $filters['date_range'] ?? '30';
    $end_date = $filters['end_date'] ?? date('Y-m-d');
    
    if ($date_range === 'custom' && !empty($filters['start_date']) && !empty($filters['end_date'])) {
        $start_date = $filters['start_date'];
        $end_date = $filters['end_date'];
    } else {
        $days = intval($date_range);
        $start_date = date('Y-m-d', strtotime("-$days days", strtotime($end_date)));
    }

    // Add time to dates for proper range comparison
    $start_datetime = $start_date . ' 00:00:00';
    $end_datetime = $end_date . ' 23:59:59';

    switch ($filters['report_type']) {
        case 'sales':
            // Sales performance report - Customer acquisition over time
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as new_customers
                    FROM customers 
                    WHERE created_at BETWEEN ? AND ?
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_datetime, $end_datetime);
            break;

        case 'customers':
            // Customer analytics - Total customers growth
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as new_customers,
                        (SELECT COUNT(*) FROM customers c2 WHERE DATE(c2.created_at) <= DATE(customers.created_at)) as total_customers
                    FROM customers 
                    WHERE created_at BETWEEN ? AND ?
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_datetime, $end_datetime);
            break;

        case 'tasks':
            // Task completion report
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as tasks_created,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as tasks_completed,
                        CASE 
                            WHEN COUNT(*) > 0 THEN 
                                ROUND((SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2)
                            ELSE 0 
                        END as completion_rate
                    FROM tasks 
                    WHERE created_at BETWEEN ? AND ?
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_datetime, $end_datetime);
            break;

        default:
            // Default sales report
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as new_customers
                    FROM customers 
                    WHERE created_at BETWEEN ? AND ?
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_datetime, $end_datetime);
            break;
    }

    try {
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
    } catch (Exception $e) {
        error_log("Report generation error: " . $e->getMessage());
    }

    return $report_data;
}

function calculateStats($report_data, $report_type = 'sales') {
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
    
    // Calculate based on report type
    switch ($report_type) {
        case 'sales':
            if ($stats['total_customers'] > 0) {
                $stats['conversion_rate'] = round(($stats['new_customers'] / $stats['total_customers']) * 100, 2);
            }
            break;
            
        case 'tasks':
            $total_tasks = array_sum(array_column($report_data, 'tasks_created'));
            if ($total_tasks > 0) {
                $stats['tasks_completed'] = round(($stats['tasks_completed'] / $total_tasks) * 100, 2);
            }
            $stats['conversion_rate'] = 0; // Not applicable for tasks
            break;
            
        default:
            if ($stats['total_customers'] > 0) {
                $stats['conversion_rate'] = round(($stats['new_customers'] / $stats['total_customers']) * 100, 2);
            }
            break;
    }
    
    return $stats;
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
    
    error_log("Updating task $task_id to status: $status");
    
    // Check if user has permission to update this task
    $user_id = $_SESSION['user_id'];
    $check_sql = "SELECT id FROM tasks WHERE id = ? AND (created_by = ? OR assigned_to = ?)";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iii", $task_id, $user_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        error_log("User $user_id doesn't have permission to update task $task_id");
        return false; // User doesn't have permission
    }
    
    $sql = "UPDATE tasks SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $task_id);
    
    $result = $stmt->execute();
    error_log("Task update result: " . ($result ? "success" : "failed"));
    
    return $result;
}

function fetchUnreadNotifications($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, title, message, type, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>