<?php
// Manifeste de l'appli installable (écran d'accueil du téléphone) : le nom suit les réglages d'identité de l'admin.
require_once __DIR__ . '/inc/brand.php';
header('Content-Type: application/manifest+json; charset=utf-8');
echo json_encode([
  'name' => brand('name'), 'short_name' => mb_substr(brand('name'), 0, 12),
  'description' => brand('tagline') . ', ' . brand('city') . '. Commandez à table et suivez votre commande jusqu\'à l\'assiette.',
  'lang' => 'fr', 'start_url' => 'menu.php', 'scope' => './', 'display' => 'standalone', 'background_color' => '#0A1A2E', 'theme_color' => '#0A1A2E',
  'icons' => [['src' => 'img/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'], ['src' => 'img/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png']],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
