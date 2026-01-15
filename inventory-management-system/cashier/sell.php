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

// Helper functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatCurrency($amount) {
    return 'Rs. ' . number_format($amount, 2);
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

function getProductByQR($qr_code) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM products WHERE qr_code = ?");
    $stmt->bind_param("s", $qr_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $product;
}

// Initialize cart in session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';

// Check for success message from invoice
if (isset($_GET['success'])) {
    $message = '✅ Sale completed successfully! Invoice generated.';
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    $product = getProductById($product_id);
    
    if (!$product) {
        $message = 'Product not found!';
    } elseif ($product['quantity'] < $quantity) {
        $message = 'Insufficient stock! Available: ' . $product['quantity'];
    } elseif ($quantity <= 0) {
        $message = 'Please enter a valid quantity!';
    } else {
        // Check if adding to cart would exceed available stock
        $cart_quantity = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
        $total_quantity = $cart_quantity + $quantity;
        
        if ($total_quantity > $product['quantity']) {
            $message = 'Cannot add! Total in cart would be ' . $total_quantity . ', but only ' . $product['quantity'] . ' available.';
        } else {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'product_name' => $product['product_name'],
                    'price' => $product['price'],
                    'quantity' => $quantity
                ];
            }
            $message = 'Product added to cart!';
        }
    }
}

// Handle QR code scan
if (isset($_POST['qr_code'])) {
    $qr_code = sanitize($_POST['qr_code']);
    $product = getProductByQR($qr_code);
    
    if (!$product) {
        $message = 'Product not found with QR code: ' . $qr_code;
    } elseif ($product['quantity'] <= 0) {
        $message = 'Product out of stock!';
    } else {
        $product_id = $product['id'];
        $cart_quantity = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
        
        if (($cart_quantity + 1) > $product['quantity']) {
            $message = 'Cannot add! Only ' . $product['quantity'] . ' available, ' . $cart_quantity . ' already in cart.';
        } else {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity']++;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'product_name' => $product['product_name'],
                    'price' => $product['price'],
                    'quantity' => 1
                ];
            }
            $message = 'Product scanned and added!';
        }
    }
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$product_id]);
    header('Location: sell.php');
    exit();
}

// Get all products with category filter
$conn = getDBConnection();
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

if ($category_filter) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE quantity > 0 AND category = ? ORDER BY product_name");
    $stmt->bind_param("s", $category_filter);
    $stmt->execute();
    $products = $stmt->get_result();
} else {
    $products = $conn->query("SELECT * FROM products WHERE quantity > 0 ORDER BY product_name");
}

// Get all categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");

