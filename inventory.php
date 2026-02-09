<?php
include('includes/db.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Scrap Inventory</title>
  <link rel="stylesheet" href="css/inventory.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <style>
    th[data-sorted="asc"]::after { content: " ↑"; color: #4CAF50; }
    th[data-sorted="desc"]::after { content: " ↓"; color: #f44336; }
    th[data-sorted] { position: relative; padding-right: 20px; }
  </style>
</head>
<body>
  <header>
    <?php include('includes/navbar.php'); ?>
  </header>

  <main class="main-container">
    <div class="card">
      <h2 class="title">Scrap Inventory</h2> 

      <div class="search-bar">
        <input type="text" id="smartSearch" placeholder="Search (e.g. type:plastic measure:kg min:10 max:100)">
    <button id="exportBtn" class="export-button">
      <i class="fas fa-file-excel"></i> Export to Excel
    </button>
      </div>

      <p class="description">This page provides an overview of all scrap types in your inventory, including buy and sell values.</p>

      <div class="table-wrapper">
        <table id="inventoryTable" data-sort-dir="asc">
          <thead>
            <tr>
              <th>Scrap Type</th>
              <th>Total or Remaining Amount</th>
              <th>Measurement Use</th>
              <th>Total Buy Cost (₱)</th>
              <th>Total Sells (₱)</th>
            </tr>
          </thead>
          <tbody id="inventoryBody">
            <?php
              $sql = "SELECT 
                        i.type,
                        i.measure,
                        SUM(CASE WHEN t.transaction_type = 'buy' THEN i.amount ELSE -i.amount END) AS total_amount,
                        SUM(CASE WHEN t.transaction_type = 'buy' THEN i.amount * i.unit_price ELSE 0 END) AS total_buy_cost,
                        SUM(CASE WHEN t.transaction_type = 'sell' THEN i.amount * i.unit_price ELSE 0 END) AS total_sell_cost
                      FROM transaction_items i
                      JOIN transactions t ON i.transaction_id = t.transaction_id
                      GROUP BY i.type, i.measure
                      ";

              $result = $conn->query($sql);

              if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
            ?>
<tr class="main-data-row" data-type="<?= htmlspecialchars($row['type']) ?>" data-measure="<?= htmlspecialchars($row['measure']) ?>">
  <td><?= htmlspecialchars($row['type']) ?></td>
  <td><?= number_format($row['total_amount'], 2) ?></td>
  <td><?= htmlspecialchars($row['measure']) ?></td>
  <td>₱<?= number_format($row['total_buy_cost'], 2) ?></td>
  <td>₱<?= number_format($row['total_sell_cost'], 2) ?></td>
</tr>
<tr class="preview-toggle">
  <td colspan="5">
    <button class="toggle-log-btn">Show Transactions</button>
  </td>
</tr>
<tr class="transaction-preview" style="display: none;">
  <td colspan="5">
    <div class="transaction-log">Loading...</div>
  </td>
</tr>

            <?php endwhile; else: ?>
            <tr><td colspan="5">No inventory data available.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
<?php include 'loader.html'; ?>
  <script src="js/inventory.js"></script>
</body>
</html>
