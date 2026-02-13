/**
 * Create Sales Module - JavaScript
 * Handles product selection, cart management, order confirmation, and receipt display
 */

// ==================== STATE ====================
let allProducts = [];
let allCategories = [];
let cart = []; // { product_id, product_name, image_filename, price, calculated_cost, quantity, custom_price?, custom_ingredients?, _defaultRequirements? }
let activeCategory = '';
let currentSaleId = null;
let orderConfirmed = false;
let manualMode = false;
let inventoryItemsCache = null;

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function () {
    loadCategories();
    loadProducts();
});

// ==================== DATA LOADING ====================
function loadCategories() {
    fetch('../api/cs-get-categories.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                allCategories = data.categories;
                renderCategoryCarousel();
                renderCategoryDropdown();
            }
        })
        .catch(err => console.error('Failed to load categories:', err));
}

function loadProducts() {
    fetch('../api/cs-get-products.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                allProducts = data.products;
                renderProducts();
            } else {
                document.getElementById('productsContainer').innerHTML =
                    '<div class="bg-white rounded-lg border border-gray-200 p-12 text-center"><p class="text-regular text-gray-500">No products available</p></div>';
            }
        })
        .catch(err => {
            console.error('Failed to load products:', err);
            document.getElementById('productsContainer').innerHTML =
                '<div class="bg-white rounded-lg border border-gray-200 p-12 text-center"><p class="text-regular text-red-500">Failed to load products</p></div>';
        });
}

// ==================== MANUAL VALUE MODE ====================
function toggleManualMode() {
    if (orderConfirmed) return;
    manualMode = !manualMode;

    const btn = document.getElementById('manualModeToggle');
    const knob = btn.querySelector('span');

    if (manualMode) {
        btn.classList.remove('bg-gray-300');
        btn.classList.add('bg-black');
        btn.setAttribute('aria-checked', 'true');
        knob.classList.remove('translate-x-1');
        knob.classList.add('translate-x-6');
        loadInventoryItems();
    } else {
        btn.classList.remove('bg-black');
        btn.classList.add('bg-gray-300');
        btn.setAttribute('aria-checked', 'false');
        knob.classList.remove('translate-x-6');
        knob.classList.add('translate-x-1');

        // Clear all manual overrides from cart items
        cart.forEach(item => {
            delete item.custom_price;
            delete item.custom_ingredients;
        });
    }

    updateOrderSummary();
    renderCartItems();
}

function loadInventoryItems() {
    if (inventoryItemsCache) return;
    fetch('../api/mp-get-inventory-items.php?in_stock_only=1')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                inventoryItemsCache = data.items;
            }
        })
        .catch(err => console.error('Failed to load inventory items:', err));
}

// ==================== CATEGORY CAROUSEL ====================
function renderCategoryCarousel() {
    const carousel = document.getElementById('categoryCarousel');

    // "All" button
    let html = `
        <button onclick="filterByCategory('')"
            class="flex-shrink-0 flex flex-col items-center justify-center gap-1.5 px-5 py-3 rounded-lg border-2 transition-colors
            ${activeCategory === '' ? 'border-black bg-gray-100' : 'border-gray-200 hover:bg-gray-50'}"
            style="min-width: 90px;">
            <span class="text-2xl">&#128722;</span>
            <span class="text-sm font-medium">All</span>
        </button>
    `;

    allCategories.forEach(cat => {
        const isActive = activeCategory === cat.category_id;
        html += `
            <button onclick="filterByCategory('${cat.category_id}')"
                class="flex-shrink-0 flex flex-col items-center justify-center gap-1.5 px-5 py-3 rounded-lg border-2 transition-colors
                ${isActive ? 'border-black bg-gray-100' : 'border-gray-200 hover:bg-gray-50'}"
                style="min-width: 90px;">
                <span class="text-2xl">&#127860;</span>
                <span class="text-sm font-medium truncate max-w-[80px]">${escapeHtml(cat.category_name)}</span>
            </button>
        `;
    });

    carousel.innerHTML = html;
}

