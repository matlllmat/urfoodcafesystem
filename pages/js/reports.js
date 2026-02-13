// ==================== STATE ====================
let trendChart = null;
let hourlyChart = null;
let topProductsChart = null;
let categoryChart = null;

// ==================== INIT ====================
document.addEventListener('DOMContentLoaded', function () {
    loadReportsData();
});

// ==================== PERIOD CHANGE ====================
function onPeriodChange() {
    const period = document.getElementById('rptPeriodFilter').value;
    const customDates = document.getElementById('rptCustomDates');

    if (period === 'custom') {
        customDates.classList.remove('hidden');
        // Don't auto-load — wait for Apply
        return;
    }

    customDates.classList.add('hidden');
    loadReportsData();
}

// ==================== LOAD DATA ====================
function loadReportsData() {
    const period = document.getElementById('rptPeriodFilter').value;
    const params = new URLSearchParams({ period });

    if (period === 'custom') {
        const from = document.getElementById('rptDateFrom').value;
        const to = document.getElementById('rptDateTo').value;
        if (!from || !to) {
            showErrorModal('Please select both start and end dates.');
            return;
        }
        params.set('date_from', from);
        params.set('date_to', to);
    }

    fetch(`../api/reports-get-data.php?${params.toString()}`)
        .then(res => {
            if (!res.ok) {
                return res.text().then(text => {
                    try { return JSON.parse(text); }
                    catch { return { success: false, message: `Server error ${res.status}: ${text.substring(0, 200)}` }; }
                });
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                renderKPI(data.kpi);
                renderTrendChart(data.daily_trend);
                renderHourlyChart(data.hourly_sales);
                renderTopProductsChart(data.top_products);
                renderCategoryChart(data.category_breakdown);
                renderStaffTable(data.staff_performance);
                renderInventoryAlerts(data.low_stock, data.expiring_soon);
            } else {
                showErrorModal(data.message || 'Failed to load report data');
            }
        })
        .catch(err => {
            console.error('Reports load error:', err);
            showErrorModal('Failed to load report data.');
        });
}

// ==================== KPI CARDS ====================
function renderKPI(kpi) {
    // Revenue
    document.getElementById('kpiRevenue').textContent = formatCurrency(kpi.revenue);
    renderChange('kpiRevenueChange', kpi.revenue_change, kpi.prev_label);

    // Profit
    const profitEl = document.getElementById('kpiProfit');
    profitEl.textContent = formatCurrency(kpi.profit);
    profitEl.classList.remove('text-success', 'text-danger');
    profitEl.classList.add(kpi.profit >= 0 ? 'text-success' : 'text-danger');
    renderChange('kpiProfitChange', kpi.profit_change, kpi.prev_label);

    // Transactions
    document.getElementById('kpiTransactions').textContent = kpi.transactions.toLocaleString();
    renderChange('kpiTransactionsChange', kpi.transactions_change, kpi.prev_label);

    // Avg Order
    document.getElementById('kpiAvgOrder').textContent = formatCurrency(kpi.avg_order);
    renderChange('kpiAvgOrderChange', kpi.avg_order_change, kpi.prev_label);
}

function renderChange(elementId, change, prevLabel) {
    const el = document.getElementById(elementId);
    if (change === 0) {
        el.innerHTML = `<span class="text-gray-400">No change vs ${escapeHtml(prevLabel)}</span>`;
        return;
    }

    const isUp = change > 0;
    const arrow = isUp
        ? '<svg class="w-3 h-3 inline mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>'
        : '<svg class="w-3 h-3 inline mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
    const colorClass = isUp ? 'text-green-600' : 'text-red-500';
    const sign = isUp ? '+' : '';

    el.innerHTML = `<span class="${colorClass}">${arrow}${sign}${change}%</span> <span class="text-gray-400">vs ${escapeHtml(prevLabel)}</span>`;
}

