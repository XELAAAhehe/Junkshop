<?php
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

$conn = new mysqli("localhost", "root", "", "junkshop_db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        i.type, 
        i.measure, 
        i.amount, 
        i.unit_price, 
        (i.amount * i.unit_price) AS subtotal
    FROM transaction_items i
    WHERE i.transaction_id = ?
");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($items);


