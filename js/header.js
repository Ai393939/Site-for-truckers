document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const header = document.querySelector('header');
    const body = document.body;
    
    if (menuToggle && header) {
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const isOpen = header.classList.contains('menu-open');
            
            if (isOpen) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });
        
        document.querySelectorAll('.mobile-menu-container a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 767) {
                    closeMobileMenu();
                }
            });
        });
        
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 767 && 
                header.classList.contains('menu-open') &&
                !menuToggle.contains(e.target) &&
                !document.querySelector('.mobile-menu-container').contains(e.target)) {
                closeMobileMenu();
            }
        });
        
        function openMobileMenu() {
            header.classList.add('menu-open');
            body.classList.add('menu-open');
            menuToggle.textContent = '✕';
        }
        
        function closeMobileMenu() {
            header.classList.remove('menu-open');
            body.classList.remove('menu-open');
            menuToggle.textContent = '☰';
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                closeMobileMenu();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && window.innerWidth <= 767) {
                closeMobileMenu();
            }
        });
    }
});