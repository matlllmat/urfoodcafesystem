<?php
require_once __DIR__ . '/../config/db.php';
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<!-- Reports Page -->
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl text-title mb-2">Reports</h1>
            <p class="text-regular text-gray-600">Business overview, trends, and comparisons</p>
        </div>

        <!-- Period Selector -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <select
                id="rptPeriodFilter"
                onchange="onPeriodChange()"
                class="px-4 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black">
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month" selected>This Month</option>
                <option value="this_year">This Year</option>
                <option value="custom">Custom Range</option>
            </select>

            <div id="rptCustomDates" class="hidden flex items-center gap-2">
                <input type="date" id="rptDateFrom"
                    class="px-3 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black" />
                <span class="text-regular text-gray-400">to</span>
                <input type="date" id="rptDateTo"
                    class="px-3 py-2 border border-gray-300 rounded-md text-regular focus:outline-none focus:ring-2 focus:ring-black" />
                <button onclick="loadReportsData()"
                    class="px-4 py-2 bg-black text-white rounded-md text-regular hover:bg-gray-800 transition-colors">
                    Apply
                </button>
            </div>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Revenue -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Revenue</p>
                    <h3 class="text-title text-2xl text-gray-800" id="kpiRevenue">-</h3>
                    <p class="text-xs mt-1" id="kpiRevenueChange"></p>
                </div>
                <div class="bg-blue-100 p-2 rounded">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Profit -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Profit</p>
                    <h3 class="text-title text-2xl" id="kpiProfit">-</h3>
                    <p class="text-xs mt-1" id="kpiProfitChange"></p>
                </div>
                <div class="bg-green-100 p-2 rounded">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Transactions -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Transactions</p>
                    <h3 class="text-title text-2xl text-gray-800" id="kpiTransactions">-</h3>
                    <p class="text-xs mt-1" id="kpiTransactionsChange"></p>
                </div>
                <div class="bg-purple-100 p-2 rounded">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Avg Order Value -->
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label text-gray-500 mb-1">Avg Order Value</p>
                    <h3 class="text-title text-2xl text-gray-800" id="kpiAvgOrder">-</h3>
                    <p class="text-xs mt-1" id="kpiAvgOrderChange"></p>
                </div>
                <div class="bg-orange-100 p-2 rounded">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1: Revenue Trend + Hourly Sales -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Revenue & Profit Trend -->
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h3 class="text-title text-base mb-4">Revenue & Profit Trend</h3>
            <div class="relative" style="height: 280px;">
                <canvas id="chartTrend"></canvas>
            </div>
            <p id="chartTrendEmpty" class="hidden text-center text-gray-400 text-label py-12">No sales data for this period</p>
        </div>

        <!-- Sales by Hour -->
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h3 class="text-title text-base mb-4">Sales by Hour of Day</h3>
            <div class="relative" style="height: 280px;">
                <canvas id="chartHourly"></canvas>
            </div>
            <p id="chartHourlyEmpty" class="hidden text-center text-gray-400 text-label py-12">No sales data for this period</p>
        </div>
    </div>

    <!-- Charts Row 2: Top Products + Category Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Top Products -->
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h3 class="text-title text-base mb-4">Top Products by Revenue</h3>
            <div class="relative" style="height: 280px;">
                <canvas id="chartTopProducts"></canvas>
            </div>
            <p id="chartTopProductsEmpty" class="hidden text-center text-gray-400 text-label py-12">No product data for this period</p>
        </div>

        <!-- Category Breakdown -->
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h3 class="text-title text-base mb-4">Revenue by Category</h3>
            <div class="relative flex items-center justify-center" style="height: 280px;">
                <canvas id="chartCategory"></canvas>
            </div>
            <p id="chartCategoryEmpty" class="hidden text-center text-gray-400 text-label py-12">No category data for this period</p>
        </div>
    </div>

    <!-- Data Tables: Staff Performance + Inventory Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Staff Performance -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-title text-base">Staff Performance</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left px-5 py-3 text-label text-gray-500 uppercase">Cashier</th>
                            <th class="text-center px-5 py-3 text-label text-gray-500 uppercase">Sales</th>
                            <th class="text-right px-5 py-3 text-label text-gray-500 uppercase">Revenue</th>
                            <th class="text-right px-5 py-3 text-label text-gray-500 uppercase">Profit</th>
                        </tr>
                    </thead>
                    <tbody id="staffTableBody">
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-gray-400 text-regular">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Inventory Alerts -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-title text-base">Inventory Alerts</h3>
            </div>
            <div id="inventoryAlerts" class="divide-y divide-gray-100">
                <div class="px-5 py-8 text-center text-gray-400 text-regular">Loading...</div>
            </div>
        </div>
    </div>
</div>

<!-- Load JS -->
<script src="js/reports.js"></script>
