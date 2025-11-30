// Global variables
let items = [];
let categories = [];
let selectedRating = 0;

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    loadCategories();
    loadItems();
});

// Initialize the application
function initializeApp() {
    // Nothing specific needed for canteen initialization
}

// Setup event listeners
function setupEventListeners() {
    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
        
        // Close mobile menu when clicking on a link
        document.querySelectorAll('.nav-link').forEach(n => n.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
        }));
    }
    
    // Modal event listeners
    setupModalListeners();
    
    // FAQ toggle listeners
    setupFAQListeners();
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(searchItems, 300));
    }
    
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', searchItems);
    }
}

// Setup modal event listeners
function setupModalListeners() {
    // Get all modals
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.close');
    
    // Close modals when clicking the close button
    closeButtons.forEach(closeButton => {
        closeButton.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.style.display = 'none';
        });
    });
    
    // Close modals when clicking outside
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
}

// Setup FAQ toggle listeners
function setupFAQListeners() {
    const faqQuestions = document.querySelectorAll('.faq-question');
    faqQuestions.forEach(question => {
        question.addEventListener('click', function() {
            const faqItem = this.parentElement;
            const isActive = faqItem.classList.contains('active');
            
            // Close all FAQ items
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Toggle current item
            if (!isActive) {
                faqItem.classList.add('active');
            }
        });
    });
}

// Load categories from API
async function loadCategories() {
    try {
        const response = await fetch('api/categories.php');
        const data = await response.json();
        
        if (data.success) {
            categories = data.categories;
            populateCategoryFilter();
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Populate category filter dropdown
function populateCategoryFilter() {
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter && categories.length > 0) {
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            categoryFilter.appendChild(option);
        });
    }
}

// Load items from API
async function loadItems() {
    try {
        showLoading();
        const response = await fetch('api/food_items.php');
        const data = await response.json();
        
        if (data.success) {
            items = data.items;
            displayItems(items);
        } else {
            showError('Failed to load menu');
        }
    } catch (error) {
        console.error('Error loading items:', error);
        showError('Error connecting to server');
    } finally {
        hideLoading();
    }
}

// Display items in the grid
function displayItems(itemsToShow) {
    const itemsGrid = document.getElementById('items-grid');
    if (!itemsGrid) return;
    
    // Filter only available items for public view
    const availableItems = itemsToShow.filter(item => item.available == 1);
    
    if (availableItems.length === 0) {
        itemsGrid.innerHTML = '<p class="no-results">No food items available at the moment.</p>';
        return;
    }
    
    const icons = {
        'Breakfast': 'fa-egg',
        'Lunch': 'fa-bowl-food',
        'Snacks': 'fa-cookie',
        'Beverages': 'fa-glass-water',
        'Desserts': 'fa-ice-cream'
    };
    
    itemsGrid.innerHTML = availableItems.map(item => `
        <div class="item-card" data-item-id="${item.id}">
            <div class="item-image">
                ${item.image_url ? 
                    `<img src="${item.image_url}" alt="${item.name}" style="width:100%;height:100%;object-fit:cover;">` : 
                    `<i class="fas ${icons[item.category_name] || 'fa-utensils'}"></i>`
                }
            </div>
            <div class="item-content">
                <h3>${item.name}</h3>
                <p>${item.description || ''}</p>
                <div class="item-footer">
                    <div class="item-info">
                        <div class="price">$${parseFloat(item.price).toFixed(2)}</div>
                        <div class="availability in-stock">
                            ${item.category_name || 'Food'}
                        </div>
                    </div>
                    <button class="rent-button" onclick="showLoginRequired()">
                        Order Now
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Show login required modal
function showLoginRequired() {
    const modal = document.getElementById('loginRequiredModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

// Search and filter items
function searchItems() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const categoryId = document.getElementById('categoryFilter')?.value || '';
    
    let filteredItems = items;
    
    // Filter by search term
    if (searchTerm) {
        filteredItems = filteredItems.filter(item => 
            item.name.toLowerCase().includes(searchTerm) ||
            (item.description && item.description.toLowerCase().includes(searchTerm))
        );
    }
    
    // Filter by category
    if (categoryId) {
        filteredItems = filteredItems.filter(item => item.category_id == categoryId);
    }
    
    displayItems(filteredItems);
}

// Utility functions
function scrollToItems() {
    const itemsSection = document.getElementById('items-section');
    if (itemsSection) {
        itemsSection.scrollIntoView({ behavior: 'smooth' });
    }
}

function showLoading() {
    const loading = document.getElementById('loading');
    if (loading) {
        loading.style.display = 'block';
    }
}

function hideLoading() {
    const loading = document.getElementById('loading');
    if (loading) {
        loading.style.display = 'none';
    }
}

function showSuccess(message) {
    showNotification(message, 'success');
}

function showError(message) {
    showNotification(message, 'error');
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
        color: white;
        padding: 1rem;
        border-radius: 5px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
    `;
    
    document.body.appendChild(notification);
    
    // Close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.remove();
    });
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add notification styles to head
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        margin-left: auto;
    }
    
    .modal-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
        justify-content: center;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #e74c3c, #f39c12);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-secondary {
        background: #f8f9fa;
        color: #333;
        border: 2px solid #ddd;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        display: inline-block;
    }
    
    .login-required-content {
        text-align: center;
        padding: 1rem;
    }
    
    .login-required-content h2 {
        color: #2c3e50;
        margin-bottom: 1rem;
    }
    
    .login-required-content p {
        color: #666;
        margin-bottom: 1.5rem;
    }
`;
document.head.appendChild(notificationStyles);