$conn->close();

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Products</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>Cashier - Sell Products</h1>
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
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Product Selection -->
            <div style="background: white; padding: 20px; border-radius: 10px;">
                <h3>Scan QR Code or Select Product</h3>
                
                <!-- Category Filter -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; margin-bottom: 10px; display: block;">Filter by Category:</label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="sell.php" class="btn <?php echo $category_filter == '' ? 'btn-primary' : 'btn-warning'; ?>" style="padding: 8px 15px; font-size: 14px;">
                            All Products
                        </a>
                        <?php 
                        if ($categories && $categories->num_rows > 0):
                            while ($cat = $categories->fetch_assoc()): 
                                $cat_name = $cat['category'];
                        ?>
                            <a href="sell.php?category=<?php echo urlencode($cat_name); ?>" 
                               class="btn <?php echo $category_filter == $cat_name ? 'btn-primary' : 'btn-warning'; ?>" 
                               style="padding: 8px 15px; font-size: 14px;">
                                <?php echo htmlspecialchars($cat_name); ?>
                            </a>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </div>
                </div>
                
                <hr style="margin: 20px 0;">
                
                <!-- QR Scanner -->
                <div>
                    <button id="startScan" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">Start QR Scanner</button>
                    <div id="qr-reader" style="display: none;"></div>
                    <form method="POST" id="qrForm" style="display: none;">
                        <input type="hidden" name="qr_code" id="qr_code_input">
                    </form>
                </div>
                
                <hr style="margin: 20px 0;">
                
                <!-- Manual Selection -->
                <form method="POST" onsubmit="return validateSellForm()">
                    <div class="form-group">
                        <label>Select Product 
                            <?php if ($category_filter): ?>
                                <span style="color: #667eea;">(<?php echo htmlspecialchars($category_filter); ?>)</span>
                            <?php endif; ?>
                        </label>
                        <select name="product_id" id="product_id" required style="width: 100%;">
                            <option value="">Choose product...</option>
                            <?php 
                            $products_data = $products->fetch_all(MYSQLI_ASSOC);
                            if (count($products_data) == 0): ?>
                                <option value="" disabled>No products available in this category</option>
                            <?php else:
                                foreach ($products_data as $product): ?>
                                <option value="<?php echo $product['id']; ?>" data-stock="<?php echo $product['quantity']; ?>">
                                    <?php echo $product['product_name']; ?> - <?php echo formatCurrency($product['price']); ?> (Stock: <?php echo $product['quantity']; ?>)
                                </option>
                            <?php endforeach;
                            endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" required>
                        <small id="stock-warning" style="color: red; display: none;">Insufficient stock!</small>
                    </div>
                    
                    <button type="submit" name="add_to_cart" class="btn btn-success">Add to Cart</button>
                </form>
            </div>
            
            <!-- Cart -->
            <div style="background: white; padding: 20px; border-radius: 10px;">
                <h3>Shopping Cart</h3>
                
                <?php if (empty($_SESSION['cart'])): ?>
                    <p>Cart is empty</p>
                <?php else: ?>
                    <div class="cart-items">
                        <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                            <div class="cart-item">
                                <div>
                                    <strong><?php echo $item['product_name']; ?></strong><br>
                                    <?php echo formatCurrency($item['price']); ?> x <?php echo $item['quantity']; ?>
                                </div>
                                <div>
                                    <strong><?php echo formatCurrency($item['price'] * $item['quantity']); ?></strong>
                                    <a href="?remove=<?php echo $id; ?>" style="color: red; margin-left: 10px;">Remove</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-total">
                        Total: <?php echo formatCurrency($total); ?>
                    </div>
                    
                    <form method="POST" action="generate_invoice.php" onsubmit="return validateInvoiceForm()">
                        <div class="form-group">
                            <label>Discount (Rs.)</label>
                            <input type="number" name="discount" id="discount" step="0.01" min="0" max="<?php echo $total; ?>" value="0" placeholder="0.00">
                           
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Generate Invoice</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        let html5QrCode;
        
        document.getElementById('startScan').addEventListener('click', function() {
            const qrReader = document.getElementById('qr-reader');
            qrReader.style.display = 'block';
            
            html5QrCode = new Html5Qrcode("qr-reader");
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: 250 },
                (decodedText) => {
                    document.getElementById('qr_code_input').value = decodedText;
                    document.getElementById('qrForm').submit();
                    html5QrCode.stop();
                }
            ).catch(err => {
                alert('Unable to start camera. Please check permissions or use manual selection.');
                console.error(err);
            });
        });
        
        // Validate sell form
        function validateSellForm() {
            const productSelect = document.getElementById('product_id');
            const quantity = parseInt(document.getElementById('quantity').value);
            
            if (!productSelect.value) {
                alert('Please select a product');
                return false;
            }
            
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const stock = parseInt(selectedOption.getAttribute('data-stock'));
            
            if (quantity > stock) {
                alert(`Insufficient stock! Available: ${stock}`);
                return false;
            }
            
            if (quantity < 1) {
                alert('Quantity must be at least 1');
                return false;
            }
            
            return true;
        }
        
        // Real-time stock validation
        document.getElementById('product_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const stock = parseInt(selectedOption.getAttribute('data-stock'));
            const quantityInput = document.getElementById('quantity');
            quantityInput.max = stock;
            quantityInput.value = Math.min(quantityInput.value, stock);
        });
        
        document.getElementById('quantity').addEventListener('input', function() {
            const productSelect = document.getElementById('product_id');
            if (productSelect.value) {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const stock = parseInt(selectedOption.getAttribute('data-stock'));
                const warning = document.getElementById('stock-warning');
                
                if (parseInt(this.value) > stock) {
                    warning.style.display = 'block';
                    this.style.borderColor = 'red';
                } else {
                    warning.style.display = 'none';
                    this.style.borderColor = '#ddd';
                }
            }
        });
        
        // Validate invoice form
        function validateInvoiceForm() {
            const discount = parseFloat(document.getElementById('discount').value);
            const maxDiscount = <?php echo $total; ?>;
            
            if (discount < 0) {
                alert('Discount cannot be negative');
                return false;
            }
            
            if (discount > maxDiscount) {
                alert(`Discount cannot exceed total amount (Rs. ${maxDiscount.toFixed(2)})`);
                return false;
            }
            
            return true;
        }
        
        // Real-time discount validation
        document.getElementById('discount').addEventListener('input', function() {
            const maxDiscount = <?php echo $total; ?>;
            if (parseFloat(this.value) > maxDiscount) {
                this.value = maxDiscount;
            }
            if (parseFloat(this.value) < 0) {
                this.value = 0;
            }
        });
    </script>
</body>
</html>