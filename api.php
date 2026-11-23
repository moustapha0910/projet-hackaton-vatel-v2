<?php
// API de l'application : carte, commandes et avis (data/state.json).
// Règle d'or : le serveur décide de tout. Le navigateur ne peut plus envoyer « l'état complet ».
//  - Public (clients)  : lire la carte, passer UNE commande, suivre / annuler / régler / noter SES commandes (jeton secret par commande).
//  - Cuisine (session) : voir toutes les commandes, les faire avancer, modifier la carte.
//  - Administrateur    : reset et test IA (le reste de l'administration est dans admin.php).
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/inc/store.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/brand.php';

function out($v, $code = 200) { http_response_code($code); echo json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function fail($msg, $code = 400) { out(['ok' => false, 'error' => $msg], $code); }
function body() { $j = json_decode((string)file_get_contents('php://input', false, null, 0, 262144), true); return is_array($j) ? $j : []; }
/* Statut du visiteur sans ouvrir de session pour les clients (une session par client ralentirait et encombrerait le serveur). */
function staff_ro($admin = false) {
  if (!isset($_COOKIE[session_name()])) return false;
  $r = $admin ? is_admin() : is_kitchen();
  if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
  return $r;
}
function order_index(array $st, $id, $tk) {            // retrouve une commande à partir de son numéro ET de son jeton secret
  if (!is_string($id) || !is_string($tk) || $tk === '') return -1;
  foreach ($st['orders'] as $i => $o) if (($o['id'] ?? '') === $id) return (isset($o['tk']) && hash_equals((string)$o['tk'], $tk)) ? $i : -1;
  return -1;
}
function stamp() { return (int)round(microtime(true) * 1000); }
const PAYS = ['Mobile Money', 'Espèces', 'Carte'];

$a = (string)($_GET['a'] ?? 'load'); $post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
if (in_array($a, ['reset', 'aitest', 'pushstatus'], true) && !staff_ro(true)) fail("Réservé à l'administrateur", 403);

if ($a === 'aitest') {
  require_once __DIR__ . '/inc/ai.php'; $p = ai_provider();
  if ($p === 'gemini' && isset($_GET['list'])) { $e = null; echo json_encode(['models' => gemini_models($e), 'error' => $e], JSON_UNESCAPED_UNICODE); exit; }
  if (!$p) { echo json_encode(['provider' => null, 'info' => 'Aucune clé renseignée dans config.php : le chat utilise les mots-clés.']); exit; }
  $err = null; $t = ai_call($p, 'Réponds par un objet JSON {"reply":"ok"}', [['role' => 'user', 'content' => 'test']], $err, 40);
  echo json_encode(['provider' => $p, 'ok' => $t !== '', 'text' => mb_substr($t, 0, 200), 'error' => $err], JSON_UNESCAPED_UNICODE); exit;
}

// Lit la réponse de l'IA même si le JSON est coupé ou entouré de texte (réponse tronquée, balises ```json, etc.)
function parse_ai_reply($txt) {
  $t = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim((string)$txt)));
  $j = null; $reply = ''; $items = []; $act = '';
  if (preg_match('/\{.*\}/s', $t, $mm)) $j = json_decode($mm[0], true);
  if (is_array($j)) { $reply = trim((string)($j['reply'] ?? '')); $items = is_array($j['items'] ?? null) ? $j['items'] : []; $act = (string)($j['action'] ?? ''); }
  else {
    if (preg_match('/"reply"\s*:\s*"((?:[^"\\\\]|\\\\.)*)/su', $t, $m)) { $d = json_decode('"' . preg_replace('/\\\\$/', '', $m[1]) . '"'); $reply = trim((string)($d ?? $m[1])); }
    elseif ($t !== '' && $t[0] !== '{') $reply = $t;
    if (preg_match_all('/"id"\s*:\s*(\d+)\s*,\s*"q"\s*:\s*(\d+)(?:\s*,\s*"why"\s*:\s*"((?:[^"\\\\]|\\\\.)*)")?/su', $t, $all, PREG_SET_ORDER))
      foreach ($all as $a) $items[] = ['id' => (int)$a[1], 'q' => (int)$a[2], 'why' => $a[3] ?? ''];
    if (preg_match('/"action"\s*:\s*"(add|checkout|none)"/u', $t, $m)) $act = $m[1];
  }
  // L'IA recopie parfois "[plats proposés : 8×1, 12×1]" dans son texte : on s'en sert si la liste d'items manque, puis on le retire
  if (preg_match('/\[plats propos[ée]s\s*:\s*([^\]]*)/u', $reply, $m) && preg_match_all('/(\d+)\s*[×x]\s*(\d+)/u', $m[1], $all, PREG_SET_ORDER) && count($all) > count($items)) {
    $why = []; foreach ($items as $x) $why[$x['id'] ?? 0] = $x['why'] ?? '';
    $items = []; foreach ($all as $a) $items[] = ['id' => (int)$a[1], 'q' => (int)$a[2], 'why' => $why[(int)$a[1]] ?? ''];
  }
  $reply = trim(preg_replace('/\s*:\s*(?=[.!?]|$)/u', '', preg_replace('/\s*\[plats propos[^\]]*(\]|$)/u', '', $reply)));
  return [$reply, $items, $act];
}
if ($a === 'chat') {
  require_once __DIR__ . '/inc/ai.php';
  $in = body();
  // La carte vient du serveur (jamais du navigateur) ; seuls les allergènes choisis par le client sont pris en compte.
  $ex = array_slice(array_values(array_filter(array_map(fn($x) => clean_label(is_scalar($x) ? $x : '', 30), (array)($in['al'] ?? [])), fn($x) => $x !== '')), 0, 8);
  $menu = array_map(fn($d) => ['id' => $d['id'], 'n' => $d['n'], 'd' => $d['d'], 'p' => $d['p'], 'm' => $d['m'], 'c' => $d['c'], 'a' => $d['a']], array_values(array_filter(read_state()['D'], fn($d) => !empty($d['o']))));
  $safe = array_values(array_filter($menu, fn($d) => !array_intersect($d['a'] ?? [], $ex)));
  $msgs = [];
  foreach (($in['messages'] ?? []) as $m) {
    $r = (($m['role'] ?? '') === 'assistant') ? 'assistant' : 'user'; $c = trim((string)($m['content'] ?? ''));
    if ($c === '') continue; $c = mb_substr($c, 0, 800);
    if ($msgs && $msgs[count($msgs) - 1]['role'] === $r) $msgs[count($msgs) - 1]['content'] .= "\n" . $c; else $msgs[] = ['role' => $r, 'content' => $c];
  }
  while ($msgs && $msgs[0]['role'] !== 'user') array_shift($msgs);
  $prov = ai_provider();
  if ($prov === '' || !$msgs || !function_exists('curl_init')) { echo '{"reply":null}'; exit; }
  // Anti-abus : l'IA est facturée/limitée par votre clé. Au-delà, le chat bascule sur le moteur à mots-clés du navigateur.
  if (!rate_hit('chat', 25, 600)) { echo '{"reply":null}'; exit; }
  $cart = json_encode(array_map(fn($l) => ['id' => (int)($l['id'] ?? 0), 'q' => (int)($l['q'] ?? 0)], array_slice(array_values((array)($in['cart'] ?? [])), 0, 30)), JSON_UNESCAPED_UNICODE);
  $bn = brand('name'); $bc = brand('city');
  $sys = "Tu es le sommelier du restaurant {$bn}, à {$bc}. Tu discutes avec un client à table : vouvoiement, ton chaleureux de bon serveur, 3 phrases maximum. "
       . "Tu réponds à ses questions (ingrédients, allergènes, temps de préparation, prix, accompagnements) UNIQUEMENT d'après la carte ci-dessous ; si l'information n'y figure pas, dis-le et invite-le à demander au personnel. "
       . "Tu recommandes 1 à 4 plats de la carte, jamais d'autres, avec le vrai prix ; si un budget est donné, le total ne doit pas le dépasser ; adapte les quantités q au nombre de personnes. Si la demande est floue, pose une seule question. "
       . "Dans tes messages précédents, les plats proposés apparaissent entre crochets sous la forme [plats proposés : id×quantité]. "
       . "Quand le client accepte tes propositions (je prends, ajoute, oui, d'accord...), mets \"action\":\"add\" et reprends exactement ces plats dans items. "
       . "Quand il veut valider, envoyer ou terminer sa commande, mets \"action\":\"checkout\" (items vides) et dis-lui de vérifier le récapitulatif puis de valider. Sinon \"action\":\"none\". Quand tu proposes des plats pour la première fois, l'action est toujours \"none\" : le client les ajoute lui-même avec les boutons. Ne recopie jamais le format [plats proposés…] dans reply. Tu ne peux jamais envoyer la commande toi-même : le client valide avec un bouton. "
       . "Réponds toujours par un objet JSON {\"reply\":\"texte\",\"items\":[{\"id\":nombre,\"q\":nombre,\"why\":\"raison de 15 mots maximum\"}],\"action\":\"none|add|checkout\"} sans aucun texte autour. "
       . "Carte disponible ce soir (les plats contenant les allergènes du client sont déjà retirés) : " . json_encode($safe, JSON_UNESCAPED_UNICODE)
       . ". Allergènes à éviter : " . implode(', ', $ex ?: ['aucun']) . ". Panier actuel du client (id, quantité) : " . $cart . ".";
  if (!empty($in['visitor'])) $sys .= " IMPORTANT : ce visiteur consulte seulement la carte, sans table : il ne peut pas commander. Conseille-le sans parler de panier, mets toujours \"action\":\"none\" et invite-le à scanner le QR code de sa table pour commander.";
  $txt = ai_call($prov, $sys, $msgs);
  [$reply, $rawItems, $actRaw] = parse_ai_reply($txt);
  $ids = array_column($safe, 'id'); $out = [];
  foreach ($rawItems as $x) {
    if (in_array($x['id'] ?? 0, $ids) && count($out) < 4) $out[] = ['id' => $x['id'], 'q' => max(1, min(10, (int)($x['q'] ?? 1))), 'why' => mb_substr((string)($x['why'] ?? ''), 0, 160)];
  }
  // "add" n'a de sens que si le client répond à une proposition déjà faite : sinon on affiche les boutons Commander
  $lastA = ''; foreach (array_reverse($msgs) as $m) if ($m['role'] === 'assistant') { $lastA = $m['content']; break; }
  $act = in_array($actRaw, ['add', 'checkout'], true) ? $actRaw : null;
  if ($act === 'add' && strpos($lastA, 'plats proposés') === false) $act = null;
  if ($act === null && !$out && $reply === '') { echo '{"reply":null}'; exit; }
  echo json_encode(['reply' => $reply !== '' ? mb_substr($reply, 0, 700) : null, 'items' => $out, 'action' => $act], JSON_UNESCAPED_UNICODE); exit;
}
if ($a === 'tables') {
  require_once __DIR__ . '/inc/store.php'; $o = [];
  foreach (jload('tables', []) as $n => $t) if (!empty($t['on'])) $o[$n] = ['label' => $t['label'] ?? ''];
  echo json_encode((object)$o, JSON_UNESCAPED_UNICODE); exit;
}

