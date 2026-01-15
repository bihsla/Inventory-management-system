<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include necessary files
require_once '../config/database.php';

// Helper function
function formatCurrency($amount) {
    if ($amount === null || $amount === '') {
        return 'Rs. 0.00';
    }
    return 'Rs. ' . number_format((float)$amount, 2);
}

$conn = getDBConnection();

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Initialize variables
$sales = null;
$summary = ['total_sales' => 0, 'total_revenue' => 0];

try {
    // Get sales data
    $query = "SELECT s.*, u.full_name as cashier_name 
              FROM sales s 
              JOIN users u ON s.cashier_id = u.id 
              WHERE DATE(s.sale_date) BETWEEN ? AND ?
              ORDER BY s.sale_date DESC";
    
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $sales = $stmt->get_result();
        $stmt->close();
    } else {
        throw new Exception("Failed to prepare sales query: " . $conn->error);
    }
    
    // Calculate summary
    $summary_query = "SELECT COUNT(*) as total_sales, COALESCE(SUM(final_amount), 0) as total_revenue 
                      FROM sales 
                      WHERE DATE(sale_date) BETWEEN ? AND ?";
    
    $stmt2 = $conn->prepare($summary_query);
    
    if ($stmt2) {
        $stmt2->bind_param("ss", $date_from, $date_to);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $summary = $result->fetch_assoc();
        $stmt2->close();
    } else {
        throw new Exception("Failed to prepare summary query: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Sales report error: " . $e->getMessage());
    $error_message = "Error loading sales data. Please try again.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>Sales Report</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="sales_report.php">Sales Report</a></li>
                    <li><a href="stock_report.php">Stock Report</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <h2>Sales Report</h2>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Filter Form -->
        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <form method="GET" style="display: flex; gap: 15px; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="sales_report.php" class="btn btn-warning">Reset</a>
            </form>
        </div>
        
        <!-- Summary Cards -->
        <div class="dashboard" style="margin-bottom: 20px;">
            <div class="card">
                <h3>Total Sales</h3>
                <div class="card-value"><?php echo $summary['total_sales']; ?></div>
            </div>
            <div class="card">
                <h3>Total Revenue</h3>
                <div class="card-value"><?php echo formatCurrency($summary['total_revenue']); ?></div>
            </div>
            <div class="card">
                <h3>Date Range</h3>
                <div style="font-size: 14px; margin-top: 10px;">
                    <?php echo date('d M Y', strtotime($date_from)); ?><br>to<br><?php echo date('d M Y', strtotime($date_to)); ?>
                </div>
            </div>
        </div>
        
        <!-- Sales Table -->
        <div class="table-container">
            <h3>Sales Details</h3>
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Cashier</th>
                        <th>Total Amount</th>
                        <th>Discount</th>
                        <th>Final Amount</th>
                        <th>Payment Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sales && $sales->num_rows > 0): ?>
                        <?php while ($sale = $sales->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($sale['sale_date'])); ?></td>
                            <td><?php echo htmlspecialchars($sale['cashier_name']); ?></td>
                            <td><?php echo formatCurrency($sale['total_amount']); ?></td>
                            <td style="color: green;"><?php echo $sale['discount'] > 0 ? '-' . formatCurrency($sale['discount']) : '-'; ?></td>
                            <td style="font-weight: bold;"><?php echo formatCurrency($sale['final_amount']); ?></td>
                            <td><?php echo strtoupper($sale['payment_method']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No sales found for the selected date range</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($sales && $sales->num_rows > 0): ?>
        <div style="margin-top: 20px; text-align: right;">
            <button onclick="window.print()" class="btn btn-success">Print Report</button>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
        @media print {
            header, nav, button, .btn, .no-print {
                display: none !important;
            }
            body {
                background: white;
            }
            .table-container {
                box-shadow: none;
            }
        }
    </style>
</body>
</html>
<?php
$conn->close();
?>