<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
if (( $_SESSION['role'] !== 'super-admin' && $_SESSION['role'] !== 'admin' ) 
    && basename($_SERVER['PHP_SELF']) == 'scrap_ui.php') {
    header("Location: dashboard.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Scrap Category Management</title>
  <link rel="stylesheet" href="css/manage_scrap.css"/>
</head>
<body>
  <header>
    <?php include('includes/navbar.php'); ?>
  </header>

  <div class="main-container">
    <div class="card">
      <div class="card-body">
        <h2 class="title">Scrap Category Management</h2>
 <div class="input-row">
  <input type="text" id="newType" placeholder="Scrap Type (e.g. Copper)" required>
  <select id="newMeasure" required>
    <option value="">Select Measure</option>
    <option value="kg">kg</option>
    <option value="pcs">pcs</option>
  </select>
  <input type="number" id="newPrice" placeholder="Price per unit (₱)" step="0.01" min="0" required>
  <button id="addCategory">Add</button>
</div>


        <div class="table-wrapper">
          <table id="scrapTable">
            <thead>
              <tr>
                <th>Scrap Type</th>
                <th>Measure</th> <!-- Add this -->
                <th>Price per Unit (₱)</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <!-- ✅ Toast Notification -->
<div id="toast" class="toast"></div>

<?php include 'loader.html'; ?>
  <script src="js/manage_scrap.js"></script>
</body>
</html>