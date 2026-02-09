const fmtPeso = (n) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 2 }).format(Number(n || 0));

let chartMonthly, chartInventory;
let monthlyData = []; // ✅ this holds data for sorting

async function loadDashboard() {
  try {
    const res = await fetch('dashboard_data.php', { cache: 'no-store' });
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    updateKPIs(data.summary || {});

    // ✅ store monthly data for sorting
    monthlyData = data.monthlyGraph || [];
    renderMonthlyChart(monthlyData);

    renderInventoryChart(data.inventoryOverview || []);
    renderRecentTransactions(data.recentTransactions || []);

  } catch (e) {
    console.error(e);
    alert('Failed to load dashboard data.');
  }
}

function updateKPIs(s) {
  document.getElementById('kpi-inventory').textContent = fmtPeso(s.total_inventory_value || 0);
  document.getElementById('kpi-types').textContent = s.total_scrap_types || 0;
  document.getElementById('kpi-buys').textContent = fmtPeso(s.total_buys_month || 0);
  document.getElementById('kpi-sells').textContent = fmtPeso(s.total_sells_month || 0);
}

function renderMonthlyChart(data) {
  const labels = data.map(r => r.month);
  const buys = data.map(r => Number(r.total_buys || 0));
  const sells = data.map(r => Number(r.total_sells || 0));

  if (chartMonthly) chartMonthly.destroy();
  chartMonthly = new Chart(document.getElementById('chartMonthly'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'Buys', data: buys, backgroundColor: '#ff8000ff' },
        { label: 'Sells', data: sells, backgroundColor: '#81965F' }
      ]
    },
    options: {
      responsive: true,
      scales: {
        y: { ticks: { callback: (v) => '₱' + v.toLocaleString() } }
      }
    }
  });
}

// 🔷 Month range filter + reset for Buys vs Sells chart
document.addEventListener('DOMContentLoaded', () => {
  const filterBtn = document.getElementById('filterMonthBtn');
  const resetBtn = document.getElementById('resetMonthBtn');
  const startInput = document.getElementById('startMonth');
  const endInput = document.getElementById('endMonth');

  if (filterBtn) {
    filterBtn.addEventListener('click', () => {
      if (!monthlyData.length) return;

      const start = startInput.value ? new Date(startInput.value + '-01') : null;
      const end = endInput.value ? new Date(endInput.value + '-01') : null;

      // Filter data by month range
      const filtered = monthlyData.filter(item => {
        const itemDate = new Date(item.month + '-01');
        if (start && itemDate < start) return false;
        if (end && itemDate > end) return false;
        return true;
      });

      renderMonthlyChart(filtered);
    });
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      // clear inputs
      startInput.value = '';
      endInput.value = '';

      // restore full chart
      renderMonthlyChart(monthlyData);
    });
  }
});




function renderInventoryChart(data) {
  const labels = data.map(r => r.type);
  const values = data.map(r => Number(r.stock_amount || 0));
  const totalScraps = values.reduce((a, b) => a + b, 0);

  // ✅ show total number of scraps below chart
  const totalDiv = document.getElementById('totalScraps');
  if (totalDiv) {
    totalDiv.textContent = `Total Scraps: ${totalScraps}`;
  }

  if (chartInventory) chartInventory.destroy();
  chartInventory = new Chart(document.getElementById('chartInventory'), {
    type: 'doughnut',
    data: { labels, datasets: [{ data: values }] },
    options: { plugins: { legend: { position: 'bottom' } } }
  });
}

function renderRecentTransactions(rows) {
  const tb = document.getElementById('recentBody');
  const totalBuysEl = document.getElementById('recentTotalBuys');
  const totalSellsEl = document.getElementById('recentTotalSells');
  const totalOverallEl = document.getElementById('recentTotalOverall');

  tb.innerHTML = '';

  if (!rows || !rows.length) {
    tb.innerHTML = '<tr><td colspan="4" class="muted">No recent transactions.</td></tr>';
    if (totalBuysEl) totalBuysEl.textContent = fmtPeso(0);
    if (totalSellsEl) totalSellsEl.textContent = fmtPeso(0);
    if (totalOverallEl) totalOverallEl.textContent = fmtPeso(0);
    return;
  }

  const frag = document.createDocumentFragment();
  let totalBuys = 0;
  let totalSells = 0;

  rows.forEach(row => {
    // parse numeric value robustly (handles "₱1,234.56" or "1234.56")
    const raw = (row.total_value ?? row.total_price ?? row.total) || 0;
    const value = Number(String(raw).replace(/[^\d.-]/g, '')) || 0;

    const typeRaw = String(row.transaction_type || '').trim().toLowerCase();
    const isSell = typeRaw === 'sell' || typeRaw === 'sells';
    const isBuy = typeRaw === 'buy' || typeRaw === 'buys';

    if (isSell) totalSells += value;
    else if (isBuy) totalBuys += value;
    else {
      // If type is ambiguous, use transaction_type field presence:
      // assume positive goes to overall (but not counted in buy/sell)
    }

    const tr = document.createElement('tr');
    const badgeClass = isSell ? 'badge badge-sell' : 'badge badge-buy';
    const dateText = row.manual_transaction_date
      ? new Date(row.manual_transaction_date.replace(' ', 'T')).toLocaleString()
      : (row.transaction_date ? new Date(String(row.transaction_date).replace(' ', 'T')).toLocaleString() : '-');

    tr.innerHTML = `
      <td>${dateText}</td>
      <td><span class="${badgeClass}">${(row.transaction_type || '-').toString().toUpperCase()}</span></td>
      <td>${row.partner_name || 'N/A'}</td>
      <td>${fmtPeso(value)}</td>
    `;
    frag.appendChild(tr);
  });

  tb.appendChild(frag);

  const overall = totalBuys + totalSells;

  if (totalBuysEl) totalBuysEl.textContent = fmtPeso(totalBuys);
  if (totalSellsEl) totalSellsEl.textContent = fmtPeso(totalSells);
  if (totalOverallEl) totalOverallEl.textContent = fmtPeso(overall);
}

document.getElementById('refreshBtn').addEventListener('click', loadDashboard);
document.addEventListener('DOMContentLoaded', loadDashboard);
