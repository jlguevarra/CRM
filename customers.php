<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// fetch role
$user_id = $_SESSION['user_id'];
// Using prepared statements for security
$stmt_role = $conn->prepare("SELECT role, name FROM users WHERE id = ? LIMIT 1");
$stmt_role->bind_param("i", $user_id);
$stmt_role->execute();
$result_user = $stmt_role->get_result();
$user_data = $result_user->fetch_assoc();
$role = $user_data['role'];
$user_name = $user_data['name'];
$stmt_role->close();


// ADD CUSTOMER
if (isset($_POST['add_customer'])) {
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $phone   = $_POST['phone'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $phone, $address);
    $stmt->execute();
    $stmt->close();

    header("Location: customers.php"); 
    exit();
}

// DELETE CUSTOMER
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: customers.php");
    exit();
}

// --- SEARCH FILTER ---
$search_term = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $search_like = "%" . $search_term . "%";
    $stmt = $conn->prepare("SELECT * FROM customers WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY id DESC");
    $stmt->bind_param("sss", $search_like, $search_like, $search_like);
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
            --secondary: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

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
        
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 16px; text-align: left; border-bottom: 1px solid #eee; }
        table th {
            background: #f8f9fa; color: #555; font-size: 12px;
            font-weight: 600; text-transform: uppercase;
        }
        table tr:hover { background-color: #f9fafb; }
        .action-cell { display: flex; gap: 10px; }
        
        .btn {
            padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer;
            font-size: 14px; text-decoration: none; display: inline-flex;
            align-items: center; gap: 5px; font-weight: 500; transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.85; }
        .btn-edit { background: #fff4e6; color: #d97706; }
        .btn-delete { background: #ffecec; color: #dc2626; }
        .btn-add {
            background: var(--success); color: white; padding: 12px 18px;
            border-radius: 8px; font-weight: bold;
        }
        
        input, textarea {
            width: 100%; padding: 12px; margin-bottom: 10px; border: 1px solid #ddd;
            border-radius: 8px; font-size: 14px; box-sizing: border-box;
        }
        textarea { min-height: 80px; resize: vertical; }

        .search-bar { display: flex; gap: 10px; align-items: center; margin-bottom: 20px; }
        .search-bar input { flex: 1; margin: 0; }
        .search-bar .btn-add { margin-top: 0; background-color: var(--primary); padding: 12px 18px; }
        .cancel-btn {
            padding: 12px 18px; border-radius: 8px; background: #f1f5f9;
            color: #334155; text-decoration: none; font-weight: 500;
        }
        
        /* Notification Styles */
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
            <h2>Manage Customers</h2>
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
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email">
                <input type="text" name="phone" placeholder="Phone">
                <textarea name="address" placeholder="Address"></textarea>
                <button type="submit" name="add_customer" class="btn btn-add">+ Add Customer</button>
            </form>
        </div>

        <div class="card">
            <h3>Customer List</h3>
            
            <form method="GET" class="search-bar">
                <input type="text" name="search" placeholder="Search by name, email or phone" value="<?= htmlspecialchars($search_term) ?>">
                <button type="submit" class="btn btn-add">Search</button>
                <a href="customers.php" class="cancel-btn">Reset</a>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $row['id']; ?></td>
                        <td><?= htmlspecialchars($row['name']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><?= htmlspecialchars($row['phone']); ?></td>
                        <td><?= htmlspecialchars($row['address']); ?></td>
                        <td class="action-cell">
                            <a href="edit_customer.php?id=<?= $row['id']; ?>" class="btn btn-edit"><i class="fas fa-pencil-alt"></i> Edit</a>
                            <a href="customers.php?delete=<?= $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this customer?');"><i class="fas fa-trash"></i> Delete</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
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