<?php
// QR codes des tables : imprimez cette page. Le lien utilise automatiquement l'IP locale du PC pour que les téléphones puissent le joindre.
require_once __DIR__ . '/inc/store.php';
require_once __DIR__ . '/inc/brand.php';
$base = base_url() . '/bienvenue.php?table=';
$n = max(1, min(40, (int)($_GET['n'] ?? 12)));
$local = (bool)preg_match('~//(localhost|127\.)~i', $base);
?><!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><title>QR codes des tables - <?= brand_h('name') ?></title>
<style>body{font-family:system-ui,sans-serif;margin:24px;color:#0A1A2E}h1{font-size:22px}.g{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:16px}.c{border:1px solid #ccd;border-radius:16px;padding:16px;text-align:center;break-inside:avoid}.c b{display:block;margin-top:8px}.c small{color:#556}</style></head><body>
<h1><?= brand_h('name') ?> : QR codes des tables</h1><p><small>Pour créer vos propres tables et gérer leurs QR codes : <a href="admin.php?t=tables">espace admin</a>.</small></p><p><small>Lien utilisé : <?= htmlspecialchars($base) ?>N</small><?php if ($local): ?><br><b>Attention :</b> IP locale introuvable. Renseignez PUBLIC_URL dans config.php pour que les téléphones puissent ouvrir ces QR codes.<?php endif; ?></p>
<div class="g"><?php for ($i = 1; $i <= $n; $i++): ?><div class="c"><div class="q" data-t="<?= $i ?>" style="display:inline-block"></div><b>Table <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></b><small>Scannez pour commander</small></div><?php endfor; ?></div>
<script src="assets/qr.js"></script>
<script>document.querySelectorAll('.q').forEach(e=>QRX.draw(e,<?= json_encode($base) ?>+e.dataset.t,170))</script></body></html>