function renderCategoryDropdown() {
    const dropdown = document.getElementById('categoryDropdown');
    let html = '<option value="">All Categories</option>';

    allCategories.forEach(cat => {
        html += `<option value="${cat.category_id}" ${activeCategory === cat.category_id ? 'selected' : ''}>
            ${escapeHtml(cat.category_name)}
        </option>`;
    });

    dropdown.innerHTML = html;
}

function scrollCategories(direction) {
    const carousel = document.getElementById('categoryCarousel');
    const scrollAmount = 200;
    carousel.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
}

function filterByCategory(categoryId) {
    activeCategory = categoryId;
    renderCategoryCarousel();
    document.getElementById('categoryDropdown').value = categoryId;
    renderProducts();
}

// ==================== PRODUCT RENDERING ====================
function getFilteredProducts() {
    // Exclude out-of-stock products
    let products = allProducts.filter(p => p.in_stock);

    // Category filter
    if (activeCategory) {
        products = products.filter(p => {
            const cats = p.all_categories ? p.all_categories.split(',') : [];
            return cats.includes(activeCategory);
        });
    }

    // Search filter
    const searchTerm = document.getElementById('productSearchInput').value.toLowerCase().trim();
    if (searchTerm) {
        products = products.filter(p => p.product_name.toLowerCase().includes(searchTerm));
    }

    // Sort
    const sortBy = document.getElementById('sortDropdown').value;
    switch (sortBy) {
        case 'name_asc':
            products.sort((a, b) => a.product_name.localeCompare(b.product_name));
            break;
        case 'name_desc':
            products.sort((a, b) => b.product_name.localeCompare(a.product_name));
            break;
        case 'price_asc':
            products.sort((a, b) => a.price - b.price);
            break;
        case 'price_desc':
            products.sort((a, b) => b.price - a.price);
            break;
    }

    return products;
}

function renderProducts() {
    const container = document.getElementById('productsContainer');
    const products = getFilteredProducts();

    if (products.length === 0) {
        container.innerHTML = `
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <p class="text-regular text-gray-500">No products found</p>
                <p class="text-label text-gray-400 mt-1">Try adjusting your filters or search term</p>
            </div>
        `;
        return;
    }

    // Group by primary category
    const grouped = {};
    products.forEach(p => {
        const catName = p.primary_category_name || 'Uncategorized';
        if (!grouped[catName]) grouped[catName] = [];
        grouped[catName].push(p);
    });

    let html = '';
    for (const [categoryName, categoryProducts] of Object.entries(grouped)) {
        html += `
            <div class="category-group">
                <button onclick="toggleCategoryGroup(this)" class="flex items-center justify-between w-full py-2 text-left group">
                    <h2 class="text-title text-lg flex items-center gap-2">
                        ${escapeHtml(categoryName)}
                        <span class="text-label text-gray-400 font-normal">${categoryProducts.length} product${categoryProducts.length !== 1 ? 's' : ''}</span>
                    </h2>
                    <svg class="w-5 h-5 text-gray-400 transition-transform category-chevron group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="category-products grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-4 mt-3">
        `;

        categoryProducts.forEach(product => {
            const cartItem = cart.find(c => c.product_id === product.product_id);
            const qty = cartItem ? cartItem.quantity : 0;

            html += `
                <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-lg transition-shadow" data-product-id="${product.product_id}">
                    <!-- Product Image -->
                    <div class="aspect-square bg-gray-50 rounded-md mb-3 flex items-center justify-center overflow-hidden">
                        <img src="../assets/images/product/${escapeHtml(product.image_filename)}"
                            alt="${escapeHtml(product.product_name)}"
                            class="w-full h-full object-cover"
                            onerror="this.src='../assets/images/product/default-product.png'" />
                    </div>

                    <!-- Product Name -->
                    <h3 class="text-product text-gray-800 mb-1 truncate" title="${escapeHtml(product.product_name)}">
                        ${escapeHtml(product.product_name)}
                    </h3>

                    <!-- Price -->
                    <p class="text-regular text-gray-600 mb-3">&#8369; ${product.price.toFixed(0)}</p>

                    <!-- Add / Quantity Controls -->
                    <div class="flex items-center justify-center">
                        ${qty === 0 ? `
                            <button onclick="addToCart('${product.product_id}')"
                                class="w-full py-2 border border-gray-300 rounded-md text-regular text-gray-600 hover:bg-gray-50 transition-colors flex items-center justify-center gap-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"/>
                                </svg>
                            </button>
                        ` : `
                            <div class="flex items-center gap-3 w-full justify-center">
                                <button onclick="updateCartQty('${product.product_id}', -1)"
                                    class="w-8 h-8 flex items-center justify-center rounded-md bg-gray-800 text-white hover:bg-black transition-colors">
                                    &minus;
                                </button>
                                <span class="text-product w-8 text-center">${qty}</span>
                                <button onclick="updateCartQty('${product.product_id}', 1)"
                                    class="w-8 h-8 flex items-center justify-center rounded-md bg-gray-800 text-white hover:bg-black transition-colors">
                                    +
                                </button>
                            </div>
                        `}
                    </div>
                </div>
            `;
        });

        html += '</div></div>';
    }

    container.innerHTML = html;
}

