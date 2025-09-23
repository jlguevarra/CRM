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

// Get all users for management (admin only) - WITH ERROR HANDLING
$all_users = [];
if ($role === 'admin') {
    if (function_exists('getAllUsers')) {
        $all_users = getAllUsers();
    } else {
        error_log("getAllUsers function not found");
        // Fallback: try to get users directly
        $fallback_sql = "SELECT id, name, email, role, phone, position FROM users ORDER BY name ASC";
        $fallback_result = $conn->query($fallback_sql);
        if ($fallback_result && $fallback_result->num_rows > 0) {
            while($row = $fallback_result->fetch_assoc()) {
                $all_users[] = $row;
            }
        }
    }
}

// Get system settings
$system_settings = getSystemSettings();

// Initialize messages
$success_message = '';
$error_message = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $profile_data = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'position' => $_POST['position'] ?? '',
            'bio' => $_POST['bio'] ?? ''
        ];
        
        if (updateUserProfile($user_id, $profile_data)) {
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Failed to update profile. Please try again.";
        }
    }
    
    if (isset($_POST['update_password'])) {
        // Update password
        if (($_POST['new_password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
            $error_message = "New passwords do not match.";
        } else {
            if (updatePassword($user_id, $_POST['current_password'] ?? '', $_POST['new_password'] ?? '')) {
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Failed to update password. Please check your current password.";
            }
        }
    }
    
    if (isset($_POST['update_preferences'])) {
        // Update user preferences
        $preferences = [
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'push_notifications' => isset($_POST['push_notifications']) ? 1 : 0,
            'task_reminders' => isset($_POST['task_reminders']) ? 1 : 0,
            'weekly_reports' => isset($_POST['weekly_reports']) ? 1 : 0,
            'theme' => $_POST['theme'] ?? 'light',
            'language' => $_POST['language'] ?? 'en',
            'timezone' => $_POST['timezone'] ?? 'UTC',
            'date_format' => $_POST['date_format'] ?? 'mm/dd/yyyy',
            'time_format' => $_POST['time_format'] ?? '12',
            'items_per_page' => $_POST['items_per_page'] ?? 25
        ];
        
        if (updateUserPreferences($user_id, $preferences)) {
            $success_message = "Preferences updated successfully!";
            $user_settings = getUserSettings($user_id); // Refresh settings
        } else {
            $error_message = "Failed to update preferences. Please try again.";
        }
    }
    
    if (isset($_POST['add_user'])) {
        // Add new user
        if (($_POST['password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
            $error_message = "Passwords do not match.";
        } else {
            $user_data = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'role' => $_POST['role'] ?? 'user',
                'phone' => $_POST['phone'] ?? '',
                'position' => $_POST['position'] ?? ''
            ];
            
            if (createUser($user_data)) {
                $success_message = "User created successfully!";
                // Refresh user list
                $all_users = getAllUsers();
            } else {
                $error_message = "Failed to create user. Email may already exist.";
            }
        }
    }
    
    if (isset($_POST['edit_user'])) {
        // Edit user
        $user_data = [
            'name' => $_POST['edit_name'] ?? '',
            'email' => $_POST['edit_email'] ?? '',
            'role' => $_POST['edit_role'] ?? 'user',
            'phone' => $_POST['edit_phone'] ?? '',
            'position' => $_POST['edit_position'] ?? ''
        ];
        
        if (updateUser($_POST['user_id'] ?? 0, $user_data)) {
            $success_message = "User updated successfully!";
            // Refresh user list
            $all_users = getAllUsers();
        } else {
            $error_message = "Failed to update user.";
        }
    }
    
    if (isset($_POST['delete_user'])) {
        // Delete user
        if (deleteUser($_POST['user_id'] ?? 0)) {
            $success_message = "User deleted successfully!";
            // Refresh user list
            $all_users = getAllUsers();
        } else {
            $error_message = "Failed to delete user. Cannot delete the last admin.";
        }
    }
    
    if (isset($_POST['update_system_settings'])) {
        // Update system settings (admin only)
        if ($role === 'admin') {
            $system_data = [
                'company_name' => $_POST['company_name'] ?? 'Your Company Inc.',
                'auto_backup' => isset($_POST['auto_backup']) ? 1 : 0,
                'backup_frequency' => $_POST['backup_frequency'] ?? 'weekly',
                'backup_location' => $_POST['backup_location'] ?? 'local',
                'email_notifications' => isset($_POST['system_email_notifications']) ? 1 : 0,
                'timezone' => $_POST['system_timezone'] ?? 'UTC',
                'date_format' => $_POST['system_date_format'] ?? 'mm/dd/yyyy',
                'time_format' => $_POST['time_format'] ?? '12',
                'items_per_page' => $_POST['items_per_page'] ?? 25
            ];
            
            if (updateSystemSettings($system_data)) {
                $success_message = "System settings updated successfully!";
                $system_settings = getSystemSettings(); // Refresh settings
            } else {
                $error_message = "Failed to update system settings. Please try again.";
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
    <link rel="stylesheet" href="settings/settings.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>CRM</h2>
        <a href="dashboard.php"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a>
        <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
        <?php if ($role === 'admin') : ?>
            <a href="users.php"><i class="fas fa-user-cog"></i> <span>Users</span></a>
            <a href="reports.php"><i class="fas fa-chart-pie"></i> <span>Reports</span></a>
            <a href="settings.php" class="active"><i class="fas fa-cog"></i> <span>Settings</span></a>
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
        
        <!-- Success/Error Messages -->
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <div class="settings-tabs">
            <div class="tab active" data-tab="profile">Profile</div>
            <div class="tab" data-tab="preferences">Preferences</div>
            <?php if ($role === 'admin') : ?>
            <div class="tab" data-tab="users">User Management</div>
            <div class="tab" data-tab="system">System Settings</div>
            <?php endif; ?>
        </div>
        
        <!-- Profile Settings -->
        <div class="tab-content active" id="profile">
            <div class="card">
                <div class="card-header">
                    <h2>Profile Information</h2>
                    <p>Update your personal information and contact details</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" name="first_name" value="<?php echo htmlspecialchars($user_details['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="last_name" value="<?php echo htmlspecialchars($user_details['last_name'] ?? ''); ?>" required>
                         </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_details['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_details['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($user_details['position'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio"><?php echo htmlspecialchars($user_details['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Changes</button>
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
                    
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" id="newPassword" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <input type="password" id="confirmPassword" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>
        
       <!-- Preferences Settings -->
<div class="tab-content" id="preferences">
    <div class="card">
        <div class="card-header">
            <h2>Notification Preferences</h2>
            <p>Manage how and when you receive notifications</p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="update_preferences" value="1">
            <div class="toggle-container">
                <div class="toggle-info">
                    <h3>Email Notifications</h3>
                    <p>Receive important updates via email</p>
                </div>
                <label class="switch">
                    <input type="checkbox" name="email_notifications" <?php echo ($user_settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="toggle-container">
                <div class="toggle-info">
                    <h3>Push Notifications</h3>
                    <p>Get instant alerts on your device</p>
                </div>
                <label class="switch">
                    <input type="checkbox" name="push_notifications" <?php echo ($user_settings['push_notifications'] ?? 1) ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="toggle-container">
                <div class="toggle-info">
                    <h3>Task Reminders</h3>
                    <p>Receive reminders for upcoming tasks</p>
                </div>
                <label class="switch">
                    <input type="checkbox" name="task_reminders" <?php echo ($user_settings['task_reminders'] ?? 1) ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="toggle-container">
                <div class="toggle-info">
                    <h3>Weekly Reports</h3>
                    <p>Get weekly performance reports</p>
                </div>
                <label class="switch">
                    <input type="checkbox" name="weekly_reports" <?php echo ($user_settings['weekly_reports'] ?? 0) ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Preferences</button>
        </form>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Display Preferences</h2>
            <p>Customize how the application looks and behaves</p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="update_preferences" value="1">
            <div class="form-group">
                <label for="theme">Theme</label>
                <select id="theme" name="theme">
                    <option value="light" <?php echo ($user_settings['theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Light</option>
                    <option value="dark" <?php echo ($user_settings['theme'] ?? '') === 'dark' ? 'selected' : ''; ?>>Dark</option>
                    <option value="auto" <?php echo ($user_settings['theme'] ?? '') === 'auto' ? 'selected' : ''; ?>>Auto (System Default)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="language">Language</label>
                <select id="language" name="language">
                    <option value="en" <?php echo ($user_settings['language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                    <option value="es" <?php echo ($user_settings['language'] ?? '') === 'es' ? 'selected' : ''; ?>>Spanish</option>
                    <option value="fr" <?php echo ($user_settings['language'] ?? '') === 'fr' ? 'selected' : ''; ?>>French</option>
                    <option value="de" <?php echo ($user_settings['language'] ?? '') === 'de' ? 'selected' : ''; ?>>German</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="timezone">Timezone</label>
                <select id="timezone" name="timezone">
                    <option value="UTC" <?php echo ($user_settings['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                    <option value="EST" <?php echo ($user_settings['timezone'] ?? '') === 'EST' ? 'selected' : ''; ?>>Eastern Time (ET)</option>
                    <option value="CST" <?php echo ($user_settings['timezone'] ?? '') === 'CST' ? 'selected' : ''; ?>>Central Time (CT)</option>
                    <option value="MST" <?php echo ($user_settings['timezone'] ?? '') === 'MST' ? 'selected' : ''; ?>>Mountain Time (MT)</option>
                    <option value="PST" <?php echo ($user_settings['timezone'] ?? '') === 'PST' ? 'selected' : ''; ?>>Pacific Time (PT)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date_format">Date Format</label>
                <select id="date_format" name="date_format">
                    <option value="mm/dd/yyyy" <?php echo (($user_settings['date_format'] ?? 'mm/dd/yyyy') === 'mm/dd/yyyy') ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                    <option value="dd/mm/yyyy" <?php echo ($user_settings['date_format'] ?? '') === 'dd/mm/yyyy' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                    <option value="yyyy-mm-dd" <?php echo ($user_settings['date_format'] ?? '') === 'yyyy-mm-dd' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="time_format">Time Format</label>
                <select id="time_format" name="time_format">
                    <option value="12" <?php echo ($user_settings['time_format'] ?? '12') === '12' ? 'selected' : ''; ?>>12-hour format</option>
                    <option value="24" <?php echo ($user_settings['time_format'] ?? '') === '24' ? 'selected' : ''; ?>>24-hour format</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="items_per_page">Items Per Page</label>
                <select id="items_per_page" name="items_per_page">
                    <option value="10" <?php echo ($user_settings['items_per_page'] ?? 25) == 10 ? 'selected' : ''; ?>>10 items</option>
                    <option value="25" <?php echo ($user_settings['items_per_page'] ?? 25) == 25 ? 'selected' : ''; ?>>25 items</option>
                    <option value="50" <?php echo ($user_settings['items_per_page'] ?? 25) == 50 ? 'selected' : ''; ?>>50 items</option>
                    <option value="100" <?php echo ($user_settings['items_per_page'] ?? 25) == 100 ? 'selected' : ''; ?>>100 items</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Preferences</button>
        </form>
    </div>
</div>
        
        <!-- User Management Settings (Admin only) -->
        <?php if ($role === 'admin') : ?>
        <div class="tab-content" id="users">
            <!-- Add User Form -->
            <div class="card">
                <div class="card-header">
                    <h2>Add New User</h2>
                    <p>Create a new user account</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="add_user" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position">
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add User</button>
                </form>
            </div>
            
            <!-- User List -->
            <div class="card">
                <div class="card-header">
                    <h2>User Management</h2>
                    <p>Manage system users and their permissions</p>
                </div>
                
                <ul class="user-list">
                    <?php if (!empty($all_users)): ?>
                        <?php foreach ($all_users as $user): ?>
                        <li class="user-item">
                            <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                            <div class="user-info">
                                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <span class="user-role role-<?php echo $user['role'] === 'admin' ? 'admin' : 'user'; ?>"><?php echo ucfirst($user['role']); ?></span>
                            <div class="user-actions">
                                <button type="button" class="edit-user" data-user-id="<?php echo $user['id']; ?>" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="delete_user" value="1">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" title="Delete" style="background: none; border: none; cursor: pointer;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="user-item">
                            <p>No users found.</p>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Edit User Modal -->
            <div id="editUserModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Edit User</h3>
                        <span class="close">&times;</span>
                    </div>
                    <form method="POST" id="editUserForm">
                        <input type="hidden" name="edit_user" value="1">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="form-group">
                            <label for="edit_name">Full Name</label>
                            <input type="text" id="edit_name" name="edit_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email Address</label>
                            <input type="email" id="edit_email" name="edit_email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_role">Role</label>
                            <select id="edit_role" name="edit_role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_phone">Phone Number</label>
                            <input type="tel" id="edit_phone" name="edit_phone">
                        </div>
                        <div class="form-group">
                            <label for="edit_position">Position</label>
                            <input type="text" id="edit_position" name="edit_position">
                        </div>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- System Settings (Admin only) -->
        <div class="tab-content" id="system">
            <div class="card">
                <div class="card-header">
                    <h2>System Configuration</h2>
                    <p>Configure system-wide settings and preferences</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="update_system_settings" value="1">
                    <div class="form-group">
                        <label for="companyName">Company Name</label>
                        <input type="text" id="companyName" name="company_name" value="<?php echo htmlspecialchars($system_settings['company_name'] ?? 'Your Company Inc.'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="timeFormat">Time Format</label>
                        <select id="timeFormat" name="time_format">
                            <option value="12" <?php echo ($system_settings['time_format'] ?? '12') === '12' ? 'selected' : ''; ?>>12-hour format</option>
                            <option value="24" <?php echo ($system_settings['time_format'] ?? '') === '24' ? 'selected' : ''; ?>>24-hour format</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="itemsPerPage">Items Per Page</label>
                        <select id="itemsPerPage" name="items_per_page">
                            <option value="10" <?php echo ($system_settings['items_per_page'] ?? '25') === '10' ? 'selected' : ''; ?>>10 items</option>
                            <option value="25" <?php echo ($system_settings['items_per_page'] ?? '25') === '25' ? 'selected' : ''; ?>>25 items</option>
                            <option value="50" <?php echo ($system_settings['items_per_page'] ?? '') === '50' ? 'selected' : ''; ?>>50 items</option>
                            <option value="100" <?php echo ($system_settings['items_per_page'] ?? '') === '100'? 'selected' : ''; ?>>100 items</option>
                        </select>
                    </div>
                    
                    <div class="toggle-container">
                        <div class="toggle-info">
                            <h3>Auto Backup</h3>
                            <p>Automatically backup system data daily</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="auto_backup" <?php echo ($system_settings['auto_backup'] ?? 1) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-container">
                        <div class="toggle-info">
                            <h3>Email Notifications</h3>
                            <p>Send email notifications for system events</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="system_email_notifications" <?php echo ($system_settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="backupFrequency">Backup Frequency</label>
                        <select id="backupFrequency" name="backup_frequency">
                            <option value="daily" <?php echo ($system_settings['backup_frequency'] ?? 'weekly') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo ($system_settings['backup_frequency'] ?? 'weekly') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="monthly" <?php echo ($system_settings['backup_frequency'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="backupLocation">Backup Location</label>
                        <select id="backupLocation" name="backup_location">
                            <option value="local" <?php echo ($system_settings['backup_location'] ?? 'local') === 'local' ? 'selected' : ''; ?>>Local Server</option>
                            <option value="cloud" <?php echo ($system_settings['backup_location'] ?? '') === 'cloud' ? 'selected' : ''; ?>>Cloud Storage</option>
                            <option value="both" <?php echo ($system_settings['backup_location'] ?? '') === 'both' ? 'selected' : ''; ?>>Both</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="system_timezone">System Timezone</label>
                        <select id="system_timezone" name="system_timezone">
                            <option value="UTC" <?php echo ($system_settings['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                            <option value="EST" <?php echo ($system_settings['timezone'] ?? '') === 'EST' ? 'selected' : ''; ?>>Eastern Time (ET)</option>
                            <option value="CST" <?php echo ($system_settings['timezone'] ?? '') === 'CST' ? 'selected' : ''; ?>>Central Time (CT)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="system_date_format">System Date Format</label>
                        <select id="system_date_format" name="system_date_format">
                            <option value="mm/dd/yyyy" <?php echo ($system_settings['date_format'] ?? 'mm/dd/yyyy') === 'mm/dd/yyyy' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                            <option value="dd/mm/yyyy" <?php echo ($system_settings['date_format'] ?? '') === 'dd/mm/yyyy' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                            <option value="yyyy-mm-dd" <?php echo ($system_settings['date_format'] ?? '') === 'yyyy-mm-dd' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Configuration</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Tab functionality
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to current tab and content
                tab.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // User management functionality
        const editButtons = document.querySelectorAll('.edit-user');
        const modal = document.getElementById('editUserModal');
        const closeBtn = document.querySelector('.close');
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                
                // Fetch user data (you would typically fetch this via AJAX)
                // For now, we'll use a simple approach
                const userItem = this.closest('.user-item');
                const userName = userItem.querySelector('h3').textContent;
                const userEmail = userItem.querySelector('p').textContent;
                const userRole = userItem.querySelector('.user-role').textContent.toLowerCase();
                
                document.getElementById('edit_user_id').value = userId;
                document.getElementById('edit_name').value = userName;
                document.getElementById('edit_email').value = userEmail;
                document.getElementById('edit_role').value = userRole;
                
                modal.style.display = 'block';
            });
        });
        
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Password confirmation
        const passwordForm = document.querySelector('form[action*="update_password"]');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match!');
                }
            });
        }

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
    </script>
</body>
</html>