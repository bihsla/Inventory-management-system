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

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: sell.php');
    exit();
}

// Include necessary files
require_once '../config/database.php';

// Helper functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatCurrency($amount) {
    return 'Rs. ' . number_format($amount, 2);
}

function generateInvoiceNumber() {
    return 'INV' . date('Ymd') . rand(1000, 9999);
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
        $quantity_before = $product['quantity'];
        $quantity_after = $quantity_before + $quantity_change;
        
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

// Get payment method and discount
$payment_method = isset($_POST['payment_method']) ? sanitize($_POST['payment_method']) : 'cash';
$discount = isset($_POST['discount']) ? (float)$_POST['discount'] : 0;
$invoice_number = generateInvoiceNumber();

$conn = getDBConnection();
$conn->begin_transaction();

try {
    // Calculate total
    $total = 0;
    $tax = 0;
    
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    // Validate discount
    if ($discount < 0) {
        $discount = 0;
    }
    if ($discount > $total) {
        $discount = $total;
    }
    
    $final_amount = $total - $discount + $tax;
    
    // Insert sale
    $stmt = $conn->prepare("INSERT INTO sales (invoice_number, cashier_id, total_amount, discount, tax, final_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sidddds", $invoice_number, $_SESSION['user_id'], $total, $discount, $tax, $final_amount, $payment_method);
    $stmt->execute();
    $sale_id = $conn->insert_id;
    
    // Insert sale items and update stock
    foreach ($_SESSION['cart'] as $product_id => $item) {
        // Insert sale item
        $subtotal = $item['price'] * $item['quantity'];
        $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisidd", $sale_id, $product_id, $item['product_name'], $item['quantity'], $item['price'], $subtotal);
        $stmt->execute();
        
        // Update stock
        $quantity_change = -$item['quantity'];
        
        // Update product stock directly
        $stmt2 = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
        $stmt2->bind_param("i", $product_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $product = $result->fetch_assoc();
        $quantity_before = $product['quantity'];
        $quantity_after = $quantity_before + $quantity_change;
        
        $stmt3 = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        $stmt3->bind_param("ii", $quantity_after, $product_id);
        $stmt3->execute();
        
        // Record in stock history
        $notes = "Sale - Invoice #$invoice_number";
        $stmt4 = $conn->prepare("INSERT INTO stock_history (product_id, quantity_before, quantity_change, quantity_after, action_type, user_id, notes) VALUES (?, ?, ?, ?, 'sale', ?, ?)");
        $stmt4->bind_param("iiiiis", $product_id, $quantity_before, $quantity_change, $quantity_after, $_SESSION['user_id'], $notes);
        $stmt4->execute();
    }
    
    $conn->commit();
    
    // Get sale details for invoice
    $cart_items = $_SESSION['cart'];
    
    // Clear cart
    $_SESSION['cart'] = [];
    
} catch (Exception $e) {
    $conn->rollback();
    die("Error processing sale: " . $e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo $invoice_number; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="no-print" style="max-width: 800px; margin: 20px auto;">
        <div class="alert alert-success" style="text-align: center; font-size: 18px;">
            ✅ <strong>Sale Successful!</strong> Invoice has been generated.
        </div>
    </div>
    
    <div class="invoice">
        <div class="invoice-header">
            <h1>INVOICE</h1>
            <p><strong>Invoice Number:</strong> <?php echo $invoice_number; ?></p>
            <p><strong>Date:</strong> <?php echo date('d-m-Y H:i:s'); ?></p>
            <p><strong>Cashier:</strong> <?php echo $_SESSION['full_name']; ?></p>
        </div>
        
        <div class="invoice-details">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo formatCurrency($item['price']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo formatCurrency($item['price'] * $item['quantity']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="text-align: right; margin-top: 30px; font-size: 18px;">
            <p><strong>Subtotal:</strong> <?php echo formatCurrency($total); ?></p>
            <?php if ($discount > 0): ?>
            <p style="color: green;"><strong>Discount:</strong> -<?php echo formatCurrency($discount); ?></p>
            <?php endif; ?>
            <?php if ($tax > 0): ?>
            <p><strong>Tax:</strong> <?php echo formatCurrency($tax); ?></p>
            <?php endif; ?>
            <hr style="margin: 10px 0;">
            <p style="font-size: 24px; color: #667eea; margin-top: 10px;">
                <strong>TOTAL: <?php echo formatCurrency($final_amount); ?></strong>
            </p>
            <p><strong>Payment Method:</strong> <?php echo strtoupper($payment_method); ?></p>
        </div>
        
        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #ddd;">
            <p>Thank you for your business!</p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
            <a href="sell.php?success=1" class="btn btn-success">New Sale</a>
            <a href="dashboard.php" class="btn btn-warning">Dashboard</a>
        </div>
    </div>
</body>
</html>