<?php
session_start();

// Check if logout is confirmed
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'true') {
    // Show confirmation page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Logout Confirmation - CRM System</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            
            .confirmation-box {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 400px;
                width: 90%;
            }
            
            .confirmation-icon {
                font-size: 64px;
                color: #ff6b6b;
                margin-bottom: 20px;
            }
            
            .confirmation-title {
                font-size: 24px;
                font-weight: bold;
                color: #333;
                margin-bottom: 10px;
            }
            
            .confirmation-message {
                color: #666;
                margin-bottom: 30px;
                line-height: 1.5;
            }
            
            .button-group {
                display: flex;
                gap: 15px;
                justify-content: center;
            }
            
            .btn {
                padding: 12px 30px;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: all 0.3s ease;
            }
            
            .btn-confirm {
                background: #ff6b6b;
                color: white;
            }
            
            .btn-confirm:hover {
                background: #ff5252;
                transform: translateY(-2px);
            }
            
            .btn-cancel {
                background: #f8f9fa;
                color: #333;
                border: 1px solid #ddd;
            }
            
            .btn-cancel:hover {
                background: #e9ecef;
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="confirmation-box">
            <div class="confirmation-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div class="confirmation-title">Confirm Logout</div>
            <div class="confirmation-message">
                Are you sure you want to logout from the CRM system?<br>
                You will need to login again to access your account.
            </div>
            <div class="button-group">
                <a href="logout.php?confirm=true" class="btn btn-confirm">Yes, Logout</a>
                <a href="dashboard.php" class="btn btn-cancel">Cancel</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// If confirmed, proceed with logout
session_destroy();

// Show success message before redirecting
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - CRM System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .logout-message {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .logout-icon {
            font-size: 64px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .logout-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .logout-text {
            color: #666;
            margin-bottom: 20px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-message">
        <div class="logout-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="logout-title">Logout Successful</div>
        <div class="logout-text">You have been successfully logged out.</div>
        <div class="spinner"></div>
        <div>Redirecting to login page...</div>
    </div>

    <script>
        // Show message for 3 seconds then redirect
        setTimeout(function() {
            window.location.href = "index.php";
        }, 3000);
        
        // Optional: Redirect immediately if user clicks anywhere
        document.addEventListener('click', function() {
            window.location.href = "index.php";
        });
    </script>
</body>
</html>
<?php exit(); ?>