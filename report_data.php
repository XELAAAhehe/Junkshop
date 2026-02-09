<?php
header('Content-Type: application/json');

include('includes/db.php');

$mysqli = $conn;

// Check connection (using the bridged variable)
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
// ... The rest of your code stays EXACTLY the same ...
$startDate = $data["startDate"] ?? date("Y-m-d");
$endDate = $data["endDate"] ?? date("Y-m-d");

/** ========================
 * SUMMARY
 * ======================== */
$summarySql = "
SELECT 
    SUM(CASE WHEN t.transaction_type = 'buy' THEN i.amount * i.unit_price ELSE 0 END) AS total_buys,
    SUM(CASE WHEN t.transaction_type = 'sell' THEN i.amount * i.unit_price ELSE 0 END) AS total_sells,
    SUM(CASE WHEN t.transaction_type = 'sell' THEN i.amount * i.unit_price ELSE 0 END) -
    SUM(CASE WHEN t.transaction_type = 'buy' THEN i.amount * i.unit_price ELSE 0 END) AS total_income
FROM transactions t
JOIN transaction_items i ON t.transaction_id = i.transaction_id
WHERE DATE(t.manual_transaction_date) BETWEEN ? AND ?
";
$stmtSummary = $mysqli->prepare($summarySql);
$stmtSummary->bind_param("ss", $startDate, $endDate);
$stmtSummary->execute();
$summary = $stmtSummary->get_result()->fetch_assoc();
$stmtSummary->close();

/** ========================
 * CHART DATA
 * ======================== */
$graphSql = "
SELECT DATE(t.manual_transaction_date) AS report_date,
    SUM(CASE WHEN t.transaction_type = 'buy' THEN i.amount * i.unit_price ELSE 0 END) AS total_buys,
    SUM(CASE WHEN t.transaction_type = 'sell' THEN i.amount * i.unit_price ELSE 0 END) AS total_sells
FROM transactions t
JOIN transaction_items i ON t.transaction_id = i.transaction_id
WHERE DATE(t.manual_transaction_date) BETWEEN ? AND ?
GROUP BY DATE(t.manual_transaction_date)
ORDER BY DATE(t.manual_transaction_date)
";
$stmtGraph = $mysqli->prepare($graphSql);
$stmtGraph->bind_param("ss", $startDate, $endDate);
$stmtGraph->execute();
$resGraph = $stmtGraph->get_result();

$dates = $buys = $sells = [];
while ($r = $resGraph->fetch_assoc()) {
    $dates[] = $r['report_date'];
    $buys[] = (float)$r['total_buys'];
    $sells[] = (float)$r['total_sells'];
}
$stmtGraph->close();

/** ========================
 * SCRAP TYPE BREAKDOWN
 * ======================== */
$typeSql = "
SELECT i.type,
    SUM(CASE WHEN t.transaction_type = 'buy' THEN i.amount * i.unit_price ELSE 0 END) AS total_buy,
    SUM(CASE WHEN t.transaction_type = 'sell' THEN i.amount * i.unit_price ELSE 0 END) AS total_sell,
    SUM(CASE WHEN t.transaction_type = 'sell' THEN i.amount * i.unit_price ELSE 0 END) -
    SUM(CASE WHEN t.transaction_type = 'buy' THEN i.amount * i.unit_price ELSE 0 END) AS profit
FROM transactions t
JOIN transaction_items i ON t.transaction_id = i.transaction_id
WHERE DATE(t.manual_transaction_date) BETWEEN ? AND ?
GROUP BY i.type
ORDER BY i.type
";
$stmtType = $mysqli->prepare($typeSql);
$stmtType->bind_param("ss", $startDate, $endDate);
$stmtType->execute();
$resType = $stmtType->get_result();

$breakdown = [];
while ($row = $resType->fetch_assoc()) {
    $breakdown[] = $row;
}
$stmtType->close();

echo json_encode([
    "summary" => $summary,
    "dates" => $dates,
    "buys" => $buys,
    "sells" => $sells,
    "scrapBreakdown" => $breakdown
]);

$mysqli->close();
?>