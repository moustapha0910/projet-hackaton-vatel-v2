<?php require_once __DIR__ . '/../brand.php'; ?>
<!DOCTYPE html>
<html lang="fr" class="bg-navy">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= brand_h('name') ?> · <?= brand_h('tagline') ?>, <?= brand_h('city') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>
  tailwind.config = { theme: { extend: {
    colors: { navy: '#0A1A2E', card: '#12243B', sky: '#ADD8E6' },
    fontFamily: { sans: ['"DM Sans"', 'system-ui', 'sans-serif'], display: ['Fraunces', 'Georgia', 'serif'] }
  } } };
</script>
<link rel="stylesheet" href="assets/accueil.css">
</head>
<body class="font-sans antialiased">
