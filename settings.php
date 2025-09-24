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

// Fetch user role from database using prepared statement
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

// Get user settings
$user_settings = getUserSettings($user_id);

// Get all users for management (admin only)
$all_users = [];
if ($role === 'admin') {
    $all_users = getAllUsers();
}

// Get system settings
$system_settings = getSystemSettings();

// Initialize messages
$success_message = '';
$error_message = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $profile_data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'position' => $_POST['position'] ?? ''
        ];
        if (updateUserProfile($user_id, $profile_data)) {
            $success_message = "Profile updated successfully!";
            $user_name = $profile_data['name']; // Update name for header
        } else {
            $error_message = "Failed to update profile.";
        }
    }
    
    if (isset($_POST['update_password'])) {
        if (($_POST['new_password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
            $error_message = "New passwords do not match.";
        } else {
            if (updatePassword($user_id, $_POST['current_password'] ?? '', $_POST['new_password'] ?? '')) {
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Failed to update password. Check current password.";
            }
        }
    }
}

// Get current user details for profile form
$user_details = getUserDetails($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - CRM System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles for the Settings page content */
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

        .alert {
            padding: 15px; margin-bottom: 20px; border-radius: 8px;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background-color: #e6f4e6; color: #27ae60; }
        .alert-error { background-color: #ffecec; color: #dc2626; }

        .settings-tabs {
            display: flex; gap: 10px; border-bottom: 1px solid #ddd; margin-bottom: 25px;
        }
        .tab {
            padding: 10px 20px; cursor: pointer; border-bottom: 3px solid transparent;
            font-weight: 500; color: var(--secondary);
        }
        .tab.active { color: var(--primary); border-bottom-color: var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .card {
            background: white; padding: 25px; border-radius: var(--border-radius); 
            box-shadow: var(--box-shadow); margin-bottom: 25px;
        }
        .card-header { margin-bottom: 20px; }
        .card-header h2 { margin: 0 0 5px 0; font-size: 20px; color: #333; }
        .card-header p { margin: 0; color: #777; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 12px; border: 1px solid #ddd;
            border-radius: 8px; font-size: 14px; box-sizing: border-box;
        }
        .btn-primary { background-color: var(--primary); color: white; border: none; cursor: pointer; padding: 12px 20px; border-radius: 8px; }

        .toggle-container {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 0; border-bottom: 1px solid #eee;
        }
        .toggle-container:last-of-type { border-bottom: none; }
        .toggle-info h3 { margin: 0 0 5px 0; font-size: 16px; }
        .toggle-info p { margin: 0; color: #777; }

        .switch { position: relative; display: inline-block; width: 50px; height: 28px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: .4s; border-radius: 34px;
        }
        .slider:before {
            position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px;
            background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(22px); }

        .user-list { list-style: none; padding: 0; }
        .user-item {
            display: flex; align-items: center; gap: 15px; padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .user-info { flex-grow: 1; }
        .user-info h3 { margin: 0 0 5px 0; font-size: 16px; }
        .user-info p { margin: 0; color: #777; }
        .user-role { padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; }
        .role-admin { background-color: #e6f4ff; color: var(--primary); }
        .role-user { background-color: #f1f5f9; color: #555; }
        .user-actions button {
            background: none; border: none; cursor: pointer; color: var(--secondary); font-size: 16px;
        }

        .modal {
            display: none; position: fixed; z-index: 1001; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);
            justify-content: center; align-items: center;
        }
        .modal-content {
            background-color: #fefefe; margin: auto; padding: 25px; border-radius: var(--border-radius);
            width: 90%; max-width: 500px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { margin: 0; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }

        /* Notification Styles */
        .notification { position: relative; cursor: pointer; }
        .notification .badge {
            position: absolute; top: -8px; right: -8px; background: #ff4757; color: white;
            border-radius: 50%; padding: 2px 6px; font-size: 12px; min-width: 18px;
            text-align: center; display: none;
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
            <h2>System Settings</h2>
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
        
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="settings-tabs">
            <div class="tab active" data-tab="profile">Profile</div>
            <div class="tab" data-tab="preferences">Preferences</div>
            <?php if ($role === 'admin') : ?>
            <div class="tab" data-tab="users">User Management</div>
            <div class="tab" data-tab="system">System Settings</div>
            <?php endif; ?>
        </div>
        
        <div class="tab-content active" id="profile">
            <div class="card">
                <div class="card-header">
                    <h2>Profile Information</h2>
                    <p>Update your personal information and contact details</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_details['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_details['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_details['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="position">Position</label>
                            <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($user_details['position'] ?? ''); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Change Password</h2>
                    <p>Secure your account by updating your password regularly</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="update_password" value="1">
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" id="currentPassword" name="current_password" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" id="confirmPassword" name="confirm_password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Update Password</button>
                </form>
            </div>
        </div>
        
        <div class="tab-content" id="preferences">
            <div class="card">
                <div class="card-header">
                    <h2>Notification Preferences</h2>
                    <p>Manage how and when you receive notifications</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="update_preferences" value="1">
                    <div class="toggle-container">
                        <div class="toggle-info"><h3>Email Notifications</h3><p>Receive important updates via email</p></div>
                        <label class="switch"><input type="checkbox" name="email_notifications" <?php echo ($user_settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>><span class="slider"></span></label>
                    </div>
                    <div class="toggle-container">
                        <div class="toggle-info"><h3>Push Notifications</h3><p>Get instant alerts on your device</p></div>
                        <label class="switch"><input type="checkbox" name="push_notifications" <?php echo ($user_settings['push_notifications'] ?? 1) ? 'checked' : ''; ?>><span class="slider"></span></label>
                    </div>
                     <button type="submit" class="btn-primary" style="margin-top: 20px;">Save Preferences</button>
                </form>
            </div>
        </div>
        
        <?php if ($role === 'admin') : ?>
        <div class="tab-content" id="users">
            <div class="card">
                <div class="card-header"><h2>User Management</h2><p>Manage system users and their permissions</p></div>
                <ul class="user-list">
                    <?php foreach ($all_users as $user_item): ?>
                    <li class="user-item">
                        <div class="user-avatar"><?php echo strtoupper(substr($user_item['name'], 0, 1)); ?></div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($user_item['name']); ?></h3>
                            <p><?php echo htmlspecialchars($user_item['email']); ?></p>
                        </div>
                        <span class="user-role role-<?php echo $user_item['role']; ?>"><?php echo ucfirst($user_item['role']); ?></span>
                        <div class="user-actions">
                            <button class="edit-user" data-user-id="<?php echo $user_item['id']; ?>"><i class="fas fa-edit"></i></button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this user?');">
                                <input type="hidden" name="delete_user" value="1">
                                <input type="hidden" name="user_id" value="<?php echo $user_item['id']; ?>">
                                <button type="submit"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div class="tab-content" id="system">
            <div class="card">
                 <div class="card-header"><h2>System Configuration</h2><p>Configure system-wide settings</p></div>
                 <form method="POST">
                    <input type="hidden" name="update_system_settings" value="1">
                    <div class="form-group">
                        <label for="companyName">Company Name</label>
                        <input type="text" id="companyName" name="company_name" value="<?php echo htmlspecialchars($system_settings['company_name'] ?? 'Your Company Inc.'); ?>">
                    </div>
                    <button type="submit" class="btn-primary">Save Configuration</button>
                 </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Tab functionality
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });

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