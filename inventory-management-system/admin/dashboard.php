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

// Initialize variables
$total_products = 0;
$total_sales = 0;
$total_revenue = 0;
$low_stock = 0;

// Get statistics with error handling
try {
    // Get total products
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_products = $row['count'];
    }
    
    // Get total sales
    $result = $conn->query("SELECT COUNT(*) as count FROM sales");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_sales = $row['count'];
    }
    
    // Get total revenue
    $result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM sales");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_revenue = $row['total'];
    }
    
    // Get low stock count
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity <= min_quantity");
    if ($result) {
        $row = $result->fetch_assoc();
        $low_stock = $row['count'];
    }
    
    // Get low stock products list
    $low_stock_products = $conn->query("SELECT product_name, quantity, min_quantity FROM products WHERE quantity <= min_quantity ORDER BY quantity ASC LIMIT 5");
    
    // Get today's statistics
    $today = date('Y-m-d');
    $today_sales_result = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(final_amount), 0) as revenue FROM sales WHERE DATE(sale_date) = '$today'");
    $today_stats = $today_sales_result->fetch_assoc();
    $today_sales_count = $today_stats['count'];
    $today_revenue = $today_stats['revenue'];
    
    // Get total stock value
    $stock_value_result = $conn->query("SELECT COALESCE(SUM(price * quantity), 0) as total_value FROM products");
    $stock_value = $stock_value_result->fetch_assoc()['total_value'];
    
    // Get out of stock products
    $out_of_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity = 0")->fetch_assoc()['count'];
    
    // Get this month's revenue
    $current_month = date('Y-m');
    $month_revenue_result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as revenue FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = '$current_month'");
    $month_revenue = $month_revenue_result->fetch_assoc()['revenue'];
    
    // Get total customers (unique sales)
    $total_customers = $conn->query("SELECT COUNT(DISTINCT cashier_id) as count FROM sales")->fetch_assoc()['count'];
    
    // Get top selling products
    $top_products = $conn->query("SELECT p.product_name, SUM(si.quantity) as total_sold, SUM(si.subtotal) as revenue 
                                   FROM sale_items si 
                                   JOIN products p ON si.product_id = p.id 
                                   GROUP BY si.product_id 
                                   ORDER BY total_sold DESC 
                                   LIMIT 5");
    
    // Get recent activities (last 10 stock movements)
    $recent_activities = $conn->query("SELECT sh.*, p.product_name, u.full_name 
                                       FROM stock_history sh 
                                       JOIN products p ON sh.product_id = p.id 
                                       JOIN users u ON sh.user_id = u.id 
                                       ORDER BY sh.created_at DESC 
                                       LIMIT 10");
    
    // Get total categories
    $cat_count_result = $conn->query("SELECT COUNT(DISTINCT category) as count FROM products WHERE category IS NOT NULL AND category != ''");
    $cat_count = $cat_count_result ? $cat_count_result->fetch_assoc()['count'] : 0;
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
}

// Close connection at the very end
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>Inventory System - Admin</h1>
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
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
        
        <div class="dashboard">
            <div class="card">
                <h3>Total Products</h3>
                <div class="card-value"><?php echo $total_products; ?></div>
                <small style="color: #666;">In inventory</small>
            </div>
            
            <div class="card">
                <h3>Total Sales</h3>
                <div class="card-value"><?php echo $total_sales; ?></div>
                <small style="color: #666;">All time</small>
            </div>
            
            <div class="card">
                <h3>Total Revenue</h3>
                <div class="card-value"><?php echo formatCurrency($total_revenue); ?></div>
                <small style="color: #666;">All time earnings</small>
            </div>
            
            <div class="card">
                <h3>Low Stock Items</h3>
                <div class="card-value" style="color: <?php echo $low_stock > 0 ? '#dc3545' : 'green'; ?>;">
                    <?php echo $low_stock; ?>
                </div>
                <?php if ($low_stock > 0): ?>
                <a href="inventory.php?filter=low_stock" style="display: block; margin-top: 10px; color: #667eea; font-size: 14px; text-decoration: none;">
                    View Low Stock →
                </a>
                <?php endif; ?>
                <small style="color: #666;">Needs restocking</small>
            </div>
        </div>
        
        <!-- Today's Statistics -->
        <div style="margin-top: 20px;">
            <h3>Today's Performance</h3>
            <div class="dashboard">
                <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h3 style="color: white;">Today's Sales</h3>
                    <div class="card-value" style="color: white;"><?php echo $today_sales_count; ?></div>
                    <small style="color: rgba(255,255,255,0.8);">Sales made today</small>
                </div>
                
                <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                    <h3 style="color: white;">Today's Revenue</h3>
                    <div class="card-value" style="color: white;"><?php echo formatCurrency($today_revenue); ?></div>
                    <small style="color: rgba(255,255,255,0.8);">Earnings today</small>
                </div>
                
                <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                    <h3 style="color: white;">Stock Value</h3>
                    <div class="card-value" style="color: white;"><?php echo formatCurrency($stock_value); ?></div>
                    <small style="color: rgba(255,255,255,0.8);">Total inventory worth</small>
                </div>
                
                <div class="card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                    <h3 style="color: white;">Month Revenue</h3>
                    <div class="card-value" style="color: white;"><?php echo formatCurrency($month_revenue); ?></div>
                    <small style="color: rgba(255,255,255,0.8)"><?php echo date('F Y'); ?></small>
                </div>
            </div>
        </div>
        
        <!-- Additional Stats -->
        <div style="margin-top: 20px;">
            <div class="dashboard">
                <div class="card">
                    <h3>Out of Stock</h3>
                    <div class="card-value" style="color: <?php echo $out_of_stock > 0 ? '#dc3545' : 'green'; ?>;">
                        <?php echo $out_of_stock; ?>
                    </div>
                    <small style="color: #666;">Items with 0 quantity</small>
                </div>
                
                <div class="card">
                    <h3>Total Cashiers</h3>
                    <div class="card-value"><?php echo $total_customers; ?></div>
                    <small style="color: #666;">Active cashiers</small>
                </div>
                
                <div class="card">
                    <h3>Avg Sale Value</h3>
                    <div class="card-value">
                        <?php echo $total_sales > 0 ? formatCurrency($total_revenue / $total_sales) : 'Rs. 0.00'; ?>
                    </div>
                    <small style="color: #666;">Per transaction</small>
                </div>
                
                <div class="card">
                    <h3>Total Categories</h3>
                    <div class="card-value">
                        <?php echo $cat_count; ?>
                    </div>
                    <small style="color: #666;">Product categories</small>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Top Selling Products -->
            <div class="table-container">
                <h3>Top Selling Products</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($top_products && $top_products->num_rows > 0): ?>
                            <?php $rank = 1; while ($product = $top_products->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span style="background: #667eea; color: white; padding: 2px 6px; border-radius: 3px; margin-right: 5px; font-size: 11px;">
                                        #<?php echo $rank++; ?>
                                    </span>
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                </td>
                                <td><?php echo $product['total_sold']; ?> units</td>
                                <td><?php echo formatCurrency($product['revenue']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">No sales data yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Recent Activities -->
            <div class="table-container">
                <h3> Recent Stock Activities</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Action</th>
                            <th>Change</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
                            <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(substr($activity['product_name'], 0, 20)); ?></td>
                                <td>
                                    <span style="background: <?php 
                                        echo $activity['action_type'] == 'add' ? '#28a745' : 
                                            ($activity['action_type'] == 'sale' ? '#dc3545' : '#ffc107'); 
                                    ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
                                        <?php echo ucfirst($activity['action_type']); ?>
                                    </span>
                                </td>
                                <td style="color: <?php echo $activity['quantity_change'] > 0 ? 'green' : 'red'; ?>; font-weight: bold;">
                                    <?php echo ($activity['quantity_change'] > 0 ? '+' : '') . $activity['quantity_change']; ?>
                                </td>
                                <td><?php echo htmlspecialchars(explode(' ', $activity['full_name'])[0]); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No activities yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="inventory.php" class="btn btn-primary">Manage Inventory</a>
            <a href="sales_report.php" class="btn btn-success">View Sales Report</a>
            <a href="stock_report.php" class="btn btn-warning">View Stock Report</a>
            <a href="add_item.php" class="btn" style="background: #17a2b8; color: white;">Add New Product</a>
        </div>
        
        <?php if ($low_stock > 0): ?>
        <div style="margin-top: 30px; background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
            <strong>⚠️ Alert:</strong> You have <?php echo $low_stock; ?> product(s) with low stock. 
            <a href="inventory.php" style="color: #667eea; font-weight: bold;">View Inventory →</a>
            
            <?php if ($low_stock_products && $low_stock_products->num_rows > 0): ?>
            <div style="margin-top: 15px;">
                <strong>Low Stock Items:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <?php while ($item = $low_stock_products->fetch_assoc()): ?>
                    <li style="margin: 5px 0;">
                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong> - 
                        Current: <span style="color: red; font-weight: bold;"><?php echo $item['quantity']; ?></span> / 
                        Min: <?php echo $item['min_quantity']; ?>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>