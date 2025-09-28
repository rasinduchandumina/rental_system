// Admin Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeAdmin();
});

function initializeAdmin() {
    // Initialize tooltips, charts, and other admin features
    setupMobileMenu();
    setupModals();
    setupDataTables();
    setupFormValidation();
    setupNotifications();
    
    // Auto-refresh dashboard stats every 30 seconds
    if(window.location.pathname.includes('dashboard.php')) {
        setInterval(refreshDashboardStats, 30000);
    }
    
    // Mark current page as active in sidebar
    markActivePage();
}

// Mobile menu functionality
function setupMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if(hamburger && sidebar) {
        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if(window.innerWidth <= 768 && 
               !sidebar.contains(e.target) && 
               !hamburger.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
}

// Modal functionality
function setupModals() {
    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    // Close modals with escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    modal.style.display = 'none';
                }
            });
        }
    });
}

// Data table enhancements
function setupDataTables() {
    // Add sorting functionality to tables
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        const headers = table.querySelectorAll('th');
        headers.forEach((header, index) => {
            if(!header.classList.contains('no-sort')) {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => sortTable(table, index));
                header.innerHTML += ' <i class="fas fa-sort sort-icon"></i>';
            }
        });
    });
}

// Form validation
function setupFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if(!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    showFieldError(field, 'This field is required');
                } else {
                    field.classList.remove('error');
                    hideFieldError(field);
                }
            });
            
            // Validate email fields
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(field => {
                if(field.value && !isValidEmail(field.value)) {
                    isValid = false;
                    field.classList.add('error');
                    showFieldError(field, 'Please enter a valid email address');
                }
            });
            
            // Validate number fields
            const numberFields = form.querySelectorAll('input[type="number"]');
            numberFields.forEach(field => {
                if(field.value && parseFloat(field.value) < 0) {
                    isValid = false;
                    field.classList.add('error');
                    showFieldError(field, 'Please enter a positive number');
                }
            });
            
            if(!isValid) {
                e.preventDefault();
                showNotification('Please fix the errors in the form', 'error');
            }
        });
    });
}

// Notification system
function setupNotifications() {
    // Auto-hide success messages after 5 seconds
    const successAlerts = document.querySelectorAll('.alert-success');
    successAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

// Sort table function
function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isNumeric = rows.every(row => {
        const cell = row.cells[column];
        return cell && !isNaN(parseFloat(cell.textContent.replace(/[^0-9.-]/g, '')));
    });
    
    const sortedRows = rows.sort((a, b) => {
        const aVal = a.cells[column].textContent.trim();
        const bVal = b.cells[column].textContent.trim();
        
        if(isNumeric) {
            return parseFloat(aVal.replace(/[^0-9.-]/g, '')) - parseFloat(bVal.replace(/[^0-9.-]/g, ''));
        } else {
            return aVal.localeCompare(bVal);
        }
    });
    
    // Clear tbody and append sorted rows
    tbody.innerHTML = '';
    sortedRows.forEach(row => tbody.appendChild(row));
    
    // Update sort icons
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        const icon = header.querySelector('.sort-icon');
        if(icon) {
            if(index === column) {
                icon.className = 'fas fa-sort-up sort-icon';
            } else {
                icon.className = 'fas fa-sort sort-icon';
            }
        }
    });
}

