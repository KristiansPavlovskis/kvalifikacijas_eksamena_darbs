
document.addEventListener('DOMContentLoaded', function() {
    initLoadingScreen();
    
    initHeader();
    
    initAnimations();
    
    initProfileDropdown();
    
    initImageFallbacks();
});

function initLoadingScreen() {
    const progressBar = document.querySelector('.progress-bar');
    let width = 0;
    
    const interval = setInterval(() => {
        if (width >= 100) {
            clearInterval(interval);
            completeLoading();
        } else {
            width += 5;
            progressBar.style.width = width + '%';
        }
    }, 30); 
    
    function completeLoading() {
        document.body.classList.remove('loading');
        const loadingScreen = document.querySelector('.loading-screen');
        loadingScreen.classList.add('fade-out');
        setTimeout(() => {
            loadingScreen.style.display = 'none';
        }, 500);
    }
}

function initHeader() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const nav = document.querySelector('.main-nav');
    
    if (menuToggle && nav) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            nav.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
        
        const navLinks = document.querySelectorAll('.nav-list a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                menuToggle.classList.remove('active');
                nav.classList.remove('active');
                document.body.classList.remove('menu-open');
            });
        });
    }
    
    const header = document.querySelector('.site-header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }
}

function initAnimations() {
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-out',
            once: true,
            offset: 50,
            delay: 150
        });
    }
}

function initProfileDropdown() {
    const profileButton = document.querySelector('.profile-button');
    const profileDropdown = document.querySelector('.profile-dropdown');
    
    if (profileButton && profileDropdown) {
        profileButton.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function(event) {
            if (profileDropdown.classList.contains('active') && 
                !profileButton.contains(event.target) && 
                !profileDropdown.contains(event.target)) {
                profileDropdown.classList.remove('active');
            }
        });
    }
}

function initImageFallbacks() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('error', function() {
            if (this.classList.contains('fallback')) return; 
            
            this.classList.add('fallback');
            
            const altText = this.alt || 'Image';
            this.src = `data:image/svg+xml;charset=UTF-8,%3Csvg xmlns="http://www.w3.org/2000/svg" width="300" height="150" viewBox="0 0 300 150"%3E%3Crect fill="%23242424" width="300" height="150"/%3E%3Ctext fill="%23ff4d4d" font-family="sans-serif" font-size="16" dy="10.5" font-weight="bold" x="50%25" y="50%25" text-anchor="middle"%3E${altText}%3C/text%3E%3C/svg%3E`;
        });
    });
}

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            window.scrollTo({
                top: target.offsetTop - 80, 
                behavior: 'smooth'
            });
        }
    });
});

window.addEventListener('scroll', function() {
    const scrollPosition = window.pageYOffset;
    const heroBackground = document.querySelector('.hero-background');
    
    if (heroBackground) {
        heroBackground.style.transform = `translateY(${scrollPosition * 0.3}px)`;
    }
});

const GYM = {
    init: function() {
        this.stickyHeader();
        this.mobileMenu();
        this.smoothScroll();
        this.initCarousels();
    },
    
    stickyHeader: function() {
        const header = document.querySelector('.site-header');
        
        if (!header) return;
        
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    },
    
    mobileMenu: function() {
        const toggle = document.querySelector('.mobile-menu-toggle');
        const nav = document.querySelector('.main-nav');
        const body = document.body;
        
        if (!toggle || !nav) return;
        
        toggle.addEventListener('click', () => {
            toggle.classList.toggle('active');
            nav.classList.toggle('active');
            body.classList.toggle('menu-open');
        });
        
        document.querySelectorAll('.nav-list a').forEach(link => {
            link.addEventListener('click', () => {
                toggle.classList.remove('active');
                nav.classList.remove('active');
                body.classList.remove('menu-open');
            });
        });
    },
    
    smoothScroll: function() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const target = document.querySelector(targetId);
                
                if (target) {
                    e.preventDefault();
                    
                    window.scrollTo({
                        top: target.offsetTop - 80, 
                        behavior: 'smooth'
                    });
                }
            });
        });
    },
    
    initCarousels: function() {
        console.log('Carousels initialized');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    GYM.init();
}); 