// ==================== STATE ====================
let allMovements = [];
let itemsLoaded = false;
let staffLoaded = false;
let searchTimeout = null;
let currentTrailPage = 1;
const trailPerPage = 20;

// ==================== INIT ====================
document.addEventListener('DOMContentLoaded', function () {
    loadInventoryTrail();

    // Search with debounce
    const searchInput = document.getElementById('itSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadInventoryTrail(1), 300);
        });
    }
});

// ==================== LOAD DATA ====================
function loadInventoryTrail(page) {
    if (page != null) currentTrailPage = page;
    const type = document.getElementById('itTypeFilter').value;
    const item = document.getElementById('itItemFilter').value;
    const staff = document.getElementById('itStaffFilter').value;
    const dateFrom = document.getElementById('itDateFrom').value;
    const dateTo = document.getElementById('itDateTo').value;
    const search = document.getElementById('itSearchInput').value.trim();
    const sort = document.getElementById('itSortFilter').value;

    const params = new URLSearchParams();
    if (type) params.set('type', type);
    if (item) params.set('item', item);
    if (staff) params.set('staff', staff);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);
    if (search) params.set('search', search);
    if (sort) params.set('sort', sort);
    params.set('page', currentTrailPage);
    params.set('per_page', trailPerPage);

    fetch(`../api/it-get-movements.php?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                allMovements = data.movements;
                updateTrailStats(data.stats);
                renderTrailTable(data.movements);
                if (data.pagination) {
                    renderTrailPagination(data.pagination);
                } else {
                    document.getElementById('trailPagination').classList.add('hidden');
                }

                // Populate filter dropdowns once
                if (!itemsLoaded && data.items) {
                    populateItemFilter(data.items);
                    itemsLoaded = true;
                }
                if (!staffLoaded && data.staff) {
                    populateStaffFilter(data.staff);
                    staffLoaded = true;
                }
            } else {
                showErrorModal(data.message || 'Failed to load inventory trail');
            }
        })
        .catch(err => {
            console.error('Load inventory trail error:', err);
            document.getElementById('trailTableBody').innerHTML =
                '<div class="px-6 py-12 text-center"><p class="text-regular text-red-500">Failed to load inventory trail</p></div>';
            document.getElementById('trailPagination').classList.add('hidden');
        });
}

function renderTrailPagination(p) {
    const container = document.getElementById('trailPagination');
    const infoEl = document.getElementById('trailPaginationInfo');
    const controlsEl = document.getElementById('trailPaginationControls');
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
        controlsHtml += `<button type="button" onclick="loadInventoryTrail(${p.page - 1})" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-100">Previous</button>`;
    }
    const startPage = Math.max(1, p.page - 2);
    const endPage = Math.min(p.total_pages, p.page + 2);
    for (let i = startPage; i <= endPage; i++) {
        const active = i === p.page ? 'bg-black text-white border-black' : 'border-gray-300 text-gray-700 hover:bg-gray-100';
        controlsHtml += `<button type="button" onclick="loadInventoryTrail(${i})" class="px-3 py-1.5 border rounded-md text-sm ${active}">${i}</button>`;
    }
    if (p.page < p.total_pages) {
        controlsHtml += `<button type="button" onclick="loadInventoryTrail(${p.page + 1})" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-100">Next</button>`;
    }
    controlsEl.innerHTML = controlsHtml;
}

