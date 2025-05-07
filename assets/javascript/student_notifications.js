/**
 * Student Notifications Module
 * Handles the notification dropdown and badge in student pages
 */

const StudentNotifications = {
    // DOM elements
    elements: {
        container: null,
        badge: null,
        dropdown: null,
        list: null,
        markAllBtn: null,
        toggle: null
    },
    
    // State tracking
    state: {
        isLoading: false,
        lastLoaded: 0,
        notifications: []
    },
    
    /**
     * Initialize the notification system
     * @param {Object} options Configuration options
     */
    init: function(options = {}) {
        // Set elements
        this.elements = {
            container: document.getElementById('notification-container'),
            badge: document.getElementById('notification-badge'),
            dropdown: document.getElementById('notification-dropdown'),
            list: document.getElementById('notification-list'),
            markAllBtn: document.getElementById('mark-all-read'),
            toggle: document.getElementById('notification-toggle')
        };
        
        // Check if all required elements exist
        if (!this.elements.badge || !this.elements.dropdown || !this.elements.list || 
            !this.elements.markAllBtn || !this.elements.toggle) {
            console.error('Notification system: Required elements not found');
            return;
        }
        
        // Set up event listeners
        this._setupEventListeners();
        
        // Initial load (but don't display dropdown yet)
        this._prefetchNotifications();
        
        // Set interval to refresh notifications badge (default: every 30 seconds)
        const refreshInterval = options.refreshInterval || 30000;
        setInterval(() => this._prefetchNotifications(), refreshInterval);
    },
    
    /**
     * Prefetch notifications from the server without showing loading state
     * This improves perceived performance when opening the dropdown
     */
    _prefetchNotifications: async function() {
        // Don't start multiple simultaneous requests
        if (this.state.isLoading) return;
        
        try {
            this.state.isLoading = true;
            
            const response = await fetch('../notifications/get_notifications.php');
            const data = await response.json();
            
            // Cache the notifications data
            this.state.notifications = data.notifications || [];
            
            // Update notification badge
            this._updateBadge(data.unread_count);
            
            // Record when data was last loaded
            this.state.lastLoaded = Date.now();
            
        } catch (error) {
            console.error('Error loading notifications:', error);
        } finally {
            this.state.isLoading = false;
        }
    },
    
    /**
     * Load notifications from the server
     * Shows loading state in the dropdown
     */
    loadNotifications: async function() {
        // If we've loaded recently, use cached data
        const CACHE_TIME = 10000; // 10 seconds
        if (Date.now() - this.state.lastLoaded < CACHE_TIME && this.state.notifications.length > 0) {
            this._updateList(this.state.notifications);
            return;
        }
        
        // Don't start multiple simultaneous requests
        if (this.state.isLoading) {
            this.elements.list.innerHTML = `
                <div class="notification-loading">
                    <div class="spinner"></div>
                    <p>Loading notifications...</p>
                </div>
            `;
            return;
        }
        
        try {
            this.state.isLoading = true;
            
            // Show loading indicator
            if (typeof showLoading === 'function') {
                showLoading(this.elements.list, 'Loading notifications...');
            } else {
                this.elements.list.innerHTML = `
                    <div class="notification-loading">
                        <div class="spinner"></div>
                        <p>Loading notifications...</p>
                    </div>
                `;
            }
            
            const response = await fetch('../notifications/get_notifications.php');
            const data = await response.json();
            
            // Cache the notifications data
            this.state.notifications = data.notifications || [];
            
            // Update notification badge
            this._updateBadge(data.unread_count);
            
            // Update notification list
            this._updateList(data.notifications);
            
            // Record when data was last loaded
            this.state.lastLoaded = Date.now();
            
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.elements.list.innerHTML = `
                <div class="notification-empty">
                    Error loading notifications
                </div>
            `;
        } finally {
            this.state.isLoading = false;
        }
    },
    
    /**
     * Mark a notification as read
     * @param {number} id Notification ID
     */
    markAsRead: async function(id) {
        try {
            const response = await fetch('../notifications/mark_as_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: id }),
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update UI
                const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                if (item) {
                    item.classList.remove('unread');
                }
                
                // Update local cache
                const index = this.state.notifications.findIndex(n => n.id === id);
                if (index >= 0) {
                    this.state.notifications[index].is_read = true;
                }
                
                // Refresh badge count
                this._prefetchNotifications();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    },
    
    /**
     * Mark all notifications as read
     */
    markAllAsRead: async function() {
        try {
            // Disable button during operation
            this.elements.markAllBtn.disabled = true;
            
            const response = await fetch('../notifications/mark_all_read.php');
            const data = await response.json();
            
            if (data.success) {
                // Update UI - remove unread class from all notifications
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Update badge
                this._updateBadge(0);
                
                // Update local cache
                this.state.notifications.forEach(n => n.is_read = true);
                
                // Show confirmation if showNotification function is available
                if (typeof showNotification === 'function') {
                    showNotification('Success', 'All notifications marked as read', 'success');
                }
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        } finally {
            this.elements.markAllBtn.disabled = false;
        }
    },
    
    /**
     * Set up event listeners
     * @private
     */
    _setupEventListeners: function() {
        // Toggle notification dropdown
        this.elements.toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const isOpen = this.elements.dropdown.classList.contains('active');
            
            if (!isOpen) {
                this.loadNotifications();
                this.elements.dropdown.classList.add('active');
            } else {
                this.elements.dropdown.classList.remove('active');
            }
        });
        
        // Close notification dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.elements.dropdown.contains(e.target) && 
                !this.elements.toggle.contains(e.target)) {
                this.elements.dropdown.classList.remove('active');
            }
        });
        
        // Mark all notifications as read
        this.elements.markAllBtn.addEventListener('click', () => {
            this.markAllAsRead();
        });
    },
    
    /**
     * Update the notification badge
     * @param {number} count Unread count
     * @private
     */
    _updateBadge: function(count) {
        if (count > 0) {
            this.elements.badge.textContent = count > 99 ? '99+' : count;
            this.elements.badge.classList.add('active');
        } else {
            this.elements.badge.classList.remove('active');
        }
    },
    
    /**
     * Update the notification list
     * @param {Array} notifications List of notifications
     * @private
     */
    _updateList: function(notifications) {
        this.elements.list.innerHTML = '';
        
        if (!notifications || notifications.length === 0) {
            this.elements.list.innerHTML = `
                <div class="notification-empty">
                    You have no notifications
                </div>
            `;
            return;
        }
        
        // Create document fragment for better performance
        const fragment = document.createDocumentFragment();
        
        notifications.forEach(notification => {
            const item = document.createElement('div');
            item.className = `notification-item ${notification.is_read ? '' : 'unread'}`;
            item.dataset.id = notification.id;
            
            item.innerHTML = `
                <div class="notification-indicator"></div>
                <div class="notification-content">
                    <h4>${notification.title}</h4>
                    <p>${notification.content}</p>
                    <span class="notification-time">${notification.created_at}</span>
                </div>
            `;
            
            // Add click handler
            item.addEventListener('click', () => this.markAsRead(notification.id));
            
            fragment.appendChild(item);
        });
        
        this.elements.list.appendChild(fragment);
    }
};