function toggleCategoryGroup(btn) {
    const products = btn.nextElementSibling;
    const chevron = btn.querySelector('.category-chevron');
    products.classList.toggle('hidden');
    chevron.classList.toggle('rotate-180');
}

function sortProducts() {
    renderProducts();
}

function searchProducts() {
    renderProducts();
}

// ==================== CART MANAGEMENT ====================
function addToCart(productId) {
    if (orderConfirmed) return;

    const product = allProducts.find(p => p.product_id === productId);
    if (!product) return;

    const existing = cart.find(c => c.product_id === productId);
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({
            product_id: product.product_id,
            product_name: product.product_name,
            image_filename: product.image_filename,
            price: parseFloat(product.price),
            calculated_cost: parseFloat(product.calculated_cost),
            quantity: 1
        });
    }

    updateOrderSummary();
    renderProducts();
}

function updateCartQty(productId, delta) {
    if (orderConfirmed) return;

    const item = cart.find(c => c.product_id === productId);
    if (!item) return;

    item.quantity += delta;
    if (item.quantity <= 0) {
        cart = cart.filter(c => c.product_id !== productId);
    }

    updateOrderSummary();
    renderProducts();
    renderCartItems();
}

function removeFromCart(productId) {
    if (orderConfirmed) return;

    cart = cart.filter(c => c.product_id !== productId);
    updateOrderSummary();
    renderProducts();
    renderCartItems();
}

function setCartQty(productId, value) {
    if (orderConfirmed) return;

    const item = cart.find(c => c.product_id === productId);
    if (!item) return;

    const newQty = parseInt(value);
    if (isNaN(newQty) || newQty <= 0) {
        cart = cart.filter(c => c.product_id !== productId);
    } else {
        item.quantity = newQty;
    }

    updateOrderSummary();
    renderProducts();
    renderCartItems();
}

function getCartTotal() {
    return cart.reduce((sum, item) => {
        const effectivePrice = item.custom_price !== undefined ? item.custom_price : item.price;
        return sum + (effectivePrice * item.quantity);
    }, 0);
}

function getCartItemCount() {
    return cart.reduce((sum, item) => sum + item.quantity, 0);
}

function updateOrderSummary() {
    const total = getCartTotal();
    const count = getCartItemCount();

    // Product selection view summary
    document.getElementById('summaryProductCount').textContent = count;
    document.getElementById('summaryTotal').innerHTML = '&#8369; ' + total.toFixed(2);
    document.getElementById('cartBadge').textContent = count;

    // Show/hide summary bar
    const summaryBar = document.getElementById('orderSummaryBar');
    if (count > 0) {
        summaryBar.classList.remove('hidden');
    } else {
        summaryBar.classList.add('hidden');
    }

    // Cart view summary
    document.getElementById('cartGrandTotal').innerHTML = '&#8369; ' + total.toFixed(2);
    document.getElementById('cartProductCount').textContent = count;

    calculateChange();
}

