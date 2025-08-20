<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// fetch role from database
$user_id = $_SESSION['user_id'];
$sql = "SELECT role FROM users WHERE id='$user_id' LIMIT 1";
$result = $conn->query($sql);
$user    = $result->fetch_assoc();
$role    = $user['role'];

// --- Fetch Stats ---
$total_customers = $conn->query("SELECT COUNT(*) as cnt FROM customers")->fetch_assoc()['cnt'];

$total_users = 0;
if ($role === 'admin') {
    $total_users = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            height: 100vh;
            background: #f3f4f6;
        }
        /* Sidebar */
        .sidebar {
            width: 230px;
            background: #007BFF;
            color: white;
            display: flex;
            flex-direction: column;
            padding-top: 20px;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 22px;
        }
        .sidebar a {
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            display: block;
            transition: background 0.3s;
            border-radius: 6px;
            margin: 4px 10px;
        }
        .sidebar a:hover {
            background: #0056b3;
        }

        /* Main content */
        .main-content {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
        }
        .header {
            background: white;
            padding: 18px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
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
        .header a {
            color: #007BFF;
            text-decoration: none;
            font-weight: bold;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .card h3 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 20px;
            color: #444;
        }
        /* Stats cards grid */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .stat-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
        }
        .stat-box h2 {
            margin: 0;
            font-size: 32px;
            color: #007BFF;
        }
        .stat-box p {
            margin: 6px 0 0;
            font-size: 14px;
            color: #555;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>CRM</h2>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="customers.php">👥 Customers</a>
        <?php if ($role === 'admin') : ?>
            <a href="users.php">👤 Users</a>
        <?php endif; ?>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> 👋</h2>
            <a href="logout.php">Logout</a>
        </div>

        <!-- Dashboard Overview -->
        <div class="card">
            <h3>Dashboard Overview</h3>
            <p>
                <?php if ($role === 'admin') : ?>
                    You can manage customers, view/manage users, and access all CRM features.
                <?php else: ?>
                    You can manage customers and view your CRM activities.
                <?php endif; ?>
            </p>
        </div>

        <!-- Stats Section -->
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
            </div>
        </div>
    </div>
</body>
</html>
