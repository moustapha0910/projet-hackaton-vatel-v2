<?php
/**
 * Page d'accueil
 * -----------------------------------------------------------------------------
 * Ce fichier ne fait qu'assembler la page. Chaque morceau a son propre fichier :
 *
 *   inc/accueil/data.php            données (carte, plats vedettes, textes)
 *   inc/accueil/head.php            <head>, polices, Tailwind
 *   inc/accueil/header.php          transition de page + en-tête fixe
 *   inc/accueil/sections/*.php      hero · stage (carrousel 3D) · sommelier · signatures · stats · cta
 *   inc/accueil/dish-modal.php      fenêtre « fiche plat »
 *   inc/accueil/footer.php          pied de page + chargement des scripts
 *
 *   assets/accueil.css              styles propres à l'accueil
 *   assets/accueil/core.js          utilitaires partagés (espace de noms Accueil)
 *   assets/accueil/session.js       table, nom du client, mode visite, panier
 *   assets/accueil/modal.js         fiche plat animée
 *   assets/accueil/motion.js        animations de défilement (GSAP / Lenis)
 *   assets/accueil/boot.js          chargement des bibliothèques puis démarrage
 */
require __DIR__ . '/inc/accueil/data.php';
require __DIR__ . '/inc/accueil/head.php';
require __DIR__ . '/inc/accueil/header.php';
?>
<main>
<?php
foreach (['hero', 'stage', 'sommelier', 'signatures', 'stats', 'cta'] as $section) {
    require __DIR__ . "/inc/accueil/sections/$section.php";
}
?>
</main>
<?php require __DIR__ . '/inc/accueil/footer.php';