// ==================== STATS ====================
function updateTrailStats(stats) {
    // Total movements
    document.getElementById('statTotalMovements').textContent = stats.total_movements;
    document.getElementById('statTodayMovements').textContent =
        stats.today_movements + ' movement' + (parseInt(stats.today_movements) !== 1 ? 's' : '') + ' today';

    // Total inbound
    document.getElementById('statTotalInbound').textContent =
        '\u20B1 ' + parseFloat(stats.total_inbound_value).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('statInboundCount').textContent =
        stats.inbound_count + ' inbound movement' + (parseInt(stats.inbound_count) !== 1 ? 's' : '');

    // Total outbound
    document.getElementById('statTotalOutbound').textContent =
        '\u20B1 ' + parseFloat(stats.total_outbound_value).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('statOutboundCount').textContent =
        stats.outbound_count + ' outbound movement' + (parseInt(stats.outbound_count) !== 1 ? 's' : '');

    // Net value
    const netValue = parseFloat(stats.net_value);
    const netValueEl = document.getElementById('statNetValue');
    const netValueIcon = document.getElementById('statNetValueIcon');
    const prefix = netValue >= 0 ? '+' : '-';
    netValueEl.textContent = prefix + '\u20B1 ' + Math.abs(netValue).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    netValueEl.classList.remove('text-success', 'text-danger', 'text-gray-800');
    netValueEl.classList.add(netValue >= 0 ? 'text-success' : 'text-danger');

    // Update icon color
    netValueIcon.classList.remove('bg-green-100', 'bg-red-100', 'bg-gray-100');
    netValueIcon.classList.add(netValue >= 0 ? 'bg-green-100' : 'bg-red-100');
    const iconSvg = netValueIcon.querySelector('svg');
    if (iconSvg) {
        iconSvg.classList.remove('text-green-600', 'text-danger', 'text-gray-600');
        iconSvg.classList.add(netValue >= 0 ? 'text-green-600' : 'text-danger');
    }
}

