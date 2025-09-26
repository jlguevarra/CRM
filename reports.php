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

// Fetch user role from database
$sql = "SELECT role, name FROM users WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $role = $user['role'];
    $user_name = $user['name'];
} else {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Check if user is admin
if ($role !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Initialize variables
$report_data = [];
$stats = [
    'total_customers' => 0, 'new_customers' => 0, 'tasks_completed' => 0,
    'conversion_rate' => 0, 'tasks_created' => 0, 'total_users' => 0
];
$report_type = $_POST['report_type'] ?? ($_GET['report_type'] ?? 'sales');
$date_range = $_POST['date_range'] ?? ($_GET['date_range'] ?? '30');
$start_date = $_POST['start_date'] ?? ($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
$end_date = $_POST['end_date'] ?? ($_GET['end_date'] ?? date('Y-m-d'));

// Handle Load Saved Report
if (isset($_GET['load_report'])) {
    $report_id = $_GET['load_report'];
    
    // Get saved report
    $table_check = $conn->query("SHOW TABLES LIKE 'saved_reports'");
    if ($table_check->num_rows > 0) {
        $sql = "SELECT * FROM saved_reports WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $report_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $saved_report = $result->fetch_assoc();
                $filters = json_decode($saved_report['filters'], true);
                
                // Set the filters from saved report
                $report_type = $filters['report_type'];
                $date_range = $filters['date_range'];
                $start_date = $filters['start_date'];
                $end_date = $filters['end_date'];
                
                $_SESSION['load_success'] = "Report '{$saved_report['title']}' loaded successfully!";
            }
        }
    }
}

// Handle PDF Export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $filters = [
        'report_type' => $report_type,
        'date_range' => $date_range,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
    
    $report_data = generateReportData($filters, $conn);
    $stats = calculateStats($report_data, $report_type, $conn);
    
    exportToPDF($report_data, $stats, $report_type, $filters, $user_name);
    exit;
}

// Handle Excel Export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filters = [
        'report_type' => $report_type,
        'date_range' => $date_range,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
    
    $report_data = generateReportData($filters, $conn);
    $stats = calculateStats($report_data, $report_type, $conn);
    
    exportToExcel($report_data, $stats, $report_type, $filters, $user_name);
    exit;
}

// Handle Saved Report Export
if (isset($_GET['export_saved'])) {
    $report_id = $_GET['export_saved'];
    $export_type = $_GET['type'] ?? 'excel';
    
    // Get saved report
    $table_check = $conn->query("SHOW TABLES LIKE 'saved_reports'");
    if ($table_check->num_rows > 0) {
        $sql = "SELECT * FROM saved_reports WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $report_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $saved_report = $result->fetch_assoc();
                $filters = json_decode($saved_report['filters'], true);
                
                $report_data = generateReportData($filters, $conn);
                $stats = calculateStats($report_data, $filters['report_type'], $conn);
                
                if ($export_type === 'pdf') {
                    exportToPDF($report_data, $stats, $filters['report_type'], $filters, $user_name, $saved_report['title']);
                } else {
                    exportToExcel($report_data, $stats, $filters['report_type'], $filters, $user_name, $saved_report['title']);
                }
                exit;
            }
        }
    }
}

// Process filters if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = [
        'report_type' => $report_type,
        'date_range' => $date_range,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
    
    // Generate report data based on filters
    $report_data = generateReportData($filters, $conn);
    $stats = calculateStats($report_data, $report_type, $conn);
}

// Handle report saving
if (isset($_POST['save_report'])) {
    $title = $_POST['report_title'] ?? 'Untitled Report';
    $description = $_POST['report_description'] ?? '';
    $filters_json = json_encode($filters);
    
    // Check if table exists, if not create it
    $table_check = $conn->query("SHOW TABLES LIKE 'saved_reports'");
    if ($table_check->num_rows == 0) {
        // Create the table if it doesn't exist
        $create_table = "CREATE TABLE saved_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            filters TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($create_table)) {
            // Table created successfully
        } else {
            $save_error = "Error creating table: " . $conn->error;
        }
    }
    
    if (!isset($save_error)) {
        $sql = "INSERT INTO saved_reports (user_id, title, description, filters, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $title, $description, $filters_json);
            
            if ($stmt->execute()) {
                $_SESSION['save_success'] = "Report saved successfully!";
                header("Location: reports.php");
                exit;
            } else {
                $save_error = "Error saving report: " . $stmt->error;
            }
        } else {
            $save_error = "Error preparing statement: " . $conn->error;
        }
    }
}

