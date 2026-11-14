<?php
// QR codes des tables : imprimez cette page. Ouvrez-la via l'adresse IP du PC (pas localhost) pour que les téléphones puissent la joindre.
$base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/index.php?table=';
$n = max(1, min(40, (int)($_GET['n'] ?? 12)));
?><!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><title>QR codes des tables - SaveurIA</title>
<style>body{font-family:system-ui,sans-serif;margin:24px;color:#0A1A2E}h1{font-size:22px}.g{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:16px}.c{border:1px solid #ccd;border-radius:16px;padding:16px;text-align:center;break-inside:avoid}.c b{display:block;margin-top:8px}.c small{color:#556}</style></head><body>
<h1>SaveurIA : QR codes des tables</h1><p><small>Pour créer vos propres tables et gérer leurs QR codes : <a href="admin.php?t=tables">espace admin</a>.</small></p><p><small>Lien utilisé : <?= htmlspecialchars($base) ?>N</small><?php if (strpos($_SERVER['HTTP_HOST'], 'localhost') === 0): ?><br><b>Attention :</b> vous êtes sur localhost, les téléphones ne pourront pas ouvrir ces QR codes. Rouvrez cette page avec l'adresse IP de votre PC.<?php endif; ?></p>
<div class="g"><?php for ($i = 1; $i <= $n; $i++): ?><div class="c"><div class="q" data-t="<?= $i ?>" style="display:inline-block"></div><b>Table <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></b><small>Scannez pour commander</small></div><?php endfor; ?></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>document.querySelectorAll('.q').forEach(e=>new QRCode(e,{text:<?= json_encode($base) ?>+e.dataset.t,width:150,height:150}))</script></body></html>
