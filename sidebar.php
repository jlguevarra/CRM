<style>
    /* CSS for the sidebar and the main page layout. */
    :root {
        --primary: #4a6cf7;
        --primary-dark: #3a5ad9;
        --secondary: #6c757d;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --light: #f8f9fa;
        --dark: #343a40;
        --sidebar-width: 230px;
        --border-radius: 12px;
        --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
    }

    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        min-height: 100vh;
        background: #f3f4f6;
    }

    .sidebar {
        width: var(--sidebar-width);
        background: var(--primary);
        color: white;
        display: flex;
        flex-direction: column;
        padding-top: 20px;
        transition: var(--transition);
        z-index: 1000;
        flex-shrink: 0;
    }

    .sidebar h2 {
        text-align: center;
        margin-bottom: 30px;
        font-size: 22px;
        padding: 0 15px;
    }

    .sidebar a {
        color: white;
        padding: 12px 20px;
        text-decoration: none;
        display: flex;
        align-items: center;
        transition: var(--transition);
        border-radius: 6px;
        margin: 4px 10px;
    }

    .sidebar a i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .sidebar a:hover {
        background: var(--primary-dark);
    }

    .sidebar a.active {
        background: white;
        color: var(--primary);
    }
    
    .main-content {
        flex: 1;
        overflow-y: auto;
        padding: 25px;
    }
    
    @media (max-width: 992px) {
        .sidebar {
            width: 70px;
            align-items: center;
        }
        .sidebar h2 {
            font-size: 16px;
        }
        .sidebar a span {
            display: none;
        }
        .sidebar a i {
            margin-right: 0;
            font-size: 18px;
        }
    }
</style>

<?php
// This PHP logic gets the current page's filename to highlight the active link
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <h2>CRM</h2>
    
    <a href="dashboard.php" class="<?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> <span>Dashboard</span>
    </a>
    
    <a href="customers.php" class="<?php echo ($currentPage == 'customers.php' || $currentPage == 'edit_customer.php') ? 'active' : ''; ?>">
        <i class="fas fa-users"></i> <span>Customers</span>
    </a>

    <?php if (isset($role) && $role === 'admin') : ?>
        <a href="users.php" class="<?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-cog"></i> <span>Users</span>
        </a>
        <a href="reports.php" class="<?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> <span>Reports</span>
        </a>
        <a href="settings.php" class="<?php echo ($currentPage == 'settings.php') ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> <span>Settings</span>
        </a>
    <?php endif; ?>

    <a href="task.php" class="<?php echo ($currentPage == 'task.php') ? 'active' : ''; ?>">
        <i class="fas fa-tasks"></i> <span>Tasks</span>
    </a>
    
    <a href="logout.php">
        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
    </a>
</div>