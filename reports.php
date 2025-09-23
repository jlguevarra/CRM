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
    'total_customers' => 0,
    'new_customers' => 0,
    'tasks_completed' => 0,
    'conversion_rate' => 0,
    'tasks_created' => 0,
    'total_users' => 0
];
$report_type = 'sales'; // Default report type

// Process filters if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = [
        'report_type' => $_POST['report_type'] ?? 'sales',
        'date_range' => $_POST['date_range'] ?? '30',
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? ''
    ];
    
    $report_type = $filters['report_type'];
    
    // Generate report based on filters
    $report_data = generateReportData($filters);
    $stats = calculateStats($report_data, $report_type);
}

// Get all reports for the current user
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
    <link rel="stylesheet" href="reports/reports.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>CRM</h2>
        <a href="dashboard.php"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a>
        <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
        <?php if ($role === 'admin') : ?>
            <a href="users.php"><i class="fas fa-user-cog"></i> <span>Users</span></a>
            <a href="reports.php" class="active"><i class="fas fa-chart-pie"></i> <span>Reports</span></a>
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
                        <option value="sales" <?= ($report_type ?? 'sales') === 'sales' ? 'selected' : '' ?>>Sales Performance</option>
                        <option value="customers" <?= ($report_type ?? '') === 'customers' ? 'selected' : '' ?>>Customer Analytics</option>
                        <option value="tasks" <?= ($report_type ?? '') === 'tasks' ? 'selected' : '' ?>>Task Completion</option>
                        <option value="users" <?= ($report_type ?? '') === 'users' ? 'selected' : '' ?>>User Activity</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="dateRange">Date Range</label>
                    <select id="dateRange" name="date_range">
                        <option value="7" <?= ($_POST['date_range'] ?? '30') === '7' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="30" <?= ($_POST['date_range'] ?? '30') === '30' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="90" <?= ($_POST['date_range'] ?? '') === '90' ? 'selected' : '' ?>>Last 90 Days</option>
                        <option value="365" <?= ($_POST['date_range'] ?? '') === '365' ? 'selected' : '' ?>>Last Year</option>
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
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </div>
        </form>
        
        <!-- Updated Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>
                    <?php 
                    if ($report_type === 'tasks') {
                        echo 'Total Tasks';
                    } elseif ($report_type === 'users') {
                        echo 'Total Users';
                    } else {
                        echo 'Total Customers';
                    }
                    ?>
                </h3>
                <div class="value">
                    <?php 
                    if ($report_type === 'tasks') {
                        echo $stats['tasks_created'] ?? 0;
                    } elseif ($report_type === 'users') {
                        echo $stats['total_users'] ?? 0;
                    } else {
                        echo $stats['total_customers'] ?? 0;
                    }
                    ?>
                </div>
                <div class="change positive">Based on selected filters</div>
            </div>
            
            <div class="stat-card">
                <h3>
                    <?php 
                    if ($report_type === 'tasks') {
                        echo 'Tasks Completed';
                    } elseif ($report_type === 'users') {
                        echo 'Tasks Completed';
                    } else {
                        echo 'New Customers';
                    }
                    ?>
                </h3>
                <div class="value">
                    <?php 
                    if ($report_type === 'tasks' || $report_type === 'users') {
                        echo $stats['tasks_completed'] ?? 0;
                    } else {
                        echo $stats['new_customers'] ?? 0;
                    }
                    ?>
                </div>
                <div class="change positive">During period</div>
            </div>
            
            <div class="stat-card">
                <h3>Completion Rate</h3>
                <div class="value"><?= $stats['conversion_rate'] ?? 0 ?>%</div>
                <div class="change <?= ($stats['conversion_rate'] ?? 0) >= 70 ? 'positive' : 'negative' ?>">
                    <?= ($stats['conversion_rate'] ?? 0) >= 70 ? 'Good' : 'Needs improvement' ?>
                </div>
            </div>
            
            <div class="stat-card">
                <h3>Data Points</h3>
                <div class="value"><?= count($report_data) ?></div>
                <div class="change positive">Records found</div>
            </div>
        </div>
        
        <!-- Rest of your HTML content remains the same -->
        <!-- ... -->
    
    <table class="data-table">
        <thead>
            <tr>
                <?php if ($report_type === 'users'): ?>
                    <th>User Name</th>
                    <th>Total Tasks</th>
                    <th>Completed Tasks</th>
                    <th>Completion Rate</th>
                <?php else: ?>
                    <th>Date</th>
                    <?php if (in_array($report_type, ['sales', 'customers'])): ?>
                        <th>New Customers</th>
                        <?php if ($report_type === 'customers'): ?>
                            <th>Total Customers</th>
                        <?php endif; ?>
                    <?php elseif ($report_type === 'tasks'): ?>
                        <th>Tasks Created</th>
                        <th>Tasks Completed</th>
                        <th>Completion Rate</th>
                    <?php else: ?>
                        <th>New Customers</th>
                    <?php endif; ?>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($report_data)): ?>
                <?php foreach ($report_data as $data): ?>
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
                            <?php if ($report_type === 'customers'): ?>
                                <td><?= htmlspecialchars($data['total_customers'] ?? '0') ?></td>
                            <?php endif; ?>
                        <?php elseif ($report_type === 'tasks'): ?>
                            <td><?= htmlspecialchars($data['tasks_created'] ?? '0') ?></td>
                            <td><?= htmlspecialchars($data['tasks_completed'] ?? '0') ?></td>
                            <td><?= htmlspecialchars($data['completion_rate'] ?? '0') ?>%</td>
                        <?php else: ?>
                            <td><?= htmlspecialchars($data['new_customers'] ?? '0') ?></td>
                        <?php endif; ?>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
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
            
            <?php if (!empty($user_reports)): ?>
                <?php foreach ($user_reports as $report): ?>
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
                <?php endforeach; ?>
            <?php else: ?>
                <div class="report-item">
                    <p>No saved reports yet.</p>
                </div>
            <?php endif; ?>
        </div>
