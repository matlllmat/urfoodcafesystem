// ==================== STATE ====================
let allSales = [];
let cashiersLoaded = false;
let voidTargetSaleId = null;
let currentReceiptSaleId = null;
let searchTimeout = null;
let currentSalesPage = 1;
const salesPerPage = 20;

// ==================== INIT ====================
document.addEventListener('DOMContentLoaded', function () {
    loadSalesHistory();

    // Search with debounce
    const searchInput = document.getElementById('shSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadSalesHistory(1), 300);
        });
    }
});

// ==================== LOAD DATA ====================
function loadSalesHistory(page) {
    if (page != null) currentSalesPage = page;
    const status = document.getElementById('shStatusFilter').value;
    const cashier = document.getElementById('shCashierFilter').value;
    const dateFrom = document.getElementById('shDateFrom').value;
    const dateTo = document.getElementById('shDateTo').value;
    const search = document.getElementById('shSearchInput').value.trim();
    const sort = document.getElementById('shSortFilter').value;

    const params = new URLSearchParams();
    if (status) params.set('status', status);
    if (cashier) params.set('cashier', cashier);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);
    if (search) params.set('search', search);
    if (sort) params.set('sort', sort);
    params.set('page', currentSalesPage);
    params.set('per_page', salesPerPage);

    fetch(`../api/sh-get-sales.php?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                allSales = data.sales;
                updateStats(data.stats);
                renderSalesTable(data.sales);
                if (data.pagination) {
                    renderSalesPagination(data.pagination);
                } else {
                    document.getElementById('salesPagination').classList.add('hidden');
                }

                // Populate cashier dropdown once
                if (!cashiersLoaded && data.cashiers) {
                    populateCashierFilter(data.cashiers);
                    cashiersLoaded = true;
                }
            } else {
                showErrorModal(data.message || 'Failed to load sales history');
            }
        })
        .catch(err => {
            console.error('Load sales error:', err);
            document.getElementById('salesTableBody').innerHTML =
                '<div class="px-6 py-12 text-center"><p class="text-regular text-red-500">Failed to load sales history</p></div>';
            document.getElementById('salesPagination').classList.add('hidden');
        });
}

function renderSalesPagination(p) {
    const container = document.getElementById('salesPagination');
    const infoEl = document.getElementById('salesPaginationInfo');
    const controlsEl = document.getElementById('salesPaginationControls');
    if (p.total_count === 0) {
        container.classList.add('hidden');
        return;
    }
    container.classList.remove('hidden');
    const from = (p.page - 1) * p.per_page + 1;
    const to = Math.min(p.page * p.per_page, p.total_count);
    infoEl.textContent = `Showing ${from}–${to} of ${p.total_count}`;

    let controlsHtml = '';
    if (p.page > 1) {
        controlsHtml += `<button type="button" onclick="loadSalesHistory(${p.page - 1})" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-100">Previous</button>`;
    }
    const startPage = Math.max(1, p.page - 2);
    const endPage = Math.min(p.total_pages, p.page + 2);
    for (let i = startPage; i <= endPage; i++) {
        const active = i === p.page ? 'bg-black text-white border-black' : 'border-gray-300 text-gray-700 hover:bg-gray-100';
        controlsHtml += `<button type="button" onclick="loadSalesHistory(${i})" class="px-3 py-1.5 border rounded-md text-sm ${active}">${i}</button>`;
    }
    if (p.page < p.total_pages) {
        controlsHtml += `<button type="button" onclick="loadSalesHistory(${p.page + 1})" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-100">Next</button>`;
    }
    controlsEl.innerHTML = controlsHtml;
}

