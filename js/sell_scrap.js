
let transactionItems = [];
let transactionCounter = 0;

// Update measurement display when scrap type changes
document.getElementById('scrapType').addEventListener('change', function() {
    const selectedOption = document.querySelector(`#scrapTypes option[value="${this.value}"]`);
    if (selectedOption) {
        const measure = selectedOption.dataset.measure;
        const buyPrice = parseFloat(selectedOption.dataset.buyprice);
        
        document.getElementById('measureDisplay').textContent = measure;
        
        // Set default selling price (20% markup)
        const defaultSellPrice = buyPrice * 1.2;
        document.getElementById('unitPrice').value = defaultSellPrice.toFixed(2);
        
        // Clear any warnings
        document.getElementById('warning').textContent = '';
    }
});

// Add item to transaction
document.getElementById('addItemBtn').addEventListener('click', function() {
    const scrapType = document.getElementById('scrapType').value;
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const unitPrice = parseFloat(document.getElementById('unitPrice').value) || 0;
    const measure = document.getElementById('measureDisplay').textContent;

    // Validation
    if (!scrapType || !amount) {
        showError('Please select scrap type and enter amount');
        return;
    }

    if (unitPrice <= 0) {
        showError('Selling price must be greater than 0');
        return;
    }

    if (measure === '--') {
        showError('Please select a valid scrap type');
        return;
    }

    // Check inventory measurement consistency
    if (inventory[scrapType] && inventory[scrapType].measure !== measure) {
        showError(`Measurement mismatch! Inventory is tracked in ${inventory[scrapType].measure}`);
        return;
    }

    // Check inventory availability
    const availableStock = inventory[scrapType]?.amount || 0;
    const alreadyAdded = transactionItems.reduce((sum, item) => {
        return sum + (item.type === scrapType ? item.amount : 0);
    }, 0);

    if (availableStock < (amount + alreadyAdded)) {
        showError(`Cannot add ${amount} ${measure} of ${scrapType}. Available: ${(availableStock - alreadyAdded).toFixed(2)} ${measure}`);
        return;
    }

    // Add to transaction
    const item = {
        id: transactionCounter++,
        type: scrapType,
        measure: measure,
        amount: amount,
        unitPrice: unitPrice,
        buyPrice: parseFloat(document.querySelector(`#scrapTypes option[value="${scrapType}"]`).dataset.buyprice)
    };

    transactionItems.push(item);
    updateTransactionDisplay();
    resetAddItemForm();
});

// Submit complete transaction
document.getElementById('submitTransaction').addEventListener('click', async function() {
    if (transactionItems.length === 0) {
        showError('Please add items to sell first');
        return;
    }

    try {
        const response = await fetch('process_sell.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                items: transactionItems.map(item => ({
                    type: item.type,
                    measure: item.measure,
                    amount: item.amount,
                    unitPrice: item.unitPrice
                })),
                name: document.getElementById('partnerName').value
            })
        });

        const result = await response.json();
        
        if (result.success) {
            alert(`Transaction successful!\n${result.message}\nTotal: ₱${calculateGrandTotal().toFixed(2)}`);
            resetTransaction();
        } else {
            showError(result.error);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Network error. Please try again.');
    }
});

// Reset buttons
document.getElementById('resetBtn').addEventListener('click', resetTransaction);

// Helper functions
function updateTransactionDisplay() {
    const itemsList = document.getElementById('itemsList');
    itemsList.innerHTML = '';

    transactionItems.forEach(item => {
        const margin = item.unitPrice - item.buyPrice;
        const marginPercent = ((margin / item.buyPrice) * 100).toFixed(1);
        const subtotal = item.amount * item.unitPrice;
        const marginClass = margin >= 0 ? 'positive-margin' : 'negative-margin';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                ${item.type} 
                <span class="item-measure">(${item.measure})</span>
            </td>
            <td>${item.amount.toFixed(2)} <span class="measure-badge">${item.measure}</span></td>
            <td>₱${item.unitPrice.toFixed(2)} <span class="measure-badge">per ${item.measure}</span></td>
            <td>₱${subtotal.toFixed(2)}</td>
            <td class="${marginClass}">
                ${margin >= 0 ? '+' : ''}₱${margin.toFixed(2)} (${marginPercent}%)
            </td>
            <td>
                <button class="remove-btn" data-id="${item.id}">Remove</button>
            </td>
        `;
        itemsList.appendChild(row);

        // Add remove functionality
        row.querySelector('.remove-btn').addEventListener('click', function() {
            transactionItems = transactionItems.filter(i => i.id !== item.id);
            updateTransactionDisplay();
        });
    });

    // Update totals
    document.getElementById('grandTotal').textContent = `₱${calculateGrandTotal().toFixed(2)}`;
    document.getElementById('totalMargin').textContent = `₱${calculateTotalMargin().toFixed(2)}`;
}

function calculateGrandTotal() {
    return transactionItems.reduce((total, item) => {
        return total + (item.amount * item.unitPrice);
    }, 0);
}

function calculateTotalMargin() {
    return transactionItems.reduce((total, item) => {
        return total + (item.amount * (item.unitPrice - item.buyPrice));
    }, 0);
}

function resetAddItemForm() {
    document.getElementById('scrapType').value = '';
    document.getElementById('amount').value = '';
    document.getElementById('unitPrice').value = '';
    document.getElementById('measureDisplay').textContent = '--';
}

function resetTransaction() {
    transactionItems = [];
    updateTransactionDisplay();
    resetAddItemForm();
    document.getElementById('partnerName').value = '';
    document.getElementById('warning').textContent = '';
}

function showError(message) {
    document.getElementById('warning').textContent = message;
}