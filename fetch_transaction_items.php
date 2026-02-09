<?php
// Prevent PHP warnings from breaking the JSON response
error_reporting(0); 
header('Content-Type: application/json');

// 1. INCLUDE YOUR SHARED CONNECTION
include('includes/db.php');

// 2. BRIDGE THE VARIABLE & CHECK CONNECTION
// Your db.php creates $conn. We use it here.
$mysqli = $conn;

if (!$mysqli) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Invalid request method"]);
    exit;
}

$transaction_id = $_POST['transaction_id'] ?? null;

if (!$transaction_id) {
    http_response_code(400);
    echo json_encode(["error" => "No transaction ID provided"]);
    exit;
}

// 3. USE THE EXISTING CONNECTION ($mysqli)
// DO NOT create a "new mysqli" here.
$stmt = $mysqli->prepare("
    SELECT 
        i.type, 
        i.measure, 
        i.amount, 
        i.unit_price, 
        (i.amount * i.unit_price) AS subtotal
    FROM transaction_items i
    WHERE i.transaction_id = ?
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Query preparation failed: " . $mysqli->error]);
    exit;
}

$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$stmt->close();
// Do not close $mysqli here if other scripts need it, but for a single AJAX call it's fine.
$mysqli->close();

echo json_encode($items);
?>