// Handle report deletion
if (isset($_GET['delete_report'])) {
    $report_id = $_GET['delete_report'];
    
    // Check if table exists first
    $table_check = $conn->query("SHOW TABLES LIKE 'saved_reports'");
    if ($table_check->num_rows > 0) {
        // Verify ownership
        $sql = "DELETE FROM saved_reports WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ii", $report_id, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['delete_success'] = "Report deleted successfully!";
                header("Location: reports.php");
                exit;
            } else {
                $delete_error = "Error deleting report: " . $stmt->error;
            }
        }
    }
}

// Get user's saved reports
$user_reports = [];
$table_check = $conn->query("SHOW TABLES LIKE 'saved_reports'");
if ($table_check->num_rows > 0) {
    $sql = "SELECT * FROM saved_reports WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $user_reports[] = $row;
        }
    }
}

// Check for session messages
$save_success = $_SESSION['save_success'] ?? '';
$delete_success = $_SESSION['delete_success'] ?? '';
$load_success = $_SESSION['load_success'] ?? '';
unset($_SESSION['save_success'], $_SESSION['delete_success'], $_SESSION['load_success']);

// Function to generate report data from actual database
function generateReportData($filters, $conn) {
    $report_type = $filters['report_type'];
    $start_date = $filters['start_date'];
    $end_date = $filters['end_date'];
    $data = [];
    
    switch ($report_type) {
        case 'sales':
            // Sales performance data - new customers per day
            $sql = "SELECT DATE(created_at) as date, COUNT(*) as new_customers 
                    FROM customers 
                    WHERE created_at BETWEEN ? AND ? 
                    GROUP BY DATE(created_at) 
                    ORDER BY date";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        case 'customers':
            // Customer analytics data - new customers and running total
            $sql = "SELECT DATE(created_at) as date, COUNT(*) as new_customers
                    FROM customers 
                    WHERE created_at BETWEEN ? AND ? 
                    GROUP BY DATE(created_at) 
                    ORDER BY date";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $running_total = 0;
            // Get total customers before start date
            $sql_total = "SELECT COUNT(*) as total FROM customers WHERE created_at < ?";
            $stmt_total = $conn->prepare($sql_total);
            $stmt_total->bind_param("s", $start_date);
            $stmt_total->execute();
            $result_total = $stmt_total->get_result();
            if ($row_total = $result_total->fetch_assoc()) {
                $running_total = $row_total['total'];
            }
            
            while ($row = $result->fetch_assoc()) {
                $running_total += $row['new_customers'];
                $row['total_customers'] = $running_total;
                $data[] = $row;
            }
            break;
            
        case 'tasks':
            // Task completion data
            $sql = "SELECT DATE(created_at) as date, 
                    COUNT(*) as tasks_created,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as tasks_completed
                    FROM tasks 
                    WHERE created_at BETWEEN ? AND ? 
                    GROUP BY DATE(created_at) 
                    ORDER BY date";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                if ($row['tasks_created'] > 0) {
                    $row['completion_rate'] = round(($row['tasks_completed'] / $row['tasks_created']) * 100, 1);
                } else {
                    $row['completion_rate'] = 0;
                }
                $data[] = $row;
            }
            break;
            
        case 'users':
            // User activity data
            $sql = "SELECT u.id, u.name, 
                    COUNT(t.id) as total_tasks,
                    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
                    FROM users u
                    LEFT JOIN tasks t ON u.id = t.assigned_to
                    WHERE (t.created_at BETWEEN ? AND ? OR t.created_at IS NULL)
                    GROUP BY u.id, u.name
                    ORDER BY u.name";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                if ($row['total_tasks'] > 0) {
                    $row['completion_rate'] = round(($row['completed_tasks'] / $row['total_tasks']) * 100, 1);
                } else {
                    $row['completion_rate'] = 0;
                }
                $data[] = $row;
            }
            break;
    }
    
    return $data;
}

