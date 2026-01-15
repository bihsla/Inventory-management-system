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

// Helper functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function getProductById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, product_code, product_name, description, category, price, quantity, min_quantity, qr_code FROM products WHERE id = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $product;
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProductById($product_id);

if (!$product) {
    header('Location: inventory.php');
    exit();
}

// Get existing categories for dropdown
$conn_temp = getDBConnection();
$existing_categories = $conn_temp->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
$conn_temp->close();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_code = sanitize($_POST['product_code']);
    $product_name = sanitize($_POST['product_name']);
    $description = sanitize($_POST['description']);
    $category = isset($_POST['category']) ? sanitize($_POST['category']) : '';
    $price = (float)$_POST['price'];
    $min_quantity = isset($_POST['min_quantity']) ? (int)$_POST['min_quantity'] : 10;
    $qr_code = isset($_POST['qr_code']) ? sanitize($_POST['qr_code']) : '';
    
    $conn = getDBConnection();
    
    // Check if product code exists for another product
    $check_stmt = $conn->prepare("SELECT id FROM products WHERE product_code = ? AND id != ?");
    
    if (!$check_stmt) {
        $error = 'Database error: ' . $conn->error;
    } else {
        $check_stmt->bind_param("si", $product_code, $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Product code already exists for another product!';
        } else {
            $stmt = $conn->prepare("UPDATE products SET product_code=?, product_name=?, description=?, category=?, price=?, min_quantity=?, qr_code=? WHERE id=?");
            
            if (!$stmt) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $stmt->bind_param("ssssdiis", $product_code, $product_name, $description, $category, $price, $min_quantity, $qr_code, $product_id);
                
                if ($stmt->execute()) {
                    $success = 'Product updated successfully!';
                    $product = getProductById($product_id);
                } else {
                    $error = 'Error updating product: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
        
        $check_stmt->close();
    }
    
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
                    <li><a href="sales_report.php">Sales Report</a></li>
                    <li><a href="stock_report.php">Stock Report</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px;">
            <h2>Edit Product: <?php echo htmlspecialchars($product['product_name']); ?></h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="product_code">Product Code *</label>
                    <input type="text" id="product_code" name="product_code" value="<?php echo htmlspecialchars($product['product_code']); ?>" required minlength="3" maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="product_name">Product Name *</label>
                    <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required minlength="3" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" maxlength="500"><?php echo isset($product['description']) ? htmlspecialchars($product['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category_select" name="category" onchange="toggleCustomCategory()">
                        <option value="">-- No Category --</option>
                        <?php 
                        if ($existing_categories && $existing_categories->num_rows > 0):
                            while ($cat = $existing_categories->fetch_assoc()): 
                                $selected = (isset($product['category']) && $product['category'] == $cat['category']) ? 'selected' : '';
                        ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $selected; ?>>
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
                    
                    <small style="color: #666;">Change category or add new one</small>
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
                    <input type="number" id="price" name="price" step="0.01" min="0.01" value="<?php echo $product['price']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="min_quantity">Minimum Quantity Alert</label>
                    <input type="number" id="min_quantity" name="min_quantity" min="0" value="<?php echo isset($product['min_quantity']) ? $product['min_quantity'] : 10; ?>">
                    <small style="color: #666;">Alert when stock falls below this quantity</small>
                </div>
                
                <div class="form-group">
                    <label for="qr_code">QR Code</label>
                    <input type="text" id="qr_code" name="qr_code" value="<?php echo isset($product['qr_code']) ? htmlspecialchars($product['qr_code']) : ''; ?>" placeholder="Enter QR code value" maxlength="255">
                </div>
                
                <div class="form-group">
                    <label>Current Quantity</label>
                    <input type="text" value="<?php echo $product['quantity']; ?>" disabled style="background: #f5f5f5;">
                    <small style="color: #666;">Use 'Update Stock' button to change quantity</small>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Update Product</button>
                    <a href="inventory.php" class="btn btn-danger">Cancel</a>
                    <a href="update_stock.php?id=<?php echo $product['id']; ?>" class="btn btn-success">Update Stock</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function validateForm() {
            const productCode = document.getElementById('product_code').value.trim();
            const productName = document.getElementById('product_name').value.trim();
            const price = parseFloat(document.getElementById('price').value);
            
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
            
            return true;
        }
        
        // Prevent negative values
        document.getElementById('price').addEventListener('input', function() {
            if (this.value < 0) this.value = 0;
        });
        
        document.getElementById('min_quantity').addEventListener('input', function() {
            if (this.value < 0) this.value = 0;
        });
    </script>
</body>
</html>