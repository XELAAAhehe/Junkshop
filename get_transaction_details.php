<?php
header('Content-Type: application/json');
include('includes/db.php');

$mysqli = $conn; 

// OPTIONAL: Check if connection works (db.php usually handles this, but this is safe to keep)
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $mysqli->connect_error]);
    exit;
}

if ($mysqli->connect_errno) {
    die("Connection failed: " . $mysqli->connect_error);
}
// Get all items from all transactions, including manual transaction date
$sql = "
SELECT 
    t.transaction_id,
    t.partner_name,
    t.upload_date,
    t.manual_transaction_date,
    i.type,
    i.measure,
    i.amount,
    i.unit_price
FROM transactions t
JOIN transaction_items i ON t.transaction_id = i.transaction_id
ORDER BY t.upload_date DESC
";

$result = $mysqli->query($sql);

$details = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }
    echo json_encode($details);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch transaction details"]);
}

$mysqli->close();
?>
