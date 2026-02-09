<?php
include('includes/db.php');
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
:root {
  --bg: #f7f8fb;
  --card: #ffffff;
  --text: #795548;
  --muted: #6b7280;
  --primary: #ff8000;
  --ring: rgba(255,128,0,.25);
}

/* ========== BASE ========== */
* { box-sizing: border-box; }

body {
  margin: 0;
  font-family: 'Inter', sans-serif;
  background-color: #FAF3E0;
  color: #3E2723;
}

.container {
  max-width: 1200px;
  margin: 24px auto;
  padding: 0 16px;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 8px 0 16px;
  color: #3E2723;
  text-align: center;
}

/* ========== KPI GRID ========== */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.card {
  background: var(--card);
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 6px 20px rgba(0,0,0,.06);
  border: 1px solid #eef2f7;
}

.kpi .label {
  color: var(--muted);
  font-size: 0.875rem;
  margin-bottom: 8px;
}

.kpi .value {
  font-size: 1.625rem;
  font-weight: 700;
}

.kpi .sub {
  margin-top: 6px;
  font-size: 0.75rem;
  color: var(--muted);
}

/* ========== GRID ========== */
.grid-2 {
  display: grid;
  grid-template-columns: 2fr 1.2fr;
  gap: 16px;
  margin-bottom: 20px;
}

/* ========== SECTION TITLES ========== */
.section-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
}

/* ========== TABLE ========== */
.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
}

thead th {
  background: #edf2f7;
  text-align: left;
  padding: 12px;
  color: #3E2723;
  font-weight: 600;
  border-bottom: 1px solid #e5e7eb;
}

tbody td {
  padding: 12px;
  border-bottom: 1px solid #f1f2f6;
}

/* ========== BUTTONS ========== */
.btn {
  border: none;
  background: #ff8000;
  color: #fff;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background 0.2s ease;
}

.btn:hover { background: #e37300; }

.btn-secondary {
  background-color: #666;
}
.btn-secondary:hover {
  background-color: #555;
}

.card-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-bottom: 8px;
}

/* ========== MONTH FILTERS ========== */
.month-filters {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.month-filters label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #333;
}

.month-filters input[type="month"] {
  padding: 6px 10px;
  font-size: 0.9rem;
  border: 1px solid #ccc;
  border-radius: 6px;
  outline: none;
  background-color: #fff;
  transition: 0.2s ease;
}

.month-filters input[type="month"]:focus {
  border-color: #ff8000;
  box-shadow: 0 0 4px rgba(255,128,0,0.3);
}
.badge {
  padding: 4px 8px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.8rem;
  color: white;
}
.badge-buy {
  background-color: #ff8000;
}
.badge-sell {
  background-color: #81965F;
}

/* ========== RESPONSIVE ========== */
@media (max-width: 1000px) {
  .grid-2 { grid-template-columns: 1fr; align-items: center;}
  .kpi-grid { grid-template-columns: repeat(2,1fr); }
}

@media (max-width: 600px) {
  .kpi-grid { grid-template-columns: 1fr;align-items: center; }
  .month-filters { flex-direction: column; align-items: center; }
  .month-filters label { width: 100%; }
  .month-filters input{ width: 100%; }
  .month-filters .btn { width: 100%; }
}
@media screen and (max-width: 480px) {
  .kpi-grid {
    grid-template-columns: 1fr;align-items: center;
  }
  .month-filters {
    flex-direction: column; align-items: center;
  }
  .month-filters input{
    width: 100%;
  }
  .month-filters label {
    width: 100%;
  }
  .month-filters .btn {
    width: 100%;
  }
}
@media screen and (max-width: 360px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }
  .month-filters {
    flex-direction: column; align-items: center;
  }
  .month-filters label {
    width: 100%;
  }
  .month-filters input{
    width: 100%;
  }
  .month-filters .btn {
    width: 100%;
  }
}
  </style>
</head>
<body>
  <header>
    <?php include('includes/navbar.php'); ?>
  </header>

  <div class="container">
    <h1 class="page-title">Dashboard</h1>

    <!-- KPIs -->
    <div class="kpi-grid">
      <div class="card kpi">
        <div class="label">Inventory Value</div>
        <div class="value" id="kpi-inventory">₱0.00</div>
        <div class="sub muted">Based on current stock × latest prices</div>
      </div>
      <div class="card kpi">
        <div class="label">Scrap Types</div>
        <div class="value" id="kpi-types">0</div>
        <div class="sub muted">From scrap_types</div>
      </div>
      <div class="card kpi">
        <div class="label">Buys (This Month)</div>
        <div class="value" id="kpi-buys">₱0.00</div>
        <div class="sub muted">Sum of buy transactions</div>
      </div>
      <div class="card kpi">
        <div class="label">Sells (This Month)</div>
        <div class="value" id="kpi-sells">₱0.00</div>
        <div class="sub muted">Sum of sell transactions</div>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid-2">
      <div class="card">
        <div class="section-title">
          <span>Buys vs Sells (By Month Range)</span>
          <div class="month-filters">
            <label>From: <input type="month" id="startMonth"></label>
            <label>To: <input type="month" id="endMonth"></label>
            <button id="filterMonthBtn" class="btn">Filter</button>
            <button id="resetMonthBtn" class="btn btn-secondary">Reset</button>
          </div>
        </div>
        <canvas id="chartMonthly" height="120"></canvas>
      </div>

      <div class="card">
        <div class="section-title">Inventory Composition</div>
        <div id="totalScraps" class="muted" style="text-align:center; margin-top:10px;"></div>
        <canvas id="chartInventory" height="120"></canvas>

      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card">
      <div class="card-actions">
        <button class="btn" id="refreshBtn">Refresh</button>
      </div>
      <div class="section-title">Recent Transactions</div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Transaction Date</th>
              <th>Transaction Type</th>
              <th>Partner Name</th>
              <th>Total (₱)</th>
            </tr>
          </thead>
          <tbody id="recentBody">
            <tr><td colspan="4" class="muted">Loading...</td></tr>
          </tbody>
            <tfoot>
              <tr>
                <td colspan="3" style="text-align:right; font-weight:600;">Total Buys:</td>
                <td id="recentTotalBuys" style="font-weight:700; color:#ff8000;">₱0.00</td>
                <td></td>
              </tr>
              <tr>
                <td colspan="3" style="text-align:right; font-weight:600;">Total Sells:</td>
                <td id="recentTotalSells" style="font-weight:700; color:#81965F;">₱0.00</td>
                <td></td>
              </tr>
              <tr>
                <td colspan="3" style="text-align:right; font-weight:600;">Overall Total:</td>
                <td id="recentTotalOverall" style="font-weight:700; color:#3E2723;">₱0.00</td>
              </tr>
            </tfoot>
        </table>
      </div>
    </div>
  </div>

  <script src="js/dashboard.js" defer></script>
  <?php include 'loader.html'; ?>
</body>
</html>
