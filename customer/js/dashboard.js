// Global variables
let products = [];
let totalProducts = 0;
let totalOrders = 0;
let totalRevenue = 0;

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function () {
    initializeDashboard();
    setupImagePreview();
});

function initializeDashboard() {
    // Animate counter numbers
    animateCounters();

    // Add hover effects to dashboard cards
    addCardHoverEffects();

    // Setup notification system
    setupNotifications();
}

function animateCounters() {
    const counters = document.querySelectorAll('.stat-number');
    counters.forEach(counter => {
        const target = parseInt(counter.innerText.replace(/[^\d]/g, '')) || 0;
        const increment = target / 100;
        let current = 0;

        if (target > 0) {
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }

                if (counter.innerText.includes('₱')) {
                    counter.innerText = '₱' + Math.floor(current).toLocaleString() + '.00';
                } else {
                    counter.innerText = Math.floor(current).toLocaleString();
                }
            }, 20);
        }
    });
}

function addCardHoverEffects() {
    const dashboardCards = document.querySelectorAll('.dashboard-card');
    dashboardCards.forEach(card => {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });

        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
}

function setupNotifications() {
    // Notification system for success messages
    window.showNotification = function (message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: var(--shadow-lg);
            border: none;
            border-radius: var(--border-radius);
        `;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
}

// Modal Functions
function openAddProductModal() {
    const modal = new bootstrap.Modal(document.getElementById('addProductModal'));
    modal.show();
}

function setupImagePreview() {
    const imageInput = document.getElementById('productImages');
    const previewContainer = document.getElementById('imagePreview');

    imageInput.addEventListener('change', function (e) {
        previewContainer.innerHTML = '';
        const files = e.target.files;

        if (files.length > 0) {
            previewContainer.style.display = 'block';

            Array.from(files).forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const imageContainer = document.createElement('div');
                        imageContainer.className = 'col-md-3 mb-2';
                        imageContainer.innerHTML = `
                            <div class="position-relative">
                                <img src="${e.target.result}" class="img-thumbnail" style="width: 100%; height: 100px; object-fit: cover;" alt="Preview ${index + 1}">
                                ${index === 0 ? '<span class="badge bg-primary position-absolute top-0 start-0 m-1">Main</span>' : ''}
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" onclick="removeImage(${index})">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        `;

                        if (previewContainer.children.length === 0) {
                            previewContainer.innerHTML = '<div class="row g-2"></div>';
                        }
                        previewContainer.querySelector('.row').appendChild(imageContainer);
                    };
                    reader.readAsDataURL(file);
                }
            });
        } else {
            previewContainer.style.display = 'none';
        }
    });
}

function removeImage(index) {
    const imageInput = document.getElementById('productImages');
    const files = Array.from(imageInput.files);

    // Create new FileList without the removed file
    const dt = new DataTransfer();
    files.forEach((file, i) => {
        if (i !== index) {
            dt.items.add(file);
        }
    });

    imageInput.files = dt.files;
    imageInput.dispatchEvent(new Event('change'));
}

function submitProduct() {
    const form = document.getElementById('addProductForm');
    const submitBtn = document.querySelector('.modal-footer .btn-primary');
    const spinner = submitBtn.querySelector('.loading-spinner');
    const btnText = submitBtn.querySelector('i');

    // Validate required fields
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    // Additional validation
    const price = parseFloat(document.getElementById('productPrice').value);
    const stock = parseInt(document.getElementById('productStock').value);
    const categoryId = parseInt(document.getElementById('productCategory').value);

    if (price <= 0) {
        document.getElementById('productPrice').classList.add('is-invalid');
        isValid = false;
    }

    if (stock < 0) {
        document.getElementById('productStock').classList.add('is-invalid');
        isValid = false;
    }

    if (categoryId <= 0) {
        document.getElementById('productCategory').classList.add('is-invalid');
        isValid = false;
    }

    if (!isValid) {
        showNotification('Please fill in all required fields with valid values.', 'danger');
        return;
    }

    // Show loading state
    spinner.style.display = 'inline-block';
    btnText.style.display = 'none';
    submitBtn.disabled = true;

    // Prepare form data for submission
    const formData = new FormData(form);

    // Make actual API call to save to database
    fetch('add_product_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Add to local products array
            products.push(data.product);

            // Update dashboard stats
            updateDashboardStats();

            // Close modal and reset form
            const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
            modal.hide();
            form.reset();
            document.getElementById('imagePreview').style.display = 'none';

            // Show success message
            showNotification(data.message, 'success');

            // Add to recent activity
            addRecentActivity('product', data.product.name);

            // Optionally refresh the page to show updated data
            setTimeout(() => {
                location.reload();
            }, 2000);

        } else {
            throw new Error(data.message || 'Failed to add product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error: ' + error.message, 'danger');
    })
    .finally(() => {
        // Reset loading state
        spinner.style.display = 'none';
        btnText.style.display = 'inline';
        submitBtn.disabled = false;
    });
}

