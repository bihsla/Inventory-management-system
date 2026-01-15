<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header('Location: ../login.php');
    exit();
}

// Include necessary files
require_once '../config/database.php';

// Helper function - Format currency
function formatCurrency($amount) {
    if ($amount === null || $amount === '') {
        return 'Rs. 0.00';
    }
    return 'Rs. ' . number_format((float)$amount, 2);
}

$conn = getDBConnection();

// Get today's date and cashier ID
$today = date('Y-m-d');
$cashier_id = $_SESSION['user_id'];

// Initialize variables
$today_sales = 0;
$today_revenue = 0;
$recent_sales = null;

try {
    // Get today's sales count
    $query = "SELECT COUNT(*) as count FROM sales WHERE cashier_id = ? AND DATE(sale_date) = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("is", $cashier_id, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $today_sales = $row['count'];
        }
        $stmt->close();
    }
    
    // Get today's revenue
    $query2 = "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE cashier_id = ? AND DATE(sale_date) = ?";
    $stmt2 = $conn->prepare($query2);
    
    if ($stmt2) {
        $stmt2->bind_param("is", $cashier_id, $today);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        if ($result2) {
            $row2 = $result2->fetch_assoc();
            $today_revenue = $row2['total'];
        }
        $stmt2->close();
    }
    
    // Get recent sales
    $query3 = "SELECT * FROM sales WHERE cashier_id = ? ORDER BY sale_date DESC LIMIT 10";
    $stmt3 = $conn->prepare($query3);
    
    if ($stmt3) {
        $stmt3->bind_param("i", $cashier_id);
        $stmt3->execute();
        $recent_sales = $stmt3->get_result();
        $stmt3->close();
    }
    
} catch (Exception $e) {
    error_log("Cashier dashboard error: " . $e->getMessage());
    $error_message = "Error loading dashboard data";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>Cashier Dashboard</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="sell.php">Sell</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="dashboard">
            <div class="card">
                <h3>Today's Sales</h3>
                <div class="card-value"><?php echo $today_sales; ?></div>
                <small style="color: #666;">Sales made today</small>
            </div>
            
            <div class="card">
                <h3>Today's Revenue</h3>
                <div class="card-value"><?php echo formatCurrency($today_revenue); ?></div>
                <small style="color: #666;">Total earnings today</small>
            </div>
            
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h3 style="color: white;">Date</h3>
                <div style="font-size: 18px; margin-top: 10px;">
                    <?php echo date('l'); ?><br>
                    <?php echo date('d F Y'); ?>
                </div>
            </div>
        </div>
        
        <div style="margin: 30px 0; text-align: center;">
            <a href="sell.php" class="btn btn-primary" style="font-size: 20px; padding: 15px 40px;">
                🛒 Start New Sale
            </a>
        </div>
        
        <div class="table-container">
            <h3>Recent Sales</h3>
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date & Time</th>
                        <th>Total Amount</th>
                        <th>Discount</th>
                        <th>Final Amount</th>
                        <th>Payment Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_sales && $recent_sales->num_rows > 0): ?>
                        <?php while ($sale = $recent_sales->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($sale['invoice_number']); ?></strong></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($sale['sale_date'])); ?></td>
                            <td><?php echo formatCurrency($sale['total_amount']); ?></td>
                            <td style="color: green;">
                                <?php 
                                $discount = isset($sale['discount']) ? $sale['discount'] : 0;
                                echo $discount > 0 ? '-' . formatCurrency($discount) : '-'; 
                                ?>
                            </td>
                            <td style="font-weight: bold; color: #667eea;">
                                <?php echo formatCurrency($sale['total_amount']); ?>
                            </td>
                            <td><?php echo strtoupper($sale['payment_method']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px;">
                                No sales yet. <a href="sell.php" style="color: #667eea; font-weight: bold;">Make your first sale →</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($today_sales > 0): ?>
        <div style="margin-top: 20px; background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;">
            <strong>✅ Great work!</strong> You've made <?php echo $today_sales; ?> sale(s) today totaling <?php echo formatCurrency($today_revenue); ?>.
        </div>
        <?php endif; ?>
    </div>
</body>
</html>