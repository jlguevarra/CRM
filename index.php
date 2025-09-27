<?php
session_start();
include 'config.php';

if (isset($_POST['login'])) {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password. Please try again.";
        }
    } else {
        $error = "No user found with that email address.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;       /* Blue */
            --primary-dark: #1e40af; /* Darker blue */
            --secondary: #f97316;    /* Orange accent */
            --light-gray: #f8fafc;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --border-radius: 14px;
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', 'Inter', sans-serif;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; padding: 20px;
        }

        .login-container {
            display: flex; width: 100%; max-width: 920px; height: 580px;
            background: white; border-radius: var(--border-radius);
            overflow: hidden; box-shadow: 0 10px 35px rgba(37, 99, 235, 0.15);
        }

        /* Left Panel */
        .login-illustration {
            flex: 1;
            background: linear-gradient(160deg, var(--primary), var(--primary-dark));
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            color: white; padding: 40px; text-align: center;
        }

        .illustration-icon {
            font-size: 85px; margin-bottom: 30px; opacity: 0.9;
        }

        .login-illustration h1 {
            font-size: 30px; font-weight: 700; margin-bottom: 12px;
        }

        .login-illustration p {
            font-size: 16px; opacity: 0.9; line-height: 1.6;
            max-width: 300px;
        }

        /* Right Panel */
        .login-box {
            flex: 1.2; padding: 55px 60px;
            display: flex; flex-direction: column; justify-content: center;
        }

        .login-header h2 {
            font-size: 28px; color: var(--text-primary);
            margin-bottom: 8px; font-weight: 700;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 15px; margin-bottom: 35px;
        }

        .form-group { margin-bottom: 22px; }
        .form-group label {
            display: block; margin-bottom: 8px;
            font-weight: 500; color: #334155; font-size: 14px;
        }

        .form-group input {
            width: 100%; padding: 14px 16px;
            border: 1px solid var(--border-color);
            border-radius: 10px; font-size: 15px;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .login-btn {
            width: 100%; padding: 14px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border: none; border-radius: 10px;
            color: white; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: var(--transition);
            margin-top: 10px;
        }

        .login-btn:hover { background: var(--secondary); }

        .error {
            background: #fef2f2; color: #b91c1c; padding: 12px;
            border-radius: 10px; margin-bottom: 20px; font-size: 14px;
            display: flex; align-items: center; gap: 8px;
            border: 1px solid #fecaca;
        }

        .register-link {
            margin-top: 25px; text-align: center;
            font-size: 14px; color: var(--text-secondary);
        }

        .register-link a {
            color: var(--primary); text-decoration: none; font-weight: 600;
        }
        .register-link a:hover { text-decoration: underline; }

        @media (max-width: 900px) {
            .login-container { flex-direction: column; max-width: 450px; height: auto; }
            .login-illustration { display: none; }
            .login-box { padding: 40px 30px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-illustration">
            <i class="fas fa-users-cog illustration-icon"></i>
            <h1>CRM System</h1>
            <p>Manage customers, track interactions, and grow your business.</p>
        </div>
        
        <div class="login-box">
            <div class="login-header">
                <h2>Sign In</h2>
                <p>Enter your credentials to access your account</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@company.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" name="login" class="login-btn">Login</button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Request Access</a>
            </div>
        </div>
    </div>
</body>
</html>
