<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/store.php';
require_once __DIR__ . '/inc/ai.php';
session_start();
$PW = defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : 'admin123';
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fcfa($n) { return number_format((float)$n, 0, ',', ' ') . ' FCFA'; }
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
if (empty($_SESSION['adm'])) {
  $err = '';
  if (isset($_POST['pw'])) {
    if (hash_equals((string)$PW, (string)$_POST['pw'])) { $_SESSION['adm'] = 1; $_SESSION['csrf'] = bin2hex(random_bytes(16)); header('Location: admin.php'); exit; }
    $err = 'Mot de passe incorrect.';
  }
  ?><!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Administration · SaveurIA</title><link rel="stylesheet" href="assets/admin.css"></head>
<body class="login"><form method="post" class="box"><div class="logo">Saveur<i>IA</i><small>Administration</small></div><p class="mut" style="margin:0">Accès réservé au gérant.</p><?php if ($err): ?><p class="err" style="margin:0"><?= h($err) ?></p><?php endif; ?><input type="password" name="pw" placeholder="Mot de passe" autofocus required><button class="btn">Se connecter</button></form></body></html><?php
  exit;
}
$csrf = $_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(16)));
$hasState = is_file(dpath('state'));
$st = jload('state', ['D' => [], 'orders' => [], 'reviews' => [], 'nid' => 301, 'uid' => 20]);
$tables = jload('tables', []);
$t = in_array($_GET['t'] ?? 'dash', ['dash', 'tables', 'orders', 'reviews', 'settings', 'export'], true) ? ($_GET['t'] ?? 'dash') : 'dash';
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
  $a = $_POST['a'] ?? ''; $msg = ''; $saveState = false;
  switch ($a) {
    case 'table_add':
      $n = pad($_POST['n'] ?? 0);
      if ((int)$n < 1 || (int)$n > 999) $msg = 'Numéro invalide (1 à 999).';
      elseif (isset($tables[$n])) $msg = "La table $n existe déjà.";
      else { $tables[$n] = ['label' => trim((string)($_POST['label'] ?? '')), 'seats' => max(1, (int)($_POST['seats'] ?? 4)), 'on' => 1]; ksort($tables); jsave('tables', $tables); $msg = "Table $n créée."; }
      break;
    case 'table_bulk':
      $f = max(1, (int)($_POST['from'] ?? 1)); $to = min(999, (int)($_POST['to'] ?? 1)); $c = 0;
      if ($to - $f > 99) $to = $f + 99;
      for ($i = $f; $i <= $to; $i++) { $n = pad($i); if (!isset($tables[$n])) { $tables[$n] = ['label' => '', 'seats' => max(1, (int)($_POST['seats'] ?? 4)), 'on' => 1]; $c++; } }
      ksort($tables); jsave('tables', $tables); $msg = "$c table(s) créée(s).";
      break;
    case 'table_edit':
      $n = pad($_POST['n'] ?? 0);
      if (isset($tables[$n])) { $tables[$n]['label'] = trim((string)($_POST['label'] ?? '')); $tables[$n]['seats'] = max(1, (int)($_POST['seats'] ?? 4)); jsave('tables', $tables); $msg = "Table $n modifiée."; }
      break;
    case 'table_del': unset($tables[pad($_POST['n'] ?? 0)]); jsave('tables', $tables); $msg = 'Table supprimée.'; break;
    case 'table_toggle':
      $n = pad($_POST['n'] ?? 0);
      if (isset($tables[$n])) { $tables[$n]['on'] = empty($tables[$n]['on']) ? 1 : 0; jsave('tables', $tables); }
      break;
    case 'order_set':
      foreach ($st['orders'] as &$o) { if (($o['id'] ?? '') === ($_POST['id'] ?? '')) $o['st'] = max(0, min(3, (int)($_POST['st'] ?? 0))); }
      unset($o); $saveState = true; $msg = 'Statut modifié.'; break;
    case 'order_pay':
      foreach ($st['orders'] as &$o) { if (($o['id'] ?? '') === ($_POST['id'] ?? '')) $o['paid'] = empty($o['paid']) ? ($o['pay'] ?? 'Espèces') : 0; }
      unset($o); $saveState = true; $msg = 'Paiement modifié.'; break;
    case 'order_del':
      $st['orders'] = array_values(array_filter($st['orders'], fn($o) => ($o['id'] ?? '') !== ($_POST['id'] ?? ''))); $saveState = true; $msg = 'Commande supprimée.'; break;
    case 'reset':
      $st['orders'] = []; $st['reviews'] = []; $saveState = true; $msg = 'Commandes et avis effacés.'; break;
  }
  if ($saveState && $hasState) jsave('state', $st);
  $_SESSION['flash'] = $msg; header('Location: admin.php?t=' . $t); exit;
}
$S = ['Reçue', 'En préparation', 'Prête', 'Servie'];
$orders = $st['orders'] ?? [];
if ($t === 'export') {
  header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="commandes-saveuria.csv"');
  echo "\xEF\xBB\xBF"; $f = fopen('php://output', 'w');
  fputcsv($f, ['Commande', 'Date', 'Table', 'Plats', 'Total', 'Paiement', 'Réglé', 'Statut', 'Note', 'Avis'], ';', '"', '\\');
  foreach ($orders as $r) fputcsv($f, [$r['id'] ?? '', date('Y-m-d H:i', (int)(($r['at'] ?? 0) / 1000)), $r['table'] ?? '', implode(' | ', array_map(fn($i) => $i['q'] . 'x ' . $i['n'], $r['items'] ?? [])), $r['tot'] ?? 0, $r['pay'] ?? '', empty($r['paid']) ? 'non' : 'oui', $S[$r['st'] ?? 0] ?? '', $r['rs'] ?? '', $r['rt'] ?? ''], ';', '"', '\\');
  exit;
}
function pf($a, $hid, $label, $cls = 'btn sm', $confirm = '') {
  global $csrf;
  $s = '<form method="post" class="inl"' . ($confirm ? ' onsubmit="return confirm(' . h(json_encode($confirm, JSON_UNESCAPED_UNICODE)) . ')"' : '') . '><input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="a" value="' . h($a) . '">';
  foreach ($hid as $k => $v) $s .= '<input type="hidden" name="' . h($k) . '" value="' . h($v) . '">';
  return $s . '<button class="' . h($cls) . '">' . h($label) . '</button></form>';
}
// ---- icônes SVG (stroke, alignées sur la charte graphique du site)
$icons = [
  'dash'    => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
  'tables'  => '<rect x="4" y="4" width="6" height="6" rx="1.2"/><rect x="14" y="4" width="6" height="6" rx="1.2"/><rect x="4" y="14" width="6" height="6" rx="1.2"/><path d="M14 14h2.5v2.5H14zM19 14v.01M14 19h.01M19 19v.01M21.5 21.5v.01"/>',
  'orders'  => '<path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/>',
  'reviews' => '<path d="M12 3l2.7 5.6 6.3.9-4.5 4.4 1 6.3-5.5-2.9-5.5 2.9 1-6.3L3 9.5l6.3-.9z"/>',
  'settings'=> '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
  'wallet'  => '<path d="M20 7H5a2 2 0 0 1 0-4h13v4z"/><path d="M3 5v14a2 2 0 0 0 2 2h15V7"/><circle cx="16" cy="14" r="1"/>',
  'receipt' => '<path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/>',
  'trend'   => '<path d="M3 17l6-6 4 4 7-7"/><path d="M14 8h6v6"/>',
  'check'   => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/>',
  'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  'star'    => '<path d="M12 3l2.7 5.6 6.3.9-4.5 4.4 1 6.3-5.5-2.9-5.5 2.9 1-6.3L3 9.5l6.3-.9z"/>',
  'chef'    => '<path d="M17 21a1 1 0 0 0 1-1v-5.4c0-.5.3-.8.7-1a4 4 0 0 0-2.1-7.6 5 5 0 0 0-9.2 0 4 4 0 0 0-2.1 7.6c.4.2.7.5.7 1V20a1 1 0 0 0 1 1zM6 17h12"/>',
  'site'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18z"/>',
  'out'     => '<path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4"/><path d="M10 12h9M14 7l5 5-5 5"/>',
];
function ic($k, $sz = 20) { global $icons; return '<svg width="' . $sz . '" height="' . $sz . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($icons[$k] ?? '') . '</svg>'; }
// ---- statistiques
$p = in_array($_GET['p'] ?? 'today', ['today', '7', '30', 'all'], true) ? ($_GET['p'] ?? 'today') : 'today';
$from = ['today' => strtotime('today'), '7' => strtotime('-6 days midnight'), '30' => strtotime('-29 days midnight'), 'all' => 0][$p];
$L = array_values(array_filter($orders, fn($o) => ($o['at'] ?? 0) / 1000 >= $from));
$ca = array_sum(array_column($L, 'tot')); $n = count($L);
$paid = 0; $top = []; $byH = array_fill(0, 24, 0); $byT = []; $rates = [];
$byDay = []; $byPay = ['Mobile Money' => 0, 'Espèces' => 0, 'Carte' => 0]; $rateDist = array_fill(1, 5, 0);
foreach ($L as $o) {
  if (!empty($o['paid'])) $paid += $o['tot'];
  foreach (($o['items'] ?? []) as $i) $top[$i['n']] = ($top[$i['n']] ?? 0) + $i['q'];
  $byH[(int)date('G', (int)(($o['at'] ?? 0) / 1000))]++;
  $k = pad($o['table'] ?? 0); $byT[$k] = ['n' => ($byT[$k]['n'] ?? 0) + 1, 'ca' => ($byT[$k]['ca'] ?? 0) + $o['tot']];
  if (isset($o['rs'])) $rates[] = $o['rs'];
  $d = date('Y-m-d', (int)(($o['at'] ?? 0) / 1000));
  $byDay[$d] = ($byDay[$d] ?? 0) + (float)($o['tot'] ?? 0);
  $pk = (string)($o['pay'] ?? 'Espèces'); if ($pk === '') $pk = 'Espèces';
  $byPay[$pk] = ($byPay[$pk] ?? 0) + (float)($o['tot'] ?? 0);
  if (isset($o['rs'])) $rateDist[max(1, min(5, (int)$o['rs']))]++;
}
arsort($top); ksort($byDay);
$avgNum = $rates ? round(array_sum($rates) / count($rates), 1) : null;
$avg = $avgNum !== null ? $avgNum . ' / 5' : '—';
$mxTca = 0; foreach ($byT as $v) $mxTca = max($mxTca, (float)$v['ca']);
$topL = []; $topV = []; $i = 0; foreach ($top as $name => $q) { if (++$i > 8) break; $topL[] = $name; $topV[] = $q; }
$payL = []; $payV = []; foreach ($byPay as $k => $v) if ($v > 0) { $payL[] = $k; $payV[] = $v; }
$chartData = [
  'days'  => array_values(array_map(fn($d) => date('d/m', strtotime($d)), array_keys($byDay))),
  'dayCa' => array_values($byDay),
  'topL'  => $topL, 'topV' => $topV,
  'payL'  => $payL, 'payV' => $payV,
  'hours' => $byH,
  'rateL' => ['1 ★', '2 ★', '3 ★', '4 ★', '5 ★'],
  'rateV' => array_values($rateDist),
];
$pending = count(array_filter($orders, fn($o) => (int)($o['st'] ?? 0) < 3));
$tabs = ['dash' => 'Tableau de bord', 'tables' => 'Tables & QR', 'orders' => 'Commandes', 'reviews' => 'Avis', 'settings' => 'Réglages'];
?><!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Administration · SaveurIA</title><link rel="stylesheet" href="assets/admin.css"></head><body>
<div class="app">
  <aside class="side" id="side">
    <div class="side-head"><div class="logo">Saveur<i>IA</i><small>Administration</small></div></div>
    <nav class="nav" id="nav">
      <a href="admin.php?t=dash" class="<?= $t === 'dash' ? 'on' : '' ?>"><?= ic('dash') ?> Tableau de bord</a>
      <a href="admin.php?t=tables" class="<?= $t === 'tables' ? 'on' : '' ?>"><?= ic('tables') ?> Tables &amp; QR</a>
      <a href="admin.php?t=orders" class="<?= $t === 'orders' ? 'on' : '' ?>"><?= ic('orders') ?> Commandes<?php if ($pending): ?><span class="cnt"><?= $pending ?></span><?php endif; ?></a>
      <a href="admin.php?t=reviews" class="<?= $t === 'reviews' ? 'on' : '' ?>"><?= ic('reviews') ?> Avis</a>
      <a href="admin.php?t=settings" class="<?= $t === 'settings' ? 'on' : '' ?>"><?= ic('settings') ?> Réglages</a>
      <div class="nav-sep"></div>
      <a href="cuisine.php"><?= ic('chef') ?> Espace cuisine</a>
      <a href="index.php"><?= ic('site') ?> Voir le site</a>
      <a href="admin.php?logout=1" class="logout"><?= ic('out') ?> Déconnexion</a>
    </nav>
  </aside>
  <div class="overlay" id="ovl"></div>
  <div class="main">
    <header class="topbar">
      <button class="burger" id="burger" aria-label="Menu">☰</button>
      <div class="page-title"><?= h($tabs[$t]) ?><small>SaveurIA · gestion du restaurant</small></div>
    </header>
    <main class="wrap">