function calculateChange() {
    const total = getCartTotal();
    const amountPaid = parseFloat(document.getElementById('amountPaidInput').value) || 0;
    const change = amountPaid - total;

    const changeEl = document.getElementById('cartChange');
    changeEl.innerHTML = '&#8369; ' + Math.max(0, change).toFixed(2);

    // Visual feedback: green if enough, red if not
    if (amountPaid > 0 && amountPaid < total) {
        changeEl.classList.add('text-red-600');
        changeEl.classList.remove('text-title');
    } else {
        changeEl.classList.remove('text-red-600');
        changeEl.classList.add('text-title');
    }
}

// ==================== VIEW SWITCHING ====================
function showCartView() {
    if (cart.length === 0) {
        showWarningModal('Your cart is empty. Add some products first.');
        return;
    }

    document.getElementById('productSelectionView').classList.add('hidden');
    document.getElementById('cartView').classList.remove('hidden');

    renderCartItems();
    updateOrderSummary();
}

function showProductView() {
    document.getElementById('cartView').classList.add('hidden');
    document.getElementById('productSelectionView').classList.remove('hidden');
    renderProducts();
}

// ==================== CART VIEW RENDERING ====================
function renderCartItems() {
    const container = document.getElementById('cartItemsContainer');

    if (cart.length === 0) {
        container.innerHTML = '<div class="text-center py-12 text-gray-500 text-regular">No items in cart</div>';
        return;
    }

    let html = '';
    cart.forEach(item => {
        const effectivePrice = item.custom_price !== undefined ? parseFloat(item.custom_price) : parseFloat(item.price);
        const lineTotal = effectivePrice * item.quantity;
        const hasManualOverride = item.custom_price !== undefined || item.custom_ingredients !== undefined;

        // Price column: editable input with peso prefix in manual mode, static text otherwise
        let priceHtml;
        if (manualMode && !orderConfirmed) {
            priceHtml = `<div class="flex items-center justify-center gap-1">
                <span class="text-gray-500 text-sm font-medium">&#8369;</span>
                <input type="number" min="0" step="0.01" value="${effectivePrice.toFixed(2)}"
                    onchange="setCustomPrice('${item.product_id}', this.value)"
                    class="w-20 px-2 py-1 border border-blue-300 rounded text-center text-regular bg-blue-50 focus:outline-none focus:ring-1 focus:ring-blue-400" />
            </div>`;
        } else {
            priceHtml = `<span class="text-regular text-gray-600">&#8369; ${effectivePrice.toFixed(2)}</span>`;
        }

        // Manual badge
        const manualBadge = hasManualOverride
            ? '<span class="ml-1 px-1.5 py-0.5 text-xs bg-blue-100 text-blue-700 rounded font-medium flex-shrink-0">M</span>'
            : '';

        // Quantity: typable input with +/- buttons, or static span when order confirmed
        let qtyHtml;
        if (orderConfirmed) {
            qtyHtml = `<span class="text-product w-10 text-center">${item.quantity}</span>`;
        } else {
            qtyHtml = `<input type="number" min="1" value="${item.quantity}"
                onchange="setCartQty('${item.product_id}', this.value)"
                class="w-14 text-center text-product border border-gray-300 rounded-md py-1 focus:outline-none focus:ring-1 focus:ring-black appearance-none" />`;
        }

        html += `
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                <!-- Main Row -->
                <div class="grid grid-cols-12 gap-2 px-4 py-4 items-center">
                    <!-- Product Name -->
                    <div class="col-span-4 flex items-center gap-3">
                        <img src="../assets/images/product/${escapeHtml(item.image_filename)}"
                            alt="${escapeHtml(item.product_name)}"
                            class="w-10 h-10 rounded-md object-cover flex-shrink-0 border border-gray-100"
                            onerror="this.src='../assets/images/product/default-product.png'" />
                        <span class="text-product truncate">${escapeHtml(item.product_name)}</span>
                        ${manualBadge}
                    </div>

                    <!-- Quantity Controls -->
                    <div class="col-span-3 flex items-center justify-center gap-2">
                        <button onclick="updateCartQty('${item.product_id}', -1)"
                            class="w-8 h-8 flex items-center justify-center rounded-md bg-gray-800 text-white hover:bg-black transition-colors ${orderConfirmed ? 'opacity-50 cursor-not-allowed' : ''}">
                            &minus;
                        </button>
                        ${qtyHtml}
                        <button onclick="updateCartQty('${item.product_id}', 1)"
                            class="w-8 h-8 flex items-center justify-center rounded-md bg-gray-800 text-white hover:bg-black transition-colors ${orderConfirmed ? 'opacity-50 cursor-not-allowed' : ''}">
                            +
                        </button>
                    </div>

                    <!-- Price -->
                    <div class="col-span-2 text-center">
                        ${priceHtml}
                    </div>

                    <!-- Total + Expand -->
                    <div class="col-span-3 flex items-center justify-end gap-3">
                        <span class="text-product">&#8369; ${lineTotal.toFixed(2)}</span>
                        <button onclick="toggleItemDetails(this, '${item.product_id}')" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                            <svg class="w-5 h-5 transition-transform item-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Expandable Details (hidden by default) -->
                <div class="item-details hidden border-t border-gray-100 bg-gray-50 px-6 py-4" data-product-detail="${item.product_id}">
                    <div class="text-label text-gray-500 mb-3 flex items-center gap-2">
                        <span class="inline-block w-16 border-t border-gray-400"></span>
                        <span>Included Inventory Items</span>
                    </div>
                    <div class="inventory-items-loading text-label text-gray-400">Loading...</div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function toggleItemDetails(btn, productId) {
    const chevron = btn.querySelector('.item-chevron');
    const card = btn.closest('.bg-white');
    const details = card.querySelector('.item-details');

    details.classList.toggle('hidden');
    chevron.classList.toggle('rotate-90');

    // Load requirements if not already loaded
    const loadingEl = details.querySelector('.inventory-items-loading');
    if (loadingEl) {
        fetch(`../api/mp-get-product-requirements.php?product_id=${productId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.requirements.length > 0) {
                    const cartItem = cart.find(c => c.product_id === productId);
                    // Cache default requirements on the cart item for manual mode
                    if (cartItem) {
                        cartItem._defaultRequirements = data.requirements;
                    }

                    if (manualMode && !orderConfirmed) {
                        renderEditableIngredients(productId, data.requirements, details);
                    } else {
                        renderReadOnlyIngredients(productId, data.requirements, details);
                    }
                } else {
                    details.innerHTML = `
                        <div class="text-label text-gray-500 mb-3 flex items-center gap-2">
                            <span class="inline-block w-16 border-t border-gray-400"></span>
                            <span>Included Inventory Items</span>
                        </div>
                        <p class="text-regular text-sm text-gray-400">No inventory requirements</p>
                    `;
                }
            })
            .catch(() => {
                details.innerHTML = '<p class="text-regular text-sm text-red-400">Failed to load details</p>';
            });
    } else if (manualMode && !orderConfirmed) {
        // Already loaded before — re-render as editable if manual mode changed
        const cartItem = cart.find(c => c.product_id === productId);
        if (cartItem && cartItem._defaultRequirements && !details.classList.contains('hidden')) {
            renderEditableIngredients(productId, cartItem._defaultRequirements, details);
        }
    }
}

