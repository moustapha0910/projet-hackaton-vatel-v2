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
<body class="login"><form method="post" class="box"><div class="logo">Saveur<i>IA</i> · Admin</div><p class="mut" style="margin:0">Accès réservé au gérant.</p><?php if ($err): ?><p class="err" style="margin:0"><?= h($err) ?></p><?php endif; ?><input type="password" name="pw" placeholder="Mot de passe" autofocus required><button class="btn">Se connecter</button></form></body></html><?php
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
// ---- statistiques
$p = in_array($_GET['p'] ?? 'today', ['today', '7', '30', 'all'], true) ? ($_GET['p'] ?? 'today') : 'today';
$from = ['today' => strtotime('today'), '7' => strtotime('-6 days midnight'), '30' => strtotime('-29 days midnight'), 'all' => 0][$p];
$L = array_values(array_filter($orders, fn($o) => ($o['at'] ?? 0) / 1000 >= $from));
$ca = array_sum(array_column($L, 'tot')); $n = count($L);
$paid = 0; $top = []; $byH = array_fill(0, 24, 0); $byT = []; $rates = [];
foreach ($L as $o) {
  if (!empty($o['paid'])) $paid += $o['tot'];
  foreach (($o['items'] ?? []) as $i) $top[$i['n']] = ($top[$i['n']] ?? 0) + $i['q'];
  $byH[(int)date('G', (int)(($o['at'] ?? 0) / 1000))]++;
  $k = pad($o['table'] ?? 0); $byT[$k] = ['n' => ($byT[$k]['n'] ?? 0) + 1, 'ca' => ($byT[$k]['ca'] ?? 0) + $o['tot']];
  if (isset($o['rs'])) $rates[] = $o['rs'];
}
arsort($top); $avg = $rates ? round(array_sum($rates) / count($rates), 1) . ' / 5' : '—';
$tabs = ['dash' => 'Tableau de bord', 'tables' => 'Tables & QR', 'orders' => 'Commandes', 'reviews' => 'Avis', 'settings' => 'Réglages'];
?><!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Administration · SaveurIA</title><link rel="stylesheet" href="assets/admin.css"></head><body>
<header class="top noprint"><div class="logo">Saveur<i>IA</i> · Admin</div>
<nav class="nav"><?php foreach ($tabs as $k => $v): ?><a href="admin.php?t=<?= $k ?>" class="<?= $t === $k ? 'on' : '' ?>"><?= $v ?></a><?php endforeach; ?></nav>
<div class="mut"><a href="cuisine.php">Cuisine</a> · <a href="index.php">Site</a> · <a href="admin.php?logout=1">Déconnexion</a></div></header>
<main class="wrap">
<?php if ($flash): ?><div class="flash noprint"><?= h($flash) ?></div><?php endif; ?>
<?php if ($PW === 'admin123'): ?><div class="warn noprint">Mot de passe par défaut : changez <b>ADMIN_PASSWORD</b> dans config.php.</div><?php endif; ?>

<?php if ($t === 'dash'): ?>
<div class="noprint" style="display:flex;gap:6px;flex-wrap:wrap"><?php foreach (['today' => "Aujourd'hui", '7' => '7 jours', '30' => '30 jours', 'all' => 'Tout'] as $k => $v): ?><a class="btn sm <?= $p === (string)$k ? '' : 'gh' ?>" href="admin.php?t=dash&p=<?= $k ?>"><?= $v ?></a><?php endforeach; ?></div>
<div class="kpis"><div><small>Chiffre d'affaires</small><b><?= fcfa($ca) ?></b></div><div><small>Commandes</small><b><?= $n ?></b></div><div><small>Ticket moyen</small><b><?= fcfa($n ? round($ca / $n) : 0) ?></b></div><div><small>Encaissé</small><b style="color:var(--ok)"><?= fcfa($paid) ?></b></div><div><small>À encaisser</small><b style="color:var(--warn)"><?= fcfa($ca - $paid) ?></b></div><div><small>Note moyenne</small><b><?= $avg ?></b></div></div>
<div class="g2">
<div class="card"><h3>Plats les plus vendus</h3><?php $mx = $top ? max($top) : 1; if (!$top) echo '<p class="mut">Aucune vente sur la période.</p>'; $i = 0; foreach ($top as $name => $q): if (++$i > 8) break; ?><div style="margin-top:10px"><?= h($name) ?> <b style="float:right"><?= $q ?></b><div class="bar"><i style="width:<?= round($q / $mx * 100) ?>%"></i></div></div><?php endforeach; ?></div>
<div class="card"><h3>Commandes par heure</h3><?php $mh = max($byH) ?: 1; ?><div class="hours"><?php for ($i = 0; $i < 24; $i++): ?><div title="<?= $i ?> h : <?= $byH[$i] ?>"><i style="height:<?= round($byH[$i] / $mh * 100) ?>%"></i><small><?= $i ?></small></div><?php endfor; ?></div></div>
</div>
<div class="card"><h3>Par table</h3><?php if (!$byT): ?><p class="mut">Aucune commande sur la période.</p><?php else: ksort($byT); ?><div class="tw"><table><tr><th>Table</th><th>Commandes</th><th>Chiffre d'affaires</th></tr><?php foreach ($byT as $k => $v): ?><tr><td>Table <?= h($k) ?></td><td><?= $v['n'] ?></td><td><?= fcfa($v['ca']) ?></td></tr><?php endforeach; ?></table></div><?php endif; ?></div>