// ==================== STATS ====================
function updateStats(stats) {
    // Today's sales
    document.getElementById('statTodaySales').textContent = stats.today_sales;
    document.getElementById('statTodayRevenue').textContent =
        '\u20B1 ' + parseFloat(stats.today_revenue).toLocaleString('en-PH', { minimumFractionDigits: 2 }) + ' today';

    // Total revenue (completed only)
    document.getElementById('statTotalRevenue').textContent =
        '\u20B1 ' + parseFloat(stats.total_revenue).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('statCompletedCount').textContent =
        stats.completed_count + ' completed sale' + (parseInt(stats.completed_count) !== 1 ? 's' : '');

    // Total profit
    const profit = parseFloat(stats.total_profit);
    const profitEl = document.getElementById('statTotalProfit');
    profitEl.textContent = '\u20B1 ' + profit.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    profitEl.classList.remove('text-success', 'text-danger');
    profitEl.classList.add(profit >= 0 ? 'text-success' : 'text-danger');
    document.getElementById('statAvgOrder').textContent =
        'Avg order: \u20B1 ' + parseFloat(stats.avg_order_value).toLocaleString('en-PH', { minimumFractionDigits: 2 });

    // Voided count
    document.getElementById('statVoidedCount').textContent = stats.voided_count;
}

// ==================== RENDER TABLE ====================
function renderSalesTable(sales) {
    const container = document.getElementById('salesTableBody');

    if (!sales || sales.length === 0) {
        container.innerHTML = `
            <div class="px-6 py-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <p class="text-regular text-gray-500">No sales found</p>
                <p class="text-label text-gray-400 mt-1">Try adjusting your filters or date range</p>
            </div>
        `;
        return;
    }

    let html = '';
    sales.forEach(sale => {
        const isVoided = sale.status === 'voided';
        const rowOpacity = isVoided ? 'opacity-60' : '';

        // Format date
        const dateObj = new Date(sale.sale_date + 'T' + sale.sale_time);
        const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const timeStr = dateObj.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

        // Status badge
        const statusBadge = isVoided
            ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-danger">Voided</span>'
            : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-success">Completed</span>';

        // Profit color
        const profit = parseFloat(sale.profit);
        const profitColor = profit >= 0 ? 'text-success' : 'text-danger';

        const saleIdEsc = escapeHtml(sale.sale_id);

        // Desktop row (clickable to open receipt)
        html += `
            <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors items-center ${rowOpacity}"
                 onclick="openShReceiptModal('${saleIdEsc}')">
                <div class="col-span-2">
                    <span class="text-product font-medium text-gray-800">${saleIdEsc}</span>
                </div>
                <div class="col-span-3">
                    <p class="text-regular text-gray-800">${dateStr}</p>
                    <p class="text-label text-gray-400">${timeStr}</p>
                </div>
                <div class="col-span-2">
                    <span class="text-regular text-gray-700">${escapeHtml(sale.cashier_name)}</span>
                </div>
                <div class="col-span-1 text-center">
                    <span class="text-regular text-gray-700">${sale.item_count}</span>
                </div>
                <div class="col-span-1 text-right">
                    <span class="text-product font-medium text-gray-800">\u20B1 ${parseFloat(sale.total_price).toFixed(2)}</span>
                </div>
                <div class="col-span-1 text-right">
                    <span class="text-regular ${profitColor}">\u20B1 ${profit.toFixed(2)}</span>
                </div>
                <div class="col-span-2 text-center">
                    ${statusBadge}
                </div>
            </div>

            <!-- Mobile card (tap to open receipt) -->
            <div class="md:hidden border-b border-gray-100 px-4 py-4 cursor-pointer hover:bg-gray-50 transition-colors ${rowOpacity}"
                 onclick="openShReceiptModal('${saleIdEsc}')">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-product font-medium text-gray-800">${saleIdEsc}</span>
                    ${statusBadge}
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-label text-gray-400">Date</span>
                        <p class="text-regular text-gray-700">${dateStr} ${timeStr}</p>
                    </div>
                    <div>
                        <span class="text-label text-gray-400">Cashier</span>
                        <p class="text-regular text-gray-700">${escapeHtml(sale.cashier_name)}</p>
                    </div>
                    <div>
                        <span class="text-label text-gray-400">Total</span>
                        <p class="text-product font-medium text-gray-800">\u20B1 ${parseFloat(sale.total_price).toFixed(2)}</p>
                    </div>
                    <div>
                        <span class="text-label text-gray-400">Profit</span>
                        <p class="text-regular ${profitColor}">\u20B1 ${profit.toFixed(2)}</p>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// ==================== CASHIER FILTER ====================
function populateCashierFilter(cashiers) {
    const select = document.getElementById('shCashierFilter');
    const currentValue = select.value;

    // Keep the default option, add cashiers
    cashiers.forEach(c => {
        const option = document.createElement('option');
        option.value = c.staff_id;
        option.textContent = c.user_name;
        select.appendChild(option);
    });

    // Restore selection if any
    if (currentValue) {
        select.value = currentValue;
    }
}

// ==================== DATE FILTER ====================
function clearDateFilters() {
    document.getElementById('shDateFrom').value = '';
    document.getElementById('shDateTo').value = '';
    loadSalesHistory();
}

// ==================== RECEIPT MODAL ====================
function openShReceiptModal(saleId) {
    const modal = document.getElementById('shReceiptModal');
    const container = document.getElementById('shReceiptContent');
    const voidLinkContainer = document.getElementById('shVoidLinkContainer');

    currentReceiptSaleId = saleId;
    container.innerHTML = '<p class="text-center text-gray-400 py-8 text-regular">Loading receipt...</p>';
    voidLinkContainer.classList.add('hidden');
    modal.classList.remove('hidden');

    fetch(`../api/cs-get-receipt.php?sale_id=${encodeURIComponent(saleId)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderShReceipt(data.sale, data.details);

                // Show void link only for completed sales
                if (data.sale.status !== 'voided') {
                    voidLinkContainer.classList.remove('hidden');
                } else {
                    voidLinkContainer.classList.add('hidden');
                }
            } else {
                container.innerHTML = '<p class="text-center text-red-500 py-4 text-regular">Failed to load receipt</p>';
            }
        })
        .catch(() => {
            container.innerHTML = '<p class="text-center text-red-500 py-4 text-regular">Error loading receipt</p>';
        });
}