function renderReadOnlyIngredients(productId, requirements, container) {
    const cartItem = cart.find(c => c.product_id === productId);
    const qty = cartItem ? cartItem.quantity : 1;

    let reqHtml = `
        <div class="grid grid-cols-3 gap-2 text-label font-medium text-gray-600 mb-2 uppercase tracking-wide">
            <span>Item Name</span>
            <span class="text-center">Qty</span>
            <span class="text-center">Unit</span>
        </div>
    `;
    requirements.forEach(req => {
        const totalUsed = (parseFloat(req.quantity_used) * qty).toFixed(2);
        reqHtml += `
            <div class="grid grid-cols-3 gap-2 text-regular text-sm text-gray-500 py-1">
                <span>${escapeHtml(req.item_name)}</span>
                <span class="text-center">${totalUsed}</span>
                <span class="text-center">${escapeHtml(req.quantity_unit)}</span>
            </div>
        `;
    });
    container.innerHTML = `
        <div class="text-label text-gray-500 mb-3 flex items-center gap-2">
            <span class="inline-block w-16 border-t border-gray-400"></span>
            <span>Included Inventory Items</span>
        </div>
        ${reqHtml}
    `;
}

function renderEditableIngredients(productId, defaultRequirements, container) {
    const cartItem = cart.find(c => c.product_id === productId);
    const qty = cartItem ? cartItem.quantity : 1;

    // Use custom_ingredients if they exist, otherwise build from defaults
    let ingredients;
    if (cartItem && cartItem.custom_ingredients) {
        ingredients = cartItem.custom_ingredients;
    } else {
        ingredients = defaultRequirements.map(req => ({
            inventory_id: req.inventory_id,
            item_name: req.item_name,
            quantity_unit: req.quantity_unit,
            quantity_used: parseFloat(req.quantity_used),
            unit_cost: parseFloat(req.unit_cost),
            is_extra: false
        }));
    }

    let ingredientRows = '';
    ingredients.forEach((ing, index) => {
        const totalUsed = (ing.quantity_used * qty).toFixed(2);
        ingredientRows += `
            <div class="flex items-center gap-3 py-2.5 px-3 bg-white rounded-lg border ${ing.is_extra ? 'border-blue-200' : 'border-gray-200'}">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-700 truncate">${escapeHtml(ing.item_name)}</p>
                    <p class="text-xs text-gray-400">${escapeHtml(ing.quantity_unit)}${ing.is_extra ? ' &middot; <span class="text-blue-500 font-medium">Extra</span>' : ' &middot; Default'}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <input type="number" min="0" step="0.01" value="${ing.quantity_used}"
                        onchange="updateIngredientQty('${productId}', ${index}, this.value)"
                        class="w-20 px-2 py-1.5 border border-blue-300 rounded-md text-center text-sm bg-blue-50 focus:outline-none focus:ring-1 focus:ring-blue-400" />
                    <span class="text-xs text-gray-400 whitespace-nowrap">&times;${qty} = ${totalUsed}</span>
                </div>
                <div class="flex-shrink-0">
                    ${ing.is_extra
                        ? `<button onclick="removeExtraIngredient('${productId}', ${index})" class="w-7 h-7 flex items-center justify-center rounded-md text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors" title="Remove">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                           </button>`
                        : `<span class="w-7 h-7 flex items-center justify-center text-gray-300" title="Default ingredient">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                           </span>`}
                </div>
            </div>
        `;
    });

    // "Add Ingredient" section
    let addIngredientHtml = '';
    if (inventoryItemsCache) {
        const existingIds = ingredients.map(i => i.inventory_id);
        const availableItems = inventoryItemsCache.filter(inv => !existingIds.includes(inv.item_id) && parseFloat(inv.available_stock || 0) > 0);

        if (availableItems.length > 0) {
            addIngredientHtml = `
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <p class="text-xs font-medium text-gray-500 mb-2 uppercase tracking-wide">Add Extra Ingredient</p>
                    <div class="flex items-center gap-2">
                        <select id="addIngredientSelect_${productId}" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-blue-400 focus:border-blue-400">
                            <option value="">Select ingredient...</option>
                            ${availableItems.map(inv =>
                                `<option value="${inv.item_id}" data-name="${escapeHtml(inv.item_name)}" data-unit="${escapeHtml(inv.quantity_unit)}" data-cost="${inv.unit_cost}">${escapeHtml(inv.item_name)} (${escapeHtml(inv.quantity_unit)})</option>`
                            ).join('')}
                        </select>
                        <button onclick="addExtraIngredient('${productId}')" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-1 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"/>
                            </svg>
                            Add
                        </button>
                    </div>
                </div>
            `;
        }
    } else {
        addIngredientHtml = '<p class="text-xs text-gray-400 mt-2">Loading inventory items...</p>';
    }

    container.innerHTML = `
        <div class="text-label text-blue-600 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <span>Editable Inventory Items</span>
            <span class="text-xs text-blue-400 font-normal">(Manual Mode)</span>
        </div>
        <div class="space-y-2">
            ${ingredientRows}
        </div>
        ${addIngredientHtml}
    `;
}