<?php if ($t === 'dash'): ?>
      <div class="noprint" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px">
        <div class="seg"><?php foreach (['today' => "Aujourd'hui", '7' => '7 jours', '30' => '30 jours', 'all' => 'Tout'] as $k => $v): ?><a href="admin.php?t=dash&p=<?= $k ?>" class="<?= $p === (string)$k ? 'on' : '' ?>"><?= $v ?></a><?php endforeach; ?></div>
        <span class="mut" style="font-size:13px"><?= count($L) ?> commande(s) · <?= fcfa($ca) ?></span>
      </div>

      <div class="kpis">
        <div class="kpi" data-tone="red" style="animation-delay:.02s"><div class="top"><span class="ic"><?= ic('wallet') ?></span><small>Chiffre d'affaires</small></div><b data-value="<?= $ca ?>" data-money="1"><?= fcfa($ca) ?></b></div>
        <div class="kpi" data-tone="blue" style="animation-delay:.06s"><div class="top"><span class="ic"><?= ic('receipt') ?></span><small>Commandes</small></div><b data-value="<?= $n ?>"><?= $n ?></b></div>
        <div class="kpi" data-tone="blue" style="animation-delay:.10s"><div class="top"><span class="ic"><?= ic('trend') ?></span><small>Ticket moyen</small></div><b data-value="<?= $n ? round($ca / $n) : 0 ?>" data-money="1"><?= fcfa($n ? round($ca / $n) : 0) ?></b></div>
        <div class="kpi" data-tone="ok" style="animation-delay:.14s"><div class="top"><span class="ic"><?= ic('check') ?></span><small>Encaissé</small></div><b data-value="<?= $paid ?>" data-money="1"><?= fcfa($paid) ?></b></div>
        <div class="kpi" data-tone="warn" style="animation-delay:.18s"><div class="top"><span class="ic"><?= ic('clock') ?></span><small>À encaisser</small></div><b data-value="<?= $ca - $paid ?>" data-money="1"><?= fcfa($ca - $paid) ?></b></div>
        <div class="kpi" data-tone="warn" style="animation-delay:.22s"><div class="top"><span class="ic"><?= ic('star') ?></span><small>Note moyenne</small></div><b data-value="<?= $avgNum ?? 0 ?>" data-dec="1"><?= $avgNum !== null ? $avgNum . ' / 5' : '—' ?></b></div>
      </div>

      <div class="charts">
        <div class="card c8"><h3><?= ic('trend', 18) ?> Chiffre d'affaires — évolution</h3><div class="chwrap"><canvas id="chCa"></canvas><div class="fb"><?php if (!$byDay): ?><p class="mut">Aucune vente sur la période.</p><?php else: $mxd = max($byDay) ?: 1; foreach ($byDay as $d => $v): ?><div style="display:flex;align-items:center;gap:8px;margin-top:6px"><span class="mut" style="width:44px;font-size:12px"><?= date('d/m', strtotime($d)) ?></span><div class="bar" style="flex:1"><i style="width:<?= round($v / $mxd * 100) ?>%"></i></div><b style="width:96px;text-align:right;font-size:13px"><?= fcfa($v) ?></b></div><?php endforeach; endif; ?></div></div></div>
        <div class="card c4"><h3><?= ic('wallet', 18) ?> Paiements</h3><div class="chwrap"><canvas id="chPay"></canvas><div class="fb"><?php foreach ($byPay as $k => $v): ?><div style="margin-top:10px"><?= h($k) ?> <b style="float:right"><?= fcfa($v) ?></b></div><?php endforeach; ?></div></div></div>
        <div class="card c6"><h3><?= ic('orders', 18) ?> Plats les plus vendus</h3><div class="chwrap"><canvas id="chTop"></canvas><div class="fb"><?php if (!$top) echo '<p class="mut">Aucune vente sur la période.</p>'; $mx = $top ? max($top) : 1; $i = 0; foreach ($top as $name => $q): if (++$i > 8) break; ?><div style="margin-top:10px"><?= h($name) ?> <b style="float:right"><?= $q ?></b><div class="bar"><i style="width:<?= round($q / $mx * 100) ?>%"></i></div></div><?php endforeach; ?></div></div></div>
        <div class="card c6"><h3><?= ic('clock', 18) ?> Commandes par heure</h3><div class="chwrap"><canvas id="chHour"></canvas><div class="fb"><div class="hours"><?php $mh = max($byH) ?: 1; for ($i = 0; $i < 24; $i++): ?><div title="<?= $i ?> h : <?= $byH[$i] ?>"><i style="height:<?= round($byH[$i] / $mh * 100) ?>%"></i><small><?= $i ?></small></div><?php endfor; ?></div></div></div></div>
        <div class="card c12"><h3><?= ic('star', 18) ?> Répartition des notes</h3><div class="chwrap sm"><canvas id="chRate"></canvas><div class="fb"><?php for ($s = 5; $s >= 1; $s--): ?><div style="margin-top:10px"><?= str_repeat('★', $s) ?> <b style="float:right"><?= $rateDist[$s] ?></b><div class="bar"><i style="width:<?= $rateDist[$s] / (max($rateDist) ?: 1) * 100 ?>%"></i></div></div><?php endfor; ?></div></div></div>
      </div>

      <div class="card mt"><h3><?= ic('tables', 18) ?> Par table</h3>
      <?php if (!$byT): ?><p class="mut">Aucune commande sur la période.</p><?php else: ksort($byT); ?>
      <div class="tbl-wrap" style="border:0;margin-top:8px"><table><tr><th>Table</th><th>Commandes</th><th>Chiffre d'affaires</th><th style="width:34%">Part</th></tr><?php foreach ($byT as $k => $v): ?><tr><td><b>Table <?= h($k) ?></b></td><td><?= $v['n'] ?></td><td><?= fcfa($v['ca']) ?></td><td><div class="bar" style="max-width:220px;margin-top:8px"><i style="width:<?= $mxTca ? round($v['ca'] / $mxTca * 100) : 0 ?>%"></i></div></td></tr><?php endforeach; ?></table></div><?php endif; ?></div>

