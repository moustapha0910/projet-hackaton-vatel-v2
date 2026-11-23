<?php
// Accès protégés : espace cuisine et espace administration.
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/store.php';

function auth_start() {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  ini_set('session.gc_maxlifetime', '43200');
  session_set_cookie_params(['lifetime' => 43200, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
  session_start();
}
function pw_for($role) {
  if ($role === 'admin') return defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : 'admin123';
  if (defined('KITCHEN_PASSWORD') && KITCHEN_PASSWORD !== '') return KITCHEN_PASSWORD;
  if (defined('KITCHEN_PIN') && KITCHEN_PIN !== '') return KITCHEN_PIN;
  return 'cuisine123';
}
function is_admin() { auth_start(); return !empty($_SESSION['adm']); }
function is_kitchen() { auth_start(); return !empty($_SESSION['k']) || !empty($_SESSION['adm']); }
/* L'en-tête CF-Connecting-IP n'est cru que si la requête arrive du tunnel cloudflared (même machine) : sinon n'importe qui pourrait le falsifier pour contourner les limites. */
function client_ip() {
  $ra = $_SERVER['REMOTE_ADDR'] ?? '?';
  if (in_array($ra, ['127.0.0.1', '::1'], true) && !empty($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) return $_SERVER['HTTP_CF_CONNECTING_IP'];
  return $ra;
}
function throttle_key($role) { return $role . '|' . client_ip(); }
function locked_for($role) {
  $a = jload('attempts', []); $e = $a[throttle_key($role)] ?? null;
  return ($e && ($e['until'] ?? 0) > time()) ? $e['until'] - time() : 0;
}
function register_fail($role) {
  $a = jload('attempts', []); $k = throttle_key($role); $now = time();
  $e = $a[$k] ?? ['n' => 0, 't' => $now];
  if ($now - ($e['t'] ?? 0) > 600) $e = ['n' => 0, 't' => $now];
  $e['n']++;
  if ($e['n'] >= 5) { $e = ['n' => 0, 't' => $now, 'until' => $now + 600]; }
  $a[$k] = $e; jsave('attempts', $a);
}
function register_ok($role) { $a = jload('attempts', []); unset($a[throttle_key($role)]); jsave('attempts', $a); }
// Retourne '' si la connexion réussit, sinon le message d'erreur à afficher.
function try_login($role, $pw) {
  $wait = locked_for($role);
  if ($wait > 0) return 'Trop de tentatives. Réessayez dans ' . (int)ceil($wait / 60) . ' min.';
  if (hash_equals((string)pw_for($role), (string)$pw)) {
    register_ok($role); auth_start(); session_regenerate_id(true);
    if ($role === 'admin') $_SESSION['adm'] = 1; else $_SESSION['k'] = 1;
    return '';
  }
  register_fail($role);
  return 'Mot de passe incorrect.';
}
