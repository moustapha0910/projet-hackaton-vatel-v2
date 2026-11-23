<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/brand.php';
if (in_array($page, ['cuisine', 'carte', 'ventes'], true) && !is_kitchen()) { header('Location: acces.php?e=cuisine'); exit; }
?>
<?php /* Gabarit commun : $page vient de la page appelante */ ?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= brand_h('name') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&display=swap" rel="stylesheet">
<link rel="manifest" href="manifest.php">
<meta name="theme-color" content="#0A1A2E">
<link rel="apple-touch-icon" href="img/icon-192.png">
<link rel="stylesheet" href="assets/style.css?v=<?= (int)@filemtime(__DIR__ . '/../assets/style.css') ?>">
<link rel="stylesheet" href="assets/refonte.css?v=<?= (int)@filemtime(__DIR__ . '/../assets/refonte.css') ?>">
</head><body>
<div id="app"></div><div class="sel" id="sel"></div><div id="mod"></div>
<script>window.PAGE='<?= htmlspecialchars($page, ENT_QUOTES) ?>';window.BRAND=<?= brand_json() ?>;</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="assets/app.js?v=<?= (int)@filemtime(__DIR__ . '/../assets/app.js') ?>"></script>
</body></html>
