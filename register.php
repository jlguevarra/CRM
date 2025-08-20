<?php
include 'config.php';

if (isset($_POST['register'])) {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // check if email already exists
    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $error = "Email already registered!";
    } else {
        // insert with role = user
        $sql = "INSERT INTO users (name, email, password, role) 
                VALUES ('$name', '$email', '$password', 'user')";
        
        if ($conn->query($sql) === TRUE) {
            $success = "Registration successful. You can now login.";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .register-container {
            display: flex;
            width: 900px;
            height: 500px;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .register-illustration {
            flex: 1;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 40px;
        }
        
        .register-illustration h1 {
            font-size: 28px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .register-illustration p {
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
        
        .register-box {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .register-header {
            margin-bottom: 30px;
        }
        
        .register-header h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 8px;
        }
        
        .register-header p {
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
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15);
        }
        
        .form-group i {
            position: absolute;
            right: 15px;
            top: 40px;
            color: #999;
        }
        
        button.register-btn {
            width: 100%;
            padding: 14px;
            background: #28a745;
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.25);
        }
        
        button.register-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(40, 167, 69, 0.3);
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
        
        .success {
            background: #e6f4ea;
            color: #137333;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #137333;
            display: flex;
            align-items: center;
        }
        
        .error i, .success i {
            margin-right: 8px;
        }
        
        .login-link {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        
        .login-link a {
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
            border-left: 3px solid #28a745;
        }
        
        .password-requirements ul {
            margin: 5px 0 0 15px;
        }
        
        /* Responsive design */
        @media (max-width: 900px) {
            .register-container {
                flex-direction: column;
                width: 100%;
                height: auto;
            }
            
            .register-illustration {
                padding: 30px 20px;
            }
            
            .illustration {
                width: 120px;
                height: 120px;
                font-size: 50px;
                margin: 20px 0;
            }
            
            .register-box {
                padding: 30px 25px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="register-container">
        <div class="register-illustration">
            <div class="illustration">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>Join Our CRM Platform</h1>
            <p>Create an account to start managing your customers and growing your business with our powerful CRM tools.</p>
        </div>
        
        <div class="register-box">
            <div class="register-header">
                <h2>Create Account</h2>
                <p>Fill in your details to get started</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                    <i class="fas fa-user"></i>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    <i class="fas fa-envelope"></i>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                    <i class="fas fa-lock"></i>
                    <div class="password-requirements">
                        <strong>Password should include:</strong>
                        <ul>
                            <li>At least 8 characters</li>
                            <li>Uppercase and lowercase letters</li>
                            <li>Numbers or special characters</li>
                        </ul>
                    </div>
                </div>
                
                <button type="submit" name="register" class="register-btn">Create Account</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>
        </div>
    </div>

    <script>
        // Simple password strength indicator
        const passwordInput = document.getElementById('password');
        const requirements = document.querySelector('.password-requirements');
        
        passwordInput.addEventListener('focus', function() {
            requirements.style.display = 'block';
        });
        
        passwordInput.addEventListener('blur', function() {
            requirements.style.display = 'none';
        });
    </script>
</body>
</html>