// Mark active page in sidebar
function markActivePage() {
    const currentPage = window.location.pathname.split('/').pop();
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
    
    sidebarLinks.forEach(link => {
        if(link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
}

// Refresh dashboard stats
async function refreshDashboardStats() {
    try {
        const response = await fetch('../api/dashboard_stats.php');
        const data = await response.json();
        
        if(data.success) {
            // Update stat cards
            updateStatCard('total_items', data.stats.total_items);
            updateStatCard('total_rentals', data.stats.total_rentals);
            updateStatCard('total_customers', data.stats.total_customers);
            updateStatCard('total_revenue', '$' + parseFloat(data.stats.total_revenue).toFixed(2));
        }
    } catch(error) {
        console.error('Error refreshing stats:', error);
    }
}

// Update stat card value
function updateStatCard(type, value) {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        if(card.classList.contains(type)) {
            const h3 = card.querySelector('h3');
            if(h3) {
                h3.textContent = value;
            }
        }
    });
}

// Utility functions
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i>
        ${message}
        <button class="close-alert" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    // Insert at the top of main content
    const mainContent = document.querySelector('.main-content');
    mainContent.insertBefore(notification, mainContent.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if(notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

function showFieldError(field, message) {
    // Remove existing error
    hideFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '0.8rem';
    errorDiv.style.marginTop = '0.2rem';
    
    field.parentNode.appendChild(errorDiv);
}

function hideFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if(existingError) {
        existingError.remove();
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Confirmation dialogs
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Export functions for global use
window.adminUtils = {
    showNotification,
    confirmDelete,
    sortTable,
    refreshDashboardStats
};

// Real-time updates (if WebSocket is implemented)
function setupRealTimeUpdates() {
    // This can be implemented with WebSocket for real-time notifications
    // For now, we'll use polling every 60 seconds
    setInterval(() => {
        if(document.querySelector('.activity-section')) {
            refreshRecentActivity();
        }
    }, 60000);
}

async function refreshRecentActivity() {
    try {
        const response = await fetch('../api/recent_activity.php');
        const data = await response.json();
        
        if(data.success) {
            updateRecentRentals(data.recent_rentals);
            updateRecentFeedback(data.recent_feedback);
        }
    } catch(error) {
        console.error('Error refreshing recent activity:', error);
    }
}

function updateRecentRentals(rentals) {
    const container = document.querySelector('.recent-rentals tbody');
    if(container && rentals) {
        container.innerHTML = rentals.map(rental => `
            <tr>
                <td>${rental.customer_name}</td>
                <td>${rental.item_name}</td>
                <td><span class="status status-${rental.status}">${rental.status}</span></td>
                <td>${new Date(rental.rental_date).toLocaleDateString()}</td>
                <td>$${parseFloat(rental.total_amount).toFixed(2)}</td>
            </tr>
        `).join('');
    }
}

function updateRecentFeedback(feedback) {
    const container = document.querySelector('.feedback-list');
    if(container && feedback) {
        container.innerHTML = feedback.map(fb => `
            <div class="feedback-item">
                <div class="feedback-header">
                    <strong>${fb.customer_name || 'Anonymous'}</strong>
                    <div class="feedback-rating">
                        ${'★'.repeat(fb.rating)}${'☆'.repeat(5-fb.rating)}
                    </div>
                    <span class="feedback-date">${new Date(fb.created_at).toLocaleDateString()}</span>
                </div>
                <div class="feedback-message">
                    ${fb.message.substring(0, 100)}${fb.message.length > 100 ? '...' : ''}
                </div>
            </div>
        `).join('');
    }
}

// Initialize real-time updates
setupRealTimeUpdates();

// Print functionality
function printReport() {
    window.print();
}

// CSV Export functionality
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if(!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for(let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for(let j = 0; j < cols.length; j++) {
            row.push(cols[j].innerText);
        }
        csv.push(row.join(','));
    }
    
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename + '.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Search functionality enhancement
function enhancedSearch(searchTerm, targetContainer) {
    const items = targetContainer.querySelectorAll('tr, .item, .card');
    let visibleCount = 0;
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        if(text.includes(searchTerm.toLowerCase())) {
            item.style.display = '';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Update results count if element exists
    const resultsCount = document.querySelector('.results-count');
    if(resultsCount) {
        resultsCount.textContent = `Showing ${visibleCount} results`;
    }
}

// Quick actions
function quickStatusUpdate(id, type, newStatus) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append(`${type}_id`, id);
    formData.append('status', newStatus);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        showNotification('Status updated successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        showNotification('Error updating status', 'error');
        console.error('Error:', error);
    });
}

// Add these styles for enhanced functionality
const adminStyles = `
    .field-error {
        color: #e74c3c !important;
        font-size: 0.8rem !important;
        margin-top: 0.2rem !important;
    }
    
    .error {
        border-color: #e74c3c !important;
    }
    
    .sort-icon {
        margin-left: 0.5rem;
        opacity: 0.5;
        transition: opacity 0.3s;
    }
    
    th:hover .sort-icon {
        opacity: 1;
    }
    
    .close-alert {
        background: none;
        border: none;
        color: inherit;
        float: right;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0;
        margin-left: 1rem;
    }
    
    .results-count {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 1rem;
    }
    
    @media print {
        .admin-sidebar,
        .admin-header,
        .action-buttons,
        .filters {
            display: none !important;
        }
        
        .main-content {
            margin-left: 0 !important;
            margin-top: 0 !important;
        }
    }
`;

// Inject admin styles
const styleSheet = document.createElement('style');
styleSheet.textContent = adminStyles;
document.head.appendChild(styleSheet);