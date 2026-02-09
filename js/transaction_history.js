const tableBody = document.getElementById("tableBody");
const searchInput = document.getElementById("searchInput");
const startDateInput = document.getElementById("startDate");
const endDateInput = document.getElementById("endDate");

// Format number as currency
function formatCurrency(num) {
  return "₱" + parseFloat(num).toFixed(2);
}

// Create a full table row of items (as a single <tr> under the main row)
function createItemRows(items) {
  let rows = items.map(item => `
    <tr>
      <td>${item.type}</td>
      <td>${item.measure}</td>
      <td>${item.amount}</td>
      <td>${formatCurrency(item.unit_price)}</td>
      <td>${formatCurrency(item.subtotal)}</td>
    </tr>
  `).join('');

  return `
    <tr class="item-row">
      <td colspan="6">
        <table class="item-details-table">
          <thead>
            <tr>
              <th>Scrap</th>
              <th>Measure</th>
              <th>Amount</th>
              <th>Unit Price</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      </td>
    </tr>
  `;
}

// Fetch and display transaction items
function fetchTransactionItems(transactionId, row) {
  fetch('fetch_transaction_items.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'transaction_id=' + encodeURIComponent(transactionId)
  })
  .then(response => response.json())
  .then(data => {
    if (!Array.isArray(data) || data.length === 0) return;

    const itemRowHTML = createItemRows(data);
    const newRow = document.createElement("tr");
    newRow.classList.add("item-row");
    newRow.innerHTML = itemRowHTML;

    row.parentNode.insertBefore(newRow, row.nextSibling);
  })
  .catch(error => {
    console.error('Fetch error:', error);
  });
}

// Handle view button click
tableBody.addEventListener("click", function (e) {
  if (e.target.classList.contains("view-btn")) {
    const row = e.target.closest("tr");
    const transactionId = row.dataset.transactionId;

    // Remove existing item rows if already expanded
    let sibling = row.nextElementSibling;
    if (sibling && sibling.classList.contains("item-row")) {
      sibling.remove();
      return;
    }

    // Fetch and show items
    fetchTransactionItems(transactionId, row);
  }
});

// Search functionality (enhanced)
searchInput.addEventListener("input", () => {
  filterTable();
});

function filterTable() {
  const filter = searchInput.value.toLowerCase();
  const startDate = startDateInput.value ? new Date(startDateInput.value) : null;
  const endDate = endDateInput.value ? new Date(endDateInput.value) : null;

  // Adjust start and end dates to include the full day
  if (startDate) {
    startDate.setHours(0, 0, 0, 0); // Start of the day
  }
  if (endDate) {
    endDate.setHours(23, 59, 59, 999); // End of the day
  }

  const mainRows = tableBody.querySelectorAll("tr.main-row");
  let anyVisible = false;

  mainRows.forEach(row => {
    const text = Array.from(row.children)
      .map(cell => cell.textContent.toLowerCase())
      .join(" ");

    const dateText = row.querySelector(".transaction-date")?.textContent.trim();
    const rowDate = dateText ? new Date(dateText) : null;

    // Log the values for debugging
    console.log("Filter:", filter);
    console.log("Start Date:", startDate);
    console.log("End Date:", endDate);
    console.log("Row Date:", rowDate);

    let matchesSearch = text.includes(filter);
    let matchesRange = true;

    // Check date range if both dates are provided
    if (startDate && endDate && rowDate) {
      matchesRange = rowDate >= startDate && rowDate <= endDate;
    } else if (startDate && rowDate) {
      matchesRange = rowDate >= startDate; // Only check start date
    } else if (endDate && rowDate) {
      matchesRange = rowDate <= endDate; // Only check end date
    }

    const show = matchesSearch && matchesRange;
    row.style.display = show ? "" : "none";

    const next = row.nextElementSibling;
    if (next && next.classList.contains("item-row")) {
      next.style.display = show ? "" : "none";
    }

    if (show) anyVisible = true;
  });

  // Show or hide "no results" row
  const noResultsRow = document.querySelector("#tableBody .no-results");
  if (!anyVisible) {
    if (!noResultsRow) {
      const newRow = document.createElement("tr");
      newRow.classList.add("no-results");
      newRow.innerHTML = `<td colspan="6" style="text-align:center;">No matching transactions found.</td>`;
      tableBody.appendChild(newRow);
    }
  } else {
    if (noResultsRow) noResultsRow.remove();
  }
}


