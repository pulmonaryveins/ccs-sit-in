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
});