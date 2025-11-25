<?php
require_once 'header.php';
require_once 'rbac.php';

// Check if user has permission to create documents
// Allow if user has any document creation permission
$hasPermission = false;
if (isset($_SESSION['user_id'])) {
    $hasPermission = userHasPermission($_SESSION['user_id'], 'create-invoice') ||
                     userHasPermission($_SESSION['user_id'], 'create-quotation') ||
                     userHasPermission($_SESSION['user_id'], 'create-receipt');
}

if (!$hasPermission) {
    $_SESSION['error_message'] = "Access denied. You don't have permission to generate documents.";
    header("Location: index.php");
    exit;
}
?>
<title>Global Document Suite - FEZA LOGISTICS</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; }
    @media print {
        .no-print { display: none !important; }
        body { padding-top: 0 !important; }
        .print-container { box-shadow: none !important; max-width: 100% !important; }
    }
    .brand-navy { color: #1e3a8a; }
    .bg-brand-navy { background-color: #1e3a8a; }
    .brand-amber { color: #f59e0b; }
    .bg-brand-amber { background-color: #f59e0b; }
    .table-row:hover { background-color: #f9fafb; }
    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
</style>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'brand-navy': '#1e3a8a',
                    'brand-amber': '#f59e0b',
                }
            }
        }
    }
</script>

<div class="max-w-6xl mx-auto p-6 no-print">
    <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Global Document Suite</h1>
        <p class="text-gray-600">Create professional documents with FEZA LOGISTICS branding</p>
    </div>

    <!-- Controls Panel -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6 no-print">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Document Type</label>
                <select id="docType" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="invoice">Tax Invoice</option>
                    <option value="proforma">Proforma Invoice</option>
                    <option value="quote">Quotation</option>
                    <option value="receipt">Receipt</option>
                    <option value="waybill">Waybill / Delivery Note</option>
                    <option value="packing_list">Packing List</option>
                    <option value="manifest">Cargo Manifest</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Document Number</label>
                <input type="text" id="docNumber" value="DOC-2025-001" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
                <input type="date" id="docDate" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
        </div>

        <!-- Customer Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Customer Name</label>
                <input type="text" id="customerName" value="Sample Customer" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Customer Address</label>
                <input type="text" id="customerAddress" value="Kigali, Rwanda" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
        </div>

        <!-- Logistics Data Section (for Waybill, Packing List, Manifest) -->
        <div id="logisticsSection" class="hidden border-t pt-4 mt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Logistics Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Origin</label>
                    <input type="text" id="origin" placeholder="Origin location" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Destination</label>
                    <input type="text" id="destination" placeholder="Destination location" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Driver</label>
                    <input type="text" id="driver" placeholder="Driver name" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-4">
            <button onclick="addRow()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                Add Item
            </button>
            <button onclick="window.print()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                Print / Save PDF
            </button>
            <button onclick="clearDocument()" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition">
                Clear All
            </button>
        </div>
    </div>
</div>

<!-- Print Container -->
<div id="printContainer" class="print-container max-w-4xl mx-auto bg-white shadow-2xl p-12">
    <!-- Header -->
    <div class="flex justify-between items-start mb-8 pb-6 border-b-2 border-brand-navy">
        <div class="flex-1">
            <h1 class="text-4xl font-bold brand-navy mb-2">FEZA LOGISTICS</h1>
            <p class="text-gray-600 text-sm">Professional Logistics Services</p>
            <p class="text-gray-600 text-sm mt-2">Kigali, Rwanda</p>
            <p class="text-gray-600 text-sm">Email: info@fezalogistics.com</p>
        </div>
        <div class="text-right">
            <!-- Logo URL as specified in requirements. Consider hosting locally for better security. -->
            <img src="https://www.fezalogistics.com/wp-content/uploads/2025/06/SQUARE-SIZEXX-FEZA-LOGO.png" 
                 alt="FEZA LOGISTICS Logo" 
                 class="w-24 h-24 object-contain mb-2"
                 onerror="this.style.display='none'"
                 crossorigin="anonymous">
        </div>
    </div>

    <!-- Document Title and Info -->
    <div class="mb-8">
        <h2 id="docTitle" class="text-3xl font-bold brand-navy mb-4">TAX INVOICE</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600">Document #: <span id="displayDocNumber" class="font-semibold text-gray-900">DOC-2025-001</span></p>
                <p class="text-sm text-gray-600">Date: <span id="displayDocDate" class="font-semibold text-gray-900"></span></p>
                <p id="dueDateDisplay" class="text-sm text-gray-600 hidden">Due Date: <span id="displayDueDate" class="font-semibold text-gray-900"></span></p>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-700 mb-1">Bill To:</p>
                <p id="displayCustomerName" class="text-gray-900 font-medium">Sample Customer</p>
                <p id="displayCustomerAddress" class="text-gray-600 text-sm">Kigali, Rwanda</p>
            </div>
        </div>
    </div>

    <!-- Logistics Data Display (for Waybill, Packing List, Manifest) -->
    <div id="logisticsDataDisplay" class="hidden mb-6 bg-blue-50 p-4 rounded-lg">
        <h3 class="text-sm font-bold text-gray-800 mb-2">LOGISTICS DATA</h3>
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
                <span class="text-gray-600">Origin:</span>
                <span id="displayOrigin" class="font-semibold text-gray-900 ml-2">—</span>
            </div>
            <div>
                <span class="text-gray-600">Destination:</span>
                <span id="displayDestination" class="font-semibold text-gray-900 ml-2">—</span>
            </div>
            <div>
                <span class="text-gray-600">Driver:</span>
                <span id="displayDriver" class="font-semibold text-gray-900 ml-2">—</span>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="mb-8">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-brand-navy text-white">
                    <th class="text-left py-3 px-4 text-sm font-semibold">Description</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold">Qty</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold price-col">Unit Price</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold price-col">Amount</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold weight-col hidden">Weight</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold obs-col hidden">Observations</th>
                </tr>
            </thead>
            <tbody id="itemsTable">
                <tr class="table-row border-b border-gray-200">
                    <td class="py-3 px-4">
                        <input type="text" value="Sample Item 1" class="item-desc w-full border-0 focus:outline-none bg-transparent" />
                    </td>
                    <td class="py-3 px-4 text-center">
                        <input type="number" value="1" class="item-qty w-20 text-center border-0 focus:outline-none bg-transparent" />
                    </td>
                    <td class="py-3 px-4 text-center price-col">
                        <input type="number" value="100.00" step="0.01" class="item-price w-24 text-center border-0 focus:outline-none bg-transparent" />
                    </td>
                    <td class="py-3 px-4 text-right price-col">
                        <span class="item-amount">100.00</span>
                    </td>
                    <td class="py-3 px-4 text-center weight-col hidden">
                        <input type="text" placeholder="0 kg" class="item-weight w-20 text-center border-0 focus:outline-none bg-transparent" />
                    </td>
                    <td class="py-3 px-4 obs-col hidden">
                        <input type="text" placeholder="Notes" class="item-obs w-full border-0 focus:outline-none bg-transparent" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Financial Summary (hidden for logistics documents) -->
    <div id="financialSection" class="flex justify-end mb-8">
        <div class="w-64">
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span class="text-gray-600">Subtotal:</span>
                <span id="subtotal" class="font-semibold">0.00</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span class="text-gray-600">Tax (<span id="taxRateDisplay">18</span>%):</span>
                <span id="taxAmount" class="font-semibold">0.00</span>
            </div>
            <div class="flex justify-between py-3 border-t-2 border-brand-navy">
                <span class="text-lg font-bold brand-navy">Total:</span>
                <span id="total" class="text-lg font-bold brand-navy">0.00</span>
            </div>
        </div>
    </div>

    <!-- Receipt Status (only for receipts) -->
    <div id="receiptStatus" class="hidden mb-8 text-center">
        <div class="inline-block bg-green-100 border-2 border-green-600 rounded-lg px-8 py-4">
            <p class="text-3xl font-bold text-green-600">PAID</p>
            <p class="text-sm text-gray-600 mt-2">Amount: <span id="receiptAmount" class="font-bold">0.00</span></p>
        </div>
    </div>

    <!-- Notes -->
    <div class="border-t-2 border-gray-200 pt-6">
        <p class="text-sm font-semibold text-gray-700 mb-2">Notes:</p>
        <textarea id="notes" class="w-full border border-gray-300 rounded-lg p-3 text-sm text-gray-700 min-h-[80px] no-print"></textarea>
        <p id="displayNotes" class="text-sm text-gray-700 whitespace-pre-wrap hidden"></p>
    </div>

    <!-- Footer -->
    <div class="mt-12 pt-6 border-t border-gray-300 text-center">
        <p class="text-sm text-gray-600">Thank you for your business!</p>
        <p class="text-xs text-gray-500 mt-2">FEZA LOGISTICS • Kigali, Rwanda • info@fezalogistics.com</p>
    </div>
</div>

<script>
// Configuration constants
const DEFAULT_TAX_RATE = 18; // Default tax rate percentage

// Configuration for different document types
const config = {
    invoice: {
        title: 'TAX INVOICE',
        showPrices: true,
        showFinancials: true,
        showDueDate: true,
        showReceipt: false,
        showLogistics: false,
        showWeight: false,
        showObs: false
    },
    proforma: {
        title: 'PROFORMA INVOICE',
        showPrices: true,
        showFinancials: true,
        showDueDate: true,
        showReceipt: false,
        showLogistics: false,
        showWeight: false,
        showObs: false
    },
    quote: {
        title: 'QUOTATION',
        showPrices: true,
        showFinancials: true,
        showDueDate: false,
        showReceipt: false,
        showLogistics: false,
        showWeight: false,
        showObs: false
    },
    receipt: {
        title: 'RECEIPT',
        showPrices: false,
        showFinancials: false,
        showDueDate: false,
        showReceipt: true,
        showLogistics: false,
        showWeight: false,
        showObs: false
    },
    waybill: {
        title: 'WAYBILL / DELIVERY NOTE',
        showPrices: false,
        showFinancials: false,
        showDueDate: false,
        showReceipt: false,
        showLogistics: true,
        showWeight: true,
        showObs: true
    },
    packing_list: {
        title: 'PACKING LIST',
        showPrices: false,
        showFinancials: false,
        showDueDate: false,
        showReceipt: false,
        showLogistics: true,
        showWeight: true,
        showObs: false
    },
    manifest: {
        title: 'CARGO MANIFEST',
        showPrices: false,
        showFinancials: false,
        showDueDate: false,
        showReceipt: false,
        showLogistics: true,
        showWeight: true,
        showObs: true
    }
};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('docDate').value = today;
    updateDisplay();
    
    // Event listeners
    document.getElementById('docType').addEventListener('change', handleDocTypeChange);
    document.getElementById('docNumber').addEventListener('input', updateDisplay);
    document.getElementById('docDate').addEventListener('input', updateDisplay);
    document.getElementById('customerName').addEventListener('input', updateDisplay);
    document.getElementById('customerAddress').addEventListener('input', updateDisplay);
    document.getElementById('notes').addEventListener('input', updateDisplay);
    
    // Logistics fields
    document.getElementById('origin').addEventListener('input', updateDisplay);
    document.getElementById('destination').addEventListener('input', updateDisplay);
    document.getElementById('driver').addEventListener('input', updateDisplay);
    
    // Auto-calculate on input changes
    document.getElementById('itemsTable').addEventListener('input', calculateTotals);
    
    handleDocTypeChange();
});