function closeShReceiptModal() {
    document.getElementById('shReceiptModal').classList.add('hidden');
    currentReceiptSaleId = null;
}

function openVoidFromReceipt() {
    if (!currentReceiptSaleId) return;
    const saleId = currentReceiptSaleId;
    closeShReceiptModal();
    openVoidModal(saleId);
}

function renderShReceipt(sale, details) {
    const container = document.getElementById('shReceiptContent');
    const isVoided = sale.status === 'voided';

    // Format date
    const saleDate = new Date(sale.sale_date);
    const dateStr = saleDate.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' });

    // Format time
    const timeParts = sale.sale_time.split(':');
    const hours = parseInt(timeParts[0]);
    const minutes = timeParts[1];
    const seconds = timeParts[2];
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;
    const timeStr = `${displayHours}:${minutes}:${seconds} ${ampm}`;

    // Check if any items are manually modified
    const hasModifiedItems = details.some(d => parseInt(d.is_manual) === 1);

    // Build details rows
    let detailsHtml = '';
    details.forEach((d, idx) => {
        const isManual = parseInt(d.is_manual) === 1;
        const modifiedBadge = isManual ? ' <span class="text-xs" style="color: #d97706; font-style: italic;">*</span>' : '';
        const hasIngredients = d.ingredients && d.ingredients.length > 0;

        detailsHtml += `
            <div class="grid grid-cols-4 gap-1 text-sm py-1${hasIngredients ? ' cursor-pointer hover:bg-gray-50' : ''}" ${hasIngredients ? `onclick="toggleReceiptIngredients(${idx})"` : ''}>
                <span class="text-center">${d.quantity}</span>
                <span class="truncate flex items-center gap-1" title="${escapeHtml(d.product_name)}">
                    ${escapeHtml(d.product_code)}${modifiedBadge}
                    ${hasIngredients ? '<svg class="w-3 h-3 text-gray-400 flex-shrink-0 transition-transform" id="ingArrow_' + idx + '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>' : ''}
                </span>
                <span class="text-right">&#8369; ${parseFloat(d.price_per_unit).toFixed(2)}</span>
                <span class="text-right">&#8369; ${parseFloat(d.subtotal).toFixed(2)}</span>
            </div>
        `;

        // Ingredients expandable section (hidden by default)
        if (hasIngredients) {
            let ingRows = '';
            d.ingredients.forEach(ing => {
                const totalUsed = (parseFloat(ing.quantity_used) * parseInt(d.quantity)).toFixed(2);
                ingRows += `
                    <div class="flex justify-between text-xs py-0.5">
                        <span class="text-gray-500">${escapeHtml(ing.item_name)}</span>
                        <span class="text-gray-400">${totalUsed} ${escapeHtml(ing.quantity_unit)}</span>
                    </div>
                `;
            });

            const manualNote = isManual
                ? '<p class="text-xs italic mt-1" style="color: #d97706;">Ingredients may differ (manually modified)</p>'
                : '';

            detailsHtml += `
                <div id="ingSection_${idx}" class="hidden ml-4 mr-1 mb-2 pl-3 border-l-2 border-gray-200">
                    <p class="text-xs font-medium text-gray-500 mb-1">Ingredients Used:</p>
                    ${ingRows}
                    ${manualNote}
                </div>
            `;
        }
    });

    // Modified order notice
    const modifiedNotice = hasModifiedItems
        ? `<div class="text-center mb-3 py-2 rounded text-xs" style="background-color: #fffbeb; border: 1px solid #fde68a; color: #92400e;">
            <span class="font-semibold">Note:</span> Items marked with * have been manually modified
           </div>`
        : '';

    // Void stamp
    const voidStamp = isVoided
        ? `<div class="text-center mb-4 py-3 rounded" style="background-color: #fef2f2; border: 2px solid #B71C1C;">
            <p class="font-bold text-lg" style="color: #B71C1C; letter-spacing: 4px;">VOIDED</p>
           </div>`
        : '';

    container.innerHTML = `
        ${voidStamp}

        <div class="text-center mb-5">
            <img src="../assets/images/darklogo.png" alt="Logo" class="h-16 w-auto mx-auto mb-3" />
            <h3 class="font-semibold text-lg">UR Foodhub + Cafe</h3>
            <p class="text-sm text-gray-500">Sampaguita corner</p>
            <p class="text-sm text-gray-500">Rosas Street, Almar Subdivision</p>
            <p class="text-sm text-gray-500">Tel: 09918040806</p>
        </div>

        <div class="border-t border-gray-300 pt-3 mb-4">
            <div class="flex justify-between text-sm mb-1">
                <span class="font-semibold">SALE NO: ${escapeHtml(sale.sale_id)}</span>
                <span class="text-gray-500">DATE: ${dateStr}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">CASHIER: ${escapeHtml(sale.cashier_name).toUpperCase()}</span>
                <span class="text-gray-500">TIME: ${timeStr}</span>
            </div>
        </div>

        ${modifiedNotice}

        <div class="border-t border-gray-300 pt-3 mb-3">
            <div class="grid grid-cols-4 gap-1 text-xs font-semibold text-gray-500 mb-2 uppercase">
                <span class="text-center">QTY</span>
                <span>P. CODE</span>
                <span class="text-right">PRICE</span>
                <span class="text-right">TOTAL</span>
            </div>
            ${detailsHtml}
        </div>

        <div class="border-t-2 border-gray-800 pt-4 mb-4">
            <div class="flex justify-between font-semibold text-xl">
                <span>GRAND TOTAL</span>
                <span>&#8369; ${parseFloat(sale.total_price).toFixed(2)}</span>
            </div>
        </div>

        <div class="border-t border-gray-300 pt-3 mb-4 space-y-2">
            <div class="flex justify-between text-sm">
                <span>CASH</span>
                <span class="font-semibold text-base">&#8369; ${parseFloat(sale.amount_paid).toFixed(2)}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span>CHANGE</span>
                <span class="font-semibold text-base">&#8369; ${parseFloat(sale.change_amount).toFixed(2)}</span>
            </div>
        </div>

        <div class="border-t border-gray-300 pt-4 text-center">
            <p class="text-sm text-gray-500 mb-3">PAID WITH CASH</p>
            ${isVoided
                ? '<p class="font-bold text-lg" style="color: #B71C1C; letter-spacing: 2px;">SALE VOIDED</p>'
                : '<p class="text-warning font-semibold text-lg">&#10033; THANK YOU &#10033;</p>'}
        </div>
    `;
}

