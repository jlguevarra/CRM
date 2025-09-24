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

// DELETE USER
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
        /* Styles for User Management Page */
        .header {
            background: white; padding: 18px 25px; border-radius: var(--border-radius);
            box-shadow: var(--box-shadow); margin-bottom: 25px; display: flex;
            justify-content: space-between; align-items: center;
        }
        .header h2 { margin: 0; font-size: 24px; color: #333; }
        .header-actions { display: flex; align-items: center; gap: 15px; }
        .user-profile { display: flex; align-items: center; gap: 10px; }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--primary); color: white;
            display: flex; justify-content: center; align-items: center; font-weight: bold;
        }

        .card {
            background: white; padding: 25px; border-radius: var(--border-radius);
            box-shadow: var(--box-shadow); margin-bottom: 25px;
        }
        .card h3 { margin-top: 0; margin-bottom: 20px; font-size: 20px; color: #333; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select {
            width: 100%; padding: 12px; border: 1px solid #ddd;
            border-radius: 8px; font-size: 14px; box-sizing: border-box;
        }

        .btn {
            padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer;
            font-size: 14px; font-weight: 500; text-decoration: none; display: inline-block;
        }
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-secondary { background-color: #f1f5f9; color: #334155; }
        .btn-edit { background: #fff4e6; color: #d97706; padding: 8px 16px; font-size: 13px; }
        .btn-delete { background: #ffecec; color: #dc2626; padding: 8px 16px; font-size: 13px; }
        
        .search-bar { display: flex; gap: 10px; align-items: center; margin-bottom: 20px; }
        .search-bar input { flex-grow: 1; padding: 12px; border-radius: 8px; border: 1px solid #ddd; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 16px; text-align: left; border-bottom: 1px solid #eee; }
        table th {
            background: #f8f9fa; color: #555; font-size: 12px;
            font-weight: 600; text-transform: uppercase;
        }
        table tr:hover { background-color: #f9fafb; }
        .role-badge {
            padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: bold;
        }
        .role-admin { background-color: #e6f4ff; color: var(--primary); }
        .role-user { background-color: #f1f5f9; color: #555; }
        .action-cell { display: flex; gap: 10px; }

        /* --- NOTIFICATION CSS ADDED --- */
        .notification { position: relative; cursor: pointer; }
        .notification .badge {
            position: absolute; top: -8px; right: -8px; background: #ff4757; color: white;
            border-radius: 50%; width: 18px; height: 18px; font-size: 11px;
            display: flex; justify-content: center; align-items: center;
        }
        .notification-dropdown {
            position: absolute; top: 100%; right: 0; width: 300px; background: white;
            border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-height: 400px; overflow-y: auto; display: none; z-index: 1000;
        }
        .notification-dropdown.active { display: block; }
        .notification-item { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.2s; }
        .notification-item:hover { background: #f8f9fa; }
        .notification-item.unread { background: #f8f9fa; border-left: 3px solid var(--primary); }
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
            <h2>Manage Users</h2>
            <div class="header-actions">
                <div class="notification" id="notificationBell">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="notificationCount">0</span>
                    <div class="notification-dropdown" id="notificationDropdown"></div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar"><?= strtoupper(substr($user_name, 0, 1)); ?></div>
                    <div>
                        <div style="font-weight: 500;"><?= htmlspecialchars($user_name); ?></div>
                        <div style="font-size: 12px; color: var(--secondary);"><?= ucfirst($role); ?></div>
                    </div>
                </div>
            </div>
        </div>
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
                    <input type="password" id="password" name="password" placeholder="<?php echo $editUser ? 'New Password (leave blank to keep current)' : 'Password'; ?>" <?php echo $editUser ? '' : 'required'; ?>>
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
                                <a href="users.php?delete=<?= $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this user?');"><i class="fas fa-trash"></i> Delete</a>
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