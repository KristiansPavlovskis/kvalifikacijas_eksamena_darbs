document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const adminWrapper = document.querySelector('.admin-wrapper');
    const sidebar = document.querySelector('.admin-sidebar');
    
    const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isSidebarCollapsed) {
        adminWrapper.classList.add('sidebar-collapsed');
    }
    
    sidebarToggle.addEventListener('click', function() {
        adminWrapper.classList.toggle('sidebar-collapsed');
        
        localStorage.setItem('sidebarCollapsed', adminWrapper.classList.contains('sidebar-collapsed'));
    });
    
    const handleResize = () => {
        if (window.innerWidth <= 992) {
            adminWrapper.classList.remove('sidebar-collapsed');
            sidebar.classList.remove('show');
            
            document.addEventListener('click', function(e) {
                if (sidebar.classList.contains('show') && 
                    !sidebar.contains(e.target) && 
                    !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            });
            
            sidebarToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('show');
            });
        } else {
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                adminWrapper.classList.add('sidebar-collapsed');
            }
            
            sidebar.classList.remove('show');
        }
    };
    
    handleResize();
    window.addEventListener('resize', handleResize);
    
    const darkModeToggle = document.querySelector('.dark-mode-toggle');
    if (darkModeToggle) {
        const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
        const storedTheme = localStorage.getItem('theme');
        
        if (storedTheme === 'dark' || (!storedTheme && prefersDarkScheme.matches)) {
            document.body.classList.add('dark-mode');
        }
        
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            
            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });
    }
    
    initializeCharts();
});

function initializeCharts() {
    console.log('Charts initialized');
} 