function updateDashboardStats() {
    totalProducts = products.length;

    // Update product count
    const productCounter = document.querySelector('.dashboard-card .stat-number');
    if (productCounter) {
        animateNumber(productCounter, totalProducts);
    }
}

function animateNumber(element, target) {
    const current = parseInt(element.innerText) || 0;
    const increment = (target - current) / 20;
    let step = current;

    const timer = setInterval(() => {
        step += increment;
        if ((increment > 0 && step >= target) || (increment < 0 && step <= target)) {
            step = target;
            clearInterval(timer);
        }
        element.innerText = Math.floor(step).toLocaleString();
    }, 50);
}

function addRecentActivity(type, title) {
    const activityContainer = document.querySelector('.recent-activity');
    const emptyState = activityContainer.querySelector('.text-center');

    if (emptyState) {
        emptyState.remove();
    }

    const activityItem = document.createElement('div');
    activityItem.className = 'activity-item';
    activityItem.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="activity-icon" style="background: ${type === 'order' ? 'var(--success)' : 'var(--primary)'};">
                <i class="bi bi-${type === 'order' ? 'cart-check' : 'box-seam'}"></i>
            </div>
            <div>
                <h6 class="mb-1">
                    ${type === 'order' ? 'New Order: ' : 'Product Added: '}${title}
                </h6>
                <p class="text-muted mb-0">
                    ${new Date().toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
    })}
                </p>
            </div>
        </div>
    `;

    // Insert at the beginning
    const firstActivity = activityContainer.querySelector('.activity-item');
    if (firstActivity) {
        activityContainer.insertBefore(activityItem, firstActivity);
    } else {
        activityContainer.appendChild(activityItem);
    }

    // Keep only the last 5 activities
    const activities = activityContainer.querySelectorAll('.activity-item');
    if (activities.length > 5) {
        activities[activities.length - 1].remove();
    }
}

// Add category validation styling
document.getElementById('productCategory').addEventListener('change', function() {
    this.classList.remove('is-invalid');
    if (this.value) {
        this.classList.add('is-valid');
    }
});

// Auto-generate SKU based on product name (if SKU field exists)
const productNameInput = document.getElementById('productName');
if (productNameInput) {
    productNameInput.addEventListener('input', function (e) {
        const skuField = document.getElementById('productSKU');
        if (skuField && !skuField.value && e.target.value) {
            const sku = e.target.value
                .toUpperCase()
                .replace(/[^A-Z0-9]/g, '')
                .substring(0, 6) + '-' + Math.random().toString(36).substr(2, 3).toUpperCase();
            skuField.value = sku;
        }
    });
}

// Price formatting
const priceInput = document.getElementById('productPrice');
if (priceInput) {
    priceInput.addEventListener('blur', function (e) {
        if (e.target.value) {
            e.target.value = parseFloat(e.target.value).toFixed(2);
        }
    });
}

// Add click effects to buttons
document.querySelectorAll('.btn, .action-btn[href]').forEach(btn => {
    btn.addEventListener('click', function (e) {
        // Create ripple effect
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple 0.6s linear;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            pointer-events: none;
        `;

        this.style.position = 'relative';
        this.style.overflow = 'hidden';
        this.appendChild(ripple);

        setTimeout(() => {
            if (ripple.parentNode) {
                ripple.remove();
            }
        }, 600);
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function (e) {
    // Alt + N to open add product modal
    if (e.altKey && e.key === 'n') {
        e.preventDefault();
        openAddProductModal();
    }

    // Escape to close modal
    if (e.key === 'Escape') {
        const modal = document.querySelector('.modal.show');
        if (modal) {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        }
    }
});

// Add tooltip to keyboard shortcut
const addProductLink = document.querySelector('a[onclick="openAddProductModal()"]');
if (addProductLink) {
    addProductLink.title = 'Alt + N';
}