<?php elseif ($t === 'tables'): $base = base_url(); $local = preg_match('~//(localhost|127\.)~', $base); ?>
      <div class="card" style="margin-bottom:16px"><h3><?= ic('tables', 18) ?> Tables &amp; QR codes</h3><p class="mut" style="margin:6px 0 0">Chaque QR code ouvre le site avec le numéro de la table : le client le voit sur l'accueil, dans son panier et sur sa commande.</p></div>
      <div class="g2 noprint" style="margin-bottom:16px">
        <form method="post" class="card"><h3>Créer une table</h3><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="table_add">
        <label>Numéro</label><input type="number" name="n" min="1" max="999" value="<?= $tables ? (int)max(array_map('intval', array_keys($tables))) + 1 : 1 ?>" required><label>Nom / zone (facultatif)</label><input name="label" placeholder="Terrasse, Salon VIP…"><label>Places</label><input type="number" name="seats" value="4" min="1"><p style="margin-top:14px"><button class="btn">Créer et générer le QR</button></p></form>
        <form method="post" class="card"><h3>Créer plusieurs tables</h3><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="table_bulk">
        <label>De la table</label><input type="number" name="from" min="1" value="1"><label>À la table</label><input type="number" name="to" min="1" value="10"><label>Places</label><input type="number" name="seats" value="4" min="1"><p style="margin-top:14px"><button class="btn">Générer</button> <span class="mut" style="font-size:13px">(100 max à la fois)</span></p></form>
      </div>
      <?php if ($tables): ?><p class="noprint" style="margin:0 0 16px"><button class="btn gh" onclick="window.print()">Imprimer tous les QR codes</button></p><?php endif; ?>
      <div class="grid"><?php if (!$tables): ?><p class="mut">Aucune table pour le moment : créez la première ci-dessus.</p><?php endif;
      $allT = []; foreach ($orders as $o) { $k = pad($o['table'] ?? 0); $allT[$k] = ($allT[$k] ?? 0) + 1; }
      foreach ($tables as $num => $tb): $u = $base . '/index.php?table=' . $num; ?>
        <div class="card tc" style="<?= empty($tb['on']) ? 'opacity:.55' : '' ?>"><div class="qr" id="qr<?= h($num) ?>" data-u="<?= h($u) ?>"></div><b>Table <?= h($num) ?></b>
        <div class="mut" style="margin:6px 0"><?= h($tb['label'] ?: 'Sans nom') ?> · <?= (int)($tb['seats'] ?? 4) ?> places<br><?= (int)($allT[$num] ?? 0) ?> commande(s)<?= empty($tb['on']) ? ' · désactivée' : '' ?></div>
        <div class="noprint" style="margin-top:10px;display:flex;gap:4px;justify-content:center;flex-wrap:wrap"><button class="btn sm gh" onclick="dl('qr<?= h($num) ?>','<?= h($num) ?>')">PNG</button> <button class="btn sm gh" data-u="<?= h($u) ?>" onclick="cp(this.dataset.u);this.textContent='Copié'">Lien</button>
        <?= pf('table_toggle', ['n' => $num], empty($tb['on']) ? 'Activer' : 'Désactiver', 'btn sm gh') ?><?= pf('table_del', ['n' => $num], 'Supprimer', 'btn sm gh', 'Supprimer la table ' . $num . ' ?') ?></div>
        <form method="post" style="margin-top:10px"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="table_edit"><input type="hidden" name="n" value="<?= h($num) ?>"><div style="display:flex;gap:6px"><input name="label" value="<?= h($tb['label'] ?? '') ?>" placeholder="Nom" style="flex:1"><input type="number" name="seats" value="<?= (int)($tb['seats'] ?? 4) ?>" min="1" style="width:70px"><button class="btn sm">OK</button></div></form></div>
      <?php endforeach; ?></div>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
      <script>
      document.querySelectorAll('.qr').forEach(function (e) { new QRCode(e, { text: e.dataset.u, width: 170, height: 170 }); });
      function dl(id, n) { var e = document.getElementById(id), c = e.querySelector('canvas'), a = document.createElement('a'); a.href = c ? c.toDataURL('image/png') : e.querySelector('img').src; a.download = 'table-' + n + '.png'; a.click(); }
      function cp(u) { if (navigator.clipboard) navigator.clipboard.writeText(u); }
      </script>

