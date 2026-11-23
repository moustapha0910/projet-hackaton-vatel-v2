<?php
/**
 * Page d'accueil · données
 * -----------------------------------------------------------------------------
 * Charge la carte (data/state.json) et prépare les listes affichées sur l'accueil :
 *   $ringDishes  : plats du carrousel 3D            (8 plats)
 *   $trackDishes : plats du défilement horizontal   (6 plats)
 *   $heroDishes  : photos du bloc de l'en-tête      (3 plats)
 *   $dishJson    : fiches des plats utilisés, pour la fenêtre de détail (JSON)
 * Si l'admin modifie un plat (prix, nom, photo, disponibilité), l'accueil suit.
 */
require_once __DIR__ . '/../store.php';

function ac_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ac_fcfa($n) { return number_format((float)$n, 0, ',', "\u{202F}") . ' FCFA'; }

$state = jload('state', ['D' => []]);

// Plats disponibles et illustrés, indexés par identifiant
$menu = [];
foreach (($state['D'] ?? []) as $d) {
    if (!empty($d['o']) && !empty($d['img']) && is_file(__DIR__ . '/../../' . $d['img'])) $menu[(int)$d['id']] = $d;
}

/** Prend d'abord les plats « vedettes » demandés, puis complète avec le reste de la carte. */
function ac_pick(array $menu, array $preferred, int $count): array {
    $out = [];
    foreach ($preferred as $id) if (isset($menu[$id])) $out[$id] = $menu[$id];
    foreach ($menu as $id => $d) { if (count($out) >= $count) break; $out[$id] = $out[$id] ?? $d; }
    return array_slice(array_values($out), 0, $count);
}

$ringDishes  = ac_pick($menu, [12, 5, 4, 7, 11, 6, 13, 10], 8);
$trackDishes = ac_pick($menu, [12, 5, 11, 7, 13, 6], 6);
$heroDishes  = ac_pick($menu, [12, 11, 5], 3);

// Fiches complètes envoyées au navigateur (une seule fois par plat)
$used = [];
foreach (array_merge($ringDishes, $trackDishes, $heroDishes) as $d) $used[(int)$d['id']] = [
    'id' => (int)$d['id'], 'n' => $d['n'], 'c' => $d['c'] ?? '', 'p' => (int)$d['p'], 'm' => (int)($d['m'] ?? 0),
    'a' => $d['a'] ?? [], 'sig' => !empty($d['s']), 'img' => $d['img'], 'd' => $d['d'] ?? '', 't' => $d['t'] ?? [],
];
$dishJson = json_encode(array_values($used), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

// Textes de la section « Comment ça marche » (carrousel 3D)
$steps = [
    ['01', 'Scannez', 'Le QR collé sur votre table ouvre la carte. Rien à installer.'],
    ['02', 'Choisissez, ou demandez', 'Parcourez la carte, ou dites au sommelier votre budget et vos allergies.'],
    ['03', 'Suivez votre commande', 'Reçue, en préparation, prête, servie : vous voyez où elle en est.'],
];

// Chiffres clés
$stats = [
    [max(count($state['D'] ?? []), 1), 'plats à la carte'],
    [4, 'étapes de suivi, en direct'],
    [5, 'allergènes que le sommelier évite'],
    [0, 'application à installer'],
];
