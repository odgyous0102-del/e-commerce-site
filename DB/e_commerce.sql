-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 22 mai 2026 à 23:09
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `e_commerce`
--

-- --------------------------------------------------------

--
-- Structure de la table `adresse`
--

DROP TABLE IF EXISTS `adresse`;
CREATE TABLE IF NOT EXISTS `adresse` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int NOT NULL,
  `type_adresse` enum('livraison','facturation') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom_complet` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ligne1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ligne2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_postal` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pays` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_principale` tinyint(1) DEFAULT '0',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_utilisateur` (`utilisateur_id`),
  KEY `idx_type` (`type_adresse`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `article`
--

DROP TABLE IF EXISTS `article`;
CREATE TABLE IF NOT EXISTS `article` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `prix` decimal(10,2) NOT NULL,
  `quantite_stock` int DEFAULT '0',
  `categorie_id` int DEFAULT NULL,
  `marque` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_principale` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT '1',
  `est_en_promotion` tinyint(1) DEFAULT '0',
  `prix_promotion` decimal(10,2) DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `idx_reference` (`reference`),
  KEY `idx_categorie` (`categorie_id`),
  KEY `idx_prix` (`prix`),
  KEY `idx_actif` (`est_actif`),
  KEY `idx_marque` (`marque`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `article`
--

INSERT INTO `article` (`id`, `reference`, `titre`, `description`, `prix`, `quantite_stock`, `categorie_id`, `marque`, `image_principale`, `est_actif`, `est_en_promotion`, `prix_promotion`, `date_creation`, `date_modification`) VALUES
(1, 'BPA', 'samsumg A80', 'tres bon avec une batterie performent', 304000.00, 5, 1, 'samsung', 'uploads/articles/68d9117cb5f89_BPA.jpeg', 1, 0, NULL, '2025-09-28 10:34:23', '2025-09-28 10:44:12'),
(2, 'BPAa', 'tecno speak10', 'au top', 1200000.00, 12, 1, 'tecno', 'uploads/articles/68d914870511e_BPAa.jpeg', 1, 0, NULL, '2025-09-28 10:57:11', '2025-09-28 10:57:11');

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

DROP TABLE IF EXISTS `avis`;
CREATE TABLE IF NOT EXISTS `avis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `utilisateur_id` int NOT NULL,
  `note` int NOT NULL,
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `est_approuve` tinyint(1) DEFAULT '0',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_article` (`article_id`),
  KEY `idx_utilisateur` (`utilisateur_id`)
) ;

--
-- Déchargement des données de la table `avis`
--

INSERT INTO `avis` (`id`, `article_id`, `utilisateur_id`, `note`, `commentaire`, `est_approuve`, `date_creation`) VALUES
(1, 2, 4, 3, 'fghn,,jhb njk', 1, '2025-09-30 21:40:07'),
(2, 1, 4, 2, 'bien au top', 1, '2025-10-10 14:27:18');

-- --------------------------------------------------------

--
-- Structure de la table `categorie_article`
--

DROP TABLE IF EXISTS `categorie_article`;
CREATE TABLE IF NOT EXISTS `categorie_article` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `parent_id` int DEFAULT NULL,
  `ordre_affichage` int DEFAULT '0',
  `est_actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categorie_article`
--

INSERT INTO `categorie_article` (`id`, `nom`, `description`, `parent_id`, `ordre_affichage`, `est_actif`) VALUES
(1, 'electronique', 'pour véhicule', NULL, 2, 1);

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

