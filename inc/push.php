<?php
// Notifications « push » : prévenir le client même téléphone verrouillé ou appli fermée (Web Push, norme VAPID).
// Principe : le serveur « sonne » le téléphone par le service de notification du navigateur (Google, Mozilla, Apple, Microsoft),
// sans contenu ; le service worker (sw.js) vient alors chercher le message sur api.php?a=pushmsg et l'affiche.
// Les clés VAPID sont créées automatiquement au premier usage dans data/vapid.json (jamais à publier : voir .gitignore).
require_once __DIR__ . '/store.php';

function b64u($s) { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function vapid_subject() {
  if (defined('VAPID_SUBJECT') && VAPID_SUBJECT !== '') return VAPID_SUBJECT;
  if (defined('PUBLIC_URL') && strpos((string)PUBLIC_URL, 'https://') === 0) return rtrim(PUBLIC_URL, '/');
  return 'mailto:admin@example.com';   // à remplacer par votre vraie adresse : define VAPID_SUBJECT dans config.php (Apple la contrôle)
}
function openssl_cfgs() {              // sous XAMPP/Windows, OpenSSL a parfois besoin qu'on lui indique son fichier de configuration
  $c = [null]; if ($e = getenv('OPENSSL_CONF')) $c[] = $e;
  foreach (['C:/xampp/apache/conf/openssl.cnf', 'C:/xampp/php/extras/ssl/openssl.cnf', 'C:/xampp/apache/bin/openssl.cnf', '/etc/ssl/openssl.cnf'] as $p) $c[] = $p;
  return $c;
}
function vapid_keys(&$err = null) {
  $ok = fn($j) => is_array($j) && !empty($j['pem']) && !empty($j['pub']);
  $j = jload('vapid', null); if ($ok($j)) return $j;
  $h = @fopen(dpath('vapid') . '.lock', 'c'); if ($h) flock($h, LOCK_EX);
  try {
    $j = jload('vapid', null); if ($ok($j)) return $j;       // créée entre-temps par une autre requête
    foreach (openssl_cfgs() as $cfg) {
      if ($cfg !== null && !is_file($cfg)) continue;
      $o = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]; if ($cfg) $o['config'] = $cfg;
      $res = @openssl_pkey_new($o); if (!$res) continue;
      $pem = ''; if (!@openssl_pkey_export($res, $pem, null, $cfg ? ['config' => $cfg] : [])) continue;
      $d = openssl_pkey_get_details($res); if (empty($d['ec']['x']) || empty($d['ec']['y'])) continue;
      $k = ['pem' => $pem, 'pub' => b64u("\x04" . str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT)), 'created' => date('c')];
      jsave('vapid', $k); return $k;
    }
    $err = 'OpenSSL ne parvient pas à créer la clé : ' . (openssl_error_string() ?: 'extension openssl désactivée dans php.ini ?'); return null;
  } finally { if ($h) { flock($h, LOCK_UN); fclose($h); } }
}
function der_to_raw($der, $len = 32) {   // signature ECDSA : format DER d'OpenSSL -> 64 octets bruts R||S exigés par JWT/ES256
  $o = 0; if (ord($der[$o++]) !== 0x30) return null; $l = ord($der[$o++]); if ($l & 0x80) $o += ($l & 0x7f);
  $out = '';
  for ($i = 0; $i < 2; $i++) {
    if (ord($der[$o++]) !== 0x02) return null; $n = ord($der[$o++]); $v = ltrim(substr($der, $o, $n), "\0"); $o += $n;
    if (strlen($v) > $len) return null; $out .= str_pad($v, $len, "\0", STR_PAD_LEFT);
  }
  return $out;
}
function vapid_jwt($aud, $k) {
  $in = b64u(json_encode(['typ' => 'JWT', 'alg' => 'ES256'])) . '.' . b64u(json_encode(['aud' => $aud, 'exp' => time() + 43200, 'sub' => vapid_subject()], JSON_UNESCAPED_SLASHES));
  $key = openssl_pkey_get_private($k['pem']); if (!$key || !openssl_sign($in, $der, $key, OPENSSL_ALGO_SHA256)) return null;
  $raw = der_to_raw($der); return $raw ? $in . '.' . b64u($raw) : null;
}

/* Seuls les vrais services de notification des navigateurs sont acceptés comme destination (sinon le serveur pourrait être détourné
   pour envoyer des requêtes vers n'importe quelle adresse interne). */
