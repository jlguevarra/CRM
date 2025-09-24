<?php
session_start();
include 'config.php';
include 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user role from database
$sql = "SELECT role, name FROM users WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $role = $user['role'];
    $user_name = $user['name'];
} else {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Check if user is admin
if ($role !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Initialize variables
$report_data = [];
$stats = [
    'total_customers' => 0, 'new_customers' => 0, 'tasks_completed' => 0,
    'conversion_rate' => 0, 'tasks_created' => 0, 'total_users' => 0
];
$report_type = $_POST['report_type'] ?? 'sales';

// Process filters if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = [
        'report_type' => $_POST['report_type'] ?? 'sales',
        'date_range' => $_POST['date_range'] ?? '30',
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? ''
    ];
    
    $report_data = generateReportData($filters);
    $stats = calculateStats($report_data, $report_type);
}

$user_reports = getReports($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - CRM System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Styles for the Reports page content, matching the dashboard theme */
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
        .header h2 { margin: 0; font-size: 24px; color: #333; }
        .header-actions { display: flex; align-items: center; gap: 15px; }
        .user-profile { display: flex; align-items: center; gap: 10px; }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--primary); color: white;
            display: flex; justify-content: center; align-items: center;
            font-weight: bold;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: flex-end;
        }
        .filter-item { display: flex; flex-direction: column; }
        .filter-item label { margin-bottom: 8px; font-size: 14px; color: #555; font-weight: 500;}
        .filter-item select, .filter-item input {
            padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;
        }
        .btn {
            padding: 10px 20px; border: none; border-radius: 6px;
            cursor: pointer; font-size: 14px; font-weight: 500;
        }
        .btn-primary { background-color: var(--primary); color: white; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white; padding: 20px; border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        .stat-card h3 { margin: 0 0 10px 0; font-size: 16px; color: #555; }
        .stat-card .value { font-size: 32px; font-weight: 600; color: var(--dark); }
        .stat-card .change { font-size: 14px; margin-top: 5px; }
        .change.positive { color: var(--success); }
        .change.negative { color: var(--danger); }

        .data-table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden; /* Ensures border-radius is respected */
        }
        .data-table th, .data-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background: #f8f9fa; color: #555; font-size: 14px;
            font-weight: 600; text-transform: uppercase;
        }
        .data-table tr:hover { background-color: #f9fafb; }

        .card {
             background: white; padding: 25px; border-radius: var(--border-radius); 
             box-shadow: var(--box-shadow); margin-bottom: 25px; margin-top: 25px;
        }
        .card-header h2 { margin: 0 0 20px 0; font-size: 20px; }
        .report-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 0; border-bottom: 1px solid #eee;
        }
        .report-item:last-child { border-bottom: none; }
        .report-actions { display: flex; gap: 10px; }
        .btn-outline { background: #f1f5f9; color: #334155; }
        
        /* Notification Styles */
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
            display: none;
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
        .notification-dropdown.active { display: block; }
        .notification-item {
            padding: 12px 15px; border-bottom: 1px solid #f0f0f0;
            cursor: pointer; transition: background 0.2s;
        }
        .notification-item:hover { background: #f8f9fa; }
        .notification-item.unread { background: #f8f9fa; border-left: 3px solid #4a6cf7; }
        .notification-item.read { opacity: 0.7; }
        .notification-title { font-weight: 600; margin-bottom: 4px; color: #333; }
        .notification-message { font-size: 14px; color: #666; margin-bottom: 4px; }
        .notification-time { font-size: 12px; color: #999; }
        .notification-empty { padding: 20px; text-align: center; color: #999; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h2>Reports</h2>
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
        
        <form method="POST" action="">
            <div class="filters">
                <div class="filter-item">
                    <label for="reportType">Report Type</label>
                    <select id="reportType" name="report_type">
                        <option value="sales" <?= $report_type === 'sales' ? 'selected' : '' ?>>Sales Performance</option>
                        <option value="customers" <?= $report_type === 'customers' ? 'selected' : '' ?>>Customer Analytics</option>
                        <option value="tasks" <?= $report_type === 'tasks' ? 'selected' : '' ?>>Task Completion</option>
                        <option value="users" <?= $report_type === 'users' ? 'selected' : '' ?>>User Activity</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="dateRange">Date Range</label>
                    <select id="dateRange" name="date_range">
                        <option value="7" <?= ($_POST['date_range'] ?? '30') === '7' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="30" <?= ($_POST['date_range'] ?? '30') === '30' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="90" <?= ($_POST['date_range'] ?? '') === '90' ? 'selected' : '' ?>>Last 90 Days</option>
                        <option value="custom" <?= ($_POST['date_range'] ?? '') === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="startDate">Start Date</label>
                    <input type="date" id="startDate" name="start_date" value="<?= $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days')) ?>">
                </div>
                <div class="filter-item">
                    <label for="endDate">End Date</label>
                    <input type="date" id="endDate" name="end_date" value="<?= $_POST['end_date'] ?? date('Y-m-d') ?>">
                </div>
                <div class="filter-item">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </div>
        </form>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= ($report_type === 'tasks') ? 'Total Tasks' : (($report_type === 'users') ? 'Total Users' : 'Total Customers') ?></h3>
                <div class="value"><?= ($report_type === 'tasks') ? ($stats['tasks_created'] ?? 0) : (($report_type === 'users') ? ($stats['total_users'] ?? 0) : ($stats['total_customers'] ?? 0)) ?></div>
                <div class="change positive">Based on selected filters</div>
            </div>
            <div class="stat-card">
                <h3><?= ($report_type === 'tasks' || $report_type === 'users') ? 'Tasks Completed' : 'New Customers' ?></h3>
                <div class="value"><?= ($report_type === 'tasks' || $report_type === 'users') ? ($stats['tasks_completed'] ?? 0) : ($stats['new_customers'] ?? 0) ?></div>
                <div class="change positive">During period</div>
            </div>
            <div class="stat-card">
                <h3>Completion Rate</h3>
                <div class="value"><?= $stats['conversion_rate'] ?? 0 ?>%</div>
                <div class="change <?= ($stats['conversion_rate'] ?? 0) >= 70 ? 'positive' : 'negative' ?>"><?= ($stats['conversion_rate'] ?? 0) >= 70 ? 'Good' : 'Needs improvement' ?></div>
            </div>
            <div class="stat-card">
                <h3>Data Points</h3>
                <div class="value"><?= count($report_data) ?></div>
                <div class="change positive">Records found</div>
            </div>
        </div>
    
        <table class="data-table">
            <thead>
                <tr>
                    <?php if ($report_type === 'users'): ?>
                        <th>User Name</th><th>Total Tasks</th><th>Completed Tasks</th><th>Completion Rate</th>
                    <?php else: ?>
                        <th>Date</th>
                        <?php if (in_array($report_type, ['sales', 'customers'])): ?>
                            <th>New Customers</th>
                            <?php if ($report_type === 'customers'): ?><th>Total Customers</th><?php endif; ?>
                        <?php elseif ($report_type === 'tasks'): ?>
                            <th>Tasks Created</th><th>Tasks Completed</th><th>Completion Rate</th>
                        <?php endif; ?>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($report_data)): foreach ($report_data as $data): ?>
                <tr>
                    <?php if ($report_type === 'users'): ?>
                        <td><?= htmlspecialchars($data['user_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($data['total_tasks'] ?? '0') ?></td>
                        <td><?= htmlspecialchars($data['completed_tasks'] ?? '0') ?></td>
                        <td><?= htmlspecialchars($data['completion_rate'] ?? '0') ?>%</td>
                    <?php else: ?>
                        <td><?= htmlspecialchars($data['date'] ?? '') ?></td>
                        <?php if (in_array($report_type, ['sales', 'customers'])): ?>
                            <td><?= htmlspecialchars($data['new_customers'] ?? '0') ?></td>
                            <?php if ($report_type === 'customers'): ?><td><?= htmlspecialchars($data['total_customers'] ?? '0') ?></td><?php endif; ?>
                        <?php elseif ($report_type === 'tasks'): ?>
                            <td><?= htmlspecialchars($data['tasks_created'] ?? '0') ?></td>
                            <td><?= htmlspecialchars($data['tasks_completed'] ?? '0') ?></td>
                            <td><?= htmlspecialchars($data['completion_rate'] ?? '0') ?>%</td>
                        <?php endif; ?>
                    <?php endif; ?>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="4" style="text-align: center;">
                        <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'No data found for the selected filters.' : 'Apply filters to generate a report.'; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="card saved-reports">
            <div class="card-header">
                <h2>Saved Reports</h2>
            </div>
            <?php if (!empty($user_reports)): foreach ($user_reports as $report): ?>
            <div class="report-item">
                <div>
                    <h3><?= htmlspecialchars($report['title']) ?></h3>
                    <p><?= htmlspecialchars($report['description']) ?></p>
                    <small>Created: <?= date('M j, Y', strtotime($report['created_at'])) ?></small>
                </div>
                <div class="report-actions">
                    <button class="btn btn-outline">View</button>
                    <button class="btn btn-outline">Export</button>
                    <button class="btn btn-outline">Delete</button>
                </div>
            </div>
            <?php endforeach; else: ?>
                <div class="report-item"><p>No saved reports yet.</p></div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // JS for filter functionality
        const dateRange = document.getElementById('dateRange');
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        
        dateRange.addEventListener('change', function() {
            const isCustom = this.value === 'custom';
            startDate.disabled = !isCustom;
            endDate.disabled = !isCustom;
            
            if (!isCustom) {
                const today = new Date();
                const days = parseInt(this.value);
                const start = new Date();
                start.setDate(today.getDate() - days);
                startDate.value = start.toISOString().split('T')[0];
                endDate.value = today.toISOString().split('T')[0];
            }
        });
        
        window.addEventListener('load', function() {
            if (dateRange.value !== 'custom') {
                startDate.disabled = true;
                endDate.disabled = true;
            }
        });

        // JS for notifications
        function loadNotifications() {
            fetch('notifications.php')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('notificationCount');
                    const dropdown = document.getElementById('notificationDropdown');
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'flex' : 'none';
                    dropdown.innerHTML = '';
                    if (data.notifications && data.notifications.length > 0) {
                        data.notifications.forEach(notification => {
                            const item = document.createElement('div');
                            item.className = `notification-item ${notification.is_read == 0 ? 'unread' : 'read'}`;
                            item.innerHTML = `<div class="notification-title">${notification.title}</div><div class="notification-message">${notification.message}</div><div class="notification-time">${formatTime(notification.created_at)}</div>`;
                            item.onclick = () => {
                                markNotificationAsRead(notification.id);
                                if (notification.related_type === 'task') { window.location.href = 'task.php'; }
                            };
                            dropdown.appendChild(item);
                        });
                    } else {
                        dropdown.innerHTML = '<div class="notification-empty">No notifications</div>';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            const diffHours = Math.floor(diffMs / 3600000);
            if (diffHours < 24) return `${diffHours}h ago`;
            const diffDays = Math.floor(diffMs / 86400000);
            if (diffDays < 7) return `${diffDays}d ago`;
            return date.toLocaleDateString();
        }

        function markNotificationAsRead(notificationId) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${notificationId}`
            }).then(() => loadNotifications());
        }

        document.getElementById('notificationBell').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('notificationDropdown').classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.notification')) {
                document.getElementById('notificationDropdown').classList.remove('active');
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            loadNotifications();
            setInterval(loadNotifications, 30000);
        });
    </script>
</body>
</html>