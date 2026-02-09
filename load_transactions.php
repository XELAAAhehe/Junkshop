<?php
include('includes/db.php');

$type = $_GET['type'] ?? '';
$measure = $_GET['measure'] ?? '';

// ✅ Include transaction_type in the SELECT statement
$stmt = $conn->prepare("SELECT transaction_date, amount, unit_price, transaction_type FROM scrap_transactions WHERE type=? AND measure=? ORDER BY transaction_date DESC");
$stmt->bind_param("ss", $type, $measure);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  echo "<table class='transaction-table'>";
  echo "<tr><th>Date</th><th>Amount</th><th>Unit Price</th></tr>";

  while ($row = $result->fetch_assoc()) {
    $sign = ($row['transaction_type'] === 'buy') ? '+' : '-';
    $amount = $sign . ' ' . number_format($row['amount'], 2);

    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['transaction_date']) . "</td>";
    echo "<td>" . $amount . "</td>";
    echo "<td>₱" . number_format($row['unit_price'], 2) . "</td>";
    echo "</tr>";
  }

  echo "</table>";
} else {
  echo "<p>No transactions found.</p>";
}
?>
