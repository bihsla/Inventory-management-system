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
require_once '../includes/functions.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProductById($product_id);

if (!$product) {
    header('Location: inventory.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_code = sanitize($_POST['product_code']);
    $product_name = sanitize($_POST['product_name']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $price = (float)$_POST['price'];
    $min_quantity = (int)$_POST['min_quantity'];
    $qr_code = sanitize($_POST['qr_code']);
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE products SET product_code=?, product_name=?, description=?, category=?, price=?, min_quantity=?, qr_code=? WHERE id=?");
    $stmt->bind_param("ssssdisi", $product_code, $product_name, $description, $category, $price, $min_quantity, $qr_code, $product_id);
    
    if ($stmt->execute()) {
        $success = 'Product updated successfully!';
        $product = getProductById($product_id); // Refresh data
    } else {
        $error = 'Error updating product: ' . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>Edit Product</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px;">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="product_code">Product Code *</label>
                    <input type="text" id="product_code" name="product_code" value="<?php echo $product['product_code']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="product_name">Product Name *</label>
                    <input type="text" id="product_name" name="product_name" value="<?php echo $product['product_name']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?php echo $product['description']; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" value="<?php echo $product['category']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="price">Price *</label>
                    <input type="number" id="price" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="min_quantity">Minimum Quantity Alert</label>
                    <input type="number" id="min_quantity" name="min_quantity" value="<?php echo $product['min_quantity']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="qr_code">QR Code</label>
                    <input type="text" id="qr_code" name="qr_code" value="<?php echo $product['qr_code']; ?>" placeholder="Enter QR code value">
                </div>
                
                <div class="form-group">
                    <label>Current Quantity</label>
                    <input type="text" value="<?php echo $product['quantity']; ?>" disabled>
                    <small style="color: #666;">Use 'Update Stock' to change quantity</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Product</button>
                <a href="inventory.php" class="btn btn-danger">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>