/* ---------- Lecture : la carte pour tout le monde, les commandes selon le rôle ---------- */
if ($a === 'load') {
  $st = read_state(); $kit = staff_ro();
  if ($kit) {
    $orders = array_map(function ($o) { unset($o['tk']); return $o; }, $st['orders']);   // la cuisine voit tout, sauf les jetons secrets
  } else {
    $want = [];                                                                          // le client ne reçoit QUE ses commandes (n° + jeton), sans téléphone
    foreach (array_slice(explode(',', (string)($_GET['o'] ?? '')), 0, 30) as $pair) { $x = explode(':', $pair, 2); if (count($x) === 2 && $x[0] !== '') $want[$x[0]] = $x[1]; }
    $orders = [];
    foreach ($st['orders'] as $o) if (isset($want[$o['id'] ?? '']) && isset($o['tk']) && hash_equals((string)$o['tk'], (string)$want[$o['id']])) { unset($o['tk'], $o['phone']); $orders[] = $o; }
  }
  out(['D' => $st['D'], 'orders' => $orders, 'reviews' => $kit ? $st['reviews'] : [], 'nid' => $kit ? $st['nid'] : 0, 'uid' => $kit ? $st['uid'] : 0]);
}

/* ---------- Commande : le serveur recalcule les prix à partir de SA carte ---------- */
if ($a === 'order' && $post) {
  if (!rate_hit('order', 8, 600)) fail('Trop de commandes envoyées en peu de temps. Patientez quelques minutes ou appelez le personnel.', 429);
  $in = body();
  $name = clean_text(preg_replace('/\s+/u', ' ', (string)($in['name'] ?? '')), 60); $phone = clean_text($in['phone'] ?? '', 25);
  if (mb_strlen($name) < 2 || strlen(preg_replace('/\D/', '', $phone)) < 8) fail('Merci de renseigner votre nom et votre numéro de téléphone (au moins 8 chiffres).');
  $tb = trim((string)($in['table'] ?? ''));
  if (!preg_match('/^\d{1,3}$/', $tb) || (int)$tb < 1) fail('Numéro de table invalide : scannez le QR code de votre table.');
  $tb = pad($tb); $tables = jload('tables', []);
  if ($tables && empty($tables[$tb]['on'])) fail("La table $tb n'est pas disponible : scannez le QR code de votre table.");
  $lines = is_array($in['items'] ?? null) ? array_slice($in['items'], 0, 40) : [];
  $pay = in_array($in['pay'] ?? '', PAYS, true) ? $in['pay'] : PAYS[0]; $note = clean_text($in['note'] ?? '', 300);
  $res = with_state(function (array &$st) use ($lines, $tb, $name, $phone, $pay, $note) {
    $byId = []; foreach ($st['D'] as $d) $byId[(int)$d['id']] = $d;
    $items = []; $tot = 0; $mx = 0;
    foreach ($lines as $l) {
      if (!is_array($l)) continue; $id = (int)($l['id'] ?? 0); $q = (int)($l['q'] ?? 0);
      if ($q < 1 || !isset($byId[$id])) continue;
      $d = $byId[$id];
      if (empty($d['o'])) return ['_nosave' => 1, 'error' => '« ' . $d['n'] . ' » n’est plus disponible : retirez-le de votre panier.'];
      $q = min($q, 20); $img = (string)($d['img'] ?? ''); if (strpos($img, 'data:') === 0) $img = '';
      $items[] = ['n' => $d['n'], 'p' => (int)$d['p'], 'q' => $q, 'note' => clean_text($l['note'] ?? '', 120), 'img' => $img];
      $tot += (int)$d['p'] * $q; $mx = max($mx, (int)($d['m'] ?? 0));
    }
    if (!$items) return ['_nosave' => 1, 'error' => 'Votre panier est vide.'];
    $used = []; foreach ($st['orders'] as $o) $used[$o['id'] ?? ''] = 1;
    while (isset($used['A' . $st['nid']])) $st['nid']++;                                   // numéro unique, même si le compteur a pris du retard
    $id = 'A' . $st['nid']; $st['nid']++; $tk = bin2hex(random_bytes(8));
    array_unshift($st['orders'], ['id' => $id, 'items' => $items, 'tot' => $tot, 'table' => $tb, 'name' => $name, 'phone' => $phone, 'note' => $note, 'pay' => $pay, 'paid' => 0, 'st' => 0, 'at' => stamp(), 'mx' => $mx, 'tk' => $tk]);
    return ['ok' => true, 'id' => $id, 'tk' => $tk];
  });
  if (isset($res['error'])) fail($res['error']);
  out($res);
}

