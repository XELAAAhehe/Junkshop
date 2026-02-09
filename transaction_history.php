<?php

include('includes/db.php');

$mysqli = $conn;

// Optional: Check connection
if ($mysqli->connect_errno) {
    die("Connection failed: " . $mysqli->connect_error);
}
// Correct SQL: Get one row per transaction_id with totals
$sql = "
SELECT 
    t.transaction_id,
    t.upload_date,
    t.manual_transaction_date,
    t.transaction_type,
    t.partner_name,
    SUM(i.amount * i.unit_price) AS total_price
FROM transactions t
JOIN transaction_items i ON t.transaction_id = i.transaction_id
GROUP BY t.transaction_id, t.upload_date, t.manual_transaction_date, t.transaction_type, t.partner_name
ORDER BY t.upload_date DESC
";


$result = $mysqli->query($sql);

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row; // Just store each row directly
}

$mysqli->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Transaction History</title>
  <link rel="stylesheet" href="css/history.css" />
</head>
<body>
  <header>
    <?php include('includes/navbar.php'); ?>
  </header>
  <main class="main-container">
    <div class="card">
      <div class="card-body">
        <h2 class="title">Transaction History</h2>
        <div class="date-filters">
          <label for="startDate">Start:</label>
          <input type="date" id="startDate" name="startDate">

          <label for="endDate">End:</label>
          <input type="date" id="endDate" name="endDate">

          <input type="text" id="searchInput" placeholder="Search by date or ID...">

          <button id="clearFiltersBtn" class="btn btn-secondary">Clear</button>
          <button class="export-btn" id="export-btn">Export to Excel</button>
          <button  class="btn btn-danger" id="pdf-btn">Export PDF</button>
        </div>


        <!-- Transaction List -->
        <div class="table-wrapper">
          <table id="historyTable">
            <thead>
              <tr>
                <th>Partner Name</th>
                <th>Transaction IDs</th>
                <th>Type</th>
                <th>Upload Date</th>
                <th>Transaction Date</th>
                <th>Total Price (₱)</th>
                <th>Actions</th>          
              </tr>
            </thead>
              <tbody id="tableBody">
              <?php foreach ($transactions as $row): ?>
                <tr class="main-row" data-transaction-id="<?php echo $row['transaction_id']; ?>">
                  <td class="partner-name"><?= htmlspecialchars($row['partner_name'] ?? 'N/A') ?></td>
                  <td class="transaction-id"><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                  <td class="transaction-type"><?php echo htmlspecialchars($row['transaction_type']); ?></td>
                  <td class="upload-date"><?php echo htmlspecialchars($row['upload_date']); ?></td>
                  <td class="transaction-date"><?php echo htmlspecialchars($row['manual_transaction_date']); ?></td>
                  <td class="total-price">₱<?php echo number_format($row['total_price'], 2); ?></td>
                  <td><button class="view-btn" data-transaction-id="<?php echo $row['transaction_id']; ?>">View Items</button></td>
                </tr>
              <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                  <tr class="no-results"><td colspan="6" style="text-align:center;">No transactions found.</td></tr>
                <?php endif; ?>
              </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
<?php include 'loader.html'; ?>
<script src="js/transaction_history.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</body>
</html>
