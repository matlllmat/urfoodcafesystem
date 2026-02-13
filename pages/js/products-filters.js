// ============================================
// PRODUCTS FILTERS - Search, Status & Sort
// ============================================

// Client-side search filtering
document.getElementById('mpSearchInput').addEventListener('input', function (e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.product-card');

    cards.forEach(card => {
        const productName = card.getAttribute('data-product-name');
        if (productName.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
});

// Server-side filters (status + sort + category trigger page reload)
function applyProductFilters() {
    const status = document.getElementById('mpStatusFilter').value;
    const category = document.getElementById('mpCategoryFilter').value;
    const sort = document.getElementById('mpSortFilter').value;

    const params = new URLSearchParams();
    params.append('page', 'manage-products');
    params.append('status', status);
    if (category) params.append('category', category);
    if (sort) params.append('sort', sort);

    window.location.href = 'main.php?' + params.toString();
}
