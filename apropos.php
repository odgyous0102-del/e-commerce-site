<?php
// Définir les variables pour le header
$page_title = 'À Propos - TechShop | Votre partenaire technologique de confiance';
$page_description = 'Découvrez l\'histoire de TechShop, nos valeurs et notre engagement pour vous offrir les meilleurs produits technologiques aux prix les plus compétitifs.';

// Inclure le header
include 'header.php';
?>

<!-- Styles spécifiques à la page À Propos -->
<style>
    .hero-section {
        background: var(--gradient-primary);
        color: white;
        padding: 6rem 0 4rem;
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" opacity="0.1"><polygon fill="white" points="1000,1000 0,1000 0,0 500,500"/></svg>');
        background-size: cover;
    }

    .stat-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }

    .stat-number {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        background: linear-gradient(45deg, #fff, #f0f0f0);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .value-card {
        background: white;
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: var(--shadow-md);
        border-left: 6px solid var(--accent-color);
        transition: transform 0.3s ease;
        height: 100%;
    }

    .value-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .value-icon {
        width: 80px;
        height: 80px;
        background: var(--gradient-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        font-size: 2rem;
        color: white;
    }

    .team-member {
        text-align: center;
        padding: 2rem;
        border-radius: 20px;
        background: white;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
    }

    .team-member:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-lg);
    }

    .team-photo {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        margin: 0 auto 1.5rem;
        background: var(--gradient-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: white;
    }

    .timeline {
        position: relative;
        padding: 2rem 0;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 50%;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--gradient-primary);
        transform: translateX(-50%);
    }

    .timeline-item {
        position: relative;
        margin-bottom: 3rem;
        width: 50%;
        padding: 0 3rem;
    }

    .timeline-item:nth-child(odd) {
        left: 0;
    }

    .timeline-item:nth-child(even) {
        left: 50%;
    }

    .timeline-content {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: var(--shadow-md);
        position: relative;
    }

    .timeline-item:nth-child(odd) .timeline-content::after {
        content: '';
        position: absolute;
        right: -10px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 10px solid white;
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
    }

    .timeline-item:nth-child(even) .timeline-content::after {
        content: '';
        position: absolute;
        left: -10px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-right: 10px solid white;
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
    }

    .timeline-dot {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        background: var(--accent-color);
        border-radius: 50%;
        border: 4px solid white;
        box-shadow: 0 0 0 4px var(--accent-color);
    }

    .timeline-item:nth-child(odd) .timeline-dot {
        right: -10px;
    }

    .timeline-item:nth-child(even) .timeline-dot {
        left: -10px;
    }

    .milestone-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        box-shadow: var(--shadow-md);
        transition: all 0.3s ease;
        border-top: 4px solid var(--primary-color);
    }

    .milestone-card:hover {
        transform: scale(1.05);
        box-shadow: var(--shadow-lg);
    }

    .milestone-icon {
        font-size: 3rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    .cta-section {
        background: var(--gradient-accent);
        color: white;
        padding: 5rem 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .cta-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" opacity="0.1"><circle cx="200" cy="200" r="100" fill="white"/><circle cx="700" cy="300" r="150" fill="white"/><circle cx="300" cy="700" r="120" fill="white"/></svg>');
    }

    @media (max-width: 768px) {
        .timeline::before {
            left: 30px;
        }

        .timeline-item {
            width: 100%;
            left: 0 !important;
            padding-left: 70px;
            padding-right: 0;
        }

        .timeline-item:nth-child(odd) .timeline-content::after,
        .timeline-item:nth-child(even) .timeline-content::after {
            left: -10px;
            right: auto;
            border-right: 10px solid white;
            border-left: none;
        }

        .timeline-dot {
            left: 20px !important;
        }

        .hero-section {
            padding: 4rem 0 2rem;
        }

        .stat-card {
            margin-bottom: 1rem;
        }
    }

    /* Animation personnalisée */
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    .floating {
        animation: float 5s ease-in-out infinite;
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        background: var(--gradient-primary);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        margin-bottom: 1rem;
    }
</style>

<!-- Section Hero -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <h1 class="display-4 fw-bold mb-4">Notre Histoire, Votre Confiance</h1>
                <p class="lead mb-4">Depuis 2010, TechShop révolutionne l'expérience d'achat technologique en France avec des produits innovants, des prix compétitifs et un service client d'exception.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="#histoire" class="btn btn-light btn-lg px-4">
                        <i class="fas fa-history me-2"></i>Notre Histoire
                    </a>
                    <a href="#valeurs" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-heart me-2"></i>Nos Valeurs
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center" data-aos="fade-left" data-aos-delay="200">
                <div class="floating">
                    <i class="fas fa-rocket display-1 text-warning"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistiques -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card">
                    <div class="stat-number">50K+</div>
                    <div class="stat-label">Clients satisfaits</div>
                </div>
            </div>
            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card">
                    <div class="stat-number">15K+</div>
                    <div class="stat-label">Produits disponibles</div>
                </div>
            </div>
            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card">
                    <div class="stat-number">12+</div>
                    <div class="stat-label">Années d'expérience</div>
                </div>
            </div>
            <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Support client</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Notre Histoire -->
<section id="histoire" class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                <h2 class="display-5 fw-bold mb-4">Notre Histoire</h2>
                <p class="lead text-muted">D'une petite startup passionnée à leader français de la vente en ligne de produits technologiques.</p>
            </div>
        </div>

        <!-- Timeline -->
        <div class="timeline">
            <div class="timeline-item" data-aos="fade-right">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4 class="fw-bold text-primary">2010</h4>
                    <h5>Fondation de TechShop</h5>
                    <p>Création de TechShop avec une vision simple : rendre la technologie accessible à tous avec un service personnalisé.</p>
                </div>
            </div>
            <div class="timeline-item" data-aos="fade-left">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4 class="fw-bold text-primary">2013</h4>
                    <h5>Premier million d'euros</h5>
                    <p>Atteinte du premier million d'euros de chiffre d'affaires et expansion de notre catalogue produit.</p>
                </div>
            </div>
            <div class="timeline-item" data-aos="fade-right">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4 class="fw-bold text-primary">2016</h4>
                    <h5>Ouverture internationale</h5>
                    <p>Lancement de nos services en Europe avec une plateforme multilingue et multicurrency.</p>
                </div>
            </div>
            <div class="timeline-item" data-aos="fade-left">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4 class="fw-bold text-primary">2020</h4>
                    <h5>Innovation technologique</h5>
                    <p>Développement de notre application mobile et mise en place de la réalité augmentée pour visualiser les produits.</p>
                </div>
            </div>
            <div class="timeline-item" data-aos="fade-right">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4 class="fw-bold text-primary">2024</h4>
                    <h5>Leader du marché</h5>
                    <p>TechShop devient la référence française avec plus de 50 000 clients satisfaits et 15 000 produits disponibles.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Nos Valeurs -->
<section id="valeurs" class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                <h2 class="display-5 fw-bold mb-4">Nos Valeurs Fondamentales</h2>
                <p class="lead text-muted">Les principes qui guident chacune de nos actions et décisions.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Satisfaction Client</h4>
                    <p class="text-muted">Notre priorité absolue. Chaque décision est prise dans l'objectif de dépasser vos attentes et garantir votre satisfaction complète.</p>
                </div>
            </div>
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Transparence</h4>
                    <p class="text-muted">Prix clairs, descriptions détaillées, avis authentiques. Nous croyons en une relation de confiance basée sur l'honnêteté.</p>
                </div>
            </div>
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Innovation</h4>
                    <p class="text-muted">Nous restons à la pointe de la technologie pour vous proposer les dernières innovations et améliorer constamment votre expérience.</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="400">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Développement Durable</h4>
                    <p class="text-muted">Engagés pour l'environnement, nous favorisons les produits éco-responsables et optimisons notre logistique pour réduire notre empreinte carbone.</p>
                </div>
            </div>
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="500">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Qualité Premium</h4>
                    <p class="text-muted">Nous sélectionnons rigoureusement chaque produit pour vous garantir une qualité exceptionnelle et une durabilité maximale.</p>
                </div>
            </div>
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="600">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Service Personnalisé</h4>
                    <p class="text-muted">Notre équipe dédiée vous accompagne à chaque étape, du choix du produit à l'après-vente, avec des conseils experts et personnalisés.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Notre Équipe -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center" data-aos="fade-up">
                <h2 class="display-5 fw-bold mb-4">Rencontrez Notre Équipe</h2>
                <p class="lead text-muted">Des passionnés de technologie dévoués à votre satisfaction.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="team-member">
                    <div class="team-photo">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h5 class="fw-bold">Marie Dubois</h5>
                    <p class="text-primary mb-2">CEO & Fondatrice</p>
                    <p class="text-muted small">Visionnaire passionnée avec 15 ans d'expérience dans le e-commerce technologique.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="team-member">
                    <div class="team-photo">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h5 class="fw-bold">Thomas Martin</h5>
                    <p class="text-primary mb-2">Directeur Technique</p>
                    <p class="text-muted small">Expert en innovation et développement, garant de la performance de notre plateforme.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="team-member">
                    <div class="team-photo">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h5 class="fw-bold">Sophie Lambert</h5>
                    <p class="text-primary mb-2">Responsable Commerciale</p>
                    <p class="text-muted small">Spécialiste des relations clients et de la curation de notre catalogue produit.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                <div class="team-member">
                    <div class="team-photo">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h5 class="fw-bold">David Petit</h5>
                    <p class="text-primary mb-2">Responsable Support</p>
                    <p class="text-muted small">Déterminé à offrir la meilleure expérience client à chaque interaction.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Engagements -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <h2 class="display-6 fw-bold mb-4">Nos Engagements</h2>
                <div class="d-flex align-items-start mb-4">
                    <div class="feature-icon me-4">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold">Livraison Express</h5>
                        <p class="mb-0">Livraison gratuite en 24-48h sur toute la France métropolitaine</p>
                    </div>
                </div>
                <div class="d-flex align-items-start mb-4">
                    <div class="feature-icon me-4">
                        <i class="fas fa-undo"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold">Retours Faciles</h5>
                        <p class="mb-0">30 jours pour changer d'avis, retours gratuits et sans frais cachés</p>
                    </div>
                </div>
                <div class="d-flex align-items-start mb-4">
                    <div class="feature-icon me-4">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold">Garantie Étendue</h5>
                        <p class="mb-0">Garantie constructeur + 1 an de garantie supplémentaire offerte</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="milestone-card bg-white text-dark">
                            <div class="milestone-icon">
                                <i class="fas fa-award"></i>
                            </div>
                            <h4 class="fw-bold">Prix Excellence 2023</h4>
                            <p class="text-muted mb-0">Meilleure expérience client</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="milestone-card bg-white text-dark">
                            <div class="milestone-icon">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <h4 class="fw-bold">Certifié Vert</h4>
                            <p class="text-muted mb-0">Engagement écologique</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="milestone-card bg-white text-dark">
                            <div class="milestone-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h4 class="fw-bold">Sécurité Max</h4>
                            <p class="text-muted mb-0">Paiements 100% sécurisés</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="milestone-card bg-white text-dark">
                            <div class="milestone-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h4 class="fw-bold">4.9/5</h4>
                            <p class="text-muted mb-0">Avis clients Trustpilot</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center" data-aos="zoom-in">
                <h2 class="display-5 fw-bold mb-4">Prêt à vivre l'expérience TechShop ?</h2>
                <p class="lead mb-4">Rejoignez des milliers de clients satisfaits et découvrez la différence TechShop.</p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="boutique.php" class="btn btn-light btn-lg px-5">
                        <i class="fas fa-shopping-bag me-2"></i>Découvrir la Boutique
                    </a>
                    <a href="contact.php" class="btn btn-outline-light btn-lg px-5">
                        <i class="fas fa-envelope me-2"></i>Nous Contacter
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Animation des statistiques
    function animateStats() {
        const stats = document.querySelectorAll('.stat-number');
        stats.forEach(stat => {
            const target = parseInt(stat.textContent);
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    stat.textContent = target + (stat.textContent.includes('+') ? '+' : '');
                    clearInterval(timer);
                } else {
                    stat.textContent = Math.floor(current) + (stat.textContent.includes('+') ? '+' : '');
                }
            }, 50);
        });
    }

    // Observer pour l'animation des statistiques
    document.addEventListener('DOMContentLoaded', function() {
        const statsSection = document.querySelector('.bg-light');
        if (statsSection) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateStats();
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            observer.observe(statsSection);
        }
    });
</script>

<?php
// Inclure le footer
include 'footer.php';
?>