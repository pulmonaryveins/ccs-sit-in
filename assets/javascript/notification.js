/**
 * Notification System
 * Provides consistent toast notifications across the application
 */

/**
 * Show a toast notification
 * @param {string} title Notification title
 * @param {string} message Notification message
 * @param {string} type Notification type (info, success, warning, error)
 * @param {number} duration Duration in milliseconds (0 for no auto-hide)
 * @returns {HTMLElement} Notification element
 */
function showNotification(title, message, type = 'info', duration = 5000) {
    const notificationContainer = document.getElementById('notification-container');
    
    if (!notificationContainer) {
        console.error('Notification container not found');
        return null;
    }
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    let icon = 'information-line';
    if (type === 'success') icon = 'check-line';
    if (type === 'error') icon = 'error-warning-line';
    if (type === 'warning') icon = 'alert-line';
    
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="ri-${icon}"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">${title}</div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close" onclick="closeNotification(this)">&times;</button>
    `;
    
    notificationContainer.appendChild(notification);
    
    // Force reflow to enable animation
    notification.getBoundingClientRect();
    notification.classList.add('show');
    
    if (duration > 0) {
        setTimeout(() => closeNotification(notification), duration);
    }
    
    return notification;
}

/**
 * Close a notification
 * @param {HTMLElement|Node} notification Notification element or close button
 */
function closeNotification(notification) {
    if (!notification) return;
    
    if (notification.tagName === 'BUTTON') {
        notification = notification.closest('.notification');
    }
    
    notification.classList.remove('show');
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.parentElement.removeChild(notification);
        }
    }, 300);
}

/**
 * Show loading indicator in a container
 * @param {HTMLElement} container Container to show loading in
 * @param {string} message Loading message
 */
function showLoading(container, message = 'Loading...') {
    if (!container) return;
    
    container.innerHTML = `
        <div class="notification-loading">
            <div class="spinner"></div>
            <p>${message}</p>
        </div>
    `;
}

/**
 * Remove loading indicator from a container
 * @param {HTMLElement} container Container with loading indicator
 */
function hideLoading(container) {
    if (!container) return;
    
    const loading = container.querySelector('.notification-loading');
    if (loading) {
        loading.remove();
    }
}