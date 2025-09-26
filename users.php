<?php
session_start();
include 'config.php';
include 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = getUserDetails($user_id);

if (!$user_data || $user_data['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$user_name = $user_data['name'];
$role = $user_data['role'];

// Initialize message variables
$success_message = '';
$error_message = '';

// ADD USER
if (isset($_POST['add_user'])) {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_add = $_POST['role'];

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error_message = "A user with this email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $role_add);
        if ($stmt->execute()) {
            $success_message = "User added successfully!";
        } else {
            $error_message = "Failed to add user. Please try again.";
        }
        $stmt->close();
    }
    $check_stmt->close();
    
    // Store messages in session for redirect
    if ($success_message) {
        $_SESSION['success_message'] = $success_message;
    }
    if ($error_message) {
        $_SESSION['error_message'] = $error_message;
    }
    header("Location: users.php");
    exit();
}

// UPDATE USER
if (isset($_POST['update_user'])) {
    $id    = intval($_POST['id']);
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $role_update = $_POST['role'];

    // Check if email already exists (excluding current user)
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check_stmt->bind_param("si", $email, $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error_message = "A user with this email already exists.";
    } else {
        // MODIFIED: Password is now required for update
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, role=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $email, $password, $role_update, $id);
        } else {
            // This 'else' block is now less likely to be hit due to 'required' in HTML, but is good for safety.
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $email, $role_update, $id);
        }
        
        if ($stmt->execute()) {
            $success_message = "User updated successfully!";
        } else {
            $error_message = "Failed to update user. Please try again.";
        }
        $stmt->close();
    }
    $check_stmt->close();
    
    // Store messages in session for redirect
    if ($success_message) {
        $_SESSION['success_message'] = $success_message;
    }
    if ($error_message) {
        $_SESSION['error_message'] = $error_message;
    }
    header("Location: users.php");
    exit();
}

// DELETE USER
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "User deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete user. Please try again.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "You cannot delete your own account.";
    }
    header("Location: users.php");
    exit();
}

// Check for session messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// --- SEARCH FILTER ---
$search_term = $_GET['search'] ?? '';
if (!empty($search_term)) {
    $search_like = "%" . $search_term . "%";
    $stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY id DESC");
    $stmt->bind_param("ss", $search_like, $search_like);
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
            --primary: #4361ee;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 8px; 
            display: flex; 
            align-items: center; 
            gap: 10px;
            transition: opacity 0.5s ease;
        }
        .alert.hiding {
            opacity: 0;
        }
        .alert-success { 
            background-color: #e6f4e6; 
            color: #27ae60; 
        }
        .alert-error { 
            background-color: #ffecec; 
            color: #dc2626; 
        }
        .card { 
            background: white; 
            padding: 25px; 
            border-radius: var(--border-radius); 
            box-shadow: var(--box-shadow); 
            margin-bottom: 25px; 
        }
        .card h3 { 
            margin-top: 0; 
            margin-bottom: 20px; 
            font-size: 20px; 
            color: #333; 
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 500; 
        }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 14px; 
            box-sizing: border-box; 
        }
        .btn { 
            padding: 12px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: 500; 
            text-decoration: none; 
            display: inline-block; 
        }
        .btn-primary { 
            background-color: var(--primary); 
            color: white; 
        }
        .btn-secondary { 
            background-color: #f1f5f9; 
            color: #334155; 
        }
        .btn-edit { 
            background: #fff4e6; 
            color: #d97706; 
            padding: 8px 16px; 
            font-size: 13px; 
        }
        .btn-delete { 
            background: #ffecec; 
            color: #dc2626; 
            padding: 8px 16px; 
            font-size: 13px; 
        }
        .search-bar { 
            display: flex; 
            gap: 10px; 
            align-items: center; 
            margin-bottom: 20px; 
        }
        .search-bar input { 
            flex-grow: 1; 
            padding: 12px; 
            border-radius: 8px; 
            border: 1px solid #ddd; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
        }
        table th, table td { 
            padding: 16px; 
            text-align: left; 
            border-bottom: 1px solid #eee; 
        }
        table th { 
            background: #f8f9fa; 
            color: #555; 
            font-size: 12px; 
            font-weight: 600; 
            text-transform: uppercase; 
        }
        table tr:hover { 
            background-color: #f9fafb; 
        }
        .role-badge { 
            padding: 4px 10px; 
            border-radius: 15px; 
            font-size: 12px; 
            font-weight: bold; 
        }
        .role-admin { 
            background-color: #e6f4ff; 
            color: var(--primary); 
        }
        .role-user { 
            background-color: #f1f5f9; 
            color: #555; 
        }
        .action-cell { 
            display: flex; 
            gap: 10px; 
        }
        
        @media (max-width: 768px) {
            .search-bar {
                flex-direction: column;
            }
            .search-bar input {
                width: 100%;
            }
            .action-cell {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h2>Manage Users</h2>
            <div class="header-actions">
                <div class="user-profile">
                    <div class="user-avatar"><?= strtoupper(substr($user_name, 0, 1)); ?></div>
                    <div>
                        <div style="font-weight: 500;"><?= htmlspecialchars($user_name); ?></div>
                        <div style="font-size: 12px; color: var(--secondary);"><?= ucfirst($role); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" id="successMessage">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-error" id="errorMessage">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h3><?php echo $editUser ? 'Edit User' : 'Add New User'; ?></h3>
            <form method="POST">
                <?php if ($editUser): ?>
                    <input type="hidden" name="id" value="<?= $editUser['id']; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($editUser['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($editUser['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="<?php echo $editUser ? 'Enter new password' : 'Password'; ?>" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?= ($editUser['role'] ?? 'user') === 'user' ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                <?php if ($editUser): ?>
                    <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h3>User List</h3>
            <form method="GET" class="search-bar">
                <input type="text" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search_term); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="users.php" class="btn btn-secondary">Reset</a>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id']; ?></td>
                        <td><?= htmlspecialchars($row['name']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><span class="role-badge role-<?= $row['role']; ?>"><?= ucfirst($row['role']); ?></span></td>
                        <td><?= date("M j, Y", strtotime($row['created_at'])); ?></td>
                        <td class="action-cell">
                            <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                <a href="users.php?edit=<?= $row['id']; ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="users.php?delete=<?= $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fas fa-trash"></i> Delete</a>
                            <?php else: ?>
                                <em>(You)</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Auto-dismiss messages after 4 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');
            
            function dismissMessage(messageElement) {
                if (messageElement) {
                    setTimeout(function() {
                        messageElement.classList.add('hiding');
                        setTimeout(function() {
                            messageElement.remove();
                        }, 500); // Wait for fade-out animation to complete
                    }, 4000); // 4 seconds
                }
            }
            
            dismissMessage(successMessage);
            dismissMessage(errorMessage);
        });
    </script>
</body>
</html>