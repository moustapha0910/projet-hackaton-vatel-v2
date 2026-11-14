<?php
require_once __DIR__ . '/../config.php'; session_start();
if (KITCHEN_PIN !== '' && in_array($page, ['cuisine','carte','ventes'], true) && empty($_SESSION['k'])) {
  if (($_POST['pin'] ?? '') === KITCHEN_PIN) { $_SESSION['k'] = 1; header('Location: ' . $page . '.php'); exit; }
  ?><!DOCTYPE html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Espace cuisine</title>
  <body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#0A1A2E;color:#fff;font-family:system-ui,sans-serif">
  <form method="post" style="background:#12243B;padding:28px;border-radius:20px;width:min(340px,90vw)"><h2 style="margin:0 0 14px">Espace cuisine</h2>
  <input name="pin" type="password" placeholder="Code PIN" autofocus style="width:100%;padding:12px;border-radius:12px;border:1px solid #2a4468;background:#0D1D31;color:#fff">
  <button style="margin-top:12px;width:100%;padding:12px;border:0;border-radius:12px;background:#f00;color:#fff;font-weight:700">Entrer</button></form></body></html><?php exit;
}
?>
<?php /* Gabarit commun : $page vient de la page appelante */ ?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SaveurIA</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head><body>
<div id="app"></div><div class="sel" id="sel"></div><div id="mod"></div>
<script>window.PAGE='<?= htmlspecialchars($page, ENT_QUOTES) ?>';</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="assets/app.js"></script>
</body></html>
