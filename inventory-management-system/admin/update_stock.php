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

// Helper functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function getProductById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $product;
}

function updateStock($product_id, $quantity_change, $action_type, $user_id, $notes = '') {
    $conn = getDBConnection();
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if (!$product) {
            throw new Exception("Product not found");
        }
        
        $quantity_before = $product['quantity'];
        $quantity_after = $quantity_before + $quantity_change;
        
        if ($quantity_after < 0) {
            throw new Exception("Cannot reduce stock below zero");
        }
        
        $stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity_after, $product_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("INSERT INTO stock_history (product_id, quantity_before, quantity_change, quantity_after, action_type, user_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiisis", $product_id, $quantity_before, $quantity_change, $quantity_after, $action_type, $user_id, $notes);
        $stmt->execute();
        
        $conn->commit();
        $stmt->close();
        $conn->close();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        return false;
    }
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProductById($product_id);

if (!$product) {
    header('Location: inventory.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quantity_change = (int)$_POST['quantity_change'];
    $notes = sanitize($_POST['notes']);
    
    if ($quantity_change == 0) {
        $error = 'Please enter a non-zero quantity change';
    } else {
        // Determine action type based on quantity change
        $action_type = $quantity_change > 0 ? 'add' : 'adjustment';
        
        if (updateStock($product_id, $quantity_change, $action_type, $_SESSION['user_id'], $notes)) {
            $success = 'Stock updated successfully!';
            $product = getProductById($product_id);
        } else {
            $error = 'Error updating stock. Cannot reduce stock below zero.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Stock</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>Update Stock</h1>
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
            <h2>Update Stock for: <?php echo htmlspecialchars($product['product_name']); ?></h2>
            
            <div style="background: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <p><strong>Product Code:</strong> <?php echo htmlspecialchars($product['product_code']); ?></p>
                <p><strong>Current Stock:</strong> <span style="font-size: 24px; color: <?php echo $product['quantity'] < $product['min_quantity'] ? 'red' : 'green'; ?>; font-weight: bold;"><?php echo $product['quantity']; ?></span></p>
                <p><strong>Minimum Stock Alert:</strong> <?php echo $product['min_quantity']; ?></p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="quantity_change">Quantity Change *</label>
                    <input type="number" id="quantity_change" name="quantity_change" required placeholder="">
                    <small style="color: #666;">
                       
                        • Current stock: <?php echo $product['quantity']; ?>
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="notes">Reason *</label>
                    <textarea id="notes" name="notes" rows="3" required placeholder=""></textarea>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                    <a href="inventory.php" class="btn btn-danger">Cancel</a>
                    <a href="edit_item.php?id=<?php echo $product['id']; ?>" class="btn btn-warning">Edit Product</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Real-time calculation preview
        document.getElementById('quantity_change').addEventListener('input', function() {
            const currentStock = <?php echo $product['quantity']; ?>;
            const change = parseInt(this.value) || 0;
            const newStock = currentStock + change;
            
            const small = this.nextElementSibling;
            const lastLine = small.lastChild;
            lastLine.textContent = '• Result: ' + currentStock + ' + (' + change + ') = ' + newStock + ' units';
            
            if (newStock < 0) {
                lastLine.style.color = 'red';
                lastLine.textContent += ' ⚠️ Cannot go below zero!';
            } else if (newStock < <?php echo $product['min_quantity']; ?>) {
                lastLine.style.color = 'orange';
                lastLine.textContent += ' ⚠️ Below minimum stock level';
            } else {
                lastLine.style.color = 'green';
            }
        });
    </script>
</body>
</html>