<?php
date_default_timezone_set('Africa/Abidjan');
function dpath($f) { $d = __DIR__ . '/../data'; if (!is_dir($d)) mkdir($d, 0777, true); return "$d/$f.json"; }
function jload($f, $def) { $p = dpath($f); if (!is_file($p)) return $def; $j = json_decode((string)file_get_contents($p), true); return is_array($j) ? $j : $def; }
function jsave($f, $v) { file_put_contents(dpath($f), json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX); }
/* ---------- État partagé (carte, commandes, avis) : tout passe par le serveur, sous verrou ---------- */
function menu_default() { $p = __DIR__ . '/menu_default.json'; $j = is_file($p) ? json_decode((string)file_get_contents($p), true) : null; return is_array($j) ? $j : []; }
function state_default() { return ['D' => menu_default(), 'orders' => [], 'reviews' => [], 'nid' => 301, 'uid' => 20]; }
function state_norm($st) {
  $d = state_default(); if (!is_array($st)) return $d;
  return [
    'D' => (isset($st['D']) && is_array($st['D']) && $st['D']) ? array_values($st['D']) : $d['D'],
    'orders' => (isset($st['orders']) && is_array($st['orders'])) ? array_values($st['orders']) : [],
    'reviews' => (isset($st['reviews']) && is_array($st['reviews'])) ? array_values($st['reviews']) : [],
    'nid' => max(1, (int)($st['nid'] ?? $d['nid'])),
    'uid' => max(1, (int)($st['uid'] ?? $d['uid'])),
  ];
}
function state_lock($mode) { $h = @fopen(dpath('state') . '.lock', 'c'); if ($h) flock($h, $mode); return $h; }
function state_unlock($h) { if ($h) { flock($h, LOCK_UN); fclose($h); } }
function read_state() { $h = state_lock(LOCK_SH); $st = state_norm(jload('state', null)); state_unlock($h); return $st; }
/* Lecture -> modification -> écriture sans qu'aucune autre requête puisse s'intercaler (deux commandes simultanées ne s'écrasent plus).
   $fn reçoit l'état par référence ; si elle renvoie un tableau contenant '_nosave', rien n'est écrit. */
function with_state(callable $fn) {
  $h = state_lock(LOCK_EX);
  try {
    $st = state_norm(jload('state', null)); $res = $fn($st);
    if (!is_array($res) || empty($res['_nosave'])) jsave('state', $st);
    if (is_array($res)) unset($res['_nosave']);
    return $res;
  } finally { state_unlock($h); }
}

/* ---------- Anti-abus : nombre de requêtes par adresse IP et par fenêtre de temps ---------- */
function rate_hit($bucket, $limit, $window) {
  $h = @fopen(dpath('ratelimit') . '.lock', 'c'); if ($h) flock($h, LOCK_EX);
  $a = jload('ratelimit', []); $now = time(); $k = $bucket . '|' . client_ip();
  $l = array_values(array_filter($a[$k] ?? [], fn($t) => $t > $now - $window));
  $ok = count($l) < $limit; if ($ok) $l[] = $now; $a[$k] = $l;
  foreach ($a as $kk => $ll) { $ll = array_values(array_filter((array)$ll, fn($t) => $t > $now - 3600)); if ($ll) $a[$kk] = $ll; else unset($a[$kk]); }
  jsave('ratelimit', $a);
  if ($h) { flock($h, LOCK_UN); fclose($h); }
  return $ok;
}