function handleDocTypeChange() {
    const docType = document.getElementById('docType').value;
    const cfg = config[docType];
    
    // Update title
    document.getElementById('docTitle').textContent = cfg.title;
    
    // Show/hide price columns
    document.querySelectorAll('.price-col').forEach(el => {
        el.classList.toggle('hidden', !cfg.showPrices);
    });
    
    // Show/hide weight columns
    document.querySelectorAll('.weight-col').forEach(el => {
        el.classList.toggle('hidden', !cfg.showWeight);
    });
    
    // Show/hide observations columns
    document.querySelectorAll('.obs-col').forEach(el => {
        el.classList.toggle('hidden', !cfg.showObs);
    });
    
    // Show/hide financial section
    document.getElementById('financialSection').classList.toggle('hidden', !cfg.showFinancials);
    
    // Show/hide due date
    document.getElementById('dueDateDisplay').classList.toggle('hidden', !cfg.showDueDate);
    
    // Show/hide receipt status
    document.getElementById('receiptStatus').classList.toggle('hidden', !cfg.showReceipt);
    
    // Show/hide logistics section in controls
    document.getElementById('logisticsSection').classList.toggle('hidden', !cfg.showLogistics);
    
    // Show/hide logistics data in display
    document.getElementById('logisticsDataDisplay').classList.toggle('hidden', !cfg.showLogistics);
    
    updateDisplay();
    calculateTotals();
}

