<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/brand.php';
auth_start();
if (isset($_GET['logout'])) {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', !empty($p['secure']), true); }
  session_destroy(); header('Location: index.php?choisir=1'); exit;
}
$e = (($_GET['e'] ?? 'cuisine') === 'admin') ? 'admin' : 'cuisine';
$dest = $e === 'admin' ? 'admin.php' : 'cuisine.php';
if (($e === 'admin' && is_admin()) || ($e === 'cuisine' && is_kitchen())) { header('Location: ' . $dest); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $err = try_login($e, $_POST['pw'] ?? ''); if ($err === '') { header('Location: ' . $dest); exit; } }
$wait = locked_for($e);
$titre = $e === 'admin' ? 'Espace administration' : 'Espace cuisine';
?><!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $titre ?> · <?= brand_h('name') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/entree.css"></head>
<body><main>
<div class="logo"><?= brand_logo() ?></div>
<h1><?= $titre ?></h1>
<p class="mut"><?= $e === 'admin' ? 'Tables, QR codes, statistiques et réglages.' : 'Commandes en cours, carte et ventes du jour.' ?></p>
<form method="post" autocomplete="off">
<label for="pw">Mot de passe</label>
<input id="pw" name="pw" type="password" required autofocus <?= $wait > 0 ? 'disabled' : '' ?>>
<?php if ($err !== '' && $wait === 0): ?><p class="err" role="alert"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php if ($wait > 0): ?><p class="err" role="alert">Trop de tentatives. Réessayez dans <?= (int)ceil($wait / 60) ?> min.</p><?php endif; ?>
<button class="btn" <?= $wait > 0 ? 'disabled' : '' ?>>Entrer</button>
</form>
<p class="note"><a href="index.php?choisir=1">← Retour à l'accueil</a></p>
</main></body></html>