/* ---------- Actions du client sur SA commande (n° + jeton secret exigés) ---------- */
if (in_array($a, ['cancel', 'mypay', 'review'], true) && $post) {
  if (!rate_hit('myorder', 60, 600)) fail('Trop de requêtes. Réessayez dans quelques minutes.', 429);
  $in = body(); $id = (string)($in['id'] ?? ''); $tk = (string)($in['tk'] ?? '');
  $res = with_state(function (array &$st) use ($a, $in, $id, $tk) {
    $i = order_index($st, $id, $tk); if ($i < 0) return ['_nosave' => 1, 'error' => 'Commande introuvable sur cet appareil.'];
    $o = &$st['orders'][$i];
    if ($a === 'cancel') {
      if (!empty($o['cx'])) return ['_nosave' => 1, 'error' => 'Cette commande est déjà annulée.'];
      if ((int)($o['st'] ?? 0) !== 0 || !empty($o['paid'])) return ['_nosave' => 1, 'error' => 'La cuisine a déjà commencé la préparation : appelez le personnel.'];
      $o['cx'] = 1; $o['cxat'] = stamp(); return ['ok' => true];
    }
    if (!empty($o['cx'])) return ['_nosave' => 1, 'error' => 'Cette commande est annulée.'];
    if ($a === 'mypay') {
      $pay = in_array($in['pay'] ?? '', PAYS, true) ? $in['pay'] : ($o['pay'] ?? PAYS[0]);
      if (!empty($in['confirm'])) { $o['pay'] = $pay; $o['paid'] = 1; } elseif (empty($o['paid'])) $o['pay'] = $pay; else return ['_nosave' => 1, 'ok' => true];
      return ['ok' => true];
    }
    $stars = (int)($in['stars'] ?? 0);                                                    // review
    if (empty($o['paid']) || !empty($o['rv'])) return ['_nosave' => 1, 'error' => 'Avis impossible pour cette commande.'];
    if ($stars < 1 || $stars > 5) return ['_nosave' => 1, 'error' => 'Choisissez une note de 1 à 5.'];
    $o['rv'] = 1; $o['rs'] = $stars; $o['rt'] = clean_text($in['text'] ?? '', 300); $st['reviews'][] = $stars;
    return ['ok' => true];
  });
  if (isset($res['error'])) fail($res['error'], 409);
  out($res);
}

