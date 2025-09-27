<?php
include 'config.php';
include 'functions.php';

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $role = 'user';
            
            $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
            
            if ($insert_stmt->execute()) {
                $success = "Registration successful! You can now log in.";
            } else {
                $error = "Error: Could not register the account.";
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account - CRM</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #2563eb;       /* Blue */
      --primary-dark: #1e40af; /* Darker Blue */
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

    .register-container {
      display: flex; width: 100%; max-width: 920px;
      background: white; border-radius: var(--border-radius);
      overflow: hidden; box-shadow: 0 10px 35px rgba(37, 99, 235, 0.15);
    }

    /* Left Panel */
    .register-illustration {
      flex: 1;
      background: linear-gradient(160deg, var(--primary), var(--primary-dark));
      display: flex; flex-direction: column;
      justify-content: center; align-items: center;
      color: white; padding: 40px; text-align: center;
    }

    .illustration-icon {
      font-size: 85px; margin-bottom: 25px; opacity: 0.9;
    }

    .register-illustration h1 {
      font-size: 30px; font-weight: 700; margin-bottom: 12px;
    }

    .register-illustration p {
      font-size: 16px; opacity: 0.9; line-height: 1.6;
      max-width: 300px;
    }

    /* Right Panel */
    .register-box {
      flex: 1.2; padding: 55px 60px;
      display: flex; flex-direction: column; justify-content: center;
    }

    .register-header h2 {
      font-size: 28px; color: var(--text-primary);
      margin-bottom: 8px; font-weight: 700;
    }

    .register-header p {
      color: var(--text-secondary);
      font-size: 15px; margin-bottom: 25px;
    }

    .form-group { margin-bottom: 18px; }
    .form-group label {
      display: block; margin-bottom: 8px;
      font-weight: 500; color: #334155; font-size: 14px;
    }

    .form-group input {
      width: 100%; padding: 13px 16px;
      border: 1px solid var(--border-color);
      border-radius: 10px; font-size: 15px;
      transition: var(--transition);
    }

    .form-group input:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
    }

    .register-btn {
      width: 100%; padding: 14px;
      background: linear-gradient(90deg, var(--primary), var(--primary-dark));
      border: none; border-radius: 10px;
      color: white; font-size: 16px; font-weight: 600;
      cursor: pointer; transition: var(--transition);
      margin-top: 10px;
    }

    .register-btn:hover { background: var(--secondary); }

    .alert {
      padding: 12px; border-radius: 10px; margin-bottom: 20px;
      font-size: 14px; display: flex; align-items: center; gap: 8px;
      border: 1px solid transparent;
    }

    .alert-error {
      background: #fef2f2; color: #b91c1c; border-color: #fecaca;
    }

    .alert-success {
      background: #ecfdf5; color: #065f46; border-color: #a7f3d0;
    }

    .login-link {
      margin-top: 25px; text-align: center;
      font-size: 14px; color: var(--text-secondary);
    }

    .login-link a {
      color: var(--primary); text-decoration: none; font-weight: 600;
    }
    .login-link a:hover { text-decoration: underline; }

    @media (max-width: 900px) {
      .register-container { flex-direction: column; max-width: 450px; }
      .register-illustration { display: none; }
      .register-box { padding: 40px 30px; }
    }
  </style>
</head>
<body>
  <div class="register-container">
    <div class="register-illustration">
      <i class="fas fa-rocket illustration-icon"></i>
      <h1>Get Started</h1>
      <p>Join our platform to streamline your workflow and boost productivity.</p>
    </div>
    
    <div class="register-box">
      <div class="register-header">
        <h2>Create an Account</h2>
        <p>Fill in your details to request access</p>
      </div>

      <?php if (!empty($error)): ?>
      <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo $error; ?></span>
      </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
      <div class="alert alert-success" id="successMessage">
        <i class="fas fa-check-circle"></i>
        <span><?php echo $success; ?></span>
      </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" placeholder="Juan Dela Cruz" required>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="you@company.com" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter a secure password" required>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required>
        </div>

        <button type="submit" name="register" class="register-btn">Create Account</button>
      </form>

      <div class="login-link">
        Already have an account? <a href="index.php">Sign In</a>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const successMessage = document.getElementById('successMessage');
      if (successMessage) {
        setTimeout(() => {
          successMessage.style.opacity = '0';
          setTimeout(() => successMessage.remove(), 500);
        }, 4000);
      }
    });
  </script>
</body>
</html>
