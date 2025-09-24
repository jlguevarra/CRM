<?php
// Start session at the very top with output buffering
ob_start();
session_start();

// Include configuration and functions
include 'config.php';
include 'functions.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get report data based on filters
$report_data = [];
$stats = [
    'total_customers' => 0,
    'new_customers' => 0,
    'tasks_completed' => 0,
    'conversion_rate' => 0
];

// Process filters if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = [
        'report_type' => $_POST['report_type'] ?? 'sales',
        'date_range' => $_POST['date_range'] ?? '30',
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? ''
    ];
    
    // Generate report based on filters
    $report_data = generateReportData($filters);
    $stats = calculateStats($report_data);
}


// Fetch user role from database
$user_id = $_SESSION['user_id'];
// Using prepared statements to prevent SQL Injection
$stmt = $conn->prepare("SELECT role, name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();


if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $role = $user['role'];
    $user_name = $user['name'];
} else {
    // User not found in database, logout
    session_destroy();
    header("Location: index.php");
    exit();
}
$stmt->close();


// Fetch Stats
$total_customers = $conn->query("SELECT COUNT(*) as cnt FROM customers")->fetch_assoc()['cnt'];
$total_users = 0;
if ($role === 'admin') {
    $total_users = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
}

// Get tasks for the dashboard using the function from functions.php
$tasks = getTasks();
$open_tasks_count = 0;
foreach ($tasks as $task) {
    if ($task['status'] !== 'completed') {
        $open_tasks_count++;
    }
}

