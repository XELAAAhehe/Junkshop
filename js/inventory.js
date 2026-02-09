document.getElementById("smartSearch").addEventListener("input", smartFilter);

function smartFilter() {
    const input = document.getElementById("smartSearch").value.toLowerCase();
    const rows = document.querySelectorAll("#inventoryBody tr");

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];

        // Detect main data row (not toggle/preview)
        if (!row.classList.contains("preview-toggle") && !row.classList.contains("transaction-preview")) {
            const cells = row.querySelectorAll("td");
            if (cells.length < 5) continue;

            const scrap = cells[0].textContent.toLowerCase();
            const amount = cells[1].textContent.toLowerCase();
            const measure = cells[2].textContent.toLowerCase();
            const buy = cells[3].textContent.toLowerCase();
            const sell = cells[4].textContent.toLowerCase();

            const match = `${scrap} ${amount} ${measure} ${buy} ${sell}`.includes(input);

            // Related rows (toggle + preview)
            const toggleRow = rows[i + 1];
            const previewRow = rows[i + 2];

            // Show/hide all related rows based on match
            row.style.display = match ? '' : 'none';
            if (toggleRow && toggleRow.classList.contains("preview-toggle")) {
                toggleRow.style.display = match ? '' : 'none';
                const btn = toggleRow.querySelector(".toggle-log-btn");
                if (btn) btn.textContent = "Show Transactions";
            }
            if (previewRow && previewRow.classList.contains("transaction-preview")) {
                previewRow.style.display = "none"; // Always hide preview on search
            }

            i += 2; // Skip next 2 rows since we handled them
        }
    }
}



document.querySelectorAll("#inventoryTable th").forEach((header, index) => {
    header.style.cursor = "pointer";
    header.removeAttribute("data-sorted");

    header.addEventListener("click", () => {
        document.querySelectorAll("#inventoryTable th").forEach(h => h.removeAttribute("data-sorted"));

        const table = document.getElementById("inventoryTable");
        const currentDir = header.getAttribute("data-sorted") || table.getAttribute("data-sort-dir") || "asc";
        const newDirection = currentDir === "asc" ? "desc" : "asc";

        sortTableByGroup(index, newDirection);
        header.setAttribute("data-sorted", newDirection);
        table.setAttribute("data-sort-dir", newDirection);
    });
});

function sortTableByGroup(columnIndex, direction) {
    const table = document.getElementById("inventoryTable");
    const body = table.tBodies[0];
    const rows = Array.from(body.rows);

    const rowGroups = [];
    for (let i = 0; i < rows.length; i += 3) {
        const group = rows.slice(i, i + 3); // [dataRow, toggleRow, previewRow]
        if (group.length === 3) rowGroups.push(group);
    }

    const isNumericColumn = columnIndex === 1 || columnIndex === 3;

    rowGroups.sort((a, b) => {
        const aCell = a[0].cells[columnIndex]?.textContent.trim() || '';
        const bCell = b[0].cells[columnIndex]?.textContent.trim() || '';

        if (isNumericColumn) {
            const aNum = parseFloat(aCell.replace(/[₱,]/g, '')) || 0;
            const bNum = parseFloat(bCell.replace(/[₱,]/g, '')) || 0;
            return direction === "asc" ? aNum - bNum : bNum - aNum;
        }

        return direction === "asc"
            ? aCell.localeCompare(bCell)
            : bCell.localeCompare(aCell);
    });

    // Re-append rows in sorted order
    rowGroups.forEach(group => body.append(...group));
}

document.getElementById("exportBtn").addEventListener("click", () => {
  if (window.showLoader) showLoader(); // ✅ manually show loader

  const table = document.getElementById("inventoryTable");

  // Clone and clean
  const exportTable = table.cloneNode(true);
  exportTable.querySelectorAll(".transaction-preview, .preview-toggle").forEach(row => {
    if (row.style.display === "none") {
      row.remove();
    }
  });

  // Fix colspan
  const colspan = 5;

  // Create <thead> at top
  const thead = exportTable.createTHead();

  // Title Row
  const titleRow = thead.insertRow();
  const titleCell = titleRow.insertCell();
  titleCell.colSpan = colspan;
  titleCell.style = "font-size:16pt;font-weight:bold;text-align:center";
  titleCell.textContent = "Scrap Inventory Report";

  // Date Row
  const dateRow = thead.insertRow();
  const dateCell = dateRow.insertCell();
  dateCell.colSpan = colspan;
  dateCell.style = "text-align:center;font-style:italic";
  dateCell.textContent = "Exported: " + new Date().toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });

  // Filter Info Row
  const searchTerm = document.getElementById("smartSearch").value;
  const sortTh = document.querySelector("#inventoryTable th[data-sorted]");
  const sortedColumnName = sortTh ? sortTh.textContent : "None";
  const sortDirection = sortTh ? sortTh.getAttribute("data-sorted") : "none";

  const filterRow = thead.insertRow();
  const filterCell = filterRow.insertCell();
  filterCell.colSpan = colspan;
  filterCell.style = "text-align:center;font-style:italic";
  filterCell.textContent = `Filtered by: ${searchTerm ? `Search: "${searchTerm}"` : "No search applied"}, Sort: ${sortedColumnName} (${sortDirection})`;

  // Create workbook
  const wb = XLSX.utils.table_to_book(exportTable, {
    sheet: "Inventory",
    sheetStubs: true,
    cellStyles: true
  });

  // Column widths
  wb.Sheets["Inventory"]["!cols"] = [
    { wch: 25 },
    { wch: 20 },
    { wch: 15 },
    { wch: 20 },
    { wch: 20 }
  ];

  const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
  const filename = `Scrap_Inventory_${dateStr}.xlsx`;

  XLSX.writeFile(wb, filename);

  // ✅ Hide loader right after export completes
  if (window.hideLoader) hideLoader();

  // ✅ Green modern notification (reused)
  const notification = document.createElement('div');
  notification.className = 'export-notification';
  notification.innerHTML = `<i class="fas fa-check-circle"></i> Exported <strong>${filename}</strong> successfully!`;
  document.body.appendChild(notification);

  setTimeout(() => {
    notification.classList.add('fade-out');
    setTimeout(() => notification.remove(), 500);
  }, 3000);
});



document.querySelectorAll(".toggle-log-btn").forEach(btn => {
  btn.addEventListener("click", function () {
    const toggleRow = btn.closest("tr");
    const mainRow = toggleRow.previousElementSibling;
    const type = mainRow.dataset.type;
    const measure = mainRow.dataset.measure;
    const previewRow = toggleRow.nextElementSibling;
    const logDiv = previewRow.querySelector(".transaction-log");

    if (previewRow.style.display === "none") {
      previewRow.style.display = "table-row";
      btn.textContent = "Hide Transactions";

      fetch(`load.php?type=${encodeURIComponent(type)}&measure=${encodeURIComponent(measure)}`)
        .then(res => res.text())
        .then(html => {
          logDiv.innerHTML = html;
        })
        .catch(err => {
          logDiv.innerHTML = "Failed to load.";
          console.error(err);
        });
    } else {
      previewRow.style.display = "none";
      btn.textContent = "Show Transactions";
    }
  });
});