function updateDisplay() {
    document.getElementById('displayDocNumber').textContent = document.getElementById('docNumber').value;
    document.getElementById('displayDocDate').textContent = document.getElementById('docDate').value;
    document.getElementById('displayCustomerName').textContent = document.getElementById('customerName').value;
    document.getElementById('displayCustomerAddress').textContent = document.getElementById('customerAddress').value;
    document.getElementById('displayNotes').textContent = document.getElementById('notes').value;
    
    // Update logistics data
    document.getElementById('displayOrigin').textContent = document.getElementById('origin').value || '—';
    document.getElementById('displayDestination').textContent = document.getElementById('destination').value || '—';
    document.getElementById('displayDriver').textContent = document.getElementById('driver').value || '—';
}

function addRow() {
    const docType = document.getElementById('docType').value;
    const cfg = config[docType];
    
    const table = document.getElementById('itemsTable');
    const row = document.createElement('tr');
    row.className = 'table-row border-b border-gray-200';
    
    row.innerHTML = `
        <td class="py-3 px-4">
            <input type="text" placeholder="Item description" class="item-desc w-full border-0 focus:outline-none bg-transparent" />
        </td>
        <td class="py-3 px-4 text-center">
            <input type="number" value="1" class="item-qty w-20 text-center border-0 focus:outline-none bg-transparent" />
        </td>
        <td class="py-3 px-4 text-center price-col ${cfg.showPrices ? '' : 'hidden'}">
            <input type="number" value="0.00" step="0.01" class="item-price w-24 text-center border-0 focus:outline-none bg-transparent" />
        </td>
        <td class="py-3 px-4 text-right price-col ${cfg.showPrices ? '' : 'hidden'}">
            <span class="item-amount">0.00</span>
        </td>
        <td class="py-3 px-4 text-center weight-col ${cfg.showWeight ? '' : 'hidden'}">
            <input type="text" placeholder="0 kg" class="item-weight w-20 text-center border-0 focus:outline-none bg-transparent" />
        </td>
        <td class="py-3 px-4 obs-col ${cfg.showObs ? '' : 'hidden'}">
            <input type="text" placeholder="Notes" class="item-obs w-full border-0 focus:outline-none bg-transparent" />
        </td>
    `;
    
    table.appendChild(row);
    calculateTotals();
}

