<?php include 'includes/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Scrap Report</title>
  <link rel="stylesheet" href="css/report.css" />
</head>
<body>
<header>
  <?php include('includes/navbar.php'); ?>
</header>

<div class="report-container">
  <h1 class="report-title">Scrap Report</h1>

  <div class="report-filters">
    <label>Date Range:</label>
    <input type="date" id="startDate">
    <input type="date" id="endDate">
<div class="chart-options">
  <label for="chartType">Chart Style:</label>
  <select id="chartType">
    <option value="bar">Bar Chart</option>
    <option value="line">Line Chart</option>
  </select>
</div>

    <button onclick="loadReport()">Generate Report</button>
    <button id="exportReportBtn" onclick="exportReport()">Export Report to Excel</button>

  </div>

  <div class="report-summary">
    <h2>Summary Table</h2>
    <table id="summaryTable" class="report-table">
      <thead>
        <tr><th>Total Buys</th><th>Total Sells</th><th>Total Income</th></tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <div class="report-graph">
    <h2>Transaction Chart</h2>
    <canvas id="transactionChart"></canvas>
  </div>

<div class="report-breakdown">
  <h2 class="report-subtitle">Scrap Type Breakdown</h2>
  <div class="report-table-wrapper">
    <table id="typeBreakdownTable" class="report-table">
      <thead>
        <tr>
          <th>Scrap Type</th>
          <th>Total Buys (₱)</th>
          <th>Total Sells (₱)</th>
          <th>Profit (₱)</th>
        </tr>
      </thead>
      <tbody>
        <!-- Filled by JS -->
      </tbody>
    </table>
  </div>
</div>

<?php include 'loader.html'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="js/report.js"></script>
</body>
</html>