<?php elseif ($t === 'tables'): $base = base_url(); $local = preg_match('~//(localhost|127\.)~', $base); ?>
<h2>Tables & QR codes</h2><p class="mut noprint">Chaque QR code ouvre le site avec le numéro de la table : le client le voit sur la page d'accueil, dans son panier et sur sa commande.</p>
<?php if ($local): ?><div class="warn noprint">Vous êtes sur <b>localhost</b> : les téléphones ne pourront pas ouvrir ces QR codes. Ouvrez l'admin avec l'IP de votre PC (ex. http://192.168.1.20/saveuria/admin.php) ou renseignez PUBLIC_URL dans config.php.</div><?php endif; ?>
<div class="g2 noprint">
<form method="post" class="card"><h3>Créer une table</h3><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="table_add">
<label>Numéro</label><input type="number" name="n" min="1" max="999" value="<?= $tables ? (int)max(array_map('intval', array_keys($tables))) + 1 : 1 ?>" required><label>Nom / zone (facultatif)</label><input name="label" placeholder="Terrasse, Salon VIP…"><label>Places</label><input type="number" name="seats" value="4" min="1"><p><button class="btn">Créer et générer le QR</button></p></form>
<form method="post" class="card"><h3>Créer plusieurs tables</h3><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="table_bulk">
<label>De la table</label><input type="number" name="from" min="1" value="1"><label>À la table</label><input type="number" name="to" min="1" value="10"><label>Places</label><input type="number" name="seats" value="4" min="1"><p><button class="btn">Générer</button> <span class="mut">(100 max à la fois)</span></p></form></div>
<?php if ($tables): ?><p class="noprint"><button class="btn gh" onclick="window.print()">Imprimer tous les QR codes</button></p><?php endif; ?>
<div class="grid"><?php if (!$tables): ?><p class="mut">Aucune table pour le moment : créez la première ci-dessus.</p><?php endif;
$allT = []; foreach ($orders as $o) { $k = pad($o['table'] ?? 0); $allT[$k] = ($allT[$k] ?? 0) + 1; }
foreach ($tables as $num => $tb): $u = $base . '/index.php?table=' . $num; ?>
<div class="card tc" style="<?= empty($tb['on']) ? 'opacity:.55' : '' ?>"><div class="qr" id="qr<?= h($num) ?>" data-u="<?= h($u) ?>"></div><b>Table <?= h($num) ?></b>
<div class="mut"><?= h($tb['label'] ?: 'Sans nom') ?> · <?= (int)($tb['seats'] ?? 4) ?> places<br><?= (int)($allT[$num] ?? 0) ?> commande(s)<?= empty($tb['on']) ? ' · désactivée' : '' ?></div>
<div class="noprint" style="margin-top:10px"><button class="btn sm gh" onclick="dl('qr<?= h($num) ?>','<?= h($num) ?>')">PNG</button> <button class="btn sm gh" data-u="<?= h($u) ?>" onclick="cp(this.dataset.u);this.textContent='Copié'">Lien</button>
<?= pf('table_toggle', ['n' => $num], empty($tb['on']) ? 'Activer' : 'Désactiver', 'btn sm gh') ?><?= pf('table_del', ['n' => $num], 'Supprimer', 'btn sm gh', 'Supprimer la table ' . $num . ' ?') ?>
<form method="post" style="margin-top:8px"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="table_edit"><input type="hidden" name="n" value="<?= h($num) ?>"><input name="label" value="<?= h($tb['label'] ?? '') ?>" placeholder="Nom" style="width:100px"> <input type="number" name="seats" value="<?= (int)($tb['seats'] ?? 4) ?>" min="1" style="width:60px"> <button class="btn sm">OK</button></form></div></div>
<?php endforeach; ?></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.querySelectorAll('.qr').forEach(function (e) { new QRCode(e, { text: e.dataset.u, width: 170, height: 170 }); });
function dl(id, n) { var e = document.getElementById(id), c = e.querySelector('canvas'), a = document.createElement('a'); a.href = c ? c.toDataURL('image/png') : e.querySelector('img').src; a.download = 'table-' + n + '.png'; a.click(); }
function cp(u) { if (navigator.clipboard) navigator.clipboard.writeText(u); }
</script>

<?php elseif ($t === 'orders'): $fs = $_GET['s'] ?? ''; $ft = $_GET['tb'] ?? '';
$R = array_reverse($orders); $R = array_filter($R, fn($o) => ($fs === '' || (string)($o['st'] ?? 0) === $fs) && ($ft === '' || pad($o['table'] ?? 0) === pad($ft))); $R = array_slice($R, 0, 200); ?>
<h2>Commandes</h2>
<form class="noprint" method="get" style="margin:12px 0"><input type="hidden" name="t" value="orders"><select name="s"><option value="">Tous les statuts</option><?php foreach ($S as $i => $v): ?><option value="<?= $i ?>" <?= $fs === (string)$i ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select> <input type="number" name="tb" placeholder="N° table" value="<?= h($ft) ?>" style="width:110px"> <button class="btn sm">Filtrer</button></form>
<div class="card tw"><table><tr><th>Commande</th><th>Date</th><th>Table</th><th>Plats</th><th>Total</th><th>Statut</th><th>Paiement</th><th></th></tr>
<?php if (!$R): ?><tr><td colspan="8" class="mut">Aucune commande.</td></tr><?php endif; foreach ($R as $o): ?>
<tr><td><b>#<?= h($o['id'] ?? '') ?></b></td><td><?= date('d/m H:i', (int)(($o['at'] ?? 0) / 1000)) ?></td><td><?= h(pad($o['table'] ?? 0)) ?></td><td><?= h(implode(' · ', array_map(fn($i) => $i['q'] . '× ' . $i['n'], $o['items'] ?? []))) ?><?= !empty($o['note']) ? '<br><span class="mut">' . h($o['note']) . '</span>' : '' ?></td><td><?= fcfa($o['tot'] ?? 0) ?></td>
<td><form method="post" class="inl"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="a" value="order_set"><input type="hidden" name="id" value="<?= h($o['id'] ?? '') ?>"><select name="st" onchange="this.form.submit()"><?php foreach ($S as $i => $v): ?><option value="<?= $i ?>" <?= (int)($o['st'] ?? 0) === $i ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></form></td>
<td><?= pf('order_pay', ['id' => $o['id'] ?? ''], empty($o['paid']) ? 'À encaisser' : 'Réglé · ' . $o['paid'], empty($o['paid']) ? 'btn sm' : 'btn sm ok') ?></td>
<td><?= pf('order_del', ['id' => $o['id'] ?? ''], 'Supprimer', 'btn sm gh', 'Supprimer la commande ' . ($o['id'] ?? '') . ' ?') ?></td></tr><?php endforeach; ?></table></div>

<?php elseif ($t === 'reviews'): $RV = array_reverse(array_values(array_filter($orders, fn($o) => isset($o['rs'])))); ?>
<h2>Avis clients</h2><p class="mut">Note moyenne (toutes périodes) : <?php $all = array_column($RV, 'rs'); echo $all ? round(array_sum($all) / count($all), 1) . ' / 5 sur ' . count($all) . ' avis' : '—'; ?></p>
<div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))"><?php if (!$RV): ?><p class="mut">Aucun avis pour le moment.</p><?php endif; foreach ($RV as $o): ?><div class="card"><div style="color:var(--warn);font-size:20px"><?= str_repeat('★', (int)$o['rs']) . '<span style="color:rgba(255,255,255,.25)">' . str_repeat('★', 5 - (int)$o['rs']) . '</span>' ?></div><p><?= !empty($o['rt']) ? h($o['rt']) : '<span class="mut">Sans commentaire</span>' ?></p><small class="mut">#<?= h($o['id']) ?> · Table <?= h(pad($o['table'] ?? 0)) ?> · <?= date('d/m H:i', (int)(($o['at'] ?? 0) / 1000)) ?></small></div><?php endforeach; ?></div>