// ==================== MANUAL MODE HELPERS ====================
function setCustomPrice(productId, value) {
    const item = cart.find(c => c.product_id === productId);
    if (!item) return;

    const newPrice = parseFloat(value);
    if (isNaN(newPrice) || newPrice < 0) return;

    if (newPrice === item.price) {
        delete item.custom_price;
    } else {
        item.custom_price = newPrice;
    }

    updateOrderSummary();
    renderCartItems();
}

function updateIngredientQty(productId, index, value) {
    const item = cart.find(c => c.product_id === productId);
    if (!item) return;

    const newQty = parseFloat(value);
    if (isNaN(newQty) || newQty < 0) return;

    // Initialize custom_ingredients from defaults if not yet done
    if (!item.custom_ingredients) {
        item.custom_ingredients = (item._defaultRequirements || []).map(req => ({
            inventory_id: req.inventory_id,
            item_name: req.item_name,
            quantity_unit: req.quantity_unit,
            quantity_used: parseFloat(req.quantity_used),
            unit_cost: parseFloat(req.unit_cost),
            is_extra: false
        }));
    }

    if (item.custom_ingredients[index]) {
        item.custom_ingredients[index].quantity_used = newQty;
    }
}

function addExtraIngredient(productId) {
    const item = cart.find(c => c.product_id === productId);
    if (!item) return;

    const select = document.getElementById(`addIngredientSelect_${productId}`);
    if (!select || !select.value) return;

    const option = select.options[select.selectedIndex];
    const invItem = {
        inventory_id: select.value,
        item_name: option.dataset.name,
        quantity_unit: option.dataset.unit,
        quantity_used: 1,
        unit_cost: parseFloat(option.dataset.cost) || 0,
        is_extra: true
    };

    // Initialize custom_ingredients from defaults if not yet done
    if (!item.custom_ingredients) {
        item.custom_ingredients = (item._defaultRequirements || []).map(req => ({
            inventory_id: req.inventory_id,
            item_name: req.item_name,
            quantity_unit: req.quantity_unit,
            quantity_used: parseFloat(req.quantity_used),
            unit_cost: parseFloat(req.unit_cost),
            is_extra: false
        }));
    }

    item.custom_ingredients.push(invItem);

    // Re-render the details section
    const detailEl = document.querySelector(`[data-product-detail="${productId}"]`);
    if (detailEl) {
        renderEditableIngredients(productId, item._defaultRequirements || [], detailEl);
    }
}

