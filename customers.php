<?php
session_start();
include 'config.php';
include 'functions.php'; // We still need this for getUserDetails

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch user role and name
$user_id = $_SESSION['user_id'];
$user = getUserDetails($user_id);
if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit();
}
$role = $user['role'];
$user_name = $user['name'];

// Initialize message variables
$success_message = '';
$error_message = '';

// ADD CUSTOMER
if (isset($_POST['add_customer'])) {
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $phone   = $_POST['phone'];
    $address = $_POST['address'];

    // Check if customer with same email already exists
    $check_stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0 && !empty($email)) {
        $error_message = "A customer with this email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phone, $address);
        if ($stmt->execute()) {
            $success_message = "Customer added successfully!";
        } else {
            $error_message = "Failed to add customer. Please try again.";
        }
        $stmt->close();
    }
    $check_stmt->close();
    
    // Store messages in session for redirect
    if ($success_message) {
        $_SESSION['success_message'] = $success_message;
    }
    if ($error_message) {
        $_SESSION['error_message'] = $error_message;
    }
    header("Location: customers.php"); 
    exit();
}

// DELETE CUSTOMER
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Customer deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete customer. Please try again.";
    }
    $stmt->close();
    header("Location: customers.php");
    exit();
}

// Check for session messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// --- SEARCH FILTER ---
$search_term = $_GET['search'] ?? '';
if (!empty($search_term)) {
    $search_like = "%" . $search_term . "%";
    $stmt = $conn->prepare("SELECT * FROM customers WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY id DESC");
    $stmt->bind_param("sss", $search_like, $search_like, $search_like);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query("SELECT * FROM customers ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Manage Customers</title>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: white; 
            padding: 18px 25px; 
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow); 
            margin-bottom: 25px; 
            display: flex;
            justify-content: space-between; 
            align-items: center;
        }
        .header h2 { 
            margin: 0; 
            font-size: 24px; 
            color: #333; 
        }
        .header-actions { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
        }
        .user-profile { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .user-avatar {
            width: 40px; 
            height: 40px; 
            border-radius: 50%;
            background: var(--primary); 
            color: white;
            display: flex; 
            justify-content: center; 
            align-items: center; 
            font-weight: bold;
        }
        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 8px; 
            display: flex; 
            align-items: center; 
            gap: 10px;
            transition: opacity 0.5s ease;
        }
        .alert.hiding {
            opacity: 0;
        }
        .alert-success { 
            background-color: #e6f4e6; 
            color: #27ae60; 
        }
        .alert-error { 
            background-color: #ffecec; 
            color: #dc2626; 
        }
        .card {
            background: white; 
            padding: 25px; 
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow); 
            margin-bottom: 25px;
        }
        .card h3 { 
            margin-top: 0; 
            margin-bottom: 20px; 
            font-size: 20px; 
            color: #333; 
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        table th, table td { 
            padding: 16px; 
            text-align: left; 
            border-bottom: 1px solid #eee; 
        }
        table th {
            background: #f8f9fa; 
            color: #555; 
            font-size: 12px;
            font-weight: 600; 
            text-transform: uppercase;
        }
        table tr:hover { 
            background-color: #f9fafb; 
        }
        .action-cell { 
            display: flex; 
            gap: 10px; 
        }
        
        .btn {
            padding: 8px 16px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer;
            font-size: 14px; 
            text-decoration: none; 
            display: inline-flex;
            align-items: center; 
            gap: 5px; 
            font-weight: 500; 
            transition: opacity 0.2s;
        }
        .btn:hover { 
            opacity: 0.85; 
        }
        .btn-edit { 
            background: #fff4e6; 
            color: #d97706; 
        }
        .btn-delete { 
            background: #ffecec; 
            color: #dc2626; 
        }
        .btn-add {
            background: var(--success); 
            color: white; 
            padding: 12px 18px;
            border-radius: 8px; 
            font-weight: bold;
        }
        
        input, textarea {
            width: 100%; 
            padding: 12px; 
            margin-bottom: 10px; 
            border: 1px solid #ddd;
            border-radius: 8px; 
            font-size: 14px; 
            box-sizing: border-box;
        }
        textarea { 
            min-height: 80px; 
            resize: vertical; 
        }

        .search-bar { 
            display: flex; 
            gap: 10px; 
            align-items: center; 
            margin-bottom: 20px; 
        }
        .search-bar input { 
            flex: 1; 
            margin: 0; 
        }
        .search-bar .btn-add { 
            margin-top: 0; 
            background-color: var(--primary); 
            padding: 12px 18px; 
        }
        .cancel-btn {
            padding: 12px 18px; 
            border-radius: 8px; 
            background: #f1f5f9;
            color: #334155; 
            text-decoration: none; 
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .search-bar {
                flex-direction: column;
            }
            .search-bar input {
                width: 100%;
            }
            .action-cell {
                flex-direction: column;
            }
            table {
                font-size: 14px;
            }
            table th, table td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h2>Manage Customers</h2>
            <div class="header-actions">
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
        <div class="alert alert-success" id="successMessage">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-error" id="errorMessage">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Add New Customer</h3>
            <form method="POST">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email">
                <input type="text" name="phone" placeholder="Phone">
                <textarea name="address" placeholder="Address"></textarea>
                <button type="submit" name="add_customer" class="btn btn-add">+ Add Customer</button>
            </form>
        </div>

        <div class="card">
            <h3>Customer List</h3>
            
            <form method="GET" class="search-bar">
                <input type="text" name="search" placeholder="Search by name, email or phone" value="<?= htmlspecialchars($search_term) ?>">
                <button type="submit" class="btn btn-add">Search</button>
                <a href="customers.php" class="cancel-btn">Reset</a>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $row['id']; ?></td>
                        <td><?= htmlspecialchars($row['name']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><?= htmlspecialchars($row['phone']); ?></td>
                        <td><?= htmlspecialchars($row['address']); ?></td>
                        <td class="action-cell">
                            <a href="edit_customer.php?id=<?= $row['id']; ?>" class="btn btn-edit"><i class="fas fa-pencil-alt"></i> Edit</a>
                            <a href="customers.php?delete=<?= $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this customer?');"><i class="fas fa-trash"></i> Delete</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Auto-dismiss messages after 4 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');
            
            function dismissMessage(messageElement) {
                if (messageElement) {
                    setTimeout(function() {
                        messageElement.classList.add('hiding');
                        setTimeout(function() {
                            messageElement.remove();
                        }, 500); // Wait for fade-out animation to complete
                    }, 4000); // 4 seconds
                }
            }
            
            dismissMessage(successMessage);
            dismissMessage(errorMessage);
        });
    </script>
</body>
</html>