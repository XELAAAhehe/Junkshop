<?php
include('includes/db.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['items']) || !is_array($data['items'])) {
        http_response_code(400);
        echo "Invalid input data.";
        exit();
    }

    $partner_name = isset($data['name']) && trim($data['name']) !== '' ? trim($data['name']) : null;
    $transaction_type = isset($data['transaction_type']) ? $data['transaction_type'] : 'buy';
    $manual_transaction_date = null;
    if (!empty($data['manual_transaction_date'])) {
        $raw = trim($data['manual_transaction_date']);
        // normalize T -> space
        $raw = str_replace('T', ' ', $raw);

        // If only date YYYY-MM-DD, append midnight
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $raw .= ' 00:00:00';
        }

        // If "YYYY-MM-DD HH:MM" add seconds
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw)) {
            $raw .= ':00';
        }

        // Allow only exact "YYYY-MM-DD HH:MM:SS" now
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $raw)) {
            $manual_transaction_date = $raw; // store as-is
        } else {
            // invalid format: fallback to current server time (or you could reject)
            $manual_transaction_date = date('Y-m-d H:i:s');
        }
    } else {
        // If client didn't send it, default to now (or you can set NULL)
        $manual_transaction_date = date('Y-m-d H:i:s');
    }



    // Start transaction
    $conn->begin_transaction();
    try {
        // Insert into `transactions` table
        $stmt = $conn->prepare("INSERT INTO transactions (upload_date, manual_transaction_date, transaction_type, partner_name) VALUES (NOW(), ?, ?, ?)");
        $stmt->bind_param("sss", $manual_transaction_date, $transaction_type, $partner_name);
        $stmt->execute();
        $transaction_id = $conn->insert_id;
        $stmt->close();

        // Prepare insert for each item
        $stmtItem = $conn->prepare("INSERT INTO transaction_items (transaction_id, type, measure, amount, unit_price) VALUES (?, ?, ?, ?, ?)");
        $stmtItem->bind_param("issdd", $transaction_id, $type, $measure, $amount, $unit_price);

        foreach ($data['items'] as $item) {
            $type = $item['type'];
            $measure = $item['measure'];
            $amount = floatval($item['amount']);
            $unit_price = floatval($item['unitPrice']);
            $stmtItem->execute();
        }

        $stmtItem->close();
        $conn->commit();

        echo "Transaction saved successfully! Transaction ID = $transaction_id";
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo "Error: " . $e->getMessage();
    }
} else {
    http_response_code(405);
    echo "Only POST requests are allowed.";
}
?>

