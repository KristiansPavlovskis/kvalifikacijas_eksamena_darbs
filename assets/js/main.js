document.addEventListener('DOMContentLoaded', function() {
    initLoadingScreen();
    
    initHeader();
    
    initAnimations();
    
    initProfileDropdown();
    
    initImageFallbacks();
    
    initTestimonialSlider();
    
    if (document.body.classList.contains('loading')) {
        setTimeout(() => {
            document.body.classList.remove('loading');
        }, 2500);
    }
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({
                    top: target.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    const scrollIndicator = document.querySelector('.scroll-indicator');
    if (scrollIndicator) {
        scrollIndicator.addEventListener('click', () => {
            const nextSection = document.querySelector('.feature-showcase');
            if (nextSection) {
                window.scrollTo({
                    top: nextSection.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    }
    
    const sliderContainer = document.querySelector('.slider-container');
    if (sliderContainer) {
        const slides = document.querySelectorAll('.workout-slide');
        const prevButton = document.querySelector('.slider-arrow.prev');
        const nextButton = document.querySelector('.slider-arrow.next');
        const dots = document.querySelectorAll('.slider-dot');
        
        let currentIndex = 0;
        const slideWidth = slides[0].offsetWidth + 24;
        
        updateDots(0);
        
        if (prevButton) {
            prevButton.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex--;
                    sliderContainer.scrollTo({
                        left: currentIndex * slideWidth,
                        behavior: 'smooth'
                    });
                    updateDots(currentIndex);
                }
            });
        }
        
        if (nextButton) {
            nextButton.addEventListener('click', () => {
                if (currentIndex < slides.length - 1) {
                    currentIndex++;
                    sliderContainer.scrollTo({
                        left: currentIndex * slideWidth,
                        behavior: 'smooth'
                    });
                    updateDots(currentIndex);
                }
            });
        }
        
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentIndex = index;
                sliderContainer.scrollTo({
                    left: currentIndex * slideWidth,
                    behavior: 'smooth'
                });
                updateDots(currentIndex);
            });
        });
        
        sliderContainer.addEventListener('scroll', () => {
            const index = Math.round(sliderContainer.scrollLeft / slideWidth);
            if (index !== currentIndex) {
                currentIndex = index;
                updateDots(currentIndex);
            }
        });
        
        function updateDots(index) {
            dots.forEach((dot, i) => {
                if (i === index) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }
    }
    
    const animatedElements = document.querySelectorAll('.fade-in, .fade-in-up, .fade-in-left, .fade-in-right');
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    animatedElements.forEach(element => {
        element.style.animationPlayState = 'paused';
        observer.observe(element);
    });
    
    document.querySelectorAll('.progress-fill').forEach(progress => {
        const parent = progress.closest('.testimonial-card');
        const progressValue = progress.getAttribute('data-progress') || 0;
        progress.style.setProperty('--progress-width', `${progressValue}%`);
    });
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
    
    const elementsWithBgImage = document.querySelectorAll('[data-background]');
    elementsWithBgImage.forEach(el => {
        const bgImage = el.getAttribute('data-background');
        if (bgImage) {
            el.style.backgroundImage = `url('${bgImage}')`;
        }
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

function initTestimonialSlider() {
    const testimonials = document.querySelector('.testimonials-slider');
    if (testimonials) {
        let isDown = false;
        let startX;
        let scrollLeft;
        
        testimonials.addEventListener('mousedown', (e) => {
            isDown = true;
            testimonials.classList.add('active');
            startX = e.pageX - testimonials.offsetLeft;
            scrollLeft = testimonials.scrollLeft;
        });
        
        testimonials.addEventListener('mouseleave', () => {
            isDown = false;
            testimonials.classList.remove('active');
        });
        
        testimonials.addEventListener('mouseup', () => {
            isDown = false;
            testimonials.classList.remove('active');
        });
        
        testimonials.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - testimonials.offsetLeft;
            const walk = (x - startX) * 2;
            testimonials.scrollLeft = scrollLeft - walk;
        });
        
        testimonials.addEventListener('touchstart', (e) => {
            isDown = true;
            testimonials.classList.add('active');
            startX = e.touches[0].pageX - testimonials.offsetLeft;
            scrollLeft = testimonials.scrollLeft;
        });
        
        testimonials.addEventListener('touchend', () => {
            isDown = false;
            testimonials.classList.remove('active');
        });
        
        testimonials.addEventListener('touchmove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.touches[0].pageX - testimonials.offsetLeft;
            const walk = (x - startX) * 2;
            testimonials.scrollLeft = scrollLeft - walk;
        });
    }
}

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