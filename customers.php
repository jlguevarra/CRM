<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// fetch role
$user_id = $_SESSION['user_id'];
$sql = "SELECT role FROM users WHERE id='$user_id' LIMIT 1";
$result = $conn->query($sql);
$user     = $result->fetch_assoc();
$role     = $user['role'];

$sql_name = "SELECT name FROM users WHERE id='$user_id' LIMIT 1";
$result_name = $conn->query($sql_name);
$user_data = $result_name->fetch_assoc();
$user_name = $user_data['name'];

// ADD CUSTOMER
if (isset($_POST['add_customer'])) {
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $phone   = $_POST['phone'];
    $address = $_POST['address'];

    $sql = "INSERT INTO customers (name, email, phone, address) 
            VALUES ('$name', '$email', '$phone', '$address')";
    $conn->query($sql);
    header("Location: customers.php"); 
    exit();
}

// DELETE CUSTOMER
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM customers WHERE id=$id");
    header("Location: customers.php");
    exit();
}

// --- SEARCH FILTER ---
$search = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $stmt = $conn->prepare("SELECT * FROM customers WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY id DESC");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query("SELECT * FROM customers ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Manage Customers</title>
    <style>
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
              --border-radius: 8px;
              --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
              --transition: all 0.3s ease;
          }

          body {
              margin: 0; 
              font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
              display: flex; 
              height: 100vh; 
              background: #f3f4f6;
          }
           /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary);
            color: white;
            display: flex;
            flex-direction: column;
            padding-top: 20px;
            transition: var(--transition);
            z-index: 1000;
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
              padding: 25px; 
              overflow-y: auto; 
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
          
          .header-actions .notification {
              position: relative;
              cursor: pointer;
          }
          
          .header-actions .notification .badge {
              position: absolute;
              top: -5px;
              right: -5px;
              background: var(--danger);
              color: white;
              border-radius: 50%;
              width: 18px;
              height: 18px;
              font-size: 11px;
              display: flex;
              justify-content: center;
              align-items: center;
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
          table {
              width: 100%; 
              border-collapse: collapse; 
              margin-top: 15px;
          }
          table th, table td {
              border: 1px solid #ddd; 
              padding: 12px; 
              text-align: left;
          }
          th {
              background: #007BFF; 
              color: white; 
              font-size: 14px;
          }
          tr:nth-child(even) { background: #f9f9f9; }

          .btn {
              padding: 6px 12px; 
              border: none; 
              border-radius: 6px; 
              cursor: pointer; 
              font-size: 14px;
          }
          .btn-edit { background: #ffc107; color: white; text-decoration: none; }
          .btn-delete { background: #dc3545; color: white; text-decoration: none; }
          .btn-add { 
              background: #28a745; 
              color: white; 
              padding: 10px 15px; 
              margin-top: 10px; 
              border-radius: 8px; 
              display: inline-block; 
              font-weight: bold;
          }
          input, textarea {
              width: 95%; 
              padding: 10px; 
              margin: 5px 0; 
              border: 1px solid #ccc; 
              border-radius: 8px; 
              font-size: 14px;
          }
          .search-bar { 
              margin-bottom: 15px; 
              display: flex; 
              gap: 10px; 
              align-items: center;
          }
          .search-bar input { 
              flex: 1; 
              margin: 0;
          }
          .cancel-btn { 
              margin-left: 10px; 
              color: #555; 
              text-decoration: none; 
              padding: 8px 12px;
              border-radius: 6px;
              background: #f8f9fa;
              border: 1px solid #ddd;
          }
          .cancel-btn:hover {
              background: #e9ecef;
          }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>CRM</h2>
        <a href="dashboard.php"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a>
        <a href="customers.php" class="active"><i class="fas fa-users"></i> <span>Customers</span></a>
        <?php if ($role === 'admin') : ?>
            <a href="users.php"><i class="fas fa-user-cog"></i> <span>Users</span></a>
            <a href="reports.php"><i class="fas fa-chart-pie"></i> <span>Reports</span></a>
            <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <?php endif; ?>
        <a href="task.php"><i class="fas fa-tasks"></i> <span>Tasks</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Manage Customer</h2>
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
            <h3>Add New Customer</h3>
            <form method="POST">
                <input type="text" name="name" placeholder="Full Name" required><br>
                <input type="email" name="email" placeholder="Email"><br>
                <input type="text" name="phone" placeholder="Phone"><br>
                <textarea name="address" placeholder="Address"></textarea><br>
                <button type="submit" name="add_customer" class="btn-add">+ Add Customer</button>
            </form>
        </div>

        <div class="card">
            <h3>Customer List</h3>
            
            <form method="GET" class="search-bar">
                <input type="text" name="search" placeholder="Search by name, email or phone" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <button type="submit" class="btn-add">Search</button>
                <a href="customers.php" class="cancel-btn">Reset</a>
            </form>

            <table>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['id']; ?></td>
                    <td><?= htmlspecialchars($row['name']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['phone']); ?></td>
                    <td><?= htmlspecialchars($row['address']); ?></td>
                    <td>
                        <a href="edit_customer.php?id=<?= $row['id']; ?>" class="btn btn-edit">✏ Edit</a>
                        <a href="customers.php?delete=<?= $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this customer?');">🗑 Delete</a>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</body>
</html>