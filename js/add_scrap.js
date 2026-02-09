const scrapType = document.getElementById('scrapType');
const measureBy = document.getElementById('measureBy');
const amount = document.getElementById('amount');
const unitPrice = document.getElementById('unitPrice');
const addItemBtn = document.getElementById('addItem');
const transactionTable = document.getElementById('transactionTable').querySelector('tbody');
const grandTotalDisplay = document.getElementById('grandTotal');
const submitBtn = document.getElementById('submitTransaction');
const resetBtn = document.getElementById('resetAll');
const partnerNameInput = document.getElementById('partnerName');
const transactionDateInput = document.getElementById('transactionDate');

let items = [];

scrapType.addEventListener('input', function () {
  const selected = scrapType.value.trim();
  if (prices[selected]) {
    measureBy.value = prices[selected].measure;
    unitPrice.value = prices[selected].unit_price;
  }
});

function renderTable() {
  transactionTable.innerHTML = "";
  let total = 0;

  items.forEach((item, index) => {
    const subtotal = (item.amount * item.unitPrice).toFixed(2);
    total += parseFloat(subtotal);

    const row = `
      <tr>
        <td>${item.type}</td>
        <td>${item.measure}</td>
        <td>${item.amount}</td>
        <td>₱${item.unitPrice.toFixed(2)}</td>
        <td>₱${subtotal}</td>
        <td><button class="remove-btn" onclick="removeItem(${index})">Remove</button></td>
      </tr>
    `;
    transactionTable.innerHTML += row;
  });

  grandTotalDisplay.textContent = "₱" + total.toFixed(2);
}

function removeItem(index) {
  items.splice(index, 1);
  renderTable();
}

addItemBtn.addEventListener('click', function (e) {
  e.preventDefault();

  if (
    scrapType.value.trim() === "" ||
    measureBy.value === "" ||
    amount.value === "" ||
    unitPrice.value === ""
  ) {
    alert("Please fill in all fields.");
    return;
  }

  items.push({
    type: scrapType.value.trim(),
    measure: measureBy.value,
    amount: parseFloat(amount.value),
    unitPrice: parseFloat(unitPrice.value)
  });

  scrapType.value = "";
  measureBy.value = "";
  amount.value = "";
  unitPrice.value = "";

  renderTable();
});

submitBtn.addEventListener('click', function () {
  if (items.length === 0) {
    alert("No scrap items to submit.");
    return;
  }

  const partnerName = partnerNameInput.value.trim();
  const transactionDateValue = transactionDateInput.value; // may be "" if left blank

  // Build payload
  const payload = {
    name: partnerName,
    transaction_type: typeof transactionType !== 'undefined' ? transactionType : 'buy',
    items: items
  };

  // Only include manual_transaction_date if user actually picked one
  if (transactionDateValue) {
    // normalize "YYYY-MM-DDTHH:MM" -> "YYYY-MM-DD HH:MM:00"
    payload.manual_transaction_date = transactionDateValue.replace("T", " ") + ":00";
  }
  console.log("Submitting payload:", payload);

  fetch('process_scrap.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(async response => {
    const text = await response.text();
    console.log("Server response:", text);
    alert(text);

    items = [];
    renderTable();
    partnerNameInput.value = "";
    transactionDateInput.value = ""; // leave blank after submit
  })
  .catch(error => {
    console.error("Error submitting items:", error);
    alert("Something went wrong!");
  });
});

resetBtn.addEventListener('click', function () {
  if (confirm("Clear all entered items?")) {
    items = [];
    renderTable();
    partnerNameInput.value = "";
    transactionDateInput.value = "";
  }
});
