<?php
date_default_timezone_set('Africa/Abidjan');
function dpath($f) { $d = __DIR__ . '/../data'; if (!is_dir($d)) mkdir($d, 0777, true); return "$d/$f.json"; }
function jload($f, $def) { $p = dpath($f); if (!is_file($p)) return $def; $j = json_decode((string)file_get_contents($p), true); return is_array($j) ? $j : $def; }
function jsave($f, $v) { file_put_contents(dpath($f), json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX); }
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
function base_url() {
  if (defined('PUBLIC_URL') && PUBLIC_URL !== '') return rtrim(PUBLIC_URL, '/');
  $s = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $port = isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 0;
  if (preg_match('~^(localhost|127\.0\.0\.1|\[?::1\]?)(:\d+)?$~i', $host)) {
    $ip = lan_ip();
    if ($ip !== '') $host = $ip . (($port && $port !== 80 && $port !== 443) ? ':' . $port : '');
  }
  return $s . '://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
}
