<?php
session_start();
include 'config.php';
include 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = getUserDetails($user_id);

if ($user['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}



// Fetch current user's name and role
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, role FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

if (!$user_data) {
    // User not found, destroy session and redirect to login
    session_destroy();
    header("Location: index.php");
    exit();
}

$user_name = $user_data['name'];
$role = $user_data['role'];

// Only admins can access this page
if ($role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// ADD USER
if (isset($_POST['add_user'])) {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_add = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $role_add);
    $stmt->execute();
    $stmt->close();

    header("Location: users.php");
    exit();
}

// UPDATE USER
if (isset($_POST['update_user'])) {
    $id    = intval($_POST['id']);
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $role_update = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $email, $password, $role_update, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $role_update, $id);
    }
    $stmt->execute();
    $stmt->close();

    header("Location: users.php");
    exit();
}

// DELETE USER (prevent self-delete)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: users.php");
    exit();
}

// --- SEARCH FILTER ---
$search = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY id DESC");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY id DESC");
}

// IF EDIT MODE
$editUser = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Manage Users</title>
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
              text-decoration: none;
              display: inline-block;
          }
          .btn-edit { background: #ffc107; color: white; }
          .btn-delete { background: #dc3545; color: white; }
          .btn-add { 
              background: #28a745; 
              color: white; 
              padding: 10px 15px; 
              margin-top: 10px; 
              border-radius: 8px; 
              display: inline-block; 
              font-weight: bold;
              border: none;
              cursor: pointer;
          }
          input, select, textarea {
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
        <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
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
            <h2>Manage User</h2>
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
            <?php if ($editUser) { ?>
                <h3>Edit User</h3>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $editUser['id']; ?>">
                    <input type="text" name="name" value="<?= htmlspecialchars($editUser['name']); ?>" required><br>
                    <input type="email" name="email" value="<?= htmlspecialchars($editUser['email']); ?>" required><br>
                    <input type="password" name="password" placeholder="New Password (leave blank to keep current)"><br>
                    <select name="role" required>
                        <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?= $editUser['role'] === 'user' ? 'selected' : ''; ?>>Manager</option>
                    </select><br>
                    <button type="submit" name="update_user" class="btn-add">Update User</button>
                    <a href="users.php" class="cancel-btn">Cancel</a>
                </form>
            <?php } else { ?>
                <h3>Add New User</h3>
                <form method="POST">
                    <input type="text" name="name" placeholder="Full Name" required><br>
                    <input type="email" name="email" placeholder="Email" required><br>
                    <input type="password" name="password" placeholder="Password" required><br>
                    <select name="role" required>
                        <option value="admin">Admin</option>
                        <option value="user">Manager</option>
                    </select><br>
                    <button type="submit" name="add_user" class="btn-add">+ Add User</button>
                </form>
            <?php } ?>
        </div>

        <div class="card">
            <h3>User List</h3>

            <form method="GET" class="search-bar">
                <input type="text" name="search" placeholder="Search by name or email" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <button type="submit" class="btn-add">Search</button>
                <a href="users.php" class="cancel-btn">Reset</a>
            </form>

            <table>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']); ?></td>
                    <td><?= htmlspecialchars($row['name']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= ucfirst(htmlspecialchars($row['role'])); ?></td>
                    <td><?= htmlspecialchars($row['created_at']); ?></td>
                    <td>
                        <?php if ($row['id'] != $_SESSION['user_id']) { ?>
                            <a href="users.php?edit=<?= $row['id']; ?>" class="btn btn-edit">✏ Edit</a>
                            <a href="users.php?delete=<?= $row['id']; ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this user?');">
                               🗑 Delete
                            </a>
                        <?php } else { ?>
                            <em>(You)</em>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</body>
</html>