/* ---------- Cuisine (session obligatoire) ---------- */
if ($a === 'advance' && $post) {
  if (!staff_ro()) fail('Accès réservé à la cuisine.', 403);
  $in = body(); $id = (string)($in['id'] ?? ''); $from = (int)($in['st'] ?? -1);          // $from = statut que la cuisine voyait en appuyant : évite de sauter une étape si deux écrans cliquent
  $res = with_state(function (array &$st) use ($id, $from) {
    foreach ($st['orders'] as &$o) if (($o['id'] ?? '') === $id) {
      if (!empty($o['cx'])) return ['_nosave' => 1, 'ok' => false, 'error' => 'Commande annulée par le client.', 'st' => (int)$o['st']];
      if ((int)$o['st'] === $from && $from >= 0 && $from < 3) { $o['st'] = $from + 1; return ['ok' => true, 'st' => $o['st'], 'moved' => true]; }
      return ['_nosave' => 1, 'ok' => true, 'st' => (int)$o['st']];                       // déjà avancée depuis un autre écran
    }
    return ['_nosave' => 1, 'ok' => false, 'error' => 'Commande introuvable.'];
  });
  if (!empty($res['moved']) && ($res['st'] ?? 0) === 2) { require_once __DIR__ . '/inc/push.php'; push_ready($id); }   // « Prête » : prévient le téléphone du client, même verrouillé
  out($res);
}
if ($a === 'save' && $post) {                                                              // la cuisine enregistre la CARTE uniquement
  if (!staff_ro()) fail('Accès réservé à la cuisine.', 403);
  $in = body(); $raw = $in['D'] ?? null;
  if (!is_array($raw) || !$raw || count($raw) > 200) fail('Carte invalide.');
  $D = []; foreach ($raw as $d) if ($c = clean_dish($d)) $D[] = $c;
  if (!$D) fail('Carte invalide : aucun plat valide (nom et prix obligatoires).');
  $maxId = max(array_column($D, 'id')); $seen = [];
  foreach ($D as &$d) { if (isset($seen[$d['id']])) $d['id'] = ++$maxId; $seen[$d['id']] = 1; } unset($d);
  $uid = max($maxId + 1, min(100000, (int)($in['uid'] ?? 0)));
  with_state(function (array &$st) use ($D, $uid) { $st['D'] = $D; $st['uid'] = max($st['uid'], $uid); return ['ok' => true]; });
  out(['ok' => true]);
}

