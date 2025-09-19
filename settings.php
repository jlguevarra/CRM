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
$user = getUserDetails($user_id);

if ($user['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
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

// Get user settings
$user_settings = getUserSettings($_SESSION['user_id']);

// Get all users for management (admin only)
$all_users = [];
if ($role === 'admin') {
    $all_users = getAllUsers();
}

// Get system settings
$system_settings = getSystemSettings();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $profile_data = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'position' => $_POST['position'],
            'bio' => $_POST['bio']
        ];
        
        if (updateUserProfile($_SESSION['user_id'], $profile_data)) {
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Failed to update profile. Please try again.";
        }
    }
    
    if (isset($_POST['update_password'])) {
        // Update password
        if (updatePassword($_SESSION['user_id'], $_POST['current_password'], $_POST['new_password'])) {
            $success_message = "Password updated successfully!";
        } else {
            $error_message = "Failed to update password. Please check your current password.";
        }
    }
    
    if (isset($_POST['update_preferences'])) {
        // Update user preferences
        $preferences = [
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'push_notifications' => isset($_POST['push_notifications']) ? 1 : 0,
            'task_reminders' => isset($_POST['task_reminders']) ? 1 : 0,
            'weekly_reports' => isset($_POST['weekly_reports']) ? 1 : 0,
            'theme' => $_POST['theme'],
            'language' => $_POST['language'],
            'timezone' => $_POST['timezone'],
            'date_format' => $_POST['date_format']
        ];
        
        if (updateUserSettings($_SESSION['user_id'], $preferences)) {
            $success_message = "Preferences updated successfully!";
        } else {
            $error_message = "Failed to update preferences. Please try again.";
        }
    }
    
    if (isset($_POST['update_system_settings'])) {
        // Update system settings (admin only)
        if ($role === 'admin') {
            $system_data = [
                'company_name' => $_POST['company_name'],
                'auto_backup' => isset($_POST['auto_backup']) ? 1 : 0,
                'backup_frequency' => $_POST['backup_frequency'],
                'backup_location' => $_POST['backup_location'],
                'email_notifications' => isset($_POST['system_email_notifications']) ? 1 : 0,
                'timezone' => $_POST['system_timezone'],
                'date_format' => $_POST['system_date_format'],
                'time_format' => $_POST['time_format'],
                'items_per_page' => $_POST['items_per_page']
            ];
            
            if (updateSystemSettings($system_data)) {
                $success_message = "System settings updated successfully!";
            } else {
                $error_message = "Failed to update system settings. Please try again.";
            }
        }
    }
    
    // Refresh data after update
    $user_settings = getUserSettings($_SESSION['user_id']);
    $system_settings = getSystemSettings();
}

// Get current user details for profile form
$user_details = getUserDetails($_SESSION['user_id']);
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
            <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
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
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
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
                        <input type="text" id="position" name= "position" value="<?php echo htmlspecialchars($user_details['position'] ?? ''); ?>">
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
                        <label for="dateFormat">Date Format</label>
                        <select id="dateFormat" name="date_format">
                            <option value="mm/dd/yyyy" <?php echo (($user_settings['date_format'] ?? 'mm/dd/yyyy') === 'mm/dd/yyyy') ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                            <option value="dd/mm/yyyy" <?php echo ($user_settings['date_format'] ?? '') === 'dd/mm/yyyy' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                            <option value="yyyy-mm-dd" <?php echo ($user_settings['date_format'] ?? '') === 'yyyy-mm-dd' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Preferences</button>
                </form>
            </div>
        </div>
        
        <!-- User Management Settings (Admin only) -->
        <?php if ($role === 'admin') : ?>
        <div class="tab-content" id="users">
            <div class="card">
                <div class="card-header">
                    <h2>User Management</h2>
                    <p>Manage system users and their permissions</p>
                </div>
                
                <button class="btn btn-primary" style="margin-bottom: 20px;"><i class="fas fa-plus"></i> Add New User</button>
                
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
                                <button title="Edit"><i class="fas fa-edit"></i></button>
                                <button title="Delete"><i class="fas fa-trash"></i></button>
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
            
            <div class="card">
                <div class="card-header">
                    <h2>Role Permissions</h2>
                    <p>Manage what different user roles can access</p>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="adminPermissions">Admin Permissions</label>
                        <select id="adminPermissions" multiple size="4">
                            <option selected>Manage Users</option>
                            <option selected>View Reports</option>
                            <option selected>System Settings</option>
                            <option selected>All Customer Access</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="userPermissions">User Permissions</label>
                        <select id="userPermissions" multiple size="4">
                            <option selected>Manage Own Customers</option>
                            <option selected>Create Tasks</option>
                            <option>View Reports</option>
                            <option>Export Data</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Permissions</button>
                </form>
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
                    
                    <button type="submit" class="btn btn-primary">Save Configuration</button>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Data Management</h2>
                    <p>Manage your system data and backups</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="update_system_settings" value="1">
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
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button class="btn btn-outline"><i class="fas fa-download"></i> Download Backup</button>
                        <button class="btn btn-outline"><i class="fas fa-upload"></i> Restore Backup</button>
                    </div>
                </form>
            </div>
            
            <div class="danger-zone">
                <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                <p>These actions are irreversible. Please be certain before proceeding.</p>
                
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button class="btn btn-outline">Clear Cache</button>
                    <button class="btn btn-outline">Reset Preferences</button>
                    <button class="btn btn-danger">Delete All Data</button>
                </div>
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
        
        // Initialize some form elements
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize toggle switches
            const switches = document.querySelectorAll('.switch input');
            switches.forEach(switchEl => {
                switchEl.addEventListener('change', function() {
                    console.log('Toggle switched:', this.checked);
                });
            });
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