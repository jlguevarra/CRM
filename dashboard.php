<?php
// Start session at the very top with output buffering
ob_start();
session_start();

//echo $_SESSION['user_id']; // should output 3

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
$sql = "SELECT role, name FROM users WHERE id='$user_id' LIMIT 1";
$result = $conn->query($sql);

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
     <link rel="stylesheet" href="dashboard/dashboard.css">
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>CRM</h2>
        <a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a>
        <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
       <?php if ($role === 'admin') : ?>
            <a href="users.php"><i class="fas fa-user-cog"></i> <span>Users</span></a>
            <a href="reports.php"><i class="fas fa-chart-pie"></i> <span>Reports</span></a>
            <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <?php endif; ?>
        <a href="task.php"><i class="fas fa-tasks"></i> <span>Tasks</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>

    <!-- Main Content -->
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

        <!-- Quick Stats -->
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
                <!-- <div class="stat-box">
                    <h2><div class="value"><?= $stats['conversion_rate'] ?>%</div></h2>
                    <p>Conversion Rate</p>
                </div> -->

            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Customer Acquisition Chart -->
            <div class="card">
                <h3>Customer Acquisition <a href="#" class="view-all">View Report</a></h3>
                <div class="chart-container">
                    <canvas id="acquisitionChart"></canvas>
                </div>
            </div>
            
           <!-- Recent Activity -->
            <div class="card">
                <h3>Recent Activity <a href="#" class="view-all">View All</a></h3>
                <ul class="activity-feed">
                    <?php 
                    // Get recent activities with error handling
                    $recent_activities = getActivityLog($user_id, 5);
                    
                    if (!empty($recent_activities)) : ?>
                        <?php foreach ($recent_activities as $activity) : ?>
                            <li class="activity-item">
                                <div class="activity-icon">
                                    <?php
                                    // Different icons based on activity type
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
            <!-- Tasks -->
           <!-- Tasks -->
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
            
            <!-- Quick Actions
            <div class="card">
                <h3>Quick Actions</h3>
                <div class="quick-actions">
                    <div class="action-btn" onclick="window.location.href='customers.php?action=add'">
                        <i class="fas fa-plus"></i>
                        <span>Add Customer</span>
                    </div>
                    <div class="action-btn" onclick="window.location.href='task.php?action=add'">
                        <i class="fas fa-tasks"></i>
                        <span>Create Task</span>
                    </div>
                    <div class="action-btn">
                        <i class="fas fa-file-invoice"></i>
                        <span>New Report</span>
                    </div>
                    <div class="action-btn">
                        <i class="fas fa-calendar"></i>
                        <span>Schedule</span>
                    </div>
                </div>
            </div>
        </div>
    </div> -->


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
            if (this.checked) {
                this.parentElement.style.opacity = '0.6';
                this.parentElement.style.textDecoration = 'line-through';
            } else {
                this.parentElement.style.opacity = '1';
                this.parentElement.style.textDecoration = 'none';
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
// Load notifications and handle interactions
function loadNotifications() {
    console.log("Loading notifications...");
    
    fetch('notifications.php')
        .then(res => {
            console.log("Notification response status:", res.status);
            return res.json();
        })
        .then(data => {
            console.log("Notifications data received:", data);
            
            const badge = document.getElementById('notificationCount');
            const dropdown = document.getElementById('notificationDropdown');

            // Update badge count
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? 'inline-block' : 'none';

            // Update dropdown content
            dropdown.innerHTML = '';
            
            if (data.notifications && data.notifications.length > 0) {
                console.log("Displaying", data.notifications.length, "notifications");
                data.notifications.forEach(notification => {
                    const item = document.createElement('div');
                    item.className = `notification-item ${notification.is_read ? 'read' : 'unread'}`;
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
                console.log("No notifications to display");
                dropdown.innerHTML = '<div class="notification-empty">No notifications</div>';
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
        });
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
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${notificationId}`
    })
    .then(response => response.text())
    .then(() => {
        loadNotifications(); // Reload notifications
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

// Toggle notification dropdown
document.getElementById('notificationBell').addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('active');
    
    // Mark all as read when dropdown is opened
    if (dropdown.classList.contains('active')) {
        fetch('mark_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        });
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.notification')) {
        document.getElementById('notificationDropdown').classList.remove('active');
    }
});

// Load notifications on page load and refresh every 30 seconds
loadNotifications();
setInterval(loadNotifications, 30000);


</script>





</body>
</html>