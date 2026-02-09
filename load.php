<?php
include('includes/db.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$type = $_GET['type'] ?? '';
$measure = $_GET['measure'] ?? '';

// Prepare query to get both upload_date and manual_transaction_date
$stmt = $conn->prepare("
  SELECT 
      t.upload_date, 
      t.manual_transaction_date, 
      i.amount, 
      i.unit_price, 
      t.transaction_type 
  FROM transaction_items i
  JOIN transactions t ON i.transaction_id = t.transaction_id
  WHERE i.type = ? AND i.measure = ?
  ORDER BY t.manual_transaction_date DESC
");

$stmt->bind_param("ss", $type, $measure);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table class='transaction-table'>";
    echo "<tr>
            <th>Upload Date</th>
            <th>Transaction Date</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Unit Price</th>
          </tr>";

    while ($row = $result->fetch_assoc()) {
        $sign = ($row['transaction_type'] === 'buy') ? '+' : '-';
        $amount = $sign . ' ' . number_format($row['amount'], 2);
        $typeLabel = ucfirst($row['transaction_type']); // Buy or Sell

        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['upload_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['manual_transaction_date']) . "</td>";
        echo "<td>" . $typeLabel . "</td>";
        echo "<td>" . $amount . "</td>";
        echo "<td>₱" . number_format($row['unit_price'], 2) . "</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "<p>No transactions found.</p>";
}

$stmt->close();
$conn->close();
?>