<?php else: $prov = ai_provider(); ?>
<h2>Réglages</h2>
<div class="g2"><div class="card"><h3>Sommelier IA</h3><p>Fournisseur actif : <b><?= $prov ? h($prov) : 'mots-clés (aucune clé)' ?></b></p><p><a href="api.php?a=aitest" target="_blank">Tester la connexion IA</a> · <a href="api.php?a=aitest&list=1" target="_blank">Lister les modèles Gemini</a></p><p class="mut">Les clés se règlent dans <b>config.php</b>.</p></div>
<div class="card"><h3>Données</h3><p>Commandes : <b><?= count($orders) ?></b> · Plats : <b><?= count($st['D'] ?? []) ?></b> · Tables : <b><?= count($tables) ?></b></p><p><a class="btn sm" href="admin.php?t=export">Exporter les commandes (CSV)</a></p><p><?= pf('reset', [], 'Effacer commandes et avis', 'btn sm gh', 'Effacer toutes les commandes et les avis ? Cette action est définitive.') ?></p></div>
<div class="card"><h3>Sécurité</h3><p>Mot de passe admin : <b><?= $PW === 'admin123' ? 'par défaut (à changer)' : 'personnalisé' ?></b></p><p>Code PIN de la cuisine : <b><?= defined('KITCHEN_PIN') && KITCHEN_PIN !== '' ? 'activé' : 'désactivé' ?></b></p><p class="mut">À modifier dans config.php (ADMIN_PASSWORD, KITCHEN_PIN).</p></div>
<div class="card"><h3>Adresse des QR codes</h3><p><b><?= h(base_url()) ?>/index.php?table=N</b></p><p class="mut">Pour la changer, renseignez PUBLIC_URL dans config.php.</p></div></div>
<?php endif; ?>
</main></body></html>