function printShReceipt() {
    const receiptContent = document.getElementById('shReceiptContent').innerHTML;

    const printWindow = window.open('', '_blank', 'width=400,height=600');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt</title>
            <style>
                body { font-family: 'Courier New', monospace; padding: 20px; max-width: 350px; margin: 0 auto; font-size: 14px; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .font-semibold { font-weight: 600; }
                .font-bold { font-weight: 700; }
                .text-lg { font-size: 1.1em; }
                .text-xl { font-size: 1.25em; }
                .text-sm { font-size: 0.9em; }
                .text-xs { font-size: 0.8em; }
                .mb-1 { margin-bottom: 4px; }
                .mb-2 { margin-bottom: 8px; }
                .mb-3 { margin-bottom: 12px; }
                .mb-4 { margin-bottom: 16px; }
                .mb-5 { margin-bottom: 20px; }
                .mt-3 { margin-top: 12px; }
                .pt-3 { padding-top: 12px; }
                .pt-4 { padding-top: 16px; }
                .py-1 { padding-top: 4px; padding-bottom: 4px; }
                .py-2 { padding-top: 8px; padding-bottom: 8px; }
                .py-3 { padding-top: 12px; padding-bottom: 12px; }
                .border-t { border-top: 1px solid #ccc; }
                .border-t-2 { border-top: 2px solid #333; }
                .space-y-2 > * + * { margin-top: 8px; }
                .grid { display: grid; }
                .grid-cols-4 { grid-template-columns: repeat(4, 1fr); }
                .gap-1 { gap: 4px; }
                .flex { display: flex; }
                .justify-between { justify-content: space-between; }
                .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                img { max-height: 60px; margin: 0 auto 10px; display: block; }
                .text-gray-500 { color: #666; }
                .text-warning { color: #d97706; }
                .rounded { border-radius: 4px; }
                @media print { body { padding: 0; } }
            </style>
        </head>
        <body>${receiptContent}</body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}

// ==================== VOID SALE ====================
function openVoidModal(saleId) {
    voidTargetSaleId = saleId;
    document.getElementById('voidSaleIdLabel').textContent = saleId;

    // Default selection: keep as lost (no restore)
    const lostOption = document.getElementById('voidInventoryLost');
    if (lostOption) {
        lostOption.checked = true;
    }

    const btn = document.getElementById('confirmVoidBtn');
    btn.disabled = false;
    btn.textContent = 'Void Sale';

    document.getElementById('shVoidModal').classList.remove('hidden');
}

function closeVoidModal() {
    document.getElementById('shVoidModal').classList.add('hidden');
    voidTargetSaleId = null;
}

function confirmVoidSale() {
    if (!voidTargetSaleId) return;

    const selectedActionEl = document.querySelector('input[name="voidInventoryAction"]:checked');
    const inventory_action = selectedActionEl ? selectedActionEl.value : 'lost';

    const btn = document.getElementById('confirmVoidBtn');
    btn.disabled = true;
    btn.textContent = 'Voiding...';

    fetch('../api/sh-void-sale.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            sale_id: voidTargetSaleId,
            inventory_action
        })
    })
        .then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    try { return JSON.parse(text); }
                    catch { return { success: false, message: `Server error ${res.status}: ${text.substring(0, 200) || 'Empty response'}` }; }
                });
            }
            return res.json();
        })
        .then(data => {
            closeVoidModal();

            if (data.success) {
                showSuccessModal(data.message);
                loadSalesHistory();
            } else {
                showErrorModal(data.message || 'Failed to void sale');
            }
        })
        .catch(err => {
            console.error('Void sale error:', err);
            closeVoidModal();
            showErrorModal('An error occurred while voiding the sale: ' + err.message);
        });
}

// ==================== INGREDIENT TOGGLE ====================
function toggleReceiptIngredients(idx) {
    const section = document.getElementById('ingSection_' + idx);
    const arrow = document.getElementById('ingArrow_' + idx);
    if (!section) return;

    const isHidden = section.classList.contains('hidden');
    section.classList.toggle('hidden');
    if (arrow) {
        arrow.style.transform = isHidden ? 'rotate(180deg)' : '';
    }
}

// ==================== UTILITIES ====================
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
