<?php
header('Content-Type: application/json');
include('includes/db.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['items']) || !is_array($data['items'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid input data."]);
        exit();
    }

    $partner_name = isset($data['name']) && trim($data['name']) !== '' ? trim($data['name']) : null;
    $transaction_type = 'sell';
    $manual_transaction_date = null;

    if (!empty($data['manual_transaction_date'])) {
        $raw = str_replace('T', ' ', trim($data['manual_transaction_date']));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) $raw .= ' 00:00:00';
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw)) $raw .= ':00';
        $manual_transaction_date = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $raw)
            ? $raw
            : date('Y-m-d H:i:s');
    } else {
        $manual_transaction_date = date('Y-m-d H:i:s');
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            INSERT INTO transactions (upload_date, manual_transaction_date, transaction_type, partner_name) 
            VALUES (NOW(), ?, ?, ?)
        ");
        $stmt->bind_param("sss", $manual_transaction_date, $transaction_type, $partner_name);
        $stmt->execute();
        $transaction_id = $conn->insert_id;
        $stmt->close();

        $stmtItem = $conn->prepare("
            INSERT INTO transaction_items (transaction_id, type, measure, amount, unit_price) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtItem->bind_param("issdd", $transaction_id, $type, $measure, $amount, $unit_price);

        foreach ($data['items'] as $item) {
            $type = $item['type'];
            $measure = $item['measure'];
            $amount = (float)$item['amount'];
            $unit_price = (float)$item['unitPrice'];
            $stmtItem->execute();
        }

        $stmtItem->close();
        $conn->commit();

        echo json_encode([
            "success" => true,
            "message" => "Sell transaction saved successfully!",
            "transaction_id" => $transaction_id
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Only POST requests are allowed."]);
}