function calculateTotals() {
    const rows = document.querySelectorAll('#itemsTable tr');
    let subtotal = 0;
    
    rows.forEach(row => {
        const qtyInput = row.querySelector('.item-qty');
        const priceInput = row.querySelector('.item-price');
        
        // Parse and validate numeric values with explicit checks
        let qty = 0;
        let price = 0;
        
        if (qtyInput && qtyInput.value) {
            const parsedQty = parseFloat(qtyInput.value.trim());
            qty = !isNaN(parsedQty) && isFinite(parsedQty) ? parsedQty : 0;
        }
        
        if (priceInput && priceInput.value) {
            const parsedPrice = parseFloat(priceInput.value.trim());
            price = !isNaN(parsedPrice) && isFinite(parsedPrice) ? parsedPrice : 0;
        }
        
        const amount = qty * price;
        
        const amountSpan = row.querySelector('.item-amount');
        if (amountSpan) {
            amountSpan.textContent = amount.toFixed(2);
        }
        
        subtotal += amount;
    });
    
    const taxAmount = subtotal * (DEFAULT_TAX_RATE / 100);
    const total = subtotal + taxAmount;
    
    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('taxAmount').textContent = taxAmount.toFixed(2);
    document.getElementById('total').textContent = total.toFixed(2);
    document.getElementById('receiptAmount').textContent = total.toFixed(2);
}

function clearDocument() {
    if (confirm('Are you sure you want to clear all data?')) {
        document.getElementById('itemsTable').innerHTML = `
            <tr class="table-row border-b border-gray-200">
                <td class="py-3 px-4">
                    <input type="text" value="Sample Item 1" class="item-desc w-full border-0 focus:outline-none bg-transparent" />
                </td>
                <td class="py-3 px-4 text-center">
                    <input type="number" value="1" class="item-qty w-20 text-center border-0 focus:outline-none bg-transparent" />
                </td>
                <td class="py-3 px-4 text-center price-col">
                    <input type="number" value="100.00" step="0.01" class="item-price w-24 text-center border-0 focus:outline-none bg-transparent" />
                </td>
                <td class="py-3 px-4 text-right price-col">
                    <span class="item-amount">100.00</span>
                </td>
                <td class="py-3 px-4 text-center weight-col hidden">
                    <input type="text" placeholder="0 kg" class="item-weight w-20 text-center border-0 focus:outline-none bg-transparent" />
                </td>
                <td class="py-3 px-4 obs-col hidden">
                    <input type="text" placeholder="Notes" class="item-obs w-full border-0 focus:outline-none bg-transparent" />
                </td>
            </tr>
        `;
        document.getElementById('notes').value = '';
        document.getElementById('origin').value = '';
        document.getElementById('destination').value = '';
        document.getElementById('driver').value = '';
        handleDocTypeChange();
    }
}

// Print preparation
window.addEventListener('beforeprint', function() {
    document.getElementById('displayNotes').textContent = document.getElementById('notes').value;
    document.getElementById('displayNotes').classList.remove('hidden');
    document.getElementById('notes').classList.add('hidden');
    
    // Style only inputs within the print container for printing
    document.querySelectorAll('#printContainer input').forEach(input => {
        input.style.border = 'none';
        input.style.background = 'transparent';
    });
});

window.addEventListener('afterprint', function() {
    document.getElementById('displayNotes').classList.add('hidden');
    document.getElementById('notes').classList.remove('hidden');
});
</script>