<?php elseif ($t === 'orders'): $fs = $_GET['s'] ?? ''; $ft = $_GET['tb'] ?? '';
$R = array_reverse($orders); $R = array_filter($R, fn($o) => ($fs === '' || (string)($o['st'] ?? 0) === $fs) && ($ft === '' || pad($o['table'] ?? 0) === pad($ft))); $R = array_slice($R, 0, 200); ?>
      <form class="noprint card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px"><input type="hidden" name="t" value="orders"><div style="flex:1;min-width:160px"><label style="margin-top:0">Statut</label><select name="s"><option value="">Tous les statuts</option><?php foreach ($S as $i => $v): ?><option value="<?= $i ?>" <?= $fs === (string)$i ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div><div style="flex:1;min-width:130px"><label style="margin-top:0">N° table</label><input type="number" name="tb" placeholder="N° table" value="<?= h($ft) ?>"></div><div><button class="btn">Filtrer</button></div></form>
      <div class="card" style="padding:0"><div class="tbl-wrap" style="border:0;border-radius:0"><table><tr><th>Commande</th><th>Date</th><th>Table</th><th>Plats</th><th>Total</th><th>Statut</th><th>Paiement</th><th></th></tr>
      <?php if (!$R): ?><tr><td colspan="8" class="mut">Aucune commande.</td></tr><?php endif; foreach ($R as $o): ?>
      <tr><td><b>#<?= h($o['id'] ?? '') ?></b></td><td><?= date('d/m H:i', (int)(($o['at'] ?? 0) / 1000)) ?></td><td><?= h(pad($o['table'] ?? 0)) ?></td><td><?= h(implode(' · ', array_map(fn($i) => $i['q'] . '× ' . $i['n'], $o['items'] ?? []))) ?><?= !empty($o['note']) ? '<br><span class="mut">' . h($o['note']) . '</span>' : '' ?></td><td><?= fcfa($o['tot'] ?? 0) ?></td>
      <td><form method="post" class="inl"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="order_set"><input type="hidden" name="id" value="<?= h($o['id'] ?? '') ?>"><select name="st" onchange="this.form.submit()" class="s<?= (int)($o['st'] ?? 0) ?>" style="font-weight:700;padding:7px 30px 7px 11px"><?php foreach ($S as $i => $v): ?><option value="<?= $i ?>" <?= (int)($o['st'] ?? 0) === $i ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></form></td>
      <td><?= pf('order_pay', ['id' => $o['id'] ?? ''], empty($o['paid']) ? 'À encaisser' : 'Réglé', empty($o['paid']) ? 'btn sm' : 'btn sm ok') ?></td>
      <td><?= pf('order_del', ['id' => $o['id'] ?? ''], 'Supprimer', 'btn sm gh', 'Supprimer la commande ' . ($o['id'] ?? '') . ' ?') ?></td></tr><?php endforeach; ?></table></div></div>