// ==================== TREND CHART ====================
function renderTrendChart(data) {
    const canvas = document.getElementById('chartTrend');
    const emptyMsg = document.getElementById('chartTrendEmpty');

    if (!data || data.length === 0) {
        canvas.style.display = 'none';
        emptyMsg.classList.remove('hidden');
        if (trendChart) { trendChart.destroy(); trendChart = null; }
        return;
    }

    canvas.style.display = '';
    emptyMsg.classList.add('hidden');

    const labels = data.map(d => formatDateShort(d.date));
    const revenues = data.map(d => d.revenue);
    const profits = data.map(d => d.profit);

    if (trendChart) trendChart.destroy();

    trendChart = new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Revenue',
                    data: revenues,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: data.length > 20 ? 0 : 3,
                    pointHoverRadius: 5,
                    borderWidth: 2
                },
                {
                    label: 'Profit',
                    data: profits,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: data.length > 20 ? 0 : 3,
                    pointHoverRadius: 5,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, padding: 16, font: { family: 'Inter', size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => `${ctx.dataset.label}: ${formatCurrency(ctx.parsed.y)}`
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, maxRotation: 45 } },
                y: { grid: { color: '#f3f4f6' }, ticks: { font: { family: 'Inter', size: 11 }, callback: v => '\u20B1' + abbreviateNumber(v) } }
            }
        }
    });
}

// ==================== HOURLY CHART ====================
function renderHourlyChart(data) {
    const canvas = document.getElementById('chartHourly');
    const emptyMsg = document.getElementById('chartHourlyEmpty');

    if (!data || data.length === 0) {
        canvas.style.display = 'none';
        emptyMsg.classList.remove('hidden');
        if (hourlyChart) { hourlyChart.destroy(); hourlyChart = null; }
        return;
    }

    canvas.style.display = '';
    emptyMsg.classList.add('hidden');

    // Fill all 24 hours
    const hourMap = {};
    data.forEach(d => { hourMap[d.hour] = d; });

    const labels = [];
    const transactions = [];
    for (let h = 6; h <= 22; h++) {
        const suffix = h < 12 ? 'AM' : 'PM';
        const display = h === 0 ? 12 : h > 12 ? h - 12 : h;
        labels.push(display + suffix);
        transactions.push(hourMap[h] ? hourMap[h].transactions : 0);
    }

    if (hourlyChart) hourlyChart.destroy();

    hourlyChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Transactions',
                data: transactions,
                backgroundColor: '#8b5cf6',
                borderRadius: 4,
                maxBarThickness: 28
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => `${ctx.parsed.y} transaction${ctx.parsed.y !== 1 ? 's' : ''}`
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 } } },
                y: {
                    grid: { color: '#f3f4f6' },
                    ticks: { font: { family: 'Inter', size: 11 }, stepSize: 1 },
                    beginAtZero: true
                }
            }
        }
    });
}

