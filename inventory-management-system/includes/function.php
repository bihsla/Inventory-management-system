<?php
// includes/functions.php
// Helper functions for the inventory management system

// Include database config if not already included
if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/../config/database.php';
}

// Sanitize input data
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Format currency in Indian Rupees
function formatCurrency($amount) {
    if ($amount === null || $amount === '') {
        return '₹0.00';
    }
    return '₹' . number_format((float)$amount, 2);
}

// Generate unique invoice number
function generateInvoiceNumber() {
    return 'INV' . date('Ymd') . rand(1000, 9999);
}

// Get product by ID
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

// Get product by product code
function getProductByCode($code) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $product;
}

// Get product by QR code
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

// Update stock with history tracking
function updateStock($product_id, $quantity_change, $action_type, $user_id, $notes = '', $reference_id = null) {
    $conn = getDBConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get current quantity
        $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Product not found");
        }
        
        $product = $result->fetch_assoc();
        $quantity_before = $product['quantity'];
        $quantity_after = $quantity_before + $quantity_change;
        
        // Check if quantity becomes negative
        if ($quantity_after < 0) {
            throw new Exception("Insufficient stock");
        }
        
        // Update product quantity
        $stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity_after, $product_id);
        $stmt->execute();
        
        // Record in stock history
        $stmt = $conn->prepare("INSERT INTO stock_history (product_id, quantity_before, quantity_change, quantity_after, action_type, user_id, reference_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiisiis", $product_id, $quantity_before, $quantity_change, $quantity_after, $action_type, $user_id, $reference_id, $notes);
        $stmt->execute();
        
        $conn->commit();
        $stmt->close();
        $conn->close();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        error_log("Stock update failed: " . $e->getMessage());
        return false;
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user has specific role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . 'login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . 'login.php');
        exit();
    }
    if (!hasRole('admin')) {
        header('Location: ' . getBaseUrl() . 'cashier/dashboard.php');
        exit();
    }
}

// Redirect if not cashier
function requireCashier() {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . 'login.php');
        exit();
    }
    if (!hasRole('cashier')) {
        header('Location: ' . getBaseUrl() . 'admin/dashboard.php');
        exit();
    }
}

// Get base URL
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove admin or cashier from path if present
    $script = preg_replace('#/(admin|cashier)$#', '', $script);
    
    return $protocol . "://" . $host . $script . '/';
}

// Get all products
function getAllProducts($order_by = 'product_name', $order = 'ASC') {
    $conn = getDBConnection();
    $allowed_columns = ['id', 'product_code', 'product_name', 'price', 'quantity', 'category'];
    $allowed_order = ['ASC', 'DESC'];
    
    if (!in_array($order_by, $allowed_columns)) {
        $order_by = 'product_name';
    }
    if (!in_array($order, $allowed_order)) {
        $order = 'ASC';
    }
    
    $query = "SELECT * FROM products ORDER BY $order_by $order";
    $result = $conn->query($query);
    
    $products = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    $conn->close();
    return $products;
}

// Get low stock products
function getLowStockProducts() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM products WHERE quantity <= min_quantity ORDER BY quantity ASC");
    
    $products = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    $conn->close();
    return $products;
}

// Get sales statistics
function getSalesStats($start_date = null, $end_date = null, $cashier_id = null) {
    $conn = getDBConnection();
    
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($start_date && $end_date) {
        $where_conditions[] = "DATE(sale_date) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= 'ss';
    }
    
    if ($cashier_id) {
        $where_conditions[] = "cashier_id = ?";
        $params[] = $cashier_id;
        $types .= 'i';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $query = "SELECT COUNT(*) as total_sales, COALESCE(SUM(final_amount), 0) as total_revenue FROM sales $where_clause";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
    } else {
        $result = $conn->query($query);
        $stats = $result->fetch_assoc();
    }
    
    $conn->close();
    return $stats;
}

// Get total products count
function getTotalProducts() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $row = $result->fetch_assoc();
    $conn->close();
    return $row['count'];
}

// Get total sales count
function getTotalSales() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT COUNT(*) as count FROM sales");
    $row = $result->fetch_assoc();
    $conn->close();
    return $row['count'];
}

// Get total revenue
function getTotalRevenue() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM sales");
    $row = $result->fetch_assoc();
    $conn->close();
    return $row['total'];
}

// Format date for display
function formatDate($date, $format = 'd-m-Y H:i:s') {
    if (empty($date)) {
        return '-';
    }
    return date($format, strtotime($date));
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Log activity (optional feature)
function logActivity($user_id, $action, $details = '') {
    // This can be implemented if you want to track all user activities
    // For now, we're using stock_history for inventory changes
    return true;
}
?>