\<?php
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

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$conn = getDBConnection();

// Get category filter
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$low_stock_filter = isset($_GET['filter']) && $_GET['filter'] === 'low_stock' ? true : false;

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM products WHERE id = $id");
    $redirect = 'inventory.php';
    if ($category_filter) $redirect .= '?category=' . urlencode($category_filter);
    if ($low_stock_filter) $redirect .= ($category_filter ? '&' : '?') . 'filter=low_stock';
    header('Location: ' . $redirect);
    exit();
}

// Get all products with filters
if ($low_stock_filter) {
    // Show only low stock items
    if ($category_filter) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE quantity <= min_quantity AND category = ? ORDER BY quantity ASC, product_name");
        $stmt->bind_param("s", $category_filter);
        $stmt->execute();
        $products = $stmt->get_result();
    } else {
        $products = $conn->query("SELECT * FROM products WHERE quantity <= min_quantity ORDER BY quantity ASC, product_name");
    }
} elseif ($category_filter) {
    // Show category filter only
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? ORDER BY product_name");
    $stmt->bind_param("s", $category_filter);
    $stmt->execute();
    $products = $stmt->get_result();
} else {
    // Show all products
    $products = $conn->query("SELECT * FROM products ORDER BY product_name");
}

// Get all categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");

// Get product count by category
$category_counts = $conn->query("SELECT category, COUNT(*) as count, SUM(quantity) as total_stock FROM products WHERE category IS NOT NULL AND category != '' GROUP BY category");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>Inventory Management</h1>
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Product Inventory</h2>
            <a href="add_item.php" class="btn btn-success">Add New Product</a>
        </div>
        
        <!-- Active Filters Display -->
        <?php if ($category_filter || $low_stock_filter): ?>
        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin-bottom: 20px;">
            <strong>Active Filters:</strong>
            <?php if ($low_stock_filter): ?>
                <span style="background: #dc3545; color: white; padding: 5px 10px; border-radius: 3px; margin: 0 5px;">
                    ⚠️ Low Stock Only
                </span>
            <?php endif; ?>
            <?php if ($category_filter): ?>
                <span style="background: #667eea; color: white; padding: 5px 10px; border-radius: 3px; margin: 0 5px;">
                    📁 <?php echo htmlspecialchars($category_filter); ?>
                </span>
            <?php endif; ?>
            <a href="inventory.php" style="color: #856404; margin-left: 10px; font-weight: bold;">✕ Clear All Filters</a>
        </div>
        <?php endif; ?>
        
        <!-- Category Filter and Stats -->
        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px;">Filter by Category</h3>
            
            <!-- Category Stats Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 20px;">
                <?php 
                $total_products = 0;
                $total_stock_all = 0;
                $category_data = [];
                
                if ($category_counts && $category_counts->num_rows > 0):
                    while ($cat_data = $category_counts->fetch_assoc()): 
                        $total_products += $cat_data['count'];
                        $total_stock_all += $cat_data['total_stock'];
                        $category_data[] = $cat_data;
                    endwhile;
                endif;
                ?>
                
                <div style="background: <?php echo !$category_filter && !$low_stock_filter ? '#667eea' : '#f0f0f0'; ?>; color: <?php echo !$category_filter && !$low_stock_filter ? 'white' : '#333'; ?>; padding: 15px; border-radius: 8px; text-align: center;">
                    <a href="inventory.php" style="color: inherit; text-decoration: none; display: block;">
                        <div style="font-size: 24px; font-weight: bold;"><?php echo $total_products; ?></div>
                        <div style="font-size: 12px;">All Products</div>
                        <div style="font-size: 10px; opacity: 0.8;">Stock: <?php echo $total_stock_all; ?></div>
                    </a>
                </div>
                
                <?php foreach ($category_data as $cat_item): ?>
                <div style="background: <?php echo $category_filter == $cat_item['category'] && !$low_stock_filter ? '#667eea' : '#f0f0f0'; ?>; color: <?php echo $category_filter == $cat_item['category'] && !$low_stock_filter ? 'white' : '#333'; ?>; padding: 15px; border-radius: 8px; text-align: center;">
                    <a href="inventory.php?category=<?php echo urlencode($cat_item['category']); ?>" style="color: inherit; text-decoration: none; display: block;">
                        <div style="font-size: 24px; font-weight: bold;"><?php echo $cat_item['count']; ?></div>
                        <div style="font-size: 12px;"><?php echo htmlspecialchars($cat_item['category']); ?></div>
                        <div style="font-size: 10px; opacity: 0.8;">Stock: <?php echo $cat_item['total_stock']; ?></div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($category_filter && !$low_stock_filter): ?>
            <div style="background: #d1ecf1; padding: 10px 15px; border-radius: 5px; border-left: 4px solid #0c5460; color: #0c5460;">
                <strong>Filtered by:</strong> <?php echo htmlspecialchars($category_filter); ?>
                <a href="inventory.php" style="color: #0c5460; margin-left: 10px; font-weight: bold;">✕ Clear Filter</a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>QR Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products && $products->num_rows > 0): ?>
                        <?php while ($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td>
                                <span style="background: #667eea; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?>
                                </span>
                            </td>
                            <td><?php echo formatCurrency($product['price']); ?></td>
                            <td style="<?php 
                                $min_qty = isset($product['min_quantity']) ? $product['min_quantity'] : 10;
                                echo $product['quantity'] <= $min_qty ? 'color: red; font-weight: bold;' : ''; 
                            ?>">
                                <?php echo $product['quantity']; ?>
                                <?php if ($product['quantity'] <= $min_qty): ?>
                                    ⚠️
                                <?php endif; ?>
                            </td>
                            <td><?php echo $product['qr_code'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <a href="edit_item.php?id=<?php echo $product['id']; ?>" class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;">Edit</a>
                                <a href="update_stock.php?id=<?php echo $product['id']; ?>" class="btn btn-success" style="padding: 5px 10px; font-size: 12px;">Update Stock</a>
                                <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px;">
                                <?php if ($low_stock_filter): ?>
                                    <div style="color: green; font-size: 18px; margin-bottom: 10px;">✓ Great! No low stock items.</div>
                                    All products have sufficient stock levels.
                                    <br><br>
                                    <a href="inventory.php" class="btn btn-primary">View All Products</a>
                                <?php elseif ($category_filter): ?>
                                    No products found in "<?php echo htmlspecialchars($category_filter); ?>" category.
                                    <br><br>
                                    <a href="inventory.php" class="btn btn-primary">View All Products</a>
                                <?php else: ?>
                                    No products found. <a href="add_item.php" style="color: #667eea; font-weight: bold;">Add your first product →</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($low_stock_filter && $products && $products->num_rows > 0): ?>
        <div style="margin-top: 20px; background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545; color: #721c24;">
            <strong>⚠️ Action Required:</strong> These products are at or below their minimum stock levels. 
            Please restock soon to avoid stock-outs.
        </div>
        <?php endif; ?>
    </div>
</body>
</html>