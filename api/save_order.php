<?php
header('Content-Type: application/json');
// Basic API to save orders and order items to the database
// Expects JSON POST with structure similar to client-side `order` object

include __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || !isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order payload']);
    exit;
}

// Create tables if missing
$createOrders = "CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_key VARCHAR(64) NOT NULL UNIQUE,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  customer_name VARCHAR(255),
  customer_email VARCHAR(255),
  address TEXT,
  country VARCHAR(128),
  city VARCHAR(128),
  post_code VARCHAR(64),
  shipping VARCHAR(128),
  payment VARCHAR(128),
  placed_at DATETIME DEFAULT CURRENT_TIMESTAMP
);";

$createItems = "CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT DEFAULT NULL,
  name VARCHAR(255),
  price DECIMAL(10,2) DEFAULT 0,
  quantity INT DEFAULT 1,
  line_total DECIMAL(10,2) DEFAULT 0,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);";

$conn->query($createOrders);
$conn->query($createItems);

// Build order values
$items = $data['items'];
$subtotal = isset($data['subtotal']) ? floatval($data['subtotal']) : 0.0;
$customer = $data['customer'] ?? null;
$shipping = $data['shipping'] ?? null;
$payment = $data['payment'] ?? null;

// Generate a unique order key
$orderKey = 'ORD-' . time() . '-' . substr(md5(uniqid('', true)), 0, 6);

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO orders (order_key, subtotal, customer_name, customer_email, address, country, city, post_code, shipping, payment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $custName = $customer ? trim(($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? '')) : null;
    $custEmail = $customer['email'] ?? null;
    $addr = $customer['address'] ?? null;
    $country = $customer['country'] ?? null;
    $city = $customer['city'] ?? null;
    $postCode = $customer['postCode'] ?? null;
    $stmt->bind_param('sdssssssss', $orderKey, $subtotal, $custName, $custEmail, $addr, $country, $city, $postCode, $shipping, $payment);
    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();

    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, name, price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($items as $it) {
        $pid = isset($it['id']) ? intval($it['id']) : null;
        $name = $it['name'] ?? null;
        $price = isset($it['price']) ? floatval($it['price']) : 0.0;
        $qty = isset($it['quantity']) ? intval($it['quantity']) : 1;
        $line = $price * $qty;
        $itemStmt->bind_param('iisdid', $orderId, $pid, $name, $price, $qty, $line);
        $itemStmt->execute();
    }
    $itemStmt->close();

    $conn->commit();

    // Build response order object for client
    $respOrder = [
        'id' => $orderId,
        'order_key' => $orderKey,
        'items' => $items,
        'subtotal' => number_format($subtotal, 2, '.', ''),
        'customer' => $customer,
        'shipping' => $shipping,
        'payment' => $payment,
        'placedAt' => date('c')
    ];

    echo json_encode(['success' => true, 'order' => $respOrder]);
    exit;
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

?>