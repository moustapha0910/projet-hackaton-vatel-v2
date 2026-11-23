<?php
require_once __DIR__ . '/inc/brand.php';
// Page d'entrée : visiter le site, espace cuisine ou espace administration.
if (isset($_GET['table'])) { header('Location: bienvenue.php?table=' . rawurlencode((string)$_GET['table'])); exit; } // anciens QR codes
?><!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= brand_h('name') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/entree.css"></head>
<body><main>
<div class="logo"><?= brand_logo() ?></div>
<h1>Bienvenue.</h1>
<p class="mut"><?= brand_h('tagline') ?>, <?= brand_h('city') ?>.</p>
<a class="btn" id="vis" href="accueil.php">Voir le site<small>Parcourir la carte, sans commander</small></a>
<p class="note">Vous êtes au restaurant ? Scannez le QR code posé sur votre table pour commander.</p>
<div class="staff">Personnel du restaurant</div>
<nav class="opts">
<a class="opt" href="acces.php?e=cuisine"><div><b>Espace cuisine</b><small>Commandes, carte et ventes du jour</small></div><span>→</span></a>
<a class="opt" href="acces.php?e=admin"><div><b>Espace administration</b><small>Tables, QR codes, statistiques, réglages</small></div><span>→</span></a>
</nav>
</main>
<script>
(function () {
  try {
    var ident = localStorage.getItem('mode') === 'client' && localStorage.getItem('cname') && localStorage.getItem('table');
    if (ident && !/[?&]choisir=1/.test(location.search)) { location.replace('accueil.php'); return; }
    document.getElementById('vis').addEventListener('click', function () { localStorage.setItem('mode', 'visiteur'); });
  } catch (e) {}
})();
</script></body></html>
