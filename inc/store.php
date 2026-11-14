<?php
date_default_timezone_set('Africa/Abidjan');
function dpath($f) { $d = __DIR__ . '/../data'; if (!is_dir($d)) mkdir($d, 0777, true); return "$d/$f.json"; }
function jload($f, $def) { $p = dpath($f); if (!is_file($p)) return $def; $j = json_decode((string)file_get_contents($p), true); return is_array($j) ? $j : $def; }
function jsave($f, $v) { file_put_contents(dpath($f), json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX); }
function pad($n) { return str_pad((string)(int)$n, 2, '0', STR_PAD_LEFT); }
function base_url() {
  if (defined('PUBLIC_URL') && PUBLIC_URL !== '') return rtrim(PUBLIC_URL, '/');
  $s = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  return $s . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
}
