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
    return 'Rs. ' . number_format($amount, 2);
}

$conn = getDBConnection();

// Get stock history
$query = "SELECT sh.*, p.product_name, u.full_name as user_name 
          FROM stock_history sh
          JOIN products p ON sh.product_id = p.id
          JOIN users u ON sh.user_id = u.id
          ORDER BY sh.created_at DESC
          LIMIT 100";
$stock_history = $conn->query($query);

// Get current stock levels
$products = $conn->query("SELECT * FROM products ORDER BY quantity ASC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Report</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>Stock Report</h1>
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
        <h2>Stock Report</h2>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <!-- Current Stock Levels -->
        <div class="table-container" style="margin-bottom: 30px;">
            <h3>Current Stock Levels</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Current Quantity</th>
                        <th>Price</th>
                        <th>Stock Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products && $products->num_rows > 0): ?>
                        <?php while ($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td style="<?php echo $product['quantity'] < 10 ? 'color: red; font-weight: bold;' : ''; ?>">
                                <?php echo $product['quantity']; ?>
                            </td>
                            <td><?php echo formatCurrency($product['price']); ?></td>
                            <td><?php echo formatCurrency($product['price'] * $product['quantity']); ?></td>
                            <td>
                                <?php if ($product['quantity'] == 0): ?>
                                    <span style="color: red; font-weight: bold;">Out of Stock</span>
                                <?php elseif ($product['quantity'] < 10): ?>
                                    <span style="color: orange; font-weight: bold;">Low Stock</span>
                                <?php else: ?>
                                    <span style="color: green;">In Stock</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No products found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Stock Movement History -->
        <div class="table-container">
            <h3>Stock Movement History (Last 100 Records)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Before</th>
                        <th>Change</th>
                        <th>After</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stock_history && $stock_history->num_rows > 0): ?>
                        <?php while ($history = $stock_history->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d-m-Y H:i', strtotime($history['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($history['product_name'] ?? 'N/A'); ?></td>
                            <td><?php echo $history['quantity_before']; ?></td>
                            <td style="color: <?php echo $history['quantity_change'] > 0 ? 'green' : 'red'; ?>; font-weight: bold;">
                                <?php echo ($history['quantity_change'] > 0 ? '+' : '') . $history['quantity_change']; ?>
                            </td>
                            <td><?php echo $history['quantity_after']; ?></td>
                            <td><?php echo ucfirst($history['action_type']); ?></td>
                            <td><?php echo htmlspecialchars($history['user_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($history['notes'] ?? '-'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 30px;">
                                No stock movement history found. 
                                <br><br>
                                <small style="color: #666;">
                                    Stock movements will appear here when you:
                                    <ul style="list-style: none; margin-top: 10px;">
                                        <li>• Add/update stock in inventory</li>
                                        <li>• Make sales through cashier</li>
                                        <li>• Make stock adjustments</li>
                                    </ul>
                                </small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>