/* ---------- Nettoyage des textes reçus (le navigateur ne doit jamais être cru sur parole) ---------- */
function clean_text($s, $max) { $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string)$s); return mb_substr(trim((string)$s), 0, $max); }
function clean_label($s, $max) { return str_replace(['<', '>', '"'], '', clean_text($s, $max)); }
function clean_dish($d) {
  if (!is_array($d)) return null;
  $id = (int)($d['id'] ?? 0); $n = clean_label($d['n'] ?? '', 80); $p = (int)($d['p'] ?? 0);
  if ($id < 1 || $n === '' || $p < 1 || $p > 1000000) return null;
  $img = trim((string)($d['img'] ?? ''));
  if ($img !== '' && (strlen($img) > 300000 || !preg_match('~^(https?://|img/|media/|data:image/(png|jpe?g|webp|gif);base64,)[^\s"\'<>]*$~i', $img))) $img = '';
  $list = function ($v, $len, $cnt) { $o = []; foreach ((array)$v as $x) { $x = clean_label(is_scalar($x) ? $x : '', $len); if ($x !== '') $o[] = $x; } return array_slice($o, 0, $cnt); };
  $cats = ['Entrées', 'Grillades', 'Plats', 'Desserts', 'Boissons'];
  return ['id' => $id, 'n' => $n, 'c' => in_array($d['c'] ?? '', $cats, true) ? $d['c'] : 'Plats', 'p' => $p, 'm' => max(1, min(240, (int)($d['m'] ?? 10))),
          'a' => $list($d['a'] ?? [], 30, 10), 's' => empty($d['s']) ? 0 : 1, 'img' => $img, 'd' => clean_label($d['d'] ?? '', 300), 't' => $list($d['t'] ?? [], 20, 8), 'o' => empty($d['o']) ? 0 : 1];
}
function pad($n) { return str_pad((string)(int)$n, 2, '0', STR_PAD_LEFT); }
function lan_ip() {
  // 1) PowerShell : interface active ayant une passerelle IPv4 (fiable, indépendant de la langue).
  if (function_exists('shell_exec')) {
    $ps = @shell_exec('powershell -NoProfile -NonInteractive -Command "(Get-NetIPConfiguration | ? { $_.IPv4DefaultGateway } | Select -First 1).IPv4Address.IPAddress" 2>&1');
    if (is_string($ps)) { $ps = trim($ps); if (preg_match('~^\d{1,3}(\.\d{1,3}){3}$~', $ps) && $ps !== '127.0.0.1') return $ps; }
  }
  // 2) ipconfig (anglais ou français) : adaptateur dont la passerelle est non vide.
  if (function_exists('shell_exec')) {
    $out = @shell_exec('ipconfig 2>&1');
    if (is_string($out)) {
      foreach (preg_split('/\r?\n(?=\S)/', $out) as $b) {
        if (preg_match('/vEthernet|VMware|VirtualBox|Loopback|Tunnel|Bluetooth|Hyper-V|hyper-v/i', $b)) continue;
        if (!preg_match('/IPv4[^\r\n:]*:\s*([0-9.]+)/i', $b, $m)) continue;
        $ip = $m[1];
        if (preg_match('~^127\.~', $ip) || preg_match('~^169\.254\.~', $ip)) continue;
        if (preg_match('/Passerelle par d[^\r\n:]*:\s*([0-9A-Fa-f.:]+)|Default Gateway[^\r\n:]*:\s*([0-9A-Fa-f.:]+)/', $b, $g)) {
          $gw = ($g[1] ?? '') !== '' ? $g[1] : ($g[2] ?? '');
          if ($gw !== '' && $gw !== '0.0.0.0') return $ip;
        }
      }
    }
  }
  // 3) repli
  $cands = [];
  $h = gethostbyname(gethostname());
  if ($h && preg_match('~^\d{1,3}(\.\d{1,3}){3}$~', $h) && !preg_match('~^(127\.|169\.254\.)~', $h)) $cands[] = $h;
  if (!empty($_SERVER['SERVER_ADDR']) && preg_match('~^\d{1,3}(\.\d{1,3}){3}$~', $_SERVER['SERVER_ADDR']) && !preg_match('~^(127\.|169\.254\.)~', $_SERVER['SERVER_ADDR'])) $cands[] = $_SERVER['SERVER_ADDR'];
  foreach ($cands as $c) {
    if (preg_match('~^(192\.168\.(56|80|142|152|198)\.|172\.(1[6-9]|2\d|3[01])\.)~', $c)) continue;
    if (preg_match('~^(192\.168\.|10\.)~', $c)) return $c;
  }
  return $cands[0] ?? '';
}
function url_clean($u) { $r = preg_replace_callback('/[^\x21-\x7e]/u', function ($m) { return rawurlencode($m[0]); }, $u); return $r === null ? $u : $r; }
function base_url() {
  if (defined('PUBLIC_URL') && PUBLIC_URL !== '') return url_clean(rtrim(PUBLIC_URL, '/'));
  $s = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $port = isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 0;
  if (preg_match('~^(localhost|127\.0\.0\.1|\[?::1\]?)(:\d+)?$~i', $host)) {
    $ip = lan_ip();
    if ($ip !== '') $host = $ip . (($port && $port !== 80 && $port !== 443) ? ':' . $port : '');
  }
  return url_clean($s . '://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
}