</div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Customer Acquisition Chart
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
        
        // Task Completion Chart
        const taskCtx = document.getElementById('taskChart').getContext('2d');
        const taskChart = new Chart(taskCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [
                    {
                        label: 'Tasks Created',
                        data: [45, 52, 48, 60, 65, 70, 78, 75, 80, 85],
                        backgroundColor: 'rgba(74, 108, 247, 0.7)',
                        borderColor: '#4a6cf7',
                        borderWidth: 1
                    },
                    {
                        label: 'Tasks Completed',
                        data: [38, 45, 42, 52, 58, 62, 70, 68, 72, 78],
                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                        borderColor: '#28a745',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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
        
        // Filter functionality
        const dateRange = document.getElementById('dateRange');
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        
        dateRange.addEventListener('change', function() {
            if (this.value === 'custom') {
                startDate.disabled = false;
                endDate.disabled = false;
            } else {
                startDate.disabled = true;
                endDate.disabled = true;
                
                // Set dates based on selection
                const today = new Date();
                const days = parseInt(this.value);
                const start = new Date();
                start.setDate(today.getDate() - days);
                
                startDate.value = start.toISOString().split('T')[0];
                endDate.value = today.toISOString().split('T')[0];
            }
        });
        
        // Initialize date inputs
        window.addEventListener('load', function() {
            const today = new Date();
            const start = new Date();
            start.setDate(today.getDate() - 30);
            
            // Only set values if they're not already set by PHP
            if (!startDate.value) startDate.value = start.toISOString().split('T')[0];
            if (!endDate.value) endDate.value = today.toISOString().split('T')[0];
            
            // Enable/disable based on current selection
            if (dateRange.value !== 'custom') {
                startDate.disabled = true;
                endDate.disabled = true;
            }
        });

        function loadNotifications() {
            fetch('notifications.php')
                .then(res => res.json())
                .then(data => {
                    const count = data.count;
                    const badge = document.getElementById('notificationCount');
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'inline-block' : 'none';
                });
        }

        // Load notifications on page load
        loadNotifications();

        // Update charts with real data
function updateChartsWithRealData(reportType, reportData) {
    if (reportType === 'sales' || reportType === 'customers') {
        // Update customer acquisition chart
        const dates = reportData.map(item => item.date);
        const newCustomers = reportData.map(item => parseInt(item.new_customers) || 0);
        
        acquisitionChart.data.labels = dates;
        acquisitionChart.data.datasets[0].data = newCustomers;
        acquisitionChart.update();
    }
    
    if (reportType === 'tasks') {
        // Update task chart
        const dates = reportData.map(item => item.date);
        const tasksCreated = reportData.map(item => parseInt(item.tasks_created) || 0);
        const tasksCompleted = reportData.map(item => parseInt(item.tasks_completed) || 0);
        
        taskChart.data.labels = dates;
        taskChart.data.datasets[0].data = tasksCreated;
        taskChart.data.datasets[1].data = tasksCompleted;
        taskChart.update();
    }
}

// Call this function after form submission
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($report_data)): ?>
document.addEventListener('DOMContentLoaded', function() {
    updateChartsWithRealData('<?php echo $report_type ?>', <?php echo json_encode($report_data) ?>);
});
<?php endif; ?>
    </script>
</body>
</html>