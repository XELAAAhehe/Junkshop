<?php
include('includes/db.php');
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Fetch available scrap types with their latest prices
$prices = [];
$result = $conn->query("SELECT type, measure, price FROM scrap_types");
while ($row = $result->fetch_assoc()) {
    $prices[$row['type']] = [
        'measure' => $row['measure'],
        'unit_price' => $row['price']
    ];
} // Simulate loading delay for demonstration
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Add Scrap Transaction</title>
  <link rel="stylesheet" href="css/add_scrap.css" />
</head>
<body>
  <header>
    <?php include('includes/navbar.php'); ?>
  </header>

<div class="main-container">
  <div class="card">
    <div class="card-body">
      <h2 class="title">Scrap Transaction</h2>

<div id="partnerNameContainer">
  <label for="partnerName" id="partnerNameLabel">Partner Name (Optional):</label>
  <input type="text" id="partnerName" placeholder="Enter name if any...">
</div>

<div id="transactionDateContainer">
  <label for="transactionDate" id="transactionDateLabel">
    Transaction Date & Time:
  </label>
  <input type="datetime-local" id="transactionDate" value="">
</div>



      <p class="subtitle">Add Scrap Items</p>
      <p class="description">Enter the details of the scrap items you want to add.</p>
      <div class="input-row">
        <input list="scrapTypes" id="scrapType" placeholder="Scrap Type" required>
        <datalist id="scrapTypes">
          <?php foreach ($prices as $type => $info): ?>
            <option value="<?= htmlspecialchars($type) ?>"></option>
          <?php endforeach; ?>
        </datalist>

        <select id="measureBy" required>
          <option value="">Measure</option>
          <option value="kg">kg</option>
          <option value="pcs">pcs</option>
        </select>

        <input type="number" id="amount" placeholder="Amount" step="0.1" min="0" required>
        <input type="number" id="unitPrice" placeholder="Price" step="0.01" min="0" required>
        <button id="addItem">Add Item</button>
      </div>

      <div class="table-wrapper">
        <table id="transactionTable">
          <thead>
            <tr>
              <th>Scrap Type</th>
              <th>Measure</th>
              <th>Amount</th>
              <th>Unit Price (₱)</th>
              <th>Subtotal (₱)</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody></tbody>
          <tfoot>
            <tr>
              <th colspan="4">Total:</th>
              <th id="grandTotal">₱0.00</th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="action-buttons">
        <button id="submitTransaction">Submit All Items</button>
        <button id="resetAll">Reset All</button>
      </div>
    </div>
  </div>
</div>

<script>
  const prices = <?php echo json_encode($prices); ?>;
</script>
<script src="js/add_scrap.js"></script>
<?php include 'loader.html'; ?>
</body>
</html>