// Get recent activities
$recent_activities = getActivityLog($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /*
         * NOTE: All sidebar, body, and :root styles have been removed from this file.
         * They are now located in the self-contained `sidebar.php` file.
         * These are the styles SPECIFIC to the dashboard content.
        */
        :root {
            /* These variables are still defined in sidebar.php, but can be overridden or added to here if needed */
            --secondary: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background: white;
            padding: 18px 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-top:20px;
        }
        
        .card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            color: #444;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card h3 .view-all {
            font-size: 13px;
            color: var(--primary);
            text-decoration: none;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .stat-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
            transition: var(--transition);
            cursor: pointer;
        }
        
        .stat-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .stat-box h2 {
            margin: 0;
            font-size: 32px;
            color: var(--primary);
        }
        
        .stat-box p {
            margin: 6px 0 0;
            font-size: 14px;
            color: #555;
        }
        
        .activity-feed {
            list-style: none;
        }
        
        .activity-item {
            display: flex;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f0f5ff;
            color: var(--primary);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-content p {
            margin: 0;
            font-size: 14px;
        }
        
        .activity-time {
            font-size: 12px;
            color: var(--secondary);
        }
        
        .task-list {
            list-style: none;
        }
        
        .task-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .task-item:last-child {
            border-bottom: none;
        }
        
        .task-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .task-content {
            flex: 1;
        }
        
        .task-title {
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .task-meta {
            display: flex;
            gap: 10px;
            font-size: 12px;
            color: var(--secondary);
        }
        
        .task-priority {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        
        .priority-high {
            background: #ffecec;
            color: var(--danger);
        }
        
        .priority-medium {
            background: #fff4e6;
            color: var(--warning);
        }
        
        .priority-low {
            background: #e6f4ff;
            color: var(--primary);
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px;
            background: #f9fafb;
            border-radius: var(--border-radius);
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .action-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        .action-btn i {
            font-size: 20px;
            margin-bottom: 8px;
        }
        
        .action-btn span {
            font-size: 13px;
        }
        
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            .stats {
                grid-template-columns: 1fr;
            }
        }
        
        .toggle-sidebar {
            display: none;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            margin-bottom: 15px;
        }
        
        @media (max-width: 992px) {
            .toggle-sidebar {
                display: block;
            }
        }
        
        .status-pending {
            background: #fff4e6;
            color: #f39c12;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        
        .status-in-progress {
            background: #e6f4ff;
            color: #3498db;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        
        .status-completed {
            background: #e6f4e6;
            color: #27ae60;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        .notification {
            position: relative;
            cursor: pointer;
        }

        .notification .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            min-width: 18px;
            text-align: center;
            display: none; /* Initially hidden */
        }

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 300px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }

        .notification-dropdown.active {
            display: block;
        }

        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-item.unread {
            background: #f8f9fa;
            border-left: 3px solid #4a6cf7;
        }

        .notification-item.read {
            opacity: 0.7;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 4px;
            color: #333;
        }

        .notification-message {
            font-size: 14px;
            color: #666;
            margin-bottom: 4px;
        }

        .notification-time {
            font-size: 12px;
            color: #999;
        }

        .notification-empty {
            padding: 20px;
            text-align: center;
            color: #999;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <button class="toggle-sidebar" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="header">
            <h2>Welcome, <?php echo htmlspecialchars($user_name); ?> 👋</h2>
            <div class="header-actions">
                <div class="notification" id="notificationBell">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="notificationCount">0</span>
                    <div class="notification-dropdown" id="notificationDropdown"></div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($user_name); ?></div>
                        <div style="font-size: 12px; color: var(--secondary);"><?php echo ucfirst($role); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Quick Stats</h3>
            <div class="stats">
                <div class="stat-box">
                    <h2><?php echo $total_customers; ?></h2>
                    <p>Total Customers</p>
                </div>
                <?php if ($role === 'admin') : ?>
                <div class="stat-box">
                    <h2><?php echo $total_users; ?></h2>
                    <p>Total Users</p>
                </div>
                <?php endif; ?>
                <div class="stat-box">
                    <h2><?php echo $open_tasks_count; ?></h2>
                    <p>Open Tasks</p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h3>Customer Acquisition <a href="#" class="view-all">View Report</a></h3>
                <div class="chart-container">
                    <canvas id="acquisitionChart"></canvas>
                </div>
            </div>
            
           <div class="card">
                <h3>Recent Activity <a href="#" class="view-all">View All</a></h3>
                <ul class="activity-feed">
                    <?php if (!empty($recent_activities)) : ?>
                        <?php foreach ($recent_activities as $activity) : ?>
                            <li class="activity-item">
                                <div class="activity-icon">
                                    <?php
                                    $icon = 'fa-history'; // default
                                    switch($activity['activity_type']) {
                                        case 'login': $icon = 'fa-sign-in-alt'; break;
                                        case 'task_create': $icon = 'fa-tasks'; break;
                                        case 'task_complete': $icon = 'fa-check-circle'; break;
                                        case 'customer_add': $icon = 'fa-user-plus'; break;
                                        case 'customer_update': $icon = 'fa-user-edit'; break;
                                        case 'report_generate': $icon = 'fa-chart-bar'; break;
                                        case 'schedule': $icon = 'fa-calendar'; break;
                                    }
                                    ?>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <p><?php echo htmlspecialchars($activity['formatted_description'] ?? $activity['description']); ?></p>
                                    <div class="activity-time">
                                        <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="activity-content">
                                <p>No recent activity found</p>
                                <div class="activity-time">
                                    Activities will appear here as you use the system
                                </div>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>
                    <?php 
                    if ($role === 'admin') {
                        echo 'All Team Tasks';
                    } else {
                        echo 'My Assigned Tasks';
                    }
                    ?> 
                    <a href="task.php" class="view-all">View All</a>
                </h3>
                <ul class="task-list">
                    <?php if (!empty($tasks)) : ?>
                        <?php $displayed_tasks = 0; ?>
                        <?php foreach ($tasks as $task) : ?>
                            <?php if ($displayed_tasks < 3) : ?>
                                <li class="task-item">
                                    <input type="checkbox" class="task-checkbox" 
                                        <?php echo $task['status'] == 'completed' ? 'checked' : ''; ?> 
                                        onchange="updateTaskStatus(<?php echo $task['id']; ?>, this.checked ? 'completed' : 'pending')">
                                    <div class="task-content">
                                        <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                                        <div class="task-meta">
                                            <div>Due: <?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'No due date'; ?></div>
                                            <div class="task-priority priority-<?php echo $task['priority']; ?>">
                                                <?php echo ucfirst($task['priority']); ?>
                                            </div>
                                            <div class="status-<?php echo $task['status']; ?>">
                                                <?php echo ucfirst(str_replace('-', ' ', $task['status'])); ?>
                                            </div>
                                            <?php if ($role === 'admin') : ?>
                                                <div class="task-assignee">
                                                    <small>Assigned to: <?php echo htmlspecialchars($task['assigned_name']); ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                                <?php $displayed_tasks++; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li class="task-item">
                            <div class="task-content">
                                <div class="task-title">No tasks found</div>
                                <div class="task-meta">
                                    <div>
                                        <?php 
                                        if ($role === 'admin') {
                                            echo 'No tasks created yet';
                                        } else {
                                            echo 'No tasks assigned to you yet';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>


    <script>
    // Toggle sidebar on mobile
    document.getElementById('toggleSidebar').addEventListener('click', function () {
        document.querySelector('.sidebar').classList.toggle('active');
    });

    // Customer acquisition chart
    const acquisitionCtx = document.getElementById('acquisitionChart').getContext('2d');
    const acquisitionChart = new Chart(acquisitionCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
            datasets: [{
                label: 'New Customers',
                data: [12, 19, 15, 17, 22, 25, 28, 24, 30, 35],
                backgroundColor: 'rgba(74, 108, 247, 0.1)',
                borderColor: '#4a6cf7',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Task checkboxes
    document.querySelectorAll('.task-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            let taskItem = this.closest('.task-item');
            if (this.checked) {
                taskItem.style.opacity = '0.6';
                taskItem.querySelector('.task-title').style.textDecoration = 'line-through';
            } else {
                taskItem.style.opacity = '1';
                taskItem.querySelector('.task-title').style.textDecoration = 'none';
            }
        });
    });


    // Function to update task status
    function updateTaskStatus(taskId, status) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'update_task_status.php';

        const taskIdInput = document.createElement('input');
        taskIdInput.type = 'hidden';
        taskIdInput.name = 'task_id';
        taskIdInput.value = taskId;
        form.appendChild(taskIdInput);

        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        form.appendChild(statusInput);

        document.body.appendChild(form);
        form.submit();
    }
    // Load notifications and handle interactions
    function loadNotifications() {
        fetch('notifications.php')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('notificationCount');
                const dropdown = document.getElementById('notificationDropdown');

                badge.textContent = data.count;
                // Use 'flex' for display to match CSS for centering
                badge.style.display = data.count > 0 ? 'flex' : 'none';

                dropdown.innerHTML = '';
                
                if (data.notifications && data.notifications.length > 0) {
                    data.notifications.forEach(notification => {
                        const item = document.createElement('div');
                        item.className = `notification-item ${notification.is_read == 0 ? 'unread' : 'read'}`;
                        item.innerHTML = `
                            <div class="notification-title">${notification.title}</div>
                            <div class="notification-message">${notification.message}</div>
                            <div class="notification-time">${formatTime(notification.created_at)}</div>
                        `;
                        item.onclick = () => {
                            markNotificationAsRead(notification.id);
                            if (notification.related_type === 'task') {
                                window.location.href = 'task.php';
                            }
                        };
                        dropdown.appendChild(item);
                    });
                } else {
                    dropdown.innerHTML = '<div class="notification-empty">No notifications</div>';
                }
            })
            .catch(error => console.error('Error loading notifications:', error));
    }

    // Format time function
    function formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        
        return date.toLocaleDateString();
    }

    // Mark notification as read
    function markNotificationAsRead(notificationId) {
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${notificationId}`
        })
        .then(() => loadNotifications())
        .catch(error => console.error('Error marking notification as read:', error));
    }

    // Toggle notification dropdown
    document.getElementById('notificationBell').addEventListener('click', function(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('notificationDropdown');
        dropdown.classList.toggle('active');
        
        if (dropdown.classList.contains('active')) {
            fetch('mark_read.php', { method: 'POST' });
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notification')) {
            document.getElementById('notificationDropdown').classList.remove('active');
        }
    });

    // Load notifications on page load and refresh every 30 seconds
    document.addEventListener('DOMContentLoaded', () => {
        loadNotifications();
        setInterval(loadNotifications, 30000);
    });

</script>
</body>
</html>