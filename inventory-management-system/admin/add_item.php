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
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$success = '';
$error = '';

// Initialize variables
$product_code = '';
$product_name = '';
$description = '';
$category = '';
$price = 0;
$quantity = 0;
$min_quantity = 10;
$qr_code = '';

// Get existing categories for dropdown
$conn_temp = getDBConnection();
$existing_categories = $conn_temp->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
$conn_temp->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_code = sanitize($_POST['product_code']);
    $product_name = sanitize($_POST['product_name']);
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
    
    // Handle category - either from dropdown or custom input
    if (isset($_POST['custom_category']) && !empty($_POST['custom_category'])) {
        $category = sanitize($_POST['custom_category']);
    } else {
        $category = isset($_POST['category']) ? sanitize($_POST['category']) : '';
    }
    
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $min_quantity = isset($_POST['min_quantity']) && $_POST['min_quantity'] != '' ? (int)$_POST['min_quantity'] : 10;
    $qr_code = isset($_POST['qr_code']) ? sanitize($_POST['qr_code']) : '';
    
    $conn = getDBConnection();
    
    if (!$conn) {
        $error = 'Database connection failed!';
    } else {
        // Check if product code already exists
        $check_stmt = $conn->prepare("SELECT id FROM products WHERE product_code = ?");
        
        if (!$check_stmt) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $check_stmt->bind_param("s", $product_code);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = 'Product code already exists! Please use a different code.';
            } else {
                // Try to insert the product
                $stmt = $conn->prepare("INSERT INTO products (product_code, product_name, description, category, price, quantity, min_quantity, qr_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    $error = 'Database prepare error: ' . $conn->error . '<br>Make sure all columns exist in your products table.';
                } else {
                    $stmt->bind_param("ssssdiis", $product_code, $product_name, $description, $category, $price, $quantity, $min_quantity, $qr_code);
                    
                    if ($stmt->execute()) {
                        $success = 'Product added successfully! Product ID: ' . $stmt->insert_id;
                        // Clear form
                        $product_code = $product_name = $description = $category = $qr_code = '';
                        $price = $quantity = 0;
                        $min_quantity = 10;
                    } else {
                        $error = 'Error adding product: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
            
            $check_stmt->close();
        }
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>Add New Product</h1>
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
        <div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px;">
            <h2>Add New Product</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="product_code">Product Code *</label>
                    <input type="text" id="product_code" name="product_code" value="<?php echo isset($product_code) ? $product_code : ''; ?>" required placeholder="e.g., PROD001" minlength="3" maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="product_name">Product Name *</label>
                    <input type="text" id="product_name" name="product_name" value="<?php echo isset($product_name) ? $product_name : ''; ?>" required placeholder="e.g., Laptop Dell Inspiron" minlength="3" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Product description..." maxlength="500"><?php echo isset($description) ? $description : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category_select" name="category" onchange="toggleCustomCategory()">
                        <option value="">-- Select Category --</option>
                        <?php 
                        if ($existing_categories && $existing_categories->num_rows > 0):
                            while ($cat = $existing_categories->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo (isset($category) && $category == $cat['category']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                        <option value="custom">+ Add New Category</option>
                    </select>
                    
                    <input type="text" id="custom_category" name="custom_category" placeholder="Enter new category name" 
                           style="display: none; margin-top: 10px;" maxlength="50">
                    
                    <small style="color: #666;">Choose existing category or add new one</small>
                </div>
                
                <script>
                function toggleCustomCategory() {
                    const select = document.getElementById('category_select');
                    const customInput = document.getElementById('custom_category');
                    
                    if (select.value === 'custom') {
                        customInput.style.display = 'block';
                        customInput.required = true;
                        select.removeAttribute('name');
                    } else {
                        customInput.style.display = 'none';
                        customInput.required = false;
                        select.setAttribute('name', 'category');
                        customInput.value = '';
                    }
                }
                </script>
                
                <div class="form-group">
                    <label for="price">Price (Rs.) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0.01" value="<?php echo isset($price) ? $price : ''; ?>" required placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label for="quantity">Initial Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="0" value="<?php echo isset($quantity) ? $quantity : ''; ?>" required placeholder="0">
                </div>
                
                <div class="form-group">
                    <label for="min_quantity">Minimum Quantity (Low Stock Alert)</label>
                    <input type="number" id="min_quantity" name="min_quantity" min="0" value="<?php echo isset($min_quantity) && $min_quantity > 0 ? $min_quantity : '10'; ?>" placeholder="10">
                    <small style="color: #666;">Alert when stock falls below this quantity</small>
                </div>
                
                <div class="form-group">
                    <label for="qr_code">QR Code</label>
                    <input type="text" id="qr_code" name="qr_code" value="<?php echo isset($qr_code) ? $qr_code : ''; ?>" placeholder="Enter QR code value (e.g., QR001)" maxlength="255">
                    <small style="color: #666;">Optional: Enter the value to be encoded in QR code</small>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Add Product</button>
                    <a href="inventory.php" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function validateForm() {
            const productCode = document.getElementById('product_code').value.trim();
            const productName = document.getElementById('product_name').value.trim();
            const price = parseFloat(document.getElementById('price').value);
            const quantity = parseInt(document.getElementById('quantity').value);
            
            if (productCode.length < 3) {
                alert('Product code must be at least 3 characters long');
                return false;
            }
            
            if (productName.length < 3) {
                alert('Product name must be at least 3 characters long');
                return false;
            }
            
            if (isNaN(price) || price <= 0) {
                alert('Please enter a valid price greater than 0');
                return false;
            }
            
            if (isNaN(quantity) || quantity < 0) {
                alert('Please enter a valid quantity (0 or more)');
                return false;
            }
            
            return true;
        }
        
        // Real-time validation feedback
        document.getElementById('price').addEventListener('input', function() {
            if (this.value < 0) this.value = 0;
        });
        
        document.getElementById('quantity').addEventListener('input', function() {
            if (this.value < 0) this.value = 0;
        });
        
        document.getElementById('min_quantity').addEventListener('input', function() {
            if (this.value < 0) this.value = 0;
        });
    </script>
</body>
</html>