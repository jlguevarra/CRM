<?php
ob_start();
session_start();
include 'config.php';
include 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role, name FROM users WHERE id = ? LIMIT 1");
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
$stmt->close();

$total_customers = $conn->query("SELECT COUNT(*) as cnt FROM customers")->fetch_assoc()['cnt'];
$total_users = 0;
if ($role === 'admin') {
    $total_users = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
}
$tasks = getTasks();
$open_tasks_count = 0;
foreach ($tasks as $task) {
    if ($task['status'] !== 'completed') {
        $open_tasks_count++;
    }
}
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
        .header {
            background: white; padding: 18px 25px; border-radius: var(--border-radius);
            box-shadow: var(--box-shadow); margin-bottom: 25px; display: flex;
            justify-content: space-between; align-items: center;
        }
        .header h2 { margin: 0; font-size: 24px; color: #333; }
        .header-actions { display: flex; align-items: center; gap: 20px; }
        .user-profile { display: flex; align-items: center; gap: 10px; }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%; background: var(--primary);
            color: white; display: flex; justify-content: center;
            align-items: center; font-weight: bold;
        }
        .dashboard-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px; margin-bottom: 25px;
        }
        .card {
            background: white; padding: 25px; border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        .card h3 {
            margin-top: 0; margin-bottom: 20px; font-size: 18px; color: #444;
            display: flex; justify-content: space-between; align-items: center;
        }
        .card h3 .view-all { font-size: 13px; color: var(--primary); text-decoration: none; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; }
        a.stat-box { text-decoration: none; color: inherit; }
        .stat-box {
            background: #f9fafb; padding: 20px; border-radius: var(--border-radius);
            text-align: center; transition: var(--transition);
        }
        .stat-box:hover { transform: translateY(-3px); box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .stat-box h2 { margin: 0; font-size: 32px; color: var(--primary); }
        .stat-box p { margin: 6px 0 0; font-size: 14px; color: #555; }
        .activity-feed { list-style: none; padding: 0; }
        .activity-item { display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid #eee; }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon {
            width: 36px; height: 36px; border-radius: 50%; background: #f0f5ff;
            color: var(--primary); display: flex; justify-content: center; align-items: center; flex-shrink: 0;
        }
        .activity-content p { margin: 0 0 4px 0; font-size: 14px; }
        .activity-time { font-size: 12px; color: var(--secondary); }
        .task-list { list-style: none; padding: 0; }
        .task-item { display: flex; align-items: center; gap: 10px; padding: 12px 0; border-bottom: 1px solid #eee; }
        .task-item:last-child { border-bottom: none; }
        .task-checkbox { width: 18px; height: 18px; cursor: pointer; }
        .task-title { font-size: 14px; margin-bottom: 4px; }
        .task-meta { display: flex; gap: 10px; font-size: 12px; color: var(--secondary); }
        .task-priority { padding: 2px 8px; border-radius: 10px; font-size: 11px; }
        .priority-high { background: #ffecec; color: var(--danger); }
        .priority-medium { background: #fff4e6; color: var(--warning); }
        .priority-low { background: #e6f4ff; color: var(--primary); }
        .chart-container { position: relative; height: 250px; width: 100%; }
        
        /* Notification Styles */
        .notification { position: relative; cursor: pointer; }
        .notification .badge {
            position: absolute; top: -8px; right: -8px; background: var(--danger); color: white;
            border-radius: 50%; width: 18px; height: 18px; font-size: 11px;
            display: flex; justify-content: center; align-items: center;
        }
        .notification-dropdown {
            position: absolute; top: 100%; right: 0; width: 320px; background: white;
            border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-height: 400px; overflow-y: auto; display: none; z-index: 1000;
        }
        .notification-dropdown.active { display: block; }
        .notification-item { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.2s; }
        .notification-item:hover { background: #f8f9fa; }
        .notification-item.unread { background: #f8f9fa; border-left: 3px solid var(--primary); }
        .notification-title { font-weight: 600; margin-bottom: 4px; color: #333; }
        .notification-message { font-size: 14px; color: #666; margin-bottom: 4px; }
        .notification-time { font-size: 12px; color: #999; }
        .notification-empty { padding: 20px; text-align: center; color: #999; }
        
        /* --- STYLES FOR NEW BUTTON --- */
        .notification-footer { padding: 8px; text-align: center; border-top: 1px solid #f0f0f0; }
        .mark-all-read-btn {
            background: none; border: none; color: var(--primary); font-weight: 500;
            cursor: pointer; font-size: 13px; padding: 5px;
        }
        .mark-all-read-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
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
                <a href="customers.php" class="stat-box">
                    <h2><?php echo $total_customers; ?></h2>
                    <p>Total Customers</p>
                </a>
                <?php if ($role === 'admin') : ?>
                <a href="users.php" class="stat-box">
                    <h2><?php echo $total_users; ?></h2>
                    <p>Total Users</p>
                </a>
                <?php endif; ?>
                <a href="task.php" class="stat-box">
                    <h2><?php echo $open_tasks_count; ?></h2>
                    <p>Open Tasks</p>
                </a>
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
                                <div class="activity-icon"><i class="fas fa-history"></i></div>
                                <div class="activity-content">
                                    <p><?php echo htmlspecialchars($activity['formatted_description'] ?? $activity['description']); ?></p>
                                    <div class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else : ?>
                         <li class="activity-item">
                            <div class="activity-icon"><i class="fas fa-info-circle"></i></div>
                            <div class="activity-content"><p>No recent activity found</p></div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

    </div>

    <script>
    // Chart.js setup
    const acquisitionCtx = document.getElementById('acquisitionChart').getContext('2d');
    new Chart(acquisitionCtx, {
        type: 'line', data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            datasets: [{
                label: 'New Customers', data: [12, 19, 15, 17, 22, 25, 28],
                backgroundColor: 'rgba(74, 108, 247, 0.1)', borderColor: '#4a6cf7',
                borderWidth: 2, tension: 0.3, fill: true
            }]
        }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    // --- NOTIFICATION JAVASCRIPT --- //
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    // --- NEW FUNCTION ---
    function markAllAsRead() {
        fetch('mark_all_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                loadNotifications(); // Refresh the list
            }
        })
        .catch(error => console.error('Error marking all as read:', error));
    }

    function loadNotifications() {
        fetch('notifications.php')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('notificationCount');
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'flex' : 'none';
                notificationDropdown.innerHTML = '';
                
                if (data.notifications && data.notifications.length > 0) {
                    data.notifications.forEach(notification => {
                        const item = document.createElement('div');
                        item.className = `notification-item ${notification.is_read == 0 ? 'unread' : 'read'}`;
                        item.innerHTML = `<div class="notification-title">${notification.title}</div><div class="notification-message">${notification.message}</div><div class="notification-time">${formatTime(notification.created_at)}</div>`;
                        item.onclick = () => {
                            // You would need a mark_one_read.php script for this part
                            if (notification.related_type === 'task') { window.location.href = 'task.php'; }
                        };
                        notificationDropdown.appendChild(item);
                    });

                    // --- MODIFIED: Add the footer with the button if there are unread notifications ---
                    if (data.count > 0) {
                        const footer = document.createElement('div');
                        footer.className = 'notification-footer';
                        footer.innerHTML = `<button class="mark-all-read-btn">Mark all as read</button>`;
                        notificationDropdown.appendChild(footer);
                        
                        // Attach event listener to the new button
                        footer.querySelector('.mark-all-read-btn').addEventListener('click', markAllAsRead);
                    }

                } else {
                    notificationDropdown.innerHTML = '<div class="notification-empty">No new notifications</div>';
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function formatTime(dateString) {
        const date = new Date(dateString); const now = new Date();
        const diffMs = now - date; const diffMins = Math.floor(diffMs / 60000);
        if (diffMins < 1) return 'Just now'; if (diffMins < 60) return `${diffMins}m ago`;
        const diffHours = Math.floor(diffMs / 3600000); if (diffHours < 24) return `${diffHours}h ago`;
        const diffDays = Math.floor(diffMs / 86400000); if (diffDays < 7) return `${diffDays}d ago`;
        return date.toLocaleDateString();
    }

    notificationBell.addEventListener('click', e => {
        e.stopPropagation(); notificationDropdown.classList.toggle('active');
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('.notification')) {
            notificationDropdown.classList.remove('active');
        }
    });
    document.addEventListener('DOMContentLoaded', () => {
        loadNotifications(); setInterval(loadNotifications, 30000);
    });
    </script>
</body>
</html>