document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('header');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.style.padding = '15px 0';
            header.style.backgroundColor = 'rgba(10, 14, 21, 0.95)';
        } else {
            header.style.padding = '20px 0';
            header.style.backgroundColor = 'rgba(10, 14, 21, 0.8)';
        }
    });
    
    const animatedElements = document.querySelectorAll('.benefit-card, .transformation-card, .step');
    
    const scrollAnimation = function() {
        animatedElements.forEach(element => {
            const position = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (position < windowHeight - 100) {
                element.classList.add('in-view');
            }
        });
    };
    
    scrollAnimation();
    
    window.addEventListener('scroll', scrollAnimation);
    
    const navLinks = document.querySelectorAll('nav a');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                window.scrollTo({
                    top: targetSection.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    const primaryButtons = document.querySelectorAll('.primary-btn');
    const secondaryButtons = document.querySelectorAll('.secondary-btn');
    
    primaryButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 6px 15px rgba(231, 76, 60, 0.4)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 10px rgba(231, 76, 60, 0.3)';
        });
    });

    const hero = document.querySelector('.hero');
    
    window.addEventListener('scroll', function() {
        const scrollPosition = window.scrollY;
        hero.style.backgroundPositionY = `${scrollPosition * 0.1}px`;
    });
    
    const addSectionIds = function() {
        const sections = {
            'Features': document.querySelector('.benefits'),
            'Results': document.querySelector('.transformation'),
            'How It Works': document.querySelector('.how-it-works')
        };
        
        for (const [name, section] of Object.entries(sections)) {
            if (section) {
                section.id = name.toLowerCase().replace(/\s+/g, '-');
            }
        }
        
        document.querySelectorAll('nav a').forEach(link => {
            const text = link.textContent;
            if (sections[text]) {
                link.setAttribute('href', `#${text.toLowerCase().replace(/\s+/g, '-')}`);
            }
        });
    };
    
    addSectionIds();
}); 