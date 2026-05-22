// js/panier.js - Fonctions globales pour le panier

// Fonction pour ajouter au panier via AJAX
function addToCart(productId, quantity = 1) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    return fetch('ajax/ajouter_panier.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour le compteur du panier
            updateCartCounter(data.total_items);
            
            // Afficher un message de succès
            showNotification(data.message, 'success');
        } else {
            // Afficher un message d'erreur
            showNotification(data.message, 'error');
        }
        return data;
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de l\'ajout au panier', 'error');
        return { success: false, message: 'Erreur réseau' };
    });
}

// Fonction pour mettre à jour le compteur du panier
function updateCartCounter(count) {
    const cartCounters = document.querySelectorAll('.cart-count');
    cartCounters.forEach(counter => {
        counter.textContent = count;
        if (count > 0) {
            counter.style.display = 'inline-block';
            counter.classList.add('pulse');
            setTimeout(() => {
                counter.classList.remove('pulse');
            }, 500);
        } else {
            counter.style.display = 'none';
        }
    });
    
    // Stocker dans le sessionStorage pour les autres pages
    sessionStorage.setItem('cartTotalItems', count);
}

// Fonction pour afficher les notifications
function showNotification(message, type = 'info') {
    // Créer une notification toast
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show position-fixed`;
    toast.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 400px;
    `;
    
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas ${type === 'error' ? 'fa-exclamation-triangle' : 'fa-check-circle'} me-2"></i>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto-suppression après 4 secondes
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 4000);
}

// CSS pour les animations
const cartStyles = `
    .pulse {
        animation: pulse 0.5s ease-in-out;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); }
        100% { transform: scale(1); }
    }
    
    .quick-add-btn.added {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }
`;

// Injecter les styles
if (!document.querySelector('#cart-styles')) {
    const style = document.createElement('style');
    style.id = 'cart-styles';
    style.textContent = cartStyles;
    document.head.appendChild(style);
}

// Initialiser le compteur au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    const savedCount = sessionStorage.getItem('cartTotalItems');
    if (savedCount) {
        updateCartCounter(parseInt(savedCount));
    }
});