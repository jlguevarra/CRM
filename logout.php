<?php
session_start();

// If logout is confirmed, destroy the session and show the logged-out message.
if (isset($_GET['confirm']) && $_GET['confirm'] === 'true') {
    session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - CRM System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a6cf7;
            --success: #28a745;
            --light-gray: #f3f4f6;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-radius: 12px;
        }
        body {
            font-family: 'Segoe UI', 'Inter', sans-serif;
            background-color: var(--light-gray);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .message-box {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .icon {
            font-size: 56px;
            margin-bottom: 20px;
        }
        .icon-success { color: var(--success); }
        h1 {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        p {
            color: var(--text-secondary);
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1.5s linear infinite;
            margin: 20px auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="message-box">
        <div class="icon icon-success">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>Logout Successful</h1>
        <p>You have been securely logged out.</p>
        <div class="spinner"></div>
        <p>Redirecting to login page...</p>
    </div>

    <script>
        setTimeout(() => { window.location.href = "index.php"; }, 2000);
    </script>
</body>
</html>
<?php
    exit(); // Stop script execution after showing the message
}

// If not confirmed, show the confirmation page.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Logout - CRM System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a6cf7;
            --danger: #dc2626;
            --danger-hover: #b91c1c;
            --light-gray: #f3f4f6;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-radius: 12px;
            --transition: all 0.2s ease;
        }
        body {
            font-family: 'Segoe UI', 'Inter', sans-serif;
            background-color: var(--light-gray);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .confirmation-box {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .icon {
            font-size: 56px;
            color: var(--danger);
            margin-bottom: 20px;
        }
        h1 {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        p {
            color: var(--text-secondary);
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: var(--transition);
        }
        .btn-confirm {
            background: var(--danger);
            color: white;
        }
        .btn-confirm:hover {
            background: var(--danger-hover);
        }
        .btn-cancel {
            background: #e2e8f0;
            color: var(--text-primary);
        }
        .btn-cancel:hover {
            background: #cbd5e1;
        }
    </style>
</head>
<body>
    <div class="confirmation-box">
        <div class="icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h1>Confirm Logout</h1>
        <p>Are you sure you want to sign out of your account?</p>
        <div class="button-group">
            <a href="dashboard.php" class="btn btn-cancel">Cancel</a>
            <a href="logout.php?confirm=true" class="btn btn-confirm">Yes, Logout</a>
        </div>
    </div>
</body>
</html>