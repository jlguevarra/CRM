<?php
session_start();
include 'config.php';

$_SESSION['user_id'] = $user['id'];

if (isset($_POST['login'])) {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // ✅ Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No user found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #007BFF 0%, #6610f2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            display: flex;
            width: 900px;
            height: 500px;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .login-illustration {
            flex: 1;
            background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 40px;
        }
        
        .login-illustration h1 {
            font-size: 28px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .login-illustration p {
            text-align: center;
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .illustration {
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 30px 0;
            font-size: 80px;
        }
        
        .login-box {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-header {
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #666;
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
        }
        
        .form-group i {
            position: absolute;
            right: 15px;
            top: 40px;
            color: #999;
        }
        
        button.login-btn {
            width: 100%;
            padding: 14px;
            background: #007BFF;
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.25);
        }
        
        button.login-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 123, 255, 0.3);
        }
        
        .error {
            background: #ffeded;
            color: #d93025;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #d93025;
            display: flex;
            align-items: center;
        }
        
        .error i {
            margin-right: 8px;
        }
        
        .register-link {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        
        .register-link a {
            color: #007BFF;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        /* Responsive design */
        @media (max-width: 900px) {
            .login-container {
                flex-direction: column;
                width: 100%;
                height: auto;
            }
            
            .login-illustration {
                padding: 30px 20px;
            }
            
            .illustration {
                width: 120px;
                height: 120px;
                font-size: 50px;
                margin: 20px 0;
            }
            
            .login-box {
                padding: 30px 25px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-illustration">
            <div class="illustration">
                <i class="fas fa-user-lock"></i>
            </div>
            <h1>Welcome to CRM System</h1>
            <p>Manage your customers, track interactions, and grow your business with our powerful CRM platform.</p>
        </div>
        
        <div class="login-box">
            <div class="login-header">
                <h2>Sign In</h2>
                <p>Enter your credentials to access your account</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    <i class="fas fa-envelope"></i>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-lock"></i>
                </div>
                
                <button type="submit" name="login" class="login-btn">Login to Dashboard</button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Request access here</a>
            </div>
        </div>
    </div>
</body>
</html>