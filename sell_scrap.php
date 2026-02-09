<?php
include('includes/db.php');
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Fetch available scrap types with their latest prices and measures
$prices = [];
$result = $conn->query("SELECT type, measure, price FROM scrap_types");
while ($row = $result->fetch_assoc()) {
    $prices[$row['type']] = [
        'measure' => $row['measure'],
        'unit_price' => $row['price']
    ];
}

// Fetch current inventory with measurements
$inventory = [];
$sql = "
    SELECT 
        i.type,
        i.measure,
        SUM(CASE WHEN t.transaction_type = 'buy' THEN i.amount ELSE -i.amount END) AS total_amount
    FROM transaction_items i
    JOIN transactions t ON i.transaction_id = t.transaction_id
    GROUP BY i.type, i.measure
    HAVING total_amount > 0
";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $inventory[$row['type']] = [
        'amount' => $row['total_amount'],
        'measure' => $row['measure']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Sell Scrap Transaction</title>
    <link rel="stylesheet" href="css/sell_scrap.css" />
</head>
<body>
    <header>
        <?php include('includes/navbar.php'); ?>
    </header>

    <div class="main-container">
        <div class="card">
            <div class="card-body">
                <h2 class="title">Sell Scrap Transaction</h2>

                <div id="partnerNameContainer">
                    <label for="partnerName" id="partnerNameLabel">Partner Name (Optional):</label>
                    <input type="text" id="partnerName" placeholder="Enter name if any...">
                </div>
                <div id="transactionDateContainer">
                <label for="transactionDate" id="transactionDateLabel">
                    Transaction Date & Time:
                </label>
                <input type="datetime-local" id="transactionDate" value="">
                </div>

                <p class="subtitle">Add Multiple Items to Sell</p>
                <p class="description">Add all scrap items you want to sell in this transaction below.</p>

                <!-- Current Items Table -->
                <div class="table-wrapper" id="currentItemsTable">
                    <!-- Add New Item Form -->
                    <div class="add-item-form">
                        <div class="input-row">
                            <input list="scrapTypes" id="scrapType" placeholder="Scrap Type" required>
                            <datalist id="scrapTypes">
                                <?php foreach ($prices as $type => $info): ?>
                                    <option value="<?= htmlspecialchars($type) ?>" 
                                            data-measure="<?= htmlspecialchars($info['measure']) ?>"
                                            data-buyprice="<?= htmlspecialchars($info['unit_price']) ?>">
                                    </option>
                                <?php endforeach; ?>
                            </datalist>

                            <input type="number" id="amount" placeholder="Amount" step="0.01" min="0" required>
                            <span id="measureDisplay" class="measure-display">--</span>
                            <input type="number" id="unitPrice" placeholder="Selling Price" step="0.01" min="0">
                            <button type="button" id="addItemBtn" class="submit-btn">Add Item</button>
                        </div>
                        <div id="warning" class="warning-message"></div>
                    </div>
                    <div class="table-wrapper">
                        <table id="transactionTable">
                            <thead>
                                <tr>
                                    <th>Scrap Type</th>
                                    <th>Amount</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                    <th>Margin</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsList"></tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Total:</th>
                                    <th id="grandTotal">₱0.00</th>
                                    <th id="totalMargin">₱0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <!-- Submit Button -->
                <div class="action-buttons">
                    <button type="button" id="submitTransaction" class="submit-btn">Complete Sale</button>
                    <button type="button" id="resetBtn" class="reset-btn">Reset All</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const prices = <?= json_encode($prices) ?>;
        const inventory = <?= json_encode($inventory) ?>;
        let transactionItems = [];
        let transactionCounter = 0;

        document.getElementById('scrapType').addEventListener('change', function () {
            const scrapType = this.value;
            const info = prices[scrapType];

            if (info) {
                const measure = info.measure;
                const buyPrice = parseFloat(info.unit_price);

                // Update the measure display
                document.getElementById('measureDisplay').textContent = measure;

                // Set the default selling price based on the buy price
                const defaultSellPrice = buyPrice * 1.2;
                document.getElementById('unitPrice').value = defaultSellPrice.toFixed(2);

                // Clear any previous warning messages
                document.getElementById('warning').textContent = '';
            } else {
                // Reset the measure display if no valid scrap type is selected
                document.getElementById('measureDisplay').textContent = '--';
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
                        name: document.getElementById('partnerName').value,
                        manual_transaction_date: document.getElementById('transactionDate').value
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
    </script>
<?php include 'loader.html'; ?>
</body>
</html>