<?php elseif ($t === 'reviews'): $RV = array_reverse(array_values(array_filter($orders, fn($o) => isset($o['rs'])))); $all = array_column($RV, 'rs'); ?>
      <div class="card" style="margin-bottom:16px"><h3><?= ic('reviews', 18) ?> Avis clients</h3><p class="mut" style="margin:6px 0 0">Note moyenne (toutes périodes) : <?php echo $all ? '<b style="color:var(--warn)">' . round(array_sum($all) / count($all), 1) . ' / 5</b> sur ' . count($all) . ' avis' : '—'; ?></p></div>
      <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))"><?php if (!$RV): ?><p class="mut">Aucun avis pour le moment.</p><?php endif; foreach ($RV as $o): ?><div class="card"><div class="stars"><?= str_repeat('★', (int)$o['rs']) . '<span style="color:rgba(255,255,255,.25)">' . str_repeat('★', 5 - (int)$o['rs']) . '</span>' ?></div><p style="margin:12px 0 14px"><?= !empty($o['rt']) ? h($o['rt']) : '<span class="mut">Sans commentaire</span>' ?></p><small class="mut">#<?= h($o['id']) ?> · Table <?= h(pad($o['table'] ?? 0)) ?> · <?= date('d/m H:i', (int)(($o['at'] ?? 0) / 1000)) ?></small></div><?php endforeach; ?></div>