function push_endpoint_ok($ep) {
  if (!is_string($ep) || strlen($ep) > 700) return false;
  $u = parse_url($ep); if (!$u || empty($u['host'])) return false;
  $host = strtolower($u['host']); $sch = strtolower($u['scheme'] ?? '');
  if (defined('PUSH_EXTRA_HOSTS') && in_array($host, (array)PUSH_EXTRA_HOSTS, true)) return in_array($sch, ['http', 'https'], true);   // tests uniquement
  if ($sch !== 'https' || isset($u['user']) || isset($u['pass'])) return false;
  foreach (['googleapis.com', 'mozilla.com', 'push.apple.com', 'notify.windows.com'] as $d) if ($host === $d || substr($host, -strlen($d) - 1) === '.' . $d) return true;
  return false;
}

/* Abonnements : data/push.json = { empreinte(endpoint) : { ep, ids[commandes suivies], sent[commandes déjà signalées], q[messages en attente], ts } } */
function with_push(callable $fn) {
  $h = @fopen(dpath('push') . '.lock', 'c'); if ($h) flock($h, LOCK_EX);
  try { $p = jload('push', []); if (!is_array($p)) $p = []; $r = $fn($p); jsave('push', $p); return $r; }
  finally { if ($h) { flock($h, LOCK_UN); fclose($h); } }
}
function push_subscribe($oid, $ep) {
  with_push(function (&$p) use ($oid, $ep) {
    $k = sha1($ep); $s = $p[$k] ?? ['ep' => $ep, 'ids' => [], 'sent' => [], 'q' => []];
    if (!in_array($oid, $s['ids'], true)) $s['ids'][] = $oid;
    $s['ids'] = array_slice($s['ids'], -40); $s['ts'] = time(); $p[$k] = $s;
    foreach ($p as $kk => $ss) if (($ss['ts'] ?? 0) < time() - 3 * 86400 && empty($ss['q'])) unset($p[$kk]);   // oublie les appareils inactifs depuis 3 jours
  });
}
function push_pop($ep) { return with_push(function (&$p) use ($ep) { $k = sha1($ep); return empty($p[$k]['q']) ? null : array_shift($p[$k]['q']); }); }

function push_send($ep) {      // renvoie le code HTTP du service de notification (201 = accepté, 404/410 = abonnement périmé), 0 = échec technique
  $k = vapid_keys(); if (!$k || !function_exists('curl_init')) return 0;
  $u = parse_url($ep); $aud = $u['scheme'] . '://' . $u['host'] . (isset($u['port']) ? ':' . $u['port'] : '');
  $jwt = vapid_jwt($aud, $k); if (!$jwt) return 0;
  $ch = curl_init($ep);
  curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => '', CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 6, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_SSL_VERIFYPEER => defined('VERIFY_SSL') ? (bool)VERIFY_SSL : true,
    CURLOPT_HTTPHEADER => ['Authorization: vapid t=' . $jwt . ', k=' . $k['pub'], 'TTL: 1800', 'Urgency: high', 'Content-Length: 0']]);
  curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return $code;
}

/* Appelée quand une commande passe à « Prête » : prévient les appareils abonnés à CETTE commande (une seule fois par appareil). */
function push_ready($oid) {
  $st = read_state(); $o = null; foreach ($st['orders'] as $x) if (($x['id'] ?? '') === $oid) { $o = $x; break; }
  if (!$o || !empty($o['cx']) || (int)($o['st'] ?? 0) !== 2) return 0;
  $names = implode(', ', array_map(fn($i) => (int)($i['q'] ?? 1) . '× ' . ($i['n'] ?? ''), $o['items'] ?? []));
  $msg = ['title' => 'Commande #' . $oid . ' prête !', 'body' => 'Table ' . ($o['table'] ?? '') . ' · ' . mb_substr($names, 0, 110), 'url' => 'commande.php?id=' . $oid, 'tag' => 'ready-' . $oid];
  $targets = with_push(function (&$p) use ($oid, $msg) {
    $t = []; foreach ($p as $k => &$s) if (in_array($oid, $s['ids'] ?? [], true) && !in_array($oid, $s['sent'] ?? [], true)) { $s['sent'][] = $oid; $s['q'][] = $msg; $t[$k] = $s['ep']; }
    return $t;
  });
  $n = 0;
  foreach ($targets as $k => $ep) {
    $code = push_send($ep);
    if ($code >= 200 && $code < 300) $n++;
    elseif ($code === 404 || $code === 410) with_push(function (&$p) use ($k) { unset($p[$k]); });
  }
  return $n;
}
