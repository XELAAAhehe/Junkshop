// =========================
// 🔔 Toast Notification System
// =========================
function showToast(message, type = "success") {
  const existingToast = document.getElementById("toast");
  if (existingToast) existingToast.remove();

  const toast = document.createElement("div");
  toast.id = "toast";
  toast.className = `toast ${type}`;
  
  // Add icons per type
  let icon = "✅";
  if (type === "error") icon = "❌";
  else if (type === "info") icon = "ℹ️";

  toast.innerHTML = `<span class="toast-icon">${icon}</span> ${message}`;
  document.body.appendChild(toast);

  // Trigger animation
  setTimeout(() => toast.classList.add("show"), 100);

  // Remove after 3s
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// =========================
// ♻️ Manage Scrap Functions
// =========================
const scrapTable = document.querySelector('#scrapTable tbody');
const newType = document.getElementById('newType');
const newPrice = document.getElementById('newPrice');
const addBtn = document.getElementById('addCategory');
const newMeasure = document.getElementById('newMeasure');

function fetchScrapList() {
  fetch('manage_scrap.php')
    .then(res => res.json())
    .then(data => {
      scrapTable.innerHTML = '';
      data.forEach(item => {
        scrapTable.innerHTML += `
          <tr>
            <td>${item.type}</td>
            <td>${item.measure}</td>
            <td>
              <input type="number" class="price-input" value="${item.price}" min="0" step="0.01"
                onchange="updatePrice(${item.id}, this.value)" />
            </td>
            <td>
              <button class="remove-btn" onclick="removeType(${item.id})">Remove</button>
            </td>
          </tr>
        `;
      });
    })
    .catch(() => showToast("Failed to load scrap list.", "error"));
}

addBtn.addEventListener('click', () => {
  const type = newType.value.trim();
  const price = parseFloat(newPrice.value);
  const measure = newMeasure.value;

  if (!type || isNaN(price) || !measure) {
    showToast('Please enter all fields including measure.', 'error');
    return;
  }

  const formData = new FormData();
  formData.append('action', 'add');
  formData.append('type', type);
  formData.append('price', price);
  formData.append('measure', measure);

  fetch('manage_scrap.php', {
    method: 'POST',
    body: formData
  })
    .then(res => res.text())
    .then(msg => {
      showToast(msg || 'Scrap type added successfully!');
      newType.value = '';
      newPrice.value = '';
      newMeasure.value = '';
      fetchScrapList();
    })
    .catch(() => showToast('Failed to add scrap type.', 'error'));
});

function updatePrice(id, price) {
  const formData = new FormData();
  formData.append('action', 'update');
  formData.append('id', id);
  formData.append('price', price);

  fetch('manage_scrap.php', {
    method: 'POST',
    body: formData
  })
    .then(() => {
      showToast('Price updated successfully!');
      fetchScrapList();
    })
    .catch(() => showToast('Failed to update price.', 'error'));
}

function removeType(id) {
  const formData = new FormData();
  formData.append('action', 'delete');
  formData.append('id', id);

  fetch('manage_scrap.php', {
    method: 'POST',
    body: formData
  })
    .then(() => {
      showToast('Scrap type removed!', 'info');
      fetchScrapList();
    })
    .catch(() => showToast('Failed to remove scrap type.', 'error'));
}

fetchScrapList();