function removeExtraIngredient(productId, index) {
    const item = cart.find(c => c.product_id === productId);
    if (!item || !item.custom_ingredients) return;

    item.custom_ingredients.splice(index, 1);

    const detailEl = document.querySelector(`[data-product-detail="${productId}"]`);
    if (detailEl) {
        renderEditableIngredients(productId, item._defaultRequirements || [], detailEl);
    }
}

// ==================== ORDER CONFIRMATION ====================
function confirmOrder() {
    if (orderConfirmed) return;

    if (cart.length === 0) {
        showWarningModal('Your cart is empty.');
        return;
    }

    const amountPaid = parseFloat(document.getElementById('amountPaidInput').value) || 0;
    const total = getCartTotal();

    if (amountPaid <= 0) {
        showErrorModal('Please enter the amount paid.');
        return;
    }

    if (amountPaid < total) {
        showErrorModal('Insufficient payment. The total is ₱' + total.toFixed(2));
        return;
    }

    // Disable button
    const btn = document.getElementById('confirmOrderBtn');
    btn.disabled = true;
    btn.textContent = 'Processing...';
    btn.classList.add('opacity-50', 'cursor-not-allowed');

    const payload = {
        items: cart.map(item => {
            const entry = {
                product_id: item.product_id,
                quantity: item.quantity
            };
            if (item.custom_price !== undefined) {
                entry.custom_price = item.custom_price;
            }
            if (item.custom_ingredients) {
                entry.custom_ingredients = item.custom_ingredients.map(ing => ({
                    inventory_id: ing.inventory_id,
                    quantity_used: ing.quantity_used
                }));
            }
            return entry;
        }),
        amount_paid: amountPaid
    };

    fetch('../api/cs-confirm-sale.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentSaleId = data.sale_id;
                orderConfirmed = true;

                btn.textContent = 'Order Confirmed';

                // Show receipt modal with sale data
                openReceiptModal(data.sale_id);
            } else {
                showErrorModal(data.message || 'Failed to confirm order');
                btn.disabled = false;
                btn.textContent = 'Confirm Order';
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        })
        .catch(err => {
            console.error('Confirm order error:', err);
            showErrorModal('An error occurred while processing the order.');
            btn.disabled = false;
            btn.textContent = 'Confirm Order';
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        });
}

