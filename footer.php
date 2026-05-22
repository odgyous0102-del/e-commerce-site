</main>

<!-- Footer -->
<footer class="bg-dark text-white pt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">MonSite E-Commerce</h5>
                <p>Votre boutique en ligne de confiance pour des produits de qualité aux meilleurs prix.</p>
                <div class="social-links">
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">Liens rapides</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-white-50 text-decoration-none">Accueil</a></li>
                    <li><a href="catalogue.php" class="text-white-50 text-decoration-none">Catalogue</a></li>
                    <li><a href="promotions.php" class="text-white-50 text-decoration-none">Promotions</a></li>
                    <li><a href="contact.php" class="text-white-50 text-decoration-none">Contact</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">Mon compte</h5>
                <ul class="list-unstyled">
                    <?php if ($est_connecte): ?>
                        <li><a href="mon_compte.php" class="text-white-50 text-decoration-none">Mon compte</a></li>
                        <li><a href="mes_commandes.php" class="text-white-50 text-decoration-none">Mes commandes</a></li>
                        <li><a href="deconnexion.php" class="text-white-50 text-decoration-none">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="connexion.php" class="text-white-50 text-decoration-none">Connexion</a></li>
                        <li><a href="inscription.php" class="text-white-50 text-decoration-none">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">Contact</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-map-marker-alt me-2"></i> 123 Rue du Commerce, Paris</li>
                    <li><i class="fas fa-phone me-2"></i> +33 1 23 45 67 89</li>
                    <li><i class="fas fa-envelope me-2"></i> contact@monsite.com</li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4">
        
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">&copy; 2025 MonSite E-Commerce. Tous droits réservés.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="cgv.php" class="text-white-50 text-decoration-none me-3">CGV</a>
                <a href="mentions-legales.php" class="text-white-50 text-decoration-none">Mentions légales</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Scripts personnalisés -->
<script src="assets/js/script.js"></script>
</body>
</html>