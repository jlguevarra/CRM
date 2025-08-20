<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate customer ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: customers.php");
    exit();
}
$id = (int)$_GET['id'];

// Fetch customer
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if (!$customer) {
    header("Location: customers.php");
    exit();
}

// Update customer
if (isset($_POST['update_customer'])) {
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $phone   = $_POST['phone'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("UPDATE customers SET name=?, email=?, phone=?, address=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $phone, $address, $id);
    $stmt->execute();

    header("Location: customers.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f3f4f6; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin:0; 
        }
        .card { 
            background: white; 
            padding: 30px; 
            border-radius: 16px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
            width: 420px; 
            animation: fadeIn 0.3s ease-in-out; 
        }
        h2 { 
            margin-top: 0; 
            color: #007BFF; 
            text-align: center; 
        }
        label { 
            font-weight: bold; 
            display: block; 
            margin-top: 12px; 
            margin-bottom: 5px; 
            color: #333; 
        }
        input, textarea { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ccc; 
            border-radius: 8px; 
            font-size: 14px; 
            transition: border 0.2s; 
        }
        input:focus, textarea:focus { 
            border-color: #007BFF; 
            outline: none; 
        }
        textarea { 
            resize: vertical; 
            min-height: 80px; 
        }
        button { 
            background: #007BFF; 
            color: white; 
            padding: 12px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            width: 100%; 
            margin-top: 15px; 
            font-size: 15px; 
            font-weight: bold; 
            transition: background 0.3s; 
        }
        button:hover { 
            background: #0056b3; 
        }
        .back-btn { 
            display: inline-block; 
            margin-top: 12px; 
            padding: 12px; 
            text-align: center; 
            background: #6c757d; 
            color: white; 
            border-radius: 8px; 
            text-decoration: none; 
            width: 100%; 
            font-weight: bold; 
            transition: background 0.3s; 
        }
        .back-btn:hover { 
            background: #5a6268; 
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>✏️ Edit Customer</h2>
        <form method="POST">
            <label>Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($customer['name']); ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($customer['email']); ?>">

            <label>Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($customer['phone']); ?>">

            <label>Address</label>
            <textarea name="address"><?= htmlspecialchars($customer['address']); ?></textarea>

            <button type="submit" name="update_customer">💾 Update Customer</button>
        </form>
        <a href="customers.php" class="back-btn">⬅ Back to Customers</a>
    </div>
</body>
</html>