<?php else: $prov = ai_provider(); ?>
      <div class="g2">
        <div class="card"><h3><?= ic('spark', 18) ?> Sommelier IA</h3><p>Fournisseur actif : <b><?= $prov ? h($prov) : 'mots-clés (aucune clé)' ?></b></p><p><a href="api.php?a=aitest" target="_blank">Tester la connexion IA</a> · <a href="api.php?a=aitest&list=1" target="_blank">Lister les modèles Gemini</a></p><p class="mut">Les clés se règlent dans <b>config.php</b>.</p></div>
        <div class="card"><h3><?= ic('receipt', 18) ?> Données</h3><p>Commandes : <b><?= count($orders) ?></b> · Plats : <b><?= count($st['D'] ?? []) ?></b> · Tables : <b><?= count($tables) ?></b></p><p style="margin-top:12px"><a class="btn sm" href="admin.php?t=export">Exporter les commandes (CSV)</a></p><p><?= pf('reset', [], 'Effacer commandes et avis', 'btn sm gh', 'Effacer toutes les commandes et les avis ? Cette action est définitive.') ?></p></div>
        <div class="card"><h3><?= ic('settings', 18) ?> Sécurité</h3><p>Mot de passe admin : <b><?= $PW === 'admin123' ? 'par défaut (à changer)' : 'personnalisé' ?></b></p><p>Code PIN de la cuisine : <b><?= defined('KITCHEN_PIN') && KITCHEN_PIN !== '' ? 'activé' : 'désactivé' ?></b></p><p class="mut">À modifier dans config.php (ADMIN_PASSWORD, KITCHEN_PIN).</p></div>
        <div class="card"><h3><?= ic('tables', 18) ?> Adresse des QR codes</h3><p><b><?= h(base_url()) ?>/index.php?table=N</b></p><p class="mut">Pour la changer, renseignez PUBLIC_URL dans config.php.</p></div>
      </div>
