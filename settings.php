<?php
session_start();
include 'config.php';
include 'functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$user = getUserDetails($user_id);
if (!$user || $user['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}
$role = $user['role'];
$user_name = $user['name'];

// Get user settings for preferences tab
$user_settings = getUserSettings($user_id);

// Initialize messages
$success_message = '';
$error_message = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // NOTE: phone and position are removed
        $profile_data = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'bio' => $_POST['bio'] ?? ''
        ];
        if (updateUserProfile($user_id, $profile_data)) {
            $success_message = "Profile updated successfully!";
            $_SESSION['name'] = trim($profile_data['first_name'] . ' ' . $profile_data['last_name']); // Update session name
            $user_name = $_SESSION['name']; // Update name for header
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
                $error_message = "Failed to update password. Check your current password.";
            }
        }
    }
    
    // Refresh user details after potential changes
    $user_details = getUserDetails($user_id);
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
        .header { background: white; padding: 18px 25px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { margin: 0; font-size: 24px; color: #333; }
        .header-actions { display: flex; align-items: center; gap: 15px; }
        .user-profile { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: white; display: flex; justify-content: center; align-items: center; font-weight: bold; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background-color: #e6f4e6; color: #27ae60; }
        .alert-error { background-color: #ffecec; color: #dc2626; }
        .settings-tabs { display: flex; gap: 10px; border-bottom: 1px solid #ddd; margin-bottom: 25px; }
        .tab { padding: 10px 20px; cursor: pointer; border-bottom: 3px solid transparent; font-weight: 500; color: var(--secondary); }
        .tab.active { color: var(--primary); border-bottom-color: var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .card { background: white; padding: 25px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); margin-bottom: 25px; }
        .card-header { margin-bottom: 20px; }
        .card-header h2 { margin: 0 0 5px 0; font-size: 20px; color: #333; }
        .card-header p { margin: 0; color: #777; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input { padding-right: 40px; }
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999; }
        .btn-primary { background-color: var(--primary); color: white; border: none; cursor: pointer; padding: 12px 20px; border-radius: 8px; }
        .toggle-container { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee; }
        .toggle-container:last-of-type { border-bottom: none; }
        .toggle-info h3 { margin: 0 0 5px 0; font-size: 16px; }
        .toggle-info p { margin: 0; color: #777; }
        .switch { position: relative; display: inline-block; width: 50px; height: 28px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(22px); }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h2>Settings</h2>
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
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="settings-tabs">
            <div class="tab active" data-tab="profile">Profile</div>
            <div class="tab" data-tab="preferences">Preferences</div>
        </div>
        
        <div class="tab-content active" id="profile">
            <div class="card">
                <div class="card-header">
                    <h2>Profile Information</h2>
                    <p>Update your personal information</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" name="first_name" value="<?= htmlspecialchars($user_details['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="last_name" value="<?= htmlspecialchars($user_details['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_details['email'] ?? ''); ?>" required>
                    </div>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Change Password</h2>
                    <p>Update your password for better security</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="update_password" value="1">
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="currentPassword" name="current_password" required>
                            <i class="fas fa-eye toggle-password"></i>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                             <div class="password-wrapper">
                                <input type="password" id="newPassword" name="new_password" required>
                                <i class="fas fa-eye toggle-password"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <div class="password-wrapper">
                                <input type="password" id="confirmPassword" name="confirm_password" required>
                                <i class="fas fa-eye toggle-password"></i>
                            </div>
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

        // Show/Hide Password functionality
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>