document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    initializeSort();
    setupSearchFilter();
    
    checkUrlParameters();
});


function initializeFilters() {
    document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            applyFilters();
        });
    });
    
    document.querySelectorAll('.clear-filter-btn').forEach(button => {
        button.addEventListener('click', function() {
            const filterType = this.getAttribute('data-filter');
            clearFilterByType(filterType);
            applyFilters();
        });
    });
    
    document.getElementById('resetAllFilters').addEventListener('click', function() {
        resetFilters();
    });
}

function initializeSort() {
    const sortDropdown = document.getElementById('sort-exercises');
    if (sortDropdown) {
        sortDropdown.addEventListener('change', function() {
            sortExercises(this.value);
        });
    }
}

function setupSearchFilter() {
    const searchInput = document.getElementById('exerciseSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            applyFilters();
        }, 300));
    }
}

function applyFilters() {
    const searchTerm = document.getElementById('exerciseSearch').value.toLowerCase();
    const exerciseCards = document.querySelectorAll('.exercise-card');
    const activeFilters = {};
    let visibleCount = 0;
    
    document.querySelectorAll('.filter-checkbox:checked').forEach(checkbox => {
        const filterType = checkbox.getAttribute('data-filter');
        const filterValue = checkbox.value;
        
        if (!activeFilters[filterType]) {
            activeFilters[filterType] = [];
        }
        activeFilters[filterType].push(filterValue);
    });
    
    updateActiveFiltersDisplay(activeFilters);
    
    exerciseCards.forEach(card => {
        let isVisible = true;
        
        if (searchTerm) {
            const exerciseName = card.querySelector('.exercise-title').textContent.toLowerCase();
            const exerciseSpecs = card.querySelector('.exercise-specs').textContent.toLowerCase();
            isVisible = exerciseName.includes(searchTerm) || exerciseSpecs.includes(searchTerm);
        }
        
        for (const [filterType, filterValues] of Object.entries(activeFilters)) {
            if (filterValues.length > 0) {
                const cardValue = card.getAttribute(`data-${filterType}`);
                if (!filterValues.some(value => cardValue === value || cardValue.includes(value))) {
                    isVisible = false;
                    break;
                }
            }
        }
        
        if (isVisible) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    updateResultCount(visibleCount);
    
    toggleNoResultsMessage(visibleCount);
    
    updateUrlParameters(activeFilters, searchTerm);
}

function updateActiveFiltersDisplay(activeFilters) {
    const activeFiltersContainer = document.getElementById('active-filters');
    activeFiltersContainer.innerHTML = '';
    
    const searchTerm = document.getElementById('exerciseSearch').value;
    if (searchTerm) {
        const searchFilter = document.createElement('div');
        searchFilter.className = 'active-filter';
        searchFilter.innerHTML = `Search: ${searchTerm} <span class="remove-filter" data-filter="search">×</span>`;
        activeFiltersContainer.appendChild(searchFilter);
        
        searchFilter.querySelector('.remove-filter').addEventListener('click', function() {
            document.getElementById('exerciseSearch').value = '';
            applyFilters();
        });
    }
    
    for (const [filterType, filterValues] of Object.entries(activeFilters)) {
        filterValues.forEach(value => {
            const filterPill = document.createElement('div');
            filterPill.className = 'active-filter';
            filterPill.innerHTML = `${filterType}: ${value} <span class="remove-filter" data-filter="${filterType}" data-value="${value}">×</span>`;
            activeFiltersContainer.appendChild(filterPill);
            
            filterPill.querySelector('.remove-filter').addEventListener('click', function() {
                const filterType = this.getAttribute('data-filter');
                const filterValue = this.getAttribute('data-value');
                const checkbox = document.querySelector(`.filter-checkbox[data-filter="${filterType}"][value="${filterValue}"]`);
                if (checkbox) {
                    checkbox.checked = false;
                    applyFilters();
                }
            });
        });
    }
}

function sortExercises(sortOption) {
    const exerciseResults = document.getElementById('exercise-results');
    const exerciseCards = Array.from(exerciseResults.querySelectorAll('.exercise-card'));
    
    exerciseCards.sort((a, b) => {
        switch (sortOption) {
            case 'name-asc':
                return a.querySelector('.exercise-title').textContent.localeCompare(
                    b.querySelector('.exercise-title').textContent
                );
            case 'name-desc':
                return b.querySelector('.exercise-title').textContent.localeCompare(
                    a.querySelector('.exercise-title').textContent
                );
            case 'difficulty-asc':
                return getDifficultyValue(a.getAttribute('data-difficulty')) - 
                       getDifficultyValue(b.getAttribute('data-difficulty'));
            case 'difficulty-desc':
                return getDifficultyValue(b.getAttribute('data-difficulty')) - 
                       getDifficultyValue(a.getAttribute('data-difficulty'));
            default:
                return 0;
        }
    });
    
    exerciseCards.forEach(card => {
        exerciseResults.appendChild(card);
    });
}

function getDifficultyValue(difficulty) {
    switch (difficulty) {
        case 'beginner': return 1;
        case 'intermediate': return 2;
        case 'advanced': return 3;
        default: return 0;
    }
}

function clearFilterByType(filterType) {
    document.querySelectorAll(`.filter-checkbox[data-filter="${filterType}"]`).forEach(checkbox => {
        checkbox.checked = false;
    });
}

function resetFilters() {
    document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    document.getElementById('exerciseSearch').value = '';
    
    const sortDropdown = document.getElementById('sort-exercises');
    if (sortDropdown) {
        sortDropdown.value = 'name-asc';
    }
    
    applyFilters();
    
    updateUrlParameters({}, '');
}

function updateResultCount(count) {
    const resultCountElement = document.getElementById('result-count');
    if (resultCountElement) {
        resultCountElement.textContent = count;
    }
}

function toggleNoResultsMessage(visibleCount) {
    const noResultsMessage = document.getElementById('no-results-message');
    if (noResultsMessage) {
        noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function updateUrlParameters(activeFilters, searchTerm) {
    const urlParams = new URLSearchParams();
    
    if (searchTerm) {
        urlParams.set('search', searchTerm);
    }
    
    for (const [filterType, filterValues] of Object.entries(activeFilters)) {
        if (filterValues.length > 0) {
            urlParams.set(filterType, filterValues.join(','));
        }
    }
    
    const newUrl = `${window.location.pathname}${urlParams.toString() ? '?' + urlParams.toString() : ''}`;
    window.history.replaceState({}, '', newUrl);
}

function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    
    const searchParam = urlParams.get('search');
    if (searchParam) {
        document.getElementById('exerciseSearch').value = searchParam;
    }
    
    for (const [key, value] of urlParams.entries()) {
        if (key !== 'search') {
            const values = value.split(',');
            values.forEach(val => {
                const checkbox = document.querySelector(`.filter-checkbox[data-filter="${key}"][value="${val}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }
    }
    
    applyFilters();
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
} 