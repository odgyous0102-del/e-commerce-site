// js/search.js
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');
    const searchSuggestions = document.getElementById('searchSuggestions');
    
    if (!searchInput) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        
        if (e.target.value.length > 2) {
            searchTimeout = setTimeout(() => {
                fetchSearchSuggestions(e.target.value);
            }, 300);
        } else {
            hideSuggestions();
        }
    });
    
    searchInput.addEventListener('focus', function() {
        if (this.value.length > 2) {
            fetchSearchSuggestions(this.value);
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!searchForm.contains(e.target)) {
            hideSuggestions();
        }
    });
    
    function fetchSearchSuggestions(query) {
        fetch(`ajax/search_suggestions.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(suggestions => {
                showSearchSuggestions(suggestions, query);
            })
            .catch(error => {
                console.error('Erreur de recherche:', error);
                hideSuggestions();
            });
    }
    
    function showSearchSuggestions(suggestions, query) {
        if (suggestions.length === 0) {
            hideSuggestions();
            return;
        }
        
        let html = '<div class="card shadow-lg border-0">';
        html += '<div class="card-body p-2">';
        
        suggestions.forEach(product => {
            const price = new Intl.NumberFormat('fr-FR').format(product.prix);
            html += `
                <a href="produit.php?id=${product.id}" class="dropdown-item d-flex align-items-center p-2">
                    <img src="${product.image_principale || 'images/default-product.jpg'}" 
                         alt="${product.titre}" 
                         class="rounded me-3" 
                         style="width: 40px; height: 40px; object-fit: cover;">
                    <div class="flex-grow-1">
                        <div class="fw-medium">${highlightText(product.titre, query)}</div>
                        <small class="text-muted">${product.marque} • ${price} FCFA</small>
                    </div>
                </a>
            `;
        });
        
        html += `<a href="boutique.php?q=${encodeURIComponent(query)}" class="dropdown-item text-center text-primary fw-medium p-2">
                    <i class="fas fa-search me-1"></i>Voir tous les résultats
                 </a>`;
        html += '</div></div>';
        
        searchSuggestions.innerHTML = html;
        searchSuggestions.classList.remove('d-none');
    }
    
    function hideSuggestions() {
        searchSuggestions.classList.add('d-none');
    }
    
    function highlightText(text, query) {
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
});