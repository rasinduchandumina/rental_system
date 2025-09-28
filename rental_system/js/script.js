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
    // Set minimum date for rental forms
    const today = new Date().toISOString().split('T')[0];
    const rentalDateInput = document.getElementById('rentalDate');
    const returnDateInput = document.getElementById('returnDate');
    
    if (rentalDateInput) {
        rentalDateInput.min = today;
        rentalDateInput.addEventListener('change', updateReturnDateMin);
    }
    
    if (returnDateInput) {
        returnDateInput.min = today;
    }
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
    
    // Form event listeners
    setupFormListeners();
    
    // FAQ toggle listeners
    setupFAQListeners();
    
    // Star rating listeners
    setupStarRatingListeners();
    
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

// Setup form event listeners
function setupFormListeners() {
    // Rental form
    const rentalForm = document.getElementById('rentalForm');
    if (rentalForm) {
        rentalForm.addEventListener('submit', handleRentalSubmit);
        
        // Update total cost when dates or quantity change
        const dateInputs = rentalForm.querySelectorAll('input[type="date"], input[type="number"]');
        dateInputs.forEach(input => {
            input.addEventListener('change', calculateTotalCost);
        });
    }
    
    // Feedback form
    const feedbackForm = document.getElementById('feedbackForm');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', handleFeedbackSubmit);
    }
    
    // Contact form
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactSubmit);
    }
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

// Setup star rating listeners
function setupStarRatingListeners() {
    const stars = document.querySelectorAll('.star');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            selectedRating = parseInt(this.dataset.rating);
            updateStarDisplay();
            document.getElementById('ratingValue').value = selectedRating;
        });
        
        star.addEventListener('mouseover', function() {
            const rating = parseInt(this.dataset.rating);
            highlightStars(rating);
        });
    });
    
    const ratingContainer = document.querySelector('.rating');
    if (ratingContainer) {
        ratingContainer.addEventListener('mouseleave', function() {
            updateStarDisplay();
        });
    }
}

// Update star display
function updateStarDisplay() {
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < selectedRating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

// Highlight stars on hover
function highlightStars(rating) {
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
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
        const response = await fetch('api/items.php');
        const data = await response.json();
        
        if (data.success) {
            items = data.items;
            displayItems(items);
        } else {
            showError('Failed to load items');
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
    
    if (itemsToShow.length === 0) {
        itemsGrid.innerHTML = '<p class="no-results">No tools found matching your criteria.</p>';
        return;
    }
    
    itemsGrid.innerHTML = itemsToShow.map(item => `
        <div class="item-card" data-item-id="${item.id}">
            <div class="item-image">
                ${item.image_url ? 
                    `<img src="${item.image_url}" alt="${item.name}" style="width:100%;height:100%;object-fit:cover;">` : 
                    `<i class="fas fa-tools"></i>`
                }
            </div>
            <div class="item-content">
                <h3>${item.name}</h3>
                <p>${item.description}</p>
                ${item.specifications ? `<div class="item-specs"><strong>Specs:</strong> ${item.specifications}</div>` : ''}
                <div class="item-footer">
                    <div class="item-info">
                        <div class="price">$${parseFloat(item.price_per_day).toFixed(2)}/day</div>
                        <div class="availability ${item.available_quantity > 0 ? 'in-stock' : 'out-of-stock'}">
                            ${item.available_quantity > 0 ? `${item.available_quantity} available` : 'Out of stock'}
                        </div>
                    </div>
                    <button class="rent-button" onclick="openRentalModal(${item.id})" ${item.available_quantity <= 0 ? 'disabled' : ''}>
                        ${item.available_quantity > 0 ? 'Rent Now' : 'Unavailable'}
                    </button>
                </div>
            </div>
        </div>
    `).join('');
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
            item.description.toLowerCase().includes(searchTerm) ||
            (item.specifications && item.specifications.toLowerCase().includes(searchTerm))
        );
    }
    
    // Filter by category
    if (categoryId) {
        filteredItems = filteredItems.filter(item => item.category_id == categoryId);
    }
    
    displayItems(filteredItems);
}

// Open rental modal
function openRentalModal(itemId) {
    const item = items.find(i => i.id == itemId);
    if (!item) return;
    
    document.getElementById('itemId').value = itemId;
    document.getElementById('rentalModal').style.display = 'block';
    
    // Store item price for calculation
    document.getElementById('rentalModal').dataset.itemPrice = item.price_per_day;
    document.getElementById('rentalModal').dataset.maxQuantity = item.available_quantity;
    
    // Set max quantity
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
        quantityInput.max = item.available_quantity;
    }
    
    calculateTotalCost();
}

// Update return date minimum when rental date changes
function updateReturnDateMin() {
    const rentalDate = document.getElementById('rentalDate').value;
    const returnDateInput = document.getElementById('returnDate');
    
    if (rentalDate && returnDateInput) {
        const nextDay = new Date(rentalDate);
        nextDay.setDate(nextDay.getDate() + 1);
        returnDateInput.min = nextDay.toISOString().split('T')[0];
        calculateTotalCost();
    }
}

// Calculate total rental cost
function calculateTotalCost() {
    const modal = document.getElementById('rentalModal');
    const rentalDate = document.getElementById('rentalDate')?.value;
    const returnDate = document.getElementById('returnDate')?.value;
    const quantity = document.getElementById('quantity')?.value || 1;
    const pricePerDay = parseFloat(modal?.dataset.itemPrice || 0);
    
    if (rentalDate && returnDate && pricePerDay) {
        const startDate = new Date(rentalDate);
        const endDate = new Date(returnDate);
        const timeDiff = endDate - startDate;
        const daysDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
        
        if (daysDiff > 0) {
            const totalCost = daysDiff * pricePerDay * quantity;
            document.getElementById('totalCost').textContent = totalCost.toFixed(2);
        } else {
            document.getElementById('totalCost').textContent = '0.00';
        }
    }
}

// Handle rental form submission
async function handleRentalSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');
    
    try {
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
        
        const response = await fetch('api/rentals.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Rental request submitted successfully! We will contact you soon.');
            document.getElementById('rentalModal').style.display = 'none';
            e.target.reset();
            // Refresh items to update availability
            loadItems();
        } else {
            showError(data.message || 'Failed to submit rental request');
        }
    } catch (error) {
        console.error('Error submitting rental:', error);
        showError('Error connecting to server');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Submit Rental Request';
    }
}

// Handle feedback form submission
async function handleFeedbackSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');
    
    try {
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
        
        const response = await fetch('api/feedback.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Thank you for your feedback!');
            document.getElementById('feedbackModal').style.display = 'none';
            e.target.reset();
            selectedRating = 0;
            updateStarDisplay();
        } else {
            showError(data.message || 'Failed to submit feedback');
        }
    } catch (error) {
        console.error('Error submitting feedback:', error);
        showError('Error connecting to server');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Submit Feedback';
    }
}

// Handle contact form submission
async function handleContactSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');
    
    try {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        
        const response = await fetch('api/contact.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Message sent successfully! We will get back to you soon.');
            e.target.reset();
        } else {
            showError(data.message || 'Failed to send message');
        }
    } catch (error) {
        console.error('Error sending message:', error);
        showError('Error connecting to server');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
    }
}

// Utility functions
function scrollToItems() {
    const itemsSection = document.getElementById('items-section');
    if (itemsSection) {
        itemsSection.scrollIntoView({ behavior: 'smooth' });
    }
}

function openFeedbackModal() {
    document.getElementById('feedbackModal').style.display = 'block';
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
`;
document.head.appendChild(notificationStyles);