document.addEventListener('DOMContentLoaded', () => {
    initializeJourneyTimeline();
    initializeResourceCards();
    initializeSearchBar();
    initializeSplitPanels();
    initializeInteractiveElements();
});

function initializeJourneyTimeline() {
    const timeline = document.querySelector('.journey-timeline');
    if (!timeline) return;
    
    let isDown = false;
    let startX;
    let scrollLeft;
    
    timeline.addEventListener('mousedown', (e) => {
        isDown = true;
        timeline.classList.add('active');
        startX = e.pageX - timeline.offsetLeft;
        scrollLeft = timeline.scrollLeft;
    });
    
    timeline.addEventListener('mouseleave', () => {
        isDown = false;
        timeline.classList.remove('active');
    });
    
    timeline.addEventListener('mouseup', () => {
        isDown = false;
        timeline.classList.remove('active');
    });
    
    timeline.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - timeline.offsetLeft;
        const walk = (x - startX) * 2;
        timeline.scrollLeft = scrollLeft - walk;
    });
    
    if (timeline.scrollWidth > timeline.clientWidth) {
        const scrollIndicator = document.createElement('div');
        scrollIndicator.className = 'scroll-indicator';
        scrollIndicator.innerHTML = '<i class="fas fa-chevron-right"></i>';
        timeline.parentNode.appendChild(scrollIndicator);
        
        scrollIndicator.addEventListener('click', () => {
            timeline.scrollBy({
                left: 300,
                behavior: 'smooth'
            });
        });
    }
}

function initializeResourceCards() {
    const resourceCards = document.querySelectorAll('.resource-card');
    resourceCards.forEach(card => {
        const link = card.querySelector('.resource-link');
        
        if (link) {
            card.addEventListener('mouseenter', () => {
                link.classList.add('hover');
            });
            
            card.addEventListener('mouseleave', () => {
                link.classList.remove('hover');
            });
            
            card.addEventListener('click', (e) => {
                if (e.target !== link && !link.contains(e.target)) {
                    link.click();
                }
            });
        }
    });
}

function initializeSearchBar() {
    const searchInput = document.querySelector('.global-search');
    const searchBtn = document.querySelector('.search-btn');
    
    if (!searchInput || !searchBtn) return;
    
    searchBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const searchTerm = searchInput.value.trim();
        if (searchTerm) {
            performSearch(searchTerm);
        }
    });
    
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                performSearch(searchTerm);
            }
        }
    });
    
    function performSearch(term) {
        console.log(`Searching for: ${term}`);
    }
}

function initializeSplitPanels() {
    const splitPanels = document.querySelectorAll('.split-panel');
    
    splitPanels.forEach(panel => {
        const cta = panel.querySelector('.split-panel-cta');
        
        const featureCards = panel.querySelectorAll('.feature-card');
        featureCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
        
        if (cta) {
            panel.addEventListener('mouseenter', () => {
                cta.classList.add('highlight');
            });
            
            panel.addEventListener('mouseleave', () => {
                cta.classList.remove('highlight');
            });
        }
    });
}

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    });
});

function initializeInteractiveElements() {
    
    const wheelSegments = document.querySelectorAll('.wheel-segment');
    const detailContents = document.querySelectorAll('.detail-content');
    
    if (wheelSegments.length > 0 && detailContents.length > 0) {
        wheelSegments[0].classList.add('active');
        detailContents[0].classList.add('active');
        
        wheelSegments.forEach(segment => {
            segment.addEventListener('click', () => {
                const segmentNum = segment.getAttribute('data-segment');
                
                wheelSegments.forEach(seg => seg.classList.remove('active'));
                detailContents.forEach(content => content.classList.remove('active'));
                
                segment.classList.add('active');
                document.getElementById(`methodology-${segmentNum}`).classList.add('active');
            });
        });
    }
    
    const splitSides = document.querySelectorAll('.split-side');
    
    if (splitSides.length > 0) {
        if (window.innerWidth > 1024) {
            splitSides.forEach(side => {
                side.addEventListener('mouseenter', () => {
                    splitSides.forEach(s => {
                        if (s === side) {
                            s.style.flex = '1.3';
                        } else {
                            s.style.flex = '0.7';
                        }
                    });
                });
            });
            
            const splitSection = document.querySelector('.dynamic-split-section');
            if (splitSection) {
                splitSection.addEventListener('mouseleave', () => {
                    splitSides.forEach(side => {
                        side.style.flex = '1';
                    });
                });
            }
        }
    }
    
    const internalLinks = document.querySelectorAll('a[href^="#"]');
    
    internalLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    const searchInput = document.querySelector('.global-search');
    const searchButton = document.querySelector('.search-btn');
    
    if (searchInput && searchButton) {
        searchButton.addEventListener('click', () => {
            const searchTerm = searchInput.value.trim().toLowerCase();
            if (searchTerm.length > 0) {
                alert(`Searching for: ${searchTerm}\nIn a full implementation, this would show search results.`);
            }
        });
        
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && searchInput.value.trim().length > 0) {
                searchButton.click();
            }
        });
    }
    
    const statCircles = document.querySelectorAll('.stat-circle');
    
    if (statCircles.length > 0 && 'IntersectionObserver' in window) {
        const statObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    
                    const circles = entry.target.parentElement.querySelectorAll('.stat-circle');
                    circles.forEach((circle, index) => {
                        setTimeout(() => {
                            circle.classList.add('pulse');
                        }, index * 150);
                    });
                    
                    statObserver.unobserve(entry.target.parentElement);
                }
            });
        }, { threshold: 0.5 });
        
        document.querySelectorAll('.split-stats').forEach(statContainer => {
            statObserver.observe(statContainer);
        });
    }
    
    const splitOverlays = document.querySelectorAll('.split-overlay');
    
    if (splitOverlays.length > 0) {
        window.addEventListener('scroll', () => {
            const scrollPosition = window.pageYOffset;
            
            splitOverlays.forEach(overlay => {
                overlay.style.transform = `translateY(${scrollPosition * 0.05}px)`;
            });
        });
    }
}

if ('ontouchstart' in document.documentElement) {
    document.body.classList.add('touch-device');
}

function animateOnScroll() {
    const elements = document.querySelectorAll('.split-features, .feature-item, .split-quote, .detail-benefits li');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                let delay = 0;
                
                if (entry.target.children.length > 0 && !entry.target.classList.contains('split-quote')) {
                    Array.from(entry.target.children).forEach((child, index) => {
                        setTimeout(() => {
                            child.classList.add('fade-in');
                        }, index * 100);
                    });
                } else {
                    entry.target.classList.add('fade-in');
                }
                
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });
    
    elements.forEach(element => {
        observer.observe(element);
    });
}

if (document.readyState === 'complete') {
    animateOnScroll();
} else {
    window.addEventListener('load', animateOnScroll);
} 