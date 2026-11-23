<?php
// Identité de l'application : ce fichier est le SEUL endroit où le nom est défini (et il se règle depuis l'admin : Réglages > Identité).
//  - name    : nom du restaurant, affiché aux clients (titres, logo, pied de page, appli installée, sommelier IA, écran cuisine)
//  - app     : nom du logiciel, affiché côté administration ; si différent de « name », un discret « Propulsé par … » apparaît en bas du site
//              (laissez-le vide pour une marque blanche sans mention du logiciel)
//  - tagline : slogan ; city : ville
// Les réglages choisis dans l'admin sont enregistrés dans data/brand.json ; les valeurs ci-dessous servent par défaut.
require_once __DIR__ . '/store.php';

function brand_defaults() { return ['name' => 'IT Resto', 'app' => 'IT Resto', 'tagline' => 'Cuisine ivoirienne et grillades', 'city' => 'Abidjan']; }
function brand_limits() { return ['name' => 40, 'app' => 40, 'tagline' => 80, 'city' => 40]; }
function brand_all() {
  static $b = null; if ($b !== null) return $b;
  $b = brand_defaults(); $j = jload('brand', []);
  if (is_array($j)) foreach ($b as $k => $v) if (isset($j[$k]) && is_string($j[$k])) { $x = clean_label($j[$k], brand_limits()[$k]); if ($x !== '' || $k !== 'name') $b[$k] = $x; }
  return $b;
}
function brand($k) { return brand_all()[$k] ?? ''; }
function brand_h($k) { return htmlspecialchars(brand($k), ENT_QUOTES, 'UTF-8'); }
/* Logo : tous les mots normaux, le dernier mis en valeur (italique, rouge) — « IT <i>Resto</i> ». Un nom d'un seul mot reste entier. */
function brand_logo($open = '<i>', $close = '</i>') {
  $w = preg_split('/\s+/u', trim(brand('name'))); $last = count($w) > 1 ? array_pop($w) : '';
  $e = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  return $e(implode(' ', $w)) . ($last !== '' ? ' ' . $open . $e($last) . $close : '');
}
function brand_initial() { return htmlspecialchars(mb_strtoupper(mb_substr(brand('name'), 0, 1)), ENT_QUOTES, 'UTF-8'); }
function brand_slug() {   // « Chez Maman & Fils » -> chez-maman-fils (pour les noms de fichiers ; accents retirés sans dépendre d'iconv/intl)
  $t = strtr(mb_strtolower(brand('name')), ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ç'=>'c','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','í'=>'i','ô'=>'o','ö'=>'o','ó'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ú'=>'u','ÿ'=>'y','œ'=>'oe','æ'=>'ae','ñ'=>'n']);
  $s = trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-'); return $s !== '' ? $s : 'restaurant';
}
function brand_credit() { return (brand('app') !== '' && brand('app') !== brand('name')) ? ' · Propulsé par ' . brand_h('app') : ''; }
/* Données passées à JavaScript (écran cuisine, chat…) — protégées contre toute balise dans le nom. */
function brand_json() { return json_encode(['name' => brand('name'), 'app' => brand('app'), 'tagline' => brand('tagline'), 'city' => brand('city')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); }
/* Enregistrement depuis l'admin : renvoie un message d'erreur ou '' */
function brand_save(array $in) {
  $b = [];
  foreach (brand_limits() as $k => $max) $b[$k] = clean_label($in[$k] ?? '', $max);
  if ($b['name'] === '') return 'Le nom du restaurant est obligatoire.';
  jsave('brand', $b); return '';
}
