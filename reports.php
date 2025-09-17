<?php
session_start();
include 'config.php';
include 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
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

// Get all reports for the current user
$user_reports = getReports($_SESSION['user_id']);
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
        <?php endif; ?>
        <a href="task.php"><i class="fas fa-tasks"></i> <span>Tasks</span></a>
        <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <button class="toggle-sidebar" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="header">
            <h2>Reports & Analytics</h2>
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
                        <option value="sales" <?= ($_POST['report_type'] ?? 'sales') === 'sales' ? 'selected' : '' ?>>Sales Performance</option>
                        <option value="customers" <?= ($_POST['report_type'] ?? '') === 'customers' ? 'selected' : '' ?>>Customer Analytics</option>
                        <option value="tasks" <?= ($_POST['report_type'] ?? '') === 'tasks' ? 'selected' : '' ?>>Task Completion</option>
                        <option value="users" <?= ($_POST['report_type'] ?? '') === 'users' ? 'selected' : '' ?>>User Activity</option>
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
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Customers</h3>
                <div class="value"><?= $stats['total_customers'] ?></div>
                <div class="change positive">+12% from last month</div>
            </div>
            <div class="stat-card">
                <h3>New Customers</h3>
                <div class="value"><?= $stats['new_customers'] ?></div>
                <div class="change positive">+8% from last month</div>
            </div>
            <div class="stat-card">
                <h3>Tasks Completed</h3>
                <div class="value"><?= $stats['tasks_completed'] ?>%</div>
                <div class="change positive">+5% from last month</div>
            </div>
            <div class="stat-card">
                <h3>Conversion Rate</h3>
                <div class="value"><?= $stats['conversion_rate'] ?>%</div>
                <div class="change negative">-2% from last month</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Customer Acquisition</h2>
                <div class="report-actions">
                    <button class="btn btn-outline"><i class="fas fa-table"></i> View Data</button>
                    <button class="btn btn-outline"><i class="fas fa-chart-bar"></i> Bar Chart</button>
                    <button class="btn btn-outline"><i class="fas fa-chart-line"></i> Line Chart</button>
                </div>
            </div>
            
            <div class="chart-container">
                <canvas id="acquisitionChart"></canvas>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Task Completion Rates</h2>
            </div>
            
            <div class="chart-container">
                <canvas id="taskChart"></canvas>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Report Data</h2>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>New Customers</th>
                        <th>Tasks Created</th>
                        <th>Tasks Completed</th>
                        <th>Conversion Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($report_data)): ?>
                        <?php foreach ($report_data as $data): ?>
                        <tr>
                            <td><?= $data['date'] ?></td>
                            <td><?= $data['new_customers'] ?></td>
                            <td><?= $data['tasks_created'] ?></td>
                            <td><?= $data['tasks_completed'] ?></td>
                            <td><?= $data['conversion_rate'] ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No data available. Apply filters to generate a report.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="pagination">
                <button>1</button>
                <button>2</button>
                <button class="active">3</button>
                <button>4</button>
                <button>5</button>
            </div>
        </div>
        
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

    </script>
</body>
</html>