// ==================== TOP PRODUCTS CHART ====================
function renderTopProductsChart(data) {
    const canvas = document.getElementById('chartTopProducts');
    const emptyMsg = document.getElementById('chartTopProductsEmpty');

    if (!data || data.length === 0) {
        canvas.style.display = 'none';
        emptyMsg.classList.remove('hidden');
        if (topProductsChart) { topProductsChart.destroy(); topProductsChart = null; }
        return;
    }

    canvas.style.display = '';
    emptyMsg.classList.add('hidden');

    const labels = data.map(d => truncateLabel(d.name, 20));
    const revenues = data.map(d => d.revenue);

    const colors = [
        '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6',
        '#06b6d4', '#ec4899', '#f97316', '#14b8a6', '#6366f1'
    ];

    if (topProductsChart) topProductsChart.destroy();

    topProductsChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Revenue',
                data: revenues,
                backgroundColor: colors.slice(0, data.length),
                borderRadius: 4,
                maxBarThickness: 24
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => data[items[0].dataIndex].name,
                        label: ctx => {
                            const item = data[ctx.dataIndex];
                            return [
                                `Revenue: ${formatCurrency(item.revenue)}`,
                                `Qty Sold: ${item.qty}`,
                                `Profit: ${formatCurrency(item.profit)}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: '#f3f4f6' },
                    ticks: { font: { family: 'Inter', size: 11 }, callback: v => '\u20B1' + abbreviateNumber(v) }
                },
                y: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 } } }
            }
        }
    });
}

// ==================== CATEGORY CHART ====================
function renderCategoryChart(data) {
    const canvas = document.getElementById('chartCategory');
    const emptyMsg = document.getElementById('chartCategoryEmpty');

    if (!data || data.length === 0) {
        canvas.style.display = 'none';
        emptyMsg.classList.remove('hidden');
        if (categoryChart) { categoryChart.destroy(); categoryChart = null; }
        return;
    }

    canvas.style.display = '';
    emptyMsg.classList.add('hidden');

    const labels = data.map(d => d.category);
    const revenues = data.map(d => d.revenue);
    const total = revenues.reduce((a, b) => a + b, 0);

    const colors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316'];

    if (categoryChart) categoryChart.destroy();

    categoryChart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: revenues,
                backgroundColor: colors.slice(0, data.length),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { usePointStyle: true, padding: 12, font: { family: 'Inter', size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                            return `${ctx.label}: ${formatCurrency(ctx.parsed)} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
}

// ==================== STAFF TABLE ====================
function renderStaffTable(data) {
    const tbody = document.getElementById('staffTableBody');

    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-5 py-8 text-center text-gray-400 text-regular">No staff sales data for this period</td></tr>';
        return;
    }

    let html = '';
    data.forEach((s, i) => {
        const profitColor = s.profit >= 0 ? 'text-success' : 'text-danger';
        html += `
            <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3">
                    <span class="text-regular text-gray-800">${escapeHtml(s.cashier)}</span>
                </td>
                <td class="px-5 py-3 text-center">
                    <span class="text-regular text-gray-700">${s.transactions}</span>
                </td>
                <td class="px-5 py-3 text-right">
                    <span class="text-product font-medium text-gray-800">${formatCurrency(s.revenue)}</span>
                </td>
                <td class="px-5 py-3 text-right">
                    <span class="text-regular ${profitColor}">${formatCurrency(s.profit)}</span>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// ==================== INVENTORY ALERTS ====================
function renderInventoryAlerts(lowStock, expiring) {
    const container = document.getElementById('inventoryAlerts');

    const hasLow = lowStock && lowStock.length > 0;
    const hasExpiring = expiring && expiring.length > 0;

    if (!hasLow && !hasExpiring) {
        container.innerHTML = `
            <div class="px-5 py-8 text-center">
                <svg class="w-10 h-10 mx-auto text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-regular text-gray-500">All inventory levels are healthy</p>
            </div>
        `;
        return;
    }

    let html = '';

    // Low stock items
    if (hasLow) {
        html += `<div class="px-5 py-3 bg-red-50"><p class="text-label text-danger font-medium uppercase">Low Stock</p></div>`;
        lowStock.forEach(item => {
            const isOut = item.current <= 0;
            const badge = isOut
                ? '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-danger">Out of Stock</span>'
                : '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Low Stock</span>';

            html += `
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-regular text-gray-800">${escapeHtml(item.name)}</p>
                        <p class="text-label text-gray-400">${item.current} / ${item.reorder_level} ${escapeHtml(item.unit)}</p>
                    </div>
                    ${badge}
                </div>
            `;
        });
    }

    // Expiring items
    if (hasExpiring) {
        html += `<div class="px-5 py-3 bg-yellow-50"><p class="text-label text-yellow-700 font-medium uppercase">Expiring Soon</p></div>`;
        expiring.forEach(item => {
            const daysText = item.days_left === 0 ? 'Today' : item.days_left === 1 ? 'Tomorrow' : `${item.days_left} days`;
            html += `
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-regular text-gray-800">${escapeHtml(item.item_name)}</p>
                        <p class="text-label text-gray-400">${escapeHtml(item.batch_title)} &middot; ${item.quantity} ${escapeHtml(item.unit)}</p>
                    </div>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">${daysText}</span>
                </div>
            `;
        });
    }

    container.innerHTML = html;
}

// ==================== UTILITIES ====================
function formatCurrency(value) {
    return '\u20B1 ' + parseFloat(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDateShort(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function abbreviateNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
}

function truncateLabel(str, max) {
    return str.length > max ? str.substring(0, max - 1) + '\u2026' : str;
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