<?php endif; ?>
    </main>
  </div>
</div>
<script>
(function () {
  var side = document.getElementById('side'), ovl = document.getElementById('ovl'), burger = document.getElementById('burger');
  function close() { side.classList.remove('open'); ovl.classList.remove('show'); }
  if (burger) burger.addEventListener('click', function () { side.classList.toggle('open'); ovl.classList.toggle('show', side.classList.contains('open')); });
  if (ovl) ovl.addEventListener('click', close);
  document.querySelectorAll('#nav a').forEach(function (a) { a.addEventListener('click', close); });
})();
</script>
<?php if ($t === 'dash'): ?>
<script id="dashData" type="application/json"><?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  // Compteurs animés
  function countUp(el) {
    var target = parseFloat(el.getAttribute('data-value') || '0');
    var money = el.getAttribute('data-money') === '1';
    var dec = el.getAttribute('data-dec') === '1';
    var dur = 1000, t0 = null;
    function fmt(v) { if (money) return Math.round(v).toLocaleString('fr-FR').replace(/[\u202f\u00a0]/g, ' ') + ' FCFA'; if (dec) return v.toFixed(1); return Math.round(v).toLocaleString('fr-FR'); }
    function step(ts) { if (!t0) t0 = ts; var p = Math.min(1, (ts - t0) / dur), e = 1 - Math.pow(1 - p, 3); el.textContent = fmt(target * e); if (p < 1) requestAnimationFrame(step); }
    requestAnimationFrame(step);
  }
  document.querySelectorAll('.kpi b[data-value]').forEach(countUp);

  var dataEl = document.getElementById('dashData');
  if (!dataEl) return;
  var D = JSON.parse(dataEl.textContent);
  var canvases = document.querySelectorAll('.chwrap canvas');
  if (!window.Chart) {
    canvases.forEach(function (c) { c.style.display = 'none'; });
    document.querySelectorAll('.fb').forEach(function (f) { f.style.display = 'block'; });
    return;
  }
  Chart.defaults.color = '#9DB2C8';
  Chart.defaults.font.family = '"Plus Jakarta Sans","Segoe UI",system-ui,sans-serif';
  Chart.defaults.animation.duration = 900;
  Chart.defaults.animation.easing = 'easeOutQuart';
  Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(13,29,49,.96)';
  Chart.defaults.plugins.tooltip.borderColor = 'rgba(173,216,230,.25)';
  Chart.defaults.plugins.tooltip.borderWidth = 1;
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.cornerRadius = 10;
  var grid = { color: 'rgba(173,216,230,.10)' };
  var money = function (v) { return Math.round(v).toLocaleString('fr-FR').replace(/[\u202f\u00a0]/g, ' ') + ' FCFA'; };
  function mk(id, cfg) { var el = document.getElementById(id); if (el) new Chart(el, cfg); }

  mk('chCa', { type: 'line', data: { labels: D.days, datasets: [{ data: D.dayCa, borderColor: '#FF0000', backgroundColor: function (ctx) { var c = ctx.chart.ctx, g = c.createLinearGradient(0, 0, 0, ctx.chart.height); g.addColorStop(0, 'rgba(255,0,0,.28)'); g.addColorStop(1, 'rgba(255,0,0,0)'); return g; }, fill: true, tension: .4, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 6, pointBackgroundColor: '#FF0000' }] }, options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' CA : ' + money(c.parsed.y); } } } }, scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 8, maxRotation: 0 } }, y: { grid: { color: grid.color }, border: { display: false }, ticks: { callback: function (v) { return v >= 1000 ? (v / 1000).toLocaleString('fr-FR') + 'k' : v; } } } } } });

  mk('chPay', { type: 'doughnut', data: { labels: D.payL, datasets: [{ data: D.payV, backgroundColor: ['#FF0000', '#2DD4A0', '#ADD8E6', '#FFC93C'], borderColor: '#0A1A2E', borderWidth: 3, hoverOffset: 10 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, padding: 16 } }, tooltip: { callbacks: { label: function (c) { var t = D.payV.reduce(function (a, b) { return a + b; }, 0) || 1; return ' ' + c.label + ' : ' + money(c.parsed) + ' (' + Math.round(c.parsed / t * 100) + '%)'; } } } } } });

  mk('chTop', { type: 'bar', data: { labels: D.topL, datasets: [{ data: D.topV, backgroundColor: '#FF0000', borderRadius: 7, borderSkipped: false }] }, options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: grid.color }, border: { display: false }, ticks: { precision: 0 } }, y: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 12 } } } } } });

  mk('chHour', { type: 'bar', data: { labels: Array.from({ length: 24 }, function (_, i) { return i + 'h'; }), datasets: [{ data: D.hours, backgroundColor: '#FF0000', borderRadius: 5, borderSkipped: false }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, border: { display: false } }, y: { grid: { color: grid.color }, border: { display: false }, ticks: { precision: 0 } } } } });

  mk('chRate', { type: 'bar', data: { labels: D.rateL, datasets: [{ data: D.rateV, backgroundColor: ['rgba(255,0,0,.55)', '#FFC93C', '#FFC93C', '#FFC93C', '#2DD4A0'], borderRadius: 7, borderSkipped: false, barThickness: 26 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, border: { display: false } }, y: { grid: { color: grid.color }, border: { display: false }, ticks: { precision: 0 } } } } });

  document.querySelectorAll('.fb').forEach(function (f) { f.style.display = 'none'; });
})();
</script>
<?php endif; ?>
</body></html>