// Function to calculate statistics from actual data
function calculateStats($report_data, $report_type, $conn) {
    $stats = [
        'total_customers' => 0, 'new_customers' => 0, 'tasks_completed' => 0,
        'conversion_rate' => 0, 'tasks_created' => 0, 'total_users' => 0
    ];
    
    switch ($report_type) {
        case 'sales':
        case 'customers':
            $stats['new_customers'] = array_sum(array_column($report_data, 'new_customers'));
            
            // Get total customers from database
            $sql = "SELECT COUNT(*) as total FROM customers";
            $result = $conn->query($sql);
            if ($row = $result->fetch_assoc()) {
                $stats['total_customers'] = $row['total'];
            }
            
            // Calculate conversion rate
            if ($stats['new_customers'] > 0) {
                $stats['conversion_rate'] = min(100, round(($stats['new_customers'] / max(1, $stats['total_customers'] / 10)) * 100, 1));
            }
            break;
            
        case 'tasks':
            $stats['tasks_created'] = array_sum(array_column($report_data, 'tasks_created'));
            $stats['tasks_completed'] = array_sum(array_column($report_data, 'tasks_completed'));
            
            if ($stats['tasks_created'] > 0) {
                $stats['conversion_rate'] = round(($stats['tasks_completed'] / $stats['tasks_created']) * 100, 1);
            }
            break;
            
        case 'users':
            $stats['total_users'] = count($report_data);
            $stats['tasks_created'] = array_sum(array_column($report_data, 'total_tasks'));
            $stats['tasks_completed'] = array_sum(array_column($report_data, 'completed_tasks'));
            
            if ($stats['tasks_created'] > 0) {
                $stats['conversion_rate'] = round(($stats['tasks_completed'] / $stats['tasks_created']) * 100, 1);
            }
            break;
    }
    
    return $stats;
}

