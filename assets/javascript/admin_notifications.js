/**
 * Admin Notification System
 * Handles fetching and displaying notifications for administrators
 */

document.addEventListener('DOMContentLoaded', function() {
    const notificationToggle = document.getElementById('notification-toggle');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationBadge = document.getElementById('notification-badge');
    const notificationList = document.getElementById('notification-list');
    const markAllReadBtn = document.getElementById('mark-all-read');
    
    // Initialize the notification system
    initNotifications();
    
    // Fetch notifications initially
    loadNotifications();
    
    // Set interval to refresh notifications (every 30 seconds)
    const refreshInterval = setInterval(loadNotifications, 30000);
    
    function initNotifications() {
        // Add toggle event for notification dropdown
        if (notificationToggle) {
            notificationToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const isOpen = notificationDropdown.classList.contains('active');
                
                if (!isOpen) {
                    loadNotifications();
                    notificationDropdown.classList.add('active');
                } else {
                    notificationDropdown.classList.remove('active');
                }
            });
        }
        
        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (notificationDropdown && !notificationDropdown.contains(e.target) && 
                !notificationToggle.contains(e.target)) {
                notificationDropdown.classList.remove('active');
            }
        });
        
        // Mark all notifications as read
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', markAllAsRead);
        }
    }
    
    // Load notifications from server
    async function loadNotifications() {
        try {
            const response = await fetch('../admin/get_notifications.php');
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
            
            // Update notification badge
            updateBadge(data.unread_count);
            
            // Update notification list
            updateNotificationList(data.notifications);
            
        } catch (error) {
            console.error('Error loading notifications:', error);
            if (notificationList) {
                notificationList.innerHTML = `
                    <div class="notification-empty">
                        Error loading notifications
                    </div>
                `;
            }
        }
    }
    
    // Update the notification badge
    function updateBadge(count) {
        if (!notificationBadge) return;
        
        if (count > 0) {
            notificationBadge.textContent = count > 99 ? '99+' : count;
            notificationBadge.classList.add('active');
        } else {
            notificationBadge.classList.remove('active');
        }
    }
    
    // Update the notification list
    function updateNotificationList(notifications) {
        if (!notificationList) return;
        
        // Clear the list
        notificationList.innerHTML = '';
        
        if (notifications.length === 0) {
            notificationList.innerHTML = `
                <div class="notification-empty">
                    You have no notifications
                </div>
            `;
            return;
        }
        
        // Add each notification
        notifications.forEach(notification => {
            const item = document.createElement('div');
            item.className = `notification-item ${notification.is_read ? '' : 'unread'}`;
            item.dataset.id = notification.id;
            item.dataset.type = notification.related_type;
            item.dataset.relatedId = notification.related_id;
            
            item.innerHTML = `
                <div class="notification-indicator"></div>
                <div class="notification-content">
                    <h4>${notification.title}</h4>
                    <p>${notification.content}</p>
                    <span class="notification-time">${notification.created_at}</span>
                </div>
            `;
            
            // Add click event to mark as read and handle navigation
            item.addEventListener('click', function() {
                handleNotificationClick(notification);
            });
            
            notificationList.appendChild(item);
        });
    }
    
    // Handle notification click
    function handleNotificationClick(notification) {
        // Mark notification as read
        markAsRead(notification.id);
        
        // Handle navigation based on notification type
        if (notification.related_type === 'reservation') {
            // Redirect to reservations page with the selected reservation highlighted
            window.location.href = `../view/admin_reservations.php?highlight=${notification.related_id}`;
        }
    }
    
    // Mark notification as read
    async function markAsRead(id) {
        try {
            const response = await fetch('../admin/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: id }),
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update UI - remove unread class from this notification
                const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                if (item) {
                    item.classList.remove('unread');
                }
                
                // Refresh notifications to update badge count
                loadNotifications();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    // Mark all notifications as read
    async function markAllAsRead() {
        try {
            const response = await fetch('../admin/mark_all_read.php');
            const data = await response.json();
            
            if (data.success) {
                // Update UI - remove unread class from all notifications
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Update badge
                updateBadge(0);
                
                // Show confirmation if notification system is available
                if (typeof showNotification === 'function') {
                    showNotification(
                        "Success", 
                        "All notifications marked as read",
                        "success",
                        3000
                    );
                }
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }
});