// ==================== RENDER TABLE ====================
function renderTrailTable(movements) {
    const container = document.getElementById('trailTableBody');

    if (!movements || movements.length === 0) {
        container.innerHTML = `
            <div class="px-4 sm:px-6 py-12 text-center min-w-0">
                <svg class="w-14 h-14 sm:w-16 sm:h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                <p class="text-regular text-gray-500">No movements found</p>
                <p class="text-label text-gray-400 mt-1 px-2">Try adjusting your filters or date range</p>
            </div>
        `;
        return;
    }

    let html = '';
    movements.forEach(mv => {
        const dateObj = new Date(mv.movement_date);
        const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const timeStr = dateObj.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

        const typeBadge = getMovementTypeBadge(mv.movement_type);
        const qty = parseFloat(mv.quantity);
        const qtySign = qty >= 0 ? '+' : '';
        const qtyColor = qty >= 0 ? 'text-success' : 'text-danger';
        const value = parseFloat(mv.total_value);
        const itemName = mv.item_name || 'Unknown Item';
        const unit = mv.quantity_unit || '';

        const mvIdEsc = escapeHtml(mv.movement_id);

        // Desktop/tablet row (md and up) – scrolls with header
        html += `
            <div class="hidden md:grid grid-cols-11 gap-2 sm:gap-4 px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors items-center min-w-[720px]"
                 onclick="openMovementDetailModal('${mvIdEsc}')">
                <div class="col-span-2 min-w-0">
                    <span class="text-product font-medium text-gray-800 text-xs sm:text-sm truncate block">${mvIdEsc}</span>
                </div>
                <div class="col-span-2">
                    <p class="text-regular text-gray-800 text-sm sm:text-base">${dateStr}</p>
                    <p class="text-label text-gray-400">${timeStr}</p>
                </div>
                <div class="col-span-2 min-w-0">
                    <p class="text-regular text-gray-800 truncate text-sm sm:text-base" title="${escapeHtml(itemName)}">${escapeHtml(itemName)}</p>
                    ${mv.batch_title ? `<p class="text-label text-gray-400 truncate">${escapeHtml(mv.batch_title)}</p>` : ''}
                </div>
                <div class="col-span-1 text-center">
                    ${typeBadge}
                </div>
                <div class="col-span-1 text-right">
                    <span class="text-product font-medium ${qtyColor} text-sm sm:text-base">${qtySign}${qty.toFixed(2)} ${escapeHtml(unit)}</span>
                </div>
                <div class="col-span-2 text-center">
                    <span class="text-regular text-gray-600 text-sm">${parseFloat(mv.old_quantity).toFixed(2)} → ${parseFloat(mv.new_quantity).toFixed(2)}</span>
                </div>
                <div class="col-span-1 text-right">
                    <span class="text-regular text-gray-700 text-sm sm:text-base">\u20B1 ${Math.abs(value).toFixed(2)}</span>
                </div>
            </div>

            <!-- Mobile card (under md) -->
            <div class="md:hidden border-b border-gray-100 px-4 py-4 cursor-pointer hover:bg-gray-50 active:bg-gray-100 transition-colors touch-manipulation"
                 onclick="openMovementDetailModal('${mvIdEsc}')">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="text-product font-medium text-gray-800 truncate min-w-0">${mvIdEsc}</span>
                    ${typeBadge}
                </div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div class="min-w-0">
                        <span class="text-label text-gray-400 block">Item</span>
                        <p class="text-regular text-gray-700 truncate">${escapeHtml(itemName)}</p>
                    </div>
                    <div>
                        <span class="text-label text-gray-400 block">Date</span>
                        <p class="text-regular text-gray-700">${dateStr}<br><span class="text-label">${timeStr}</span></p>
                    </div>
                    <div>
                        <span class="text-label text-gray-400 block">Qty Change</span>
                        <p class="text-product font-medium ${qtyColor}">${qtySign}${qty.toFixed(2)} ${escapeHtml(unit)}</p>
                    </div>
                    <div>
                        <span class="text-label text-gray-400 block">Value</span>
                        <p class="text-regular text-gray-700">\u20B1 ${Math.abs(value).toFixed(2)}</p>
                    </div>
                    <div class="col-span-2">
                        <span class="text-label text-gray-400 block">Before → After</span>
                        <p class="text-regular text-gray-700">${parseFloat(mv.old_quantity).toFixed(2)} → ${parseFloat(mv.new_quantity).toFixed(2)} ${escapeHtml(unit)}</p>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// ==================== FILTER DROPDOWNS ====================
function populateItemFilter(items) {
    const select = document.getElementById('itItemFilter');
    const currentValue = select.value;

    items.forEach(item => {
        const option = document.createElement('option');
        option.value = item.item_id;
        option.textContent = item.item_name;
        select.appendChild(option);
    });

    if (currentValue) {
        select.value = currentValue;
    }
}

function populateStaffFilter(staff) {
    const select = document.getElementById('itStaffFilter');
    const currentValue = select.value;

    staff.forEach(s => {
        const option = document.createElement('option');
        option.value = s.staff_id;
        option.textContent = s.user_name;
        select.appendChild(option);
    });

    if (currentValue) {
        select.value = currentValue;
    }
}

// ==================== DATE FILTER ====================
function clearDateFilters() {
    document.getElementById('itDateFrom').value = '';
    document.getElementById('itDateTo').value = '';
    loadInventoryTrail();
}

// ==================== MOVEMENT DETAIL MODAL ====================
function openMovementDetailModal(movementId) {
    const modal = document.getElementById('movementDetailModal');
    const container = document.getElementById('movementDetailContent');

    // Find movement in cached data
    const mv = allMovements.find(m => m.movement_id === movementId);
    if (!mv) {
        container.innerHTML = '<p class="text-center text-red-500 py-4 text-regular">Movement not found</p>';
        modal.classList.remove('hidden');
        return;
    }

    const dateObj = new Date(mv.movement_date);
    const dateStr = dateObj.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    const timeStr = dateObj.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true });

    const qty = parseFloat(mv.quantity);
    const qtySign = qty >= 0 ? '+' : '';
    const qtyColor = qty >= 0 ? 'text-success' : 'text-danger';
    const typeBadge = getMovementTypeBadge(mv.movement_type);
    const unit = mv.quantity_unit || '';

    container.innerHTML = `
        <div class="space-y-4">
            <!-- Movement ID & Type card -->
            <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-gray-50 border border-gray-100">
                <div class="min-w-0">
                    <p class="text-label text-gray-500 uppercase tracking-wide">Movement ID</p>
                    <p class="text-product font-semibold text-gray-900 truncate">${escapeHtml(mv.movement_id)}</p>
                </div>
                ${typeBadge}
            </div>

            <!-- Date & Time -->
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                <p class="text-label text-gray-500 uppercase tracking-wide mb-1">Date & Time</p>
                <p class="text-regular text-gray-800 font-medium">${dateStr}</p>
                <p class="text-regular text-gray-600">${timeStr}</p>
            </div>

            <!-- Item -->
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                <p class="text-label text-gray-500 uppercase tracking-wide mb-1">Item</p>
                <p class="text-regular text-gray-800 font-medium">${escapeHtml(mv.item_name || 'Unknown Item')}</p>
                ${mv.batch_title ? `<p class="text-label text-gray-500 mt-1">Batch: ${escapeHtml(mv.batch_title)}</p>` : ''}
            </div>

            <!-- Quantity flow -->
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                <p class="text-label text-gray-500 uppercase tracking-wide mb-3">Quantity</p>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="py-2 px-2 rounded-lg bg-white border border-gray-200">
                        <p class="text-label text-gray-500">Before</p>
                        <p class="text-product font-semibold text-gray-800">${parseFloat(mv.old_quantity).toFixed(2)}</p>
                        <p class="text-label text-gray-400">${escapeHtml(unit)}</p>
                    </div>
                    <div class="py-2 px-2 rounded-lg bg-white border border-gray-200">
                        <p class="text-label text-gray-500">Change</p>
                        <p class="text-product font-semibold ${qtyColor}">${qtySign}${qty.toFixed(2)}</p>
                        <p class="text-label text-gray-400">${escapeHtml(unit)}</p>
                    </div>
                    <div class="py-2 px-2 rounded-lg bg-white border border-gray-200">
                        <p class="text-label text-gray-500">After</p>
                        <p class="text-product font-semibold text-gray-800">${parseFloat(mv.new_quantity).toFixed(2)}</p>
                        <p class="text-label text-gray-400">${escapeHtml(unit)}</p>
                    </div>
                </div>
            </div>

            <!-- Cost -->
            <div class="grid grid-cols-2 gap-3">
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                    <p class="text-label text-gray-500 uppercase tracking-wide mb-1">Unit Cost</p>
                    <p class="text-regular text-gray-800 font-medium">\u20B1 ${parseFloat(mv.unit_cost).toFixed(2)}</p>
                </div>
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                    <p class="text-label text-gray-500 uppercase tracking-wide mb-1">Total Value</p>
                    <p class="text-regular text-gray-800 font-medium">\u20B1 ${Math.abs(parseFloat(mv.total_value)).toFixed(2)}</p>
                </div>
            </div>

            <!-- Reference (if present) -->
            ${mv.reference_type ? `
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                    <p class="text-label text-gray-500 uppercase tracking-wide mb-1">Reference</p>
                    <p class="text-regular text-gray-800">${escapeHtml(mv.reference_type)}${mv.reference_id ? ': ' + escapeHtml(mv.reference_id) : ''}</p>
                </div>
            ` : ''}

            <!-- Staff -->
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                <p class="text-label text-gray-500 uppercase tracking-wide mb-1">Staff</p>
                <p class="text-regular text-gray-800 font-medium">${escapeHtml(mv.staff_name || 'System')}</p>
            </div>

            <!-- Reason (if present) -->
            ${mv.reason ? `
                <div class="p-4 rounded-xl bg-amber-50/80 border border-amber-100">
                    <p class="text-label text-amber-800 uppercase tracking-wide mb-2">Reason / Notes</p>
                    <p class="text-regular text-gray-700 leading-relaxed">${escapeHtml(mv.reason)}</p>
                </div>
            ` : ''}
        </div>
    `;

    modal.classList.remove('hidden');
}

function closeMovementDetailModal() {
    document.getElementById('movementDetailModal').classList.add('hidden');
}

// ==================== UTILITIES ====================
function getMovementTypeBadge(type) {
    const badges = {
        'initial_stock': '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Initial Stock</span>',
        'restock': '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Restock</span>',
        'sale': '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Sale</span>',
        'disposal': '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Disposal</span>',
        'adjustment': '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Adjustment</span>'
    };
    return badges[type] || `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">${escapeHtml(type)}</span>`;
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