// ==================== RECEIPT MODAL ====================
function openReceiptModal(saleId) {
    const modal = document.getElementById('receiptModal');
    const container = document.getElementById('receiptContent');

    container.innerHTML = '<p class="text-center text-gray-400 py-8 text-regular">Loading receipt...</p>';
    modal.classList.remove('hidden');

    fetch(`../api/cs-get-receipt.php?sale_id=${saleId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderReceipt(data.sale, data.details);
            } else {
                container.innerHTML = '<p class="text-center text-red-500 py-4 text-regular">Failed to load receipt</p>';
            }
        })
        .catch(() => {
            container.innerHTML = '<p class="text-center text-red-500 py-4 text-regular">Error loading receipt</p>';
        });
}

function closeReceiptModal() {
    document.getElementById('receiptModal').classList.add('hidden');
}

function closeReceiptAndGoToProducts() {
    closeReceiptModal();
    resetTransaction();
}

function printReceipt() {
    const receiptContent = document.getElementById('receiptContent').innerHTML;

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
                .modified-badge { color: #d97706; font-style: italic; }
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

function renderReceipt(sale, details) {
    const container = document.getElementById('receiptContent');

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

    let detailsHtml = '';
    details.forEach(d => {
        const isManual = parseInt(d.is_manual) === 1;
        const modifiedBadge = isManual ? ' <span class="text-xs modified-badge" style="color: #d97706; font-style: italic;">*</span>' : '';
        detailsHtml += `
            <div class="grid grid-cols-4 gap-1 text-sm py-1">
                <span class="text-center">${d.quantity}</span>
                <span class="truncate" title="${escapeHtml(d.product_name)}">${escapeHtml(d.product_code)}${modifiedBadge}</span>
                <span class="text-right">&#8369; ${parseFloat(d.price_per_unit).toFixed(2)}</span>
                <span class="text-right">&#8369; ${parseFloat(d.subtotal).toFixed(2)}</span>
            </div>
        `;
    });

    // Modified order notice
    const modifiedNotice = hasModifiedItems
        ? `<div class="text-center mb-3 py-2 rounded text-xs" style="background-color: #fffbeb; border: 1px solid #fde68a; color: #92400e;">
            <span class="font-semibold">Note:</span> Items marked with * have been manually modified
           </div>`
        : '';

    container.innerHTML = `
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
            <p class="text-warning font-semibold text-lg">&#10033; THANK YOU &#10033;</p>
        </div>
    `;
}

// ==================== RESET ====================
function confirmResetTransaction() {
    if (cart.length === 0) {
        // Nothing to reset, just go back
        resetTransaction();
        return;
    }
    document.getElementById('resetConfirmModal').classList.remove('hidden');
}

function closeResetConfirmModal() {
    document.getElementById('resetConfirmModal').classList.add('hidden');
}

function resetTransaction() {
    cart = [];
    currentSaleId = null;
    orderConfirmed = false;
    manualMode = false;

    // Reset manual mode toggle UI
    const toggleBtn = document.getElementById('manualModeToggle');
    if (toggleBtn) {
        toggleBtn.classList.remove('bg-black');
        toggleBtn.classList.add('bg-gray-300');
        toggleBtn.setAttribute('aria-checked', 'false');
        const knob = toggleBtn.querySelector('span');
        if (knob) {
            knob.classList.remove('translate-x-6');
            knob.classList.add('translate-x-1');
        }
    }

    // Reset UI
    document.getElementById('amountPaidInput').value = '0';
    closeReceiptModal();

    const btn = document.getElementById('confirmOrderBtn');
    btn.disabled = false;
    btn.textContent = 'Confirm Order';
    btn.classList.remove('opacity-50', 'cursor-not-allowed');

    // Reload products to refresh stock
    loadProducts();

    updateOrderSummary();
    renderCartItems();
    showProductView();
}

// ==================== UTILITIES ====================
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
