document.addEventListener('DOMContentLoaded', function() {
    // Dropdown functionality
    const dropdowns = document.querySelectorAll('.nav-dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        
        toggle.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent document click from immediately closing it
            
            // Close all other open dropdowns
            dropdowns.forEach(otherDropdown => {
                if (otherDropdown !== dropdown && otherDropdown.classList.contains('open')) {
                    otherDropdown.classList.remove('open');
                }
            });
            
            // Toggle this dropdown
            dropdown.classList.toggle('open');
        });
    });
    
    // Close dropdowns when clicking elsewhere
    document.addEventListener('click', () => {
        dropdowns.forEach(dropdown => {
            if (dropdown.classList.contains('open')) {
                dropdown.classList.remove('open');
            }
        });
    });
    
    // Prevent dropdowns from closing when clicking inside them
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    });
    
    // Profile panel toggle
    const profileTrigger = document.getElementById('profile-trigger');
    const profilePanel = document.getElementById('profile-panel');
    const backdrop = document.getElementById('backdrop');
    
    if (profileTrigger && profilePanel && backdrop) {
        profileTrigger.addEventListener('click', () => {
            profilePanel.classList.toggle('active');
            backdrop.classList.toggle('active');
            document.body.style.overflow = profilePanel.classList.contains('active') ? 'hidden' : '';
        });
        
        backdrop.addEventListener('click', () => {
            profilePanel.classList.remove('active');
            backdrop.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Notification toggle
    const notificationToggle = document.getElementById('notification-toggle');
    const notificationDropdown = document.getElementById('notification-dropdown');
    
    if (notificationToggle && notificationDropdown) {
        notificationToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            notificationDropdown.classList.toggle('active');
            
            // Close profile panel if open
            if (profilePanel && profilePanel.classList.contains('active')) {
                profilePanel.classList.remove('active');
                backdrop.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        // Close notification dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (notificationDropdown && notificationDropdown.classList.contains('active') && 
                !notificationToggle.contains(e.target) && 
                !notificationDropdown.contains(e.target)) {
                notificationDropdown.classList.remove('active');
            }
        });
        
        // Prevent dropdown from closing when clicking inside
        if (notificationDropdown) {
            notificationDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
    }
});