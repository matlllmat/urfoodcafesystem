// ============================================
// SEARCH & FILTER FUNCTIONS
// ============================================

// Search functionality
document.getElementById('searchInput').addEventListener('input', function (e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.item-card');

    cards.forEach(card => {
        const itemName = card.getAttribute('data-item-name');
        if (itemName.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
});

// Category dropdown functionality
let categoryDropdownOpen = false;

function toggleCategoryDropdown() {
    const menu = document.getElementById('categoryDropdownMenu');
    categoryDropdownOpen = !categoryDropdownOpen;

    if (categoryDropdownOpen) {
        menu.classList.remove('hidden');
    } else {
        menu.classList.add('hidden');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function (e) {
    const button = document.getElementById('categoryDropdownButton');
    const menu = document.getElementById('categoryDropdownMenu');

    if (!button.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.add('hidden');
        categoryDropdownOpen = false;
    }
});

// Handle "All Categories" checkbox
function handleAllCategoriesChange(checkbox) {
    if (checkbox.checked) {
        document.getElementById('noCategoryCheckbox').checked = false;
        const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
        categoryCheckboxes.forEach(cb => cb.checked = false);
        applyFiltersWithCategories('all_grouped', []);
    }
}

// Handle "No Category Grouping" checkbox
function handleNoCategoryChange(checkbox) {
    if (checkbox.checked) {
        document.getElementById('allCategoriesCheckbox').checked = false;
        const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
        categoryCheckboxes.forEach(cb => cb.checked = false);
        applyFiltersWithCategories('flat', []);
    }
}

// Handle individual category checkbox changes
function handleCategoryCheckboxChange() {
    document.getElementById('allCategoriesCheckbox').checked = false;
    document.getElementById('noCategoryCheckbox').checked = false;

    const categoryCheckboxes = document.querySelectorAll('.category-checkbox:checked');
    const selectedCategories = Array.from(categoryCheckboxes).map(cb => cb.value);

    if (selectedCategories.length === 0) {
        document.getElementById('noCategoryCheckbox').checked = true;
        applyFiltersWithCategories('flat', []);
    } else {
        applyFiltersWithCategories('grouped', selectedCategories);
    }
}

// Apply filters with category data
function applyFiltersWithCategories(viewMode, categories) {
    const status = document.getElementById('statusFilter').value;
    const sort = document.getElementById('sortFilter').value;

    const params = new URLSearchParams();
    params.append('page', 'manage-inventory');
    params.append('view_mode', viewMode);

    if (categories.length > 0) {
        categories.forEach(cat => {
            params.append('categories[]', cat);
        });
    }

    if (status) params.append('status', status);
    if (sort) params.append('sort', sort);

    window.location.href = 'main.php?' + params.toString();
}

// Apply filters (for status and sort dropdowns)
function applyFilters() {
    const allCategoriesChecked = document.getElementById('allCategoriesCheckbox').checked;
    const noCategoryChecked = document.getElementById('noCategoryCheckbox').checked;
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox:checked');
    const selectedCategories = Array.from(categoryCheckboxes).map(cb => cb.value);

    let viewMode = 'flat';
    let categories = [];

    if (allCategoriesChecked) {
        viewMode = 'all_grouped';
    } else if (noCategoryChecked) {
        viewMode = 'flat';
    } else if (selectedCategories.length > 0) {
        viewMode = 'grouped';
        categories = selectedCategories;
    }

    applyFiltersWithCategories(viewMode, categories);
}