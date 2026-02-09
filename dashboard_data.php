<?php
header('Content-Type: application/json');

include('includes/db.php');

$mysqli = $conn; 


if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $mysqli->connect_error]);
    exit;
}

// ===== SUMMARY =====
$summarySql = "
SELECT
    (SELECT SUM(stock.amount * st.price) 
     FROM (
        SELECT ti.type, SUM(CASE WHEN t.transaction_type = 'buy' THEN ti.amount ELSE -ti.amount END) AS amount
        FROM transaction_items ti
        JOIN transactions t ON ti.transaction_id = t.transaction_id
        GROUP BY ti.type
     ) AS stock
     JOIN scrap_types st ON stock.type = st.type
    ) AS total_inventory_value,
    (SELECT COUNT(*) FROM scrap_types) AS total_scrap_types,
    (SELECT SUM(ti.amount * ti.unit_price)
     FROM transactions t
     JOIN transaction_items ti ON t.transaction_id = ti.transaction_id
     WHERE t.transaction_type = 'buy' AND MONTH(t.manual_transaction_date) = MONTH(CURDATE())
           AND YEAR(t.manual_transaction_date) = YEAR(CURDATE())
    ) AS total_buys_month,
    (SELECT SUM(ti.amount * ti.unit_price)
     FROM transactions t
     JOIN transaction_items ti ON t.transaction_id = ti.transaction_id
     WHERE t.transaction_type = 'sell' AND MONTH(t.manual_transaction_date) = MONTH(CURDATE())
           AND YEAR(t.manual_transaction_date) = YEAR(CURDATE())
    ) AS total_sells_month
";
$summary = $mysqli->query($summarySql)->fetch_assoc();

// ===== RECENT TRANSACTIONS =====
$recentSql = "
SELECT t.manual_transaction_date, t.transaction_type, t.partner_name,
       SUM(ti.amount * ti.unit_price) AS total_value
FROM transactions t
JOIN transaction_items ti ON t.transaction_id = ti.transaction_id
GROUP BY t.transaction_id
ORDER BY t.manual_transaction_date DESC
LIMIT 10
";
$recentTransactions = [];
$res = $mysqli->query($recentSql);
while ($row = $res->fetch_assoc()) {
    $recentTransactions[] = $row;
}

// ===== INVENTORY OVERVIEW =====
$inventorySql = "
SELECT ti.type, SUM(CASE WHEN t.transaction_type = 'buy' THEN ti.amount ELSE -ti.amount END) AS stock_amount
FROM transaction_items ti
JOIN transactions t ON ti.transaction_id = t.transaction_id
GROUP BY ti.type
";
$inventoryOverview = [];
$res = $mysqli->query($inventorySql);
while ($row = $res->fetch_assoc()) {
    $inventoryOverview[] = $row;
}

// ===== MONTHLY GRAPH =====
$graphSql = "
SELECT DATE_FORMAT(t.manual_transaction_date, '%Y-%m') AS month,
       SUM(CASE WHEN t.transaction_type = 'buy' THEN ti.amount * ti.unit_price ELSE 0 END) AS total_buys,
       SUM(CASE WHEN t.transaction_type = 'sell' THEN ti.amount * ti.unit_price ELSE 0 END) AS total_sells
FROM transactions t
JOIN transaction_items ti ON t.transaction_id = ti.transaction_id
WHERE t.manual_transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY month
ORDER BY month ASC
";
$monthlyGraph = [];
$res = $mysqli->query($graphSql);
while ($row = $res->fetch_assoc()) {
    $monthlyGraph[] = $row;
}

// ===== OUTPUT =====
echo json_encode([
    "summary" => $summary,
    "recentTransactions" => $recentTransactions,
    "inventoryOverview" => $inventoryOverview,
    "monthlyGraph" => $monthlyGraph
]);

$mysqli->close();
?>