DROP TABLE IF EXISTS `commande`;
CREATE TABLE IF NOT EXISTS `commande` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference_commande` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `utilisateur_id` int NOT NULL,
  `statut_id` int NOT NULL,
  `adresse_livraison_id` int NOT NULL,
  `adresse_facturation_id` int NOT NULL,
  `date_commande` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_articles` decimal(10,2) NOT NULL DEFAULT '0.00',
  `frais_livraison` decimal(10,2) DEFAULT '0.00',
  `remise` decimal(10,2) DEFAULT '0.00',
  `total_commande` decimal(10,2) NOT NULL,
  `mode_paiement` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_facture` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_expedition` datetime DEFAULT NULL,
  `date_livraison` datetime DEFAULT NULL,
  `tracking_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_commande` (`reference_commande`),
  UNIQUE KEY `numero_facture` (`numero_facture`),
  KEY `idx_utilisateur` (`utilisateur_id`),
  KEY `idx_statut` (`statut_id`),
  KEY `idx_date_commande` (`date_commande`),
  KEY `idx_adresse_livraison` (`adresse_livraison_id`),
  KEY `idx_adresse_facturation` (`adresse_facturation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `historique_statut_commande`
--

DROP TABLE IF EXISTS `historique_statut_commande`;
CREATE TABLE IF NOT EXISTS `historique_statut_commande` (
  `id` int NOT NULL AUTO_INCREMENT,
  `commande_id` int NOT NULL,
  `ancien_statut_id` int DEFAULT NULL,
  `nouveau_statut_id` int NOT NULL,
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_changement` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_commande` (`commande_id`),
  KEY `idx_nouveau_statut` (`nouveau_statut_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `image_article`
--

DROP TABLE IF EXISTS `image_article`;
CREATE TABLE IF NOT EXISTS `image_article` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `chemin_image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordre_affichage` int DEFAULT '0',
  `est_principale` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_article` (`article_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `image_article`
--

INSERT INTO `image_article` (`id`, `article_id`, `chemin_image`, `ordre_affichage`, `est_principale`) VALUES
(1, 1, 'uploads/articles/68d90f2fc17a9.jpeg', 0, 0),
(2, 1, 'uploads/articles/68d90f2fc9421.jpeg', 1, 0);

-- --------------------------------------------------------

--
-- Structure de la table `ligne_commande`
--

DROP TABLE IF EXISTS `ligne_commande`;
CREATE TABLE IF NOT EXISTS `ligne_commande` (
  `id` int NOT NULL AUTO_INCREMENT,
  `commande_id` int NOT NULL,
  `article_id` int NOT NULL,
  `quantite` int NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `remise_ligne` decimal(10,2) DEFAULT '0.00',
  `total_ligne` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_commande` (`commande_id`),
  KEY `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `paiement`
--

DROP TABLE IF EXISTS `paiement`;
CREATE TABLE IF NOT EXISTS `paiement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `commande_id` int NOT NULL,
  `reference_paiement` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `mode_paiement` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('en_attente','paye','echec','rembourse') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `date_paiement` datetime DEFAULT NULL,
  `donnees_paiement` json DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_paiement` (`reference_paiement`),
  KEY `idx_commande` (`commande_id`),
  KEY `idx_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `statut_commande`
--

DROP TABLE IF EXISTS `statut_commande`;
CREATE TABLE IF NOT EXISTS `statut_commande` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ordre` int DEFAULT '0',
  `est_actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `statut_commande`
--

INSERT INTO `statut_commande` (`id`, `code`, `nom`, `description`, `ordre`, `est_actif`) VALUES
(1, 'en_attente', 'En attente', 'Commande en attente de traitement', 1, 1),
(2, 'confirmee', 'Confirmée', 'Commande confirmée et en préparation', 2, 1),
(3, 'expediee', 'Expédiée', 'Commande expédiée au client', 3, 1),
(4, 'livree', 'Livrée', 'Commande livrée au client', 4, 1),
(5, 'annulee', 'Annulée', 'Commande annulée', 5, 1),
(6, 'rembourse', 'Remboursée', 'Commande remboursée', 6, 1);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('client','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'client',
  `est_actif` tinyint(1) DEFAULT '1',
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `derniere_connexion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `est_actif`, `date_inscription`, `derniere_connexion`) VALUES
(1, 'Admin', 'System', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\'', 'admin', 1, '2025-09-25 17:59:26', NULL),
(2, 'Admin', 'System', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2025-09-25 18:47:52', '2026-03-19 14:26:57'),
(4, 'tom', 'tommmt', 'youssouf@client.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 1, '2025-09-27 17:05:30', '2025-10-15 21:25:56'),
(6, 'odg', 'youssouf', 'admina@gmail.com', 'admin123', 'admin', 1, '2025-09-27 17:11:36', NULL),
(7, 'Martin', 'Sophie', 'sophie.martin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2025-09-27 17:17:38', NULL),
(8, 'Dubois', 'Pierre', 'youssouf@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2025-09-27 17:17:38', '2025-10-10 14:24:11');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `adresse`
--
ALTER TABLE `adresse`
  ADD CONSTRAINT `adresse_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `article`
--
ALTER TABLE `article`
  ADD CONSTRAINT `article_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categorie_article` (`id`);

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `categorie_article`
--
ALTER TABLE `categorie_article`
  ADD CONSTRAINT `categorie_article_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categorie_article` (`id`);

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `commande_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commande_ibfk_2` FOREIGN KEY (`statut_id`) REFERENCES `statut_commande` (`id`),
  ADD CONSTRAINT `commande_ibfk_3` FOREIGN KEY (`adresse_livraison_id`) REFERENCES `adresse` (`id`),
  ADD CONSTRAINT `commande_ibfk_4` FOREIGN KEY (`adresse_facturation_id`) REFERENCES `adresse` (`id`);

--
-- Contraintes pour la table `historique_statut_commande`
--
ALTER TABLE `historique_statut_commande`
  ADD CONSTRAINT `historique_statut_commande_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historique_statut_commande_ibfk_2` FOREIGN KEY (`nouveau_statut_id`) REFERENCES `statut_commande` (`id`);

--
-- Contraintes pour la table `image_article`
--
ALTER TABLE `image_article`
  ADD CONSTRAINT `image_article_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  ADD CONSTRAINT `ligne_commande_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ligne_commande_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`);

--
-- Contraintes pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD CONSTRAINT `paiement_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