/* ---------- Notifications push (téléphone verrouillé / appli fermée) ---------- */
if ($a === 'vapid') {                                                                      // clé publique que le navigateur utilise pour s'abonner
  require_once __DIR__ . '/inc/push.php'; $k = vapid_keys($err);
  $k ? out(['key' => $k['pub']]) : out(['key' => null, 'error' => $err], 503);
}
if ($a === 'push_sub' && $post) {                                                          // le client lie son téléphone à SA commande (n° + jeton secret)
  require_once __DIR__ . '/inc/push.php';
  if (!rate_hit('pushsub', 40, 600)) fail('Trop de requêtes. Réessayez dans quelques minutes.', 429);
  $in = body(); $ep = $in['sub']['endpoint'] ?? '';
  if (!push_endpoint_ok($ep)) fail('Service de notification non reconnu.');
  if (order_index(read_state(), (string)($in['id'] ?? ''), (string)($in['tk'] ?? '')) < 0) fail('Commande introuvable sur cet appareil.', 409);
  push_subscribe((string)$in['id'], $ep); out(['ok' => true]);
}
if ($a === 'pushmsg' && $post) {                                                           // appelée par sw.js à la réception d'un push : renvoie le message en attente
  require_once __DIR__ . '/inc/push.php'; $in = body(); $ep = (string)($in['ep'] ?? '');
  $m = push_endpoint_ok($ep) ? push_pop($ep) : null; out($m ?: new stdClass);
}
if ($a === 'pushstatus') {                                                                 // administrateur : diagnostic (ouvrir api.php?a=pushstatus une fois connecté)
  require_once __DIR__ . '/inc/push.php'; $k = vapid_keys($err); $p = jload('push', []);
  $net = null;                                                                             // joint-on vraiment le service de notification de Google ? (certificats SSL de XAMPP, pare-feu…)
  if (function_exists('curl_init')) {
    $ch = curl_init('https://fcm.googleapis.com/');
    curl_setopt_array($ch, [CURLOPT_NOBODY => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 6, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_SSL_VERIFYPEER => defined('VERIFY_SSL') ? (bool)VERIFY_SSL : true]);
    curl_exec($ch); $net = curl_errno($ch) ? 'ÉCHEC : ' . curl_error($ch) : 'OK (HTTP ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . ')'; curl_close($ch);
  }
  out(['cles_vapid' => (bool)$k, 'erreur' => $err ?? null, 'openssl' => extension_loaded('openssl'), 'curl' => function_exists('curl_init'), 'connexion_service_google' => $net, 'appareils_abonnes' => count($p), 'contact_vapid' => vapid_subject()]);
}

/* ---------- Administrateur ---------- */
if ($a === 'reset') { $h = state_lock(LOCK_EX); @unlink(dpath('state')); state_unlock($h); out(['ok' => true]); }

fail('Action inconnue.', 404);
