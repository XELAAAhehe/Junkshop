document.getElementById("chartType").addEventListener("change", () => {
  loadReport(); // redraw when chart type changes
});

function loadReport() {
  const startDate = document.getElementById("startDate").value;
  const endDate = document.getElementById("endDate").value;

  fetch("report_data.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ startDate, endDate }),
  })
    .then((res) => res.json())
    .then((data) => {
      updateSummary(data.summary);
      updateChart(data);
      updateTypeBreakdown(data.scrapBreakdown);
    })
    .catch((err) => console.error("Fetch error:", err));
}

function updateSummary(summary) {
  const tbody = document.querySelector("#summaryTable tbody");
  tbody.innerHTML = `
    <tr>
      <td>₱${parseFloat(summary.total_buys || 0).toFixed(2)}</td>
      <td>₱${parseFloat(summary.total_sells || 0).toFixed(2)}</td>
      <td>₱${parseFloat(summary.total_income || 0).toFixed(2)}</td>
    </tr>
  `;
}
function updateTypeBreakdown(items) {
  const tbody = document.querySelector("#typeBreakdownTable tbody");
  tbody.innerHTML = "";

  if (!items || items.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;">No breakdown data available.</td></tr>`;
    return;
  }

  items.forEach(item => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${item.type}</td>
      <td>₱${parseFloat(item.total_buy || 0).toFixed(2)}</td>
      <td>₱${parseFloat(item.total_sell || 0).toFixed(2)}</td>
      <td>₱${parseFloat(item.profit || 0).toFixed(2)}</td>
    `;
    tbody.appendChild(tr);
  });
}

let chartInstance = null;

function updateChart(graphData) {
  const ctx = document.getElementById("transactionChart").getContext("2d");
  const chartType = document.getElementById("chartType").value || "bar";

  if (chartInstance) chartInstance.destroy();

  const isLine = chartType === "line";

  chartInstance = new Chart(ctx, {
    type: chartType,
    data: {
      labels: graphData.dates,
      datasets: [
        {
          label: "Buys (₱)",
          data: graphData.buys,
          backgroundColor: isLine ? "transparent" : "rgba(255, 166, 0, 1)",
          borderColor: "rgba(255, 166, 0, 1)",
          borderWidth: 2,
          fill: false,
          tension: 0.3,
          pointRadius: 4,
          pointHoverRadius: 6
        },
        {
          label: "Sells (₱)",
          data: graphData.sells,
          backgroundColor: isLine ? "transparent" : "rgba(140, 156, 109, 0.8)",
          borderColor: "rgba(103, 118, 87, 1)",
          borderWidth: 2,
          fill: false,
          tension: 0.3,
          pointRadius: 4,
          pointHoverRadius: 6
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: "top" },
        title: {
          display: true,
          text: "Daily Transactions"
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: value => `₱${value}`
          }
        }
      }
    }
  });
}


function updateTypeBreakdown(items) {
  const tbody = document.querySelector("#typeBreakdownTable tbody");
  tbody.innerHTML = "";

  if (!items || items.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;">No breakdown data available.</td></tr>`;
    return;
  }

  items.forEach(item => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${item.type}</td>
      <td>₱${parseFloat(item.total_buy || 0).toFixed(2)}</td>
      <td>₱${parseFloat(item.total_sell || 0).toFixed(2)}</td>
      <td>₱${parseFloat(item.profit || 0).toFixed(2)}</td>
    `;
    tbody.appendChild(tr);
  });
}

function exportReport() {
  if (window.showLoader) showLoader(); // ✅ show loader at start

  const startDate = document.getElementById("startDate").value || "N/A";
  const endDate = document.getElementById("endDate").value || "N/A";
  const exportDate = new Date().toLocaleString();

  // ==== Summary Sheet ====
  const summaryRow = document.querySelector("#summaryTable tbody tr");
  if (!summaryRow) {
    alert("No summary to export.");
    if (window.hideLoader) hideLoader();
    return;
  }

  const [buys, sells, income] = summaryRow.children;
  const summaryData = [
    ["Scrap Inventory Report"],
    [`Date Generated: ${exportDate}`],
    [`Date Range: ${startDate} to ${endDate}`],
    [],
    ["Total Buys", "Total Sells", "Total Income"],
    [buys.textContent, sells.textContent, income.textContent],
  ];
  const summarySheet = XLSX.utils.aoa_to_sheet(summaryData);

  // ==== Chart Sheet ====
  if (!chartInstance) {
    alert("No chart data available.");
    if (window.hideLoader) hideLoader();
    return;
  }

  const labels = chartInstance.data.labels;
  const buyData = chartInstance.data.datasets[0].data;
  const sellData = chartInstance.data.datasets[1].data;

  const chartData = [["Date", "Buy (₱)", "Sell (₱)"]];
  for (let i = 0; i < labels.length; i++) {
    chartData.push([labels[i], buyData[i], sellData[i]]);
  }
  const chartSheet = XLSX.utils.aoa_to_sheet(chartData);

  // ==== Scrap Type Breakdown Sheet ====
  const breakdownTable = document.querySelector("#typeBreakdownTable tbody");
  let breakdownSheet;

  if (breakdownTable && breakdownTable.rows.length > 0) {
    const breakdownRows = Array.from(breakdownTable.rows).map(row =>
      Array.from(row.cells).map(cell => cell.textContent)
    );

    breakdownSheet = XLSX.utils.aoa_to_sheet([
      ["Scrap Type Breakdown"],
      ["Type", "Buy (₱)", "Sell (₱)", "Profit (₱)"],
      ...breakdownRows,
    ]);
  } else {
    breakdownSheet = XLSX.utils.aoa_to_sheet([
      ["Scrap Type Breakdown"],
      ["No data available for selected date range."]
    ]);
  }

  // ==== Create and Export Workbook ====
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, summarySheet, "Summary");
  XLSX.utils.book_append_sheet(wb, chartSheet, "Chart Data");
  XLSX.utils.book_append_sheet(wb, breakdownSheet, "Scrap Breakdown");

  const fileName = `Scrap_Report_${new Date().toISOString().slice(0, 10)}.xlsx`;
  XLSX.writeFile(wb, fileName);

  // ✅ Hide loader after export
  if (window.hideLoader) hideLoader();
}