// PDF Export Function - Improved version
function exportToPDF($report_data, $stats, $report_type, $filters, $user_name, $title = '') {
    $filename = $title ?: $report_type . '_report_' . date('Y-m-d');
    
    // Create a simple HTML-based PDF
    $html = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .header-info { margin-bottom: 20px; }
            .stats { margin: 20px 0; }
            .stat-item { margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h1>CRM Report</h1>
        
        <div class='header-info'>
            <p><strong>Generated by:</strong> {$user_name}</p>
            <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>Report Type:</strong> " . ucfirst($report_type) . "</p>
            <p><strong>Period:</strong> {$filters['start_date']} to {$filters['end_date']}</p>
        </div>
        
        <div class='stats'>
            <h2>Statistics</h2>";
    
    foreach ($stats as $key => $value) {
        $label = ucfirst(str_replace('_', ' ', $key));
        $html .= "<div class='stat-item'><strong>{$label}:</strong> {$value}</div>";
    }
    
    $html .= "</div>";
    
    if (!empty($report_data)) {
        $html .= "<h2>Report Data</h2><table>";
        
        // Table headers
        $html .= "<tr>";
        foreach (array_keys((array)$report_data[0]) as $header) {
            $html .= "<th>" . ucfirst(str_replace('_', ' ', $header)) . "</th>";
        }
        $html .= "</tr>";
        
        // Table rows
        foreach ($report_data as $row) {
            $html .= "<tr>";
            foreach ($row as $value) {
                $html .= "<td>" . $value . "</td>";
            }
            $html .= "</tr>";
        }
        
        $html .= "</table>";
    }
    
    $html .= "</body></html>";
    
    // Use MPDF or Dompdf for better PDF generation
    // For now, output as HTML that can be printed as PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    
    // Simple PDF content using TCPDF-like structure
    $pdf_content = "%PDF-1.4
1 0 obj
<</Type/Catalog/Pages 2 0 R>>
endobj
2 0 obj
<</Type/Pages/Kids[3 0 R]/Count 1>>
endobj
3 0 obj
<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R>>
endobj
4 0 obj
<</Length 1000>>
stream
BT
/F1 12 Tf
50 750 Td
(CRM Report) Tj
0 -20 Td
(Generated by: {$user_name}) Tj
0 -20 Td
(Date: " . date('Y-m-d H:i:s') . ") Tj
0 -20 Td
(Report Type: " . ucfirst($report_type) . ") Tj
0 -20 Td
(Period: {$filters['start_date']} to {$filters['end_date']}) Tj
0 -40 Td
(Statistics:) Tj
0 -20 Td
";

    foreach ($stats as $key => $value) {
        $label = ucfirst(str_replace('_', ' ', $key));
        $pdf_content .= "({$label}: {$value}) Tj\n0 -20 Td\n";
    }
    
    $pdf_content .= "0 -40 Td
(Report Data:) Tj
0 -20 Td
";
    
    if (!empty($report_data)) {
        foreach ($report_data as $row) {
            $line = implode(' | ', $row);
            $pdf_content .= "({$line}) Tj\n0 -20 Td\n";
        }
    }
    
    $pdf_content .= "ET
endstream
endobj
xref
0 5
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000239 00000 n 
trailer
<</Size 5/Root 1 0 R>>
startxref
" . strlen($pdf_content) . "
%%EOF";
    
    echo $pdf_content;
    exit;
}

// Excel Export Function
function exportToExcel($report_data, $stats, $report_type, $filters, $user_name, $title = '') {
    $filename = $title ?: $report_type . '_report_' . date('Y-m-d');
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo "<html>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<style>td { border: 1px solid #000; padding: 5px; }</style>";
    echo "</head>";
    echo "<body>";
    echo "<table>";
    
    // Header information
    echo "<tr><td colspan='4'><b>CRM Report</b></td></tr>";
    echo "<tr><td><b>Generated by:</b></td><td>" . $user_name . "</td><td><b>Date:</b></td><td>" . date('Y-m-d H:i:s') . "</td></tr>";
    echo "<tr><td><b>Report Type:</b></td><td>" . ucfirst($report_type) . "</td><td><b>Period:</b></td><td>" . $filters['start_date'] . " to " . $filters['end_date'] . "</td></tr>";
    echo "<tr><td colspan='4'></td></tr>";
    
    // Statistics
    echo "<tr><td colspan='4'><b>Statistics</b></td></tr>";
    foreach ($stats as $key => $value) {
        echo "<tr><td>" . ucfirst(str_replace('_', ' ', $key)) . "</td><td>" . $value . "</td><td colspan='2'></td></tr>";
    }
    echo "<tr><td colspan='4'></td></tr>";
    
    // Report data headers
    if (!empty($report_data)) {
        echo "<tr><td colspan='" . count((array)$report_data[0]) . "'><b>Report Data</b></td></tr>";
        echo "<tr>";
        foreach (array_keys((array)$report_data[0]) as $header) {
            echo "<td><b>" . ucfirst(str_replace('_', ' ', $header)) . "</b></td>";
        }
        echo "</tr>";
        
        // Report data rows
        foreach ($report_data as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . $value . "</td>";
            }
            echo "</tr>";
        }
    }
    
    echo "</table>";
    echo "</body>";
    echo "</html>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - CRM System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: #333;
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 0;
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

        .filters {
            background: white; 
            padding: 20px; 
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow); 
            margin-bottom: 25px; 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; 
            align-items: flex-end;
        }
        
        .filter-item { 
            display: flex; 
            flex-direction: column; 
        }
        
        .filter-item label { 
            margin-bottom: 8px; 
            font-size: 14px; 
            color: #555; 
            font-weight: 500;
        }
        
        .filter-item select, .filter-item input {
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px; 
            border: none; 
            border-radius: 6px;
            cursor: pointer; 
            font-size: 14px; 
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary { 
            background-color: var(--primary); 
            color: white; 
        }
        
        .btn-success { 
            background-color: var(--success); 
            color: white; 
        }
        
        .btn-danger { 
            background-color: var(--danger); 
            color: white; 
        }
        
        .btn-outline { 
            background: #f1f5f9; 
            color: #334155; 
            border: 1px solid #cbd5e1;
        }

        .stats-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px; 
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white; 
            padding: 20px; 
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 { 
            margin: 0 0 10px 0; 
            font-size: 16px; 
            color: #555; 
        }
        
        .stat-card .value { 
            font-size: 32px; 
            font-weight: 600; 
            color: var(--dark); 
        }
        
        .stat-card .change { 
            font-size: 14px; 
            margin-top: 5px; 
        }
        
        .change.positive { 
            color: var(--success); 
        }
        
        .change.negative { 
            color: var(--danger); 
        }

        .data-table {
            width: 100%; 
            background: white; 
            border-collapse: collapse;
            border-radius: var(--border-radius); 
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 25px;
        }
        
        .data-table th, .data-table td { 
            padding: 16px; 
            text-align: left; 
            border-bottom: 1px solid #eee; 
        }
        
        .data-table th {
            background: #f8f9fa; 
            color: #555; 
            font-size: 14px;
            font-weight: 600; 
            text-transform: uppercase;
        }
        
        .data-table tr:hover { 
            background-color: #f9fafb; 
        }

        .card {
             background: white; 
             padding: 25px; 
             border-radius: var(--border-radius); 
             box-shadow: var(--box-shadow); 
             margin-bottom: 25px; 
        }
        
        .card-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-header h2 { 
            margin: 0; 
            font-size: 20px; 
        }
        
        .report-item {
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            padding: 15px 0; 
            border-bottom: 1px solid #eee;
        }
        
        .report-item:last-child { 
            border-bottom: none; 
        }
        
        .report-actions { 
            display: flex; 
            gap: 10px; 
        }
        
        .chart-container {
            height: 300px;
            margin-bottom: 25px;
        }
        
        .export-options {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            transition: opacity 0.5s ease;
        }
        
        .alert.hiding {
            opacity: 0;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            margin: 0;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        /* Sidebar adjustments */
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 100;
        }
        
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h2>Reports</h2>
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
        
        <?php if ($save_success): ?>
            <div class="alert alert-success" id="saveSuccess">
                <?php echo $save_success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($load_success): ?>
            <div class="alert alert-success" id="loadSuccess">
                <?php echo $load_success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($save_error)): ?>
            <div class="alert alert-error"><?php echo $save_error; ?></div>
        <?php endif; ?>
        
        <?php if ($delete_success): ?>
            <div class="alert alert-success" id="deleteSuccess">
                <?php echo $delete_success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($delete_error)): ?>
            <div class="alert alert-error"><?php echo $delete_error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="filters">
                <div class="filter-item">
                    <label for="reportType">Report Type</label>
                    <select id="reportType" name="report_type">
                        <option value="sales" <?= $report_type === 'sales' ? 'selected' : '' ?>>Sales Performance</option>
                        <option value="customers" <?= $report_type === 'customers' ? 'selected' : '' ?>>Customer Analytics</option>
                        <option value="tasks" <?= $report_type === 'tasks' ? 'selected' : '' ?>>Task Completion</option>
                        <option value="users" <?= $report_type === 'users' ? 'selected' : '' ?>>User Activity</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="dateRange">Date Range</label>
                    <select id="dateRange" name="date_range">
                        <option value="7" <?= $date_range === '7' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="30" <?= $date_range === '30' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="90" <?= $date_range === '90' ? 'selected' : '' ?>>Last 90 Days</option>
                        <option value="custom" <?= $date_range === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="startDate">Start Date</label>
                    <input type="date" id="startDate" name="start_date" value="<?= $start_date ?>">
                </div>
                <div class="filter-item">
                    <label for="endDate">End Date</label>
                    <input type="date" id="endDate" name="end_date" value="<?= $end_date ?>">
                </div>
                <div class="filter-item">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </div>
        </form>
        
        <?php if (!empty($report_data)): ?>
            <div class="export-options">
                <a href="reports.php?export=pdf&report_type=<?= $report_type ?>&date_range=<?= $date_range ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-outline">
                    <i class="fas fa-file-pdf"></i> Export to PDF
                </a>
                <a href="reports.php?export=excel&report_type=<?= $report_type ?>&date_range=<?= $date_range ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-outline">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </a>
                <button class="btn btn-success" onclick="openSaveModal()"><i class="fas fa-save"></i> Save Report</button>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?= ($report_type === 'tasks') ? 'Total Tasks' : (($report_type === 'users') ? 'Total Users' : 'Total Customers') ?></h3>
                    <div class="value"><?= ($report_type === 'tasks') ? ($stats['tasks_created'] ?? 0) : (($report_type === 'users') ? ($stats['total_users'] ?? 0) : ($stats['total_customers'] ?? 0)) ?></div>
                    <div class="change positive">Based on selected filters</div>
                </div>
                <div class="stat-card">
                    <h3><?= ($report_type === 'tasks' || $report_type === 'users') ? 'Tasks Completed' : 'New Customers' ?></h3>
                    <div class="value"><?= ($report_type === 'tasks' || $report_type === 'users') ? ($stats['tasks_completed'] ?? 0) : ($stats['new_customers'] ?? 0) ?></div>
                    <div class="change positive">During period</div>
                </div>
                <div class="stat-card">
                    <h3>Completion Rate</h3>
                    <div class="value"><?= $stats['conversion_rate'] ?? 0 ?>%</div>
                    <div class="change <?= ($stats['conversion_rate'] ?? 0) >= 70 ? 'positive' : 'negative' ?>"><?= ($stats['conversion_rate'] ?? 0) >= 70 ? 'Good' : 'Needs improvement' ?></div>
                </div>
                <div class="stat-card">
                    <h3>Data Points</h3>
                    <div class="value"><?= count($report_data) ?></div>
                    <div class="change positive">Records found</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Report Visualization</h2>
                </div>
                <div class="chart-container">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>
        
            <table class="data-table">
                <thead>
                    <tr>
                        <?php if ($report_type === 'users'): ?>
                            <th>User Name</th><th>Total Tasks</th><th>Completed Tasks</th><th>Completion Rate</th>
                        <?php else: ?>
                            <th>Date</th>
                            <?php if (in_array($report_type, ['sales', 'customers'])): ?>
                                <th>New Customers</th>
                                <?php if ($report_type === 'customers'): ?><th>Total Customers</th><?php endif; ?>
                            <?php elseif ($report_type === 'tasks'): ?>
                                <th>Tasks Created</th><th>Tasks Completed</th><th>Completion Rate</th>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $row): ?>
                    <tr>
                        <?php if ($report_type === 'users'): ?>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= $row['total_tasks'] ?></td>
                            <td><?= $row['completed_tasks'] ?></td>
                            <td><?= $row['completion_rate'] ?>%</td>
                        <?php else: ?>
                            <td><?= date('M j, Y', strtotime($row['date'])) ?></td>
                            <?php if (in_array($report_type, ['sales', 'customers'])): ?>
                                <td><?= $row['new_customers'] ?></td>
                                <?php if ($report_type === 'customers'): ?><td><?= $row['total_customers'] ?? 'N/A' ?></td><?php endif; ?>
                            <?php elseif ($report_type === 'tasks'): ?>
                                <td><?= $row['tasks_created'] ?></td>
                                <td><?= $row['tasks_completed'] ?></td>
                                <td><?= $row['completion_rate'] ?>%</td>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-chart-bar"></i>
                <h3>No Report Data</h3>
                <p>Apply filters and generate a report to see data visualization and statistics.</p>
            </div>
        <?php endif; ?>

        <div class="card saved-reports">
            <div class="card-header">
                <h2>Saved Reports</h2>
            </div>
            <?php if (!empty($user_reports)): ?>
                <?php foreach ($user_reports as $report): ?>
                <div class="report-item">
                    <div>
                        <h3><?= htmlspecialchars($report['title']) ?></h3>
                        <p><?= htmlspecialchars($report['description']) ?></p>
                        <small>Created: <?= date('M j, Y', strtotime($report['created_at'])) ?></small>
                    </div>
                    <div class="report-actions">
                        <a href="reports.php?load_report=<?= $report['id'] ?>" class="btn btn-outline">Load</a>
                        <a href="reports.php?export_saved=<?= $report['id'] ?>&type=excel" class="btn btn-outline">Export Excel</a>
                        <a href="reports.php?export_saved=<?= $report['id'] ?>&type=pdf" class="btn btn-outline">Export PDF</a>
                        <a href="reports.php?delete_report=<?= $report['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this report?')">Delete</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Saved Reports</h3>
                    <p>Generate a report and save it to access it later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Save Report Modal -->
    <div id="saveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Save Report</h3>
                <button class="close-modal" onclick="closeSaveModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="report_type" value="<?= $report_type ?>">
                <input type="hidden" name="date_range" value="<?= $date_range ?>">
                <input type="hidden" name="start_date" value="<?= $start_date ?>">
                <input type="hidden" name="end_date" value="<?= $end_date ?>">
                
                <div class="form-group">
                    <label for="reportTitle">Report Title</label>
                    <input type="text" id="reportTitle" name="report_title" value="<?= ucfirst($report_type) ?> Report - <?= date('M j, Y') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="reportDescription">Description</label>
                    <textarea id="reportDescription" name="report_description" rows="3" placeholder="Optional description of this report"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeSaveModal()">Cancel</button>
                    <button type="submit" name="save_report" class="btn btn-primary">Save Report</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-dismiss success messages after 4 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMessages = document.querySelectorAll('.alert-success');
            
            successMessages.forEach(function(message) {
                setTimeout(function() {
                    message.classList.add('hiding');
                    setTimeout(function() {
                        message.remove();
                    }, 500);
                }, 4000);
            });
            
            // Initialize date range functionality
            const dateRange = document.getElementById('dateRange');
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            
            if (dateRange) {
                dateRange.addEventListener('change', function() {
                    const isCustom = this.value === 'custom';
                    startDate.disabled = !isCustom;
                    endDate.disabled = !isCustom;
                    
                    if (!isCustom) {
                        const today = new Date();
                        const days = parseInt(this.value);
                        const start = new Date();
                        start.setDate(today.getDate() - days);
                        startDate.value = start.toISOString().split('T')[0];
                        endDate.value = today.toISOString().split('T')[0];
                    }
                });
                
                if (dateRange.value !== 'custom') {
                    startDate.disabled = true;
                    endDate.disabled = true;
                }
            }
            
            // Initialize chart if we have data
            <?php if (!empty($report_data)): ?>
                initializeChart();
            <?php endif; ?>
        });
        
        // Chart initialization
        function initializeChart() {
            const ctx = document.getElementById('reportChart').getContext('2d');
            const reportType = '<?= $report_type ?>';
            
            <?php 
            if ($report_type === 'users') {
                $labels = [];
                $data1 = [];
                $data2 = [];
                
                foreach ($report_data as $row) {
                    $labels[] = $row['name'];
                    $data1[] = $row['total_tasks'];
                    $data2[] = $row['completed_tasks'];
                }
                
                echo "const labels = " . json_encode($labels) . ";\n";
                echo "const data1 = " . json_encode($data1) . ";\n";
                echo "const data2 = " . json_encode($data2) . ";\n";
            } else {
                $labels = [];
                $data1 = [];
                $data2 = [];
                
                foreach ($report_data as $row) {
                    $labels[] = date('M j', strtotime($row['date']));
                    
                    if ($report_type === 'sales' || $report_type === 'customers') {
                        $data1[] = $row['new_customers'];
                        if ($report_type === 'customers') {
                            $data2[] = $row['total_customers'];
                        }
                    } elseif ($report_type === 'tasks') {
                        $data1[] = $row['tasks_created'];
                        $data2[] = $row['tasks_completed'];
                    }
                }
                
                echo "const labels = " . json_encode($labels) . ";\n";
                echo "const data1 = " . json_encode($data1) . ";\n";
                if (!empty($data2)) {
                    echo "const data2 = " . json_encode($data2) . ";\n";
                }
            }
            ?>
            
            let datasets = [];
            
            if (reportType === 'sales') {
                datasets = [{
                    label: 'New Customers',
                    data: data1,
                    backgroundColor: 'rgba(67, 97, 238, 0.2)',
                    borderColor: 'rgba(67, 97, 238, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }];
            } else if (reportType === 'customers') {
                datasets = [
                    {
                        label: 'New Customers',
                        data: data1,
                        backgroundColor: 'rgba(67, 97, 238, 0.2)',
                        borderColor: 'rgba(67, 97, 238, 1)',
                        borderWidth: 2,
                        tension: 0.3
                    },
                    {
                        label: 'Total Customers',
                        data: data2,
                        backgroundColor: 'rgba(40, 167, 69, 0.2)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        tension: 0.3
                    }
                ];
            } else if (reportType === 'tasks') {
                datasets = [
                    {
                        label: 'Tasks Created',
                        data: data1,
                        backgroundColor: 'rgba(67, 97, 238, 0.2)',
                        borderColor: 'rgba(67, 97, 238, 1)',
                        borderWidth: 2,
                        tension: 0.3
                    },
                    {
                        label: 'Tasks Completed',
                        data: data2,
                        backgroundColor: 'rgba(40, 167, 69, 0.2)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        tension: 0.3
                    }
                ];
            } else if (reportType === 'users') {
                datasets = [
                    {
                        label: 'Total Tasks',
                        data: data1,
                        backgroundColor: 'rgba(67, 97, 238, 0.2)',
                        borderColor: 'rgba(67, 97, 238, 1)',
                        borderWidth: 2
                    },
                    {
                        label: 'Completed Tasks',
                        data: data2,
                        backgroundColor: 'rgba(40, 167, 69, 0.2)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2
                    }
                ];
            }
            
            new Chart(ctx, {
                type: reportType === 'users' ? 'bar' : 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Modal functions
        function openSaveModal() {
            document.getElementById('saveModal').style.display = 'flex';
        }
        
        function closeSaveModal() {
            document.getElementById('saveModal').style.display = 'none';
        }
    </script>
</body>
</html>