// Clear filters
document.getElementById("clearFiltersBtn").addEventListener("click", () => {
  searchInput.value = "";
  startDateInput.value = "";
  endDateInput.value = "";
  filterTable();
});

document.addEventListener("DOMContentLoaded", function () {
   document.getElementById("export-btn").addEventListener("click", () => {
        const table = document.getElementById("historyTable");
        const searchValue = document.getElementById("searchInput").value || "None";
        const startDate = document.getElementById("startDate").value;
        const endDate = document.getElementById("endDate").value;
        const dateRange = (startDate && endDate) ? `${startDate} to ${endDate}` : "None";
        const exportDate = new Date().toLocaleString();

        // Get only visible main rows
        const visibleRows = Array.from(table.querySelectorAll("tbody tr.main-row")).filter(row => row.style.display !== "none");

        if (visibleRows.length === 0) {
            alert("No transactions to export.");
            return;
        }

        // Main Sheet: Transaction Summary
        const mainData = [
            ["Transaction Summary"],
            [`Exported on: ${exportDate}`],
            [`Search Filter: ${searchValue}`],
            [`Date Range Filter: ${dateRange}`],
            [],
            ["Partner Name", "Transaction ID", "Transaction Type", "Upload Date", "Manual Transaction Date", "Total Price"]
        ];

        const visibleTransactionIds = [];

        visibleRows.forEach(row => {
            const partnerName = row.querySelector(".partner-name")?.innerText.trim() || "N/A";
            const transactionId = row.querySelector(".transaction-id")?.innerText.trim() || "";
            const transactionType = row.querySelector(".transaction-type")?.innerText.trim() || "";
            const uploadDate = row.querySelector(".upload-date")?.innerText.trim() || "";
            const manualTransactionDate = row.querySelector(".transaction-date")?.innerText.trim() || "";
            const totalPrice = row.querySelector(".total-price")?.innerText.replace("₱", "").trim() || "0.00";

            visibleTransactionIds.push(transactionId);

            mainData.push([partnerName, transactionId, transactionType, uploadDate, manualTransactionDate, totalPrice]);
        });

        const summarySheet = XLSX.utils.aoa_to_sheet(mainData);
        summarySheet["!cols"] = [
            { wch: 25 },
            { wch: 20 },
            { wch: 10 },
            { wch: 20 },
            { wch: 20 },
            { wch: 15 }
        ];

        // Fetch details and create details sheet
        fetch("get_transaction_details.php")
            .then(response => response.json())
            .then(data => {
                const filteredDetails = data.filter(item =>
                    visibleTransactionIds.includes(item.transaction_id)
                );

                const detailsData = [
                    ["Transaction Details"],
                    [`Exported on: ${exportDate}`],
                    [`Search Filter: ${searchValue}`],
                    [`Date Range Filter: ${dateRange}`],
                    [],
                    ["Partner Name", "Transaction ID", "Upload Date", "Manual Transaction Date", "Scrap Type", "Measure", "Amount", "Unit Price", "Subtotal"]
                ];

                filteredDetails.forEach(item => {
                    detailsData.push([
                        item.partner_name || "N/A",
                        item.transaction_id,
                        item.upload_date || "",
                        item.manual_transaction_date || "",
                        item.type,
                        item.measure,
                        item.amount,
                        item.unit_price,
                        (item.amount * item.unit_price).toFixed(2)
                    ]);
                });


                const detailsSheet = XLSX.utils.aoa_to_sheet(detailsData);
                detailsSheet["!cols"] = [
                    { wch: 25 },
                    { wch: 20 },
                    { wch: 15 },
                    { wch: 10 },
                    { wch: 10 },
                    { wch: 12 },
                    { wch: 12 }
                ];

                // Export workbook with both sheets
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, summarySheet, "Summary");
                XLSX.utils.book_append_sheet(wb, detailsSheet, "Details");

                XLSX.writeFile(wb, `Transaction_History_${new Date().toISOString().slice(0, 10)}.xlsx`);
                if (window.hideLoader) hideLoader();

            })
            .catch(error => {
                console.error("Error fetching details:", error);
                alert("Failed to fetch transaction details.");
                if (window.hideLoader) hideLoader();
            });
    });
document.getElementById("pdf-btn").addEventListener("click", async () => {
  const { jsPDF } = window.jspdf;

  if (window.showLoader) showLoader(); // show loader at start

  const table = document.getElementById("historyTable");
  const searchValue = document.getElementById("searchInput").value || "None";
  const startDate = document.getElementById("startDate").value;
  const endDate = document.getElementById("endDate").value;
  const dateRange = (startDate && endDate) ? `${startDate} to ${endDate}` : "None";
  const exportDate = new Date().toLocaleString();

  const visibleRows = Array.from(table.querySelectorAll("tbody tr.main-row"))
    .filter(row => row.style.display !== "none");

  if (visibleRows.length === 0) {
    alert("No transactions to export.");
    if (window.hideLoader) hideLoader();
    return;
  }

  // Create PDF document
  const doc = new jsPDF("l", "pt", "a4");
  doc.setFont("helvetica", "bold");
  doc.setFontSize(16);
  doc.text("Transaction Report", 40, 40);
  doc.setFontSize(10);
  doc.setFont("helvetica", "normal");
  doc.text(`Exported on: ${exportDate}`, 40, 60);
  doc.text(`Search Filter: ${searchValue}`, 40, 75);
  doc.text(`Date Range: ${dateRange}`, 40, 90);

  // Table header
  const headers = ["Partner Name", "Transaction ID", "Type", "Upload Date", "Manual Date", "Total (₱)"];

  // Table data
  const data = visibleRows.map(row => [
    row.querySelector(".partner-name")?.innerText.trim() || "N/A",
    row.querySelector(".transaction-id")?.innerText.trim() || "",
    row.querySelector(".transaction-type")?.innerText.trim() || "",
    row.querySelector(".upload-date")?.innerText.trim() || "",
    row.querySelector(".transaction-date")?.innerText.trim() || "",
    row.querySelector(".total-price")?.innerText.trim() || "₱0.00"
  ]);

  // Calculate totals
  let totalBuys = 0;
  let totalSells = 0;

  data.forEach(row => {
    const type = row[2].toLowerCase();
    const val = parseFloat(row[5].replace(/[₱,]/g, "")) || 0;
    if (type.includes("buy")) totalBuys += val;
    if (type.includes("sell")) totalSells += val;
  });

  // Generate table
  doc.autoTable({
    head: [headers],
    body: data,
    startY: 110,
    theme: "grid",
    headStyles: { fillColor: [255, 128, 0], textColor: 255, fontStyle: "bold" },
    styles: { fontSize: 9, cellPadding: 5, lineWidth: 0.1 },
    margin: { left: 40, right: 40 },
  });

  // Totals section
  const finalY = doc.lastAutoTable.finalY + 25;
  doc.setFont("helvetica", "bold");
  doc.setFontSize(12);
  doc.setTextColor(50, 50, 50);

  const totalBuysText = `Total Buys: ₱${totalBuys.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
  const totalSellsText = `Total Sells: ₱${totalSells.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
  const netText = `Net Difference: ₱${(totalSells - totalBuys).toLocaleString(undefined, { minimumFractionDigits: 2 })}`;

  doc.text(totalBuysText, 40, finalY);
  doc.setTextColor(150, 50, 50);
  doc.text(totalSellsText, 250, finalY);
  doc.setTextColor(0, 102, 0);
  doc.text(netText, 450, finalY);

  // Footer page numbers
  const pageCount = doc.internal.getNumberOfPages();
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.setFontSize(9);
    doc.setTextColor(100);
    doc.text(`Page ${i} of ${pageCount}`, doc.internal.pageSize.getWidth() - 100, doc.internal.pageSize.getHeight() - 20);
  }

  // Save and hide loader
  doc.save(`Transaction_Report_${new Date().toISOString().slice(0, 10)}.pdf`);
  if (window.hideLoader) hideLoader();
});

    // Listen for changes
    searchInput.addEventListener("input", filterTable);
    startDateInput.addEventListener("change", filterTable);
    endDateInput.addEventListener("change", filterTable);
});

