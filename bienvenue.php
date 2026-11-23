<?php require_once __DIR__ . '/inc/brand.php'; ?>
<?php // Page d'arrivée après le scan du QR code : table + nom + numéro, puis accès au site. ?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Bienvenue · <?= brand_h('name') ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&display=swap" rel="stylesheet">
<style>
:root{color-scheme:dark;--bg:#0A1A2E;--card:#12243B;--in:#0D1D31;--line:rgba(173,216,230,.16);--red:#FF0000;--blue:#ADD8E6;--mut:#9DB2C8}
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px;background:radial-gradient(700px 360px at 50% 0,rgba(125,14,34,.22),transparent 70%) no-repeat,var(--bg);color:#fff;font:16px/1.45 "DM Sans","Segoe UI",system-ui,sans-serif}
main{width:min(440px,100%);background:var(--card);border:1px solid var(--line);border-radius:14px;padding:28px 24px}
.logo{font-family:Fraunces,Georgia,serif;font-weight:600;font-size:22px}.logo i{color:var(--red);font-style:italic;font-weight:500;margin-left:.25em}
h1{margin:18px 0 4px;font-family:Fraunces,Georgia,serif;font-weight:600;font-size:28px;letter-spacing:-.01em}.mut{color:var(--mut);margin:0}
.tb{margin:18px 0 6px;border:1px solid var(--line);border-radius:10px;padding:14px 18px;background:var(--in);display:flex;align-items:center;justify-content:space-between;gap:12px}
.tb small{display:block;color:var(--mut);letter-spacing:.14em;font-size:11px;text-transform:uppercase}.tb b{font-size:34px;letter-spacing:-.02em;line-height:1.1}.tb span{color:var(--blue);font-size:14px;text-align:right}
label{display:block;margin:16px 0 6px;color:var(--mut);font-size:12px;letter-spacing:.1em;text-transform:uppercase}
input{width:100%;background:var(--in);border:1px solid var(--line);color:#fff;border-radius:8px;padding:14px 16px;font:inherit}input:focus{outline:2px solid var(--blue);outline-offset:1px}input.bad{border-color:#ff6b6b}
.hint{font-size:13px;color:var(--mut);margin:10px 0 0}.err{color:#ff8a8a;min-height:20px;margin:10px 0 0;font-size:14px}
.btn{width:100%;margin-top:6px;background:var(--red);color:#fff;border:0;border-radius:8px;padding:15px;font:inherit;font-weight:800;cursor:pointer;box-shadow:0 10px 28px rgba(255,0,0,.3)}.btn:disabled{opacity:.5;cursor:default;box-shadow:none}
</style></head>
<body><main>
<div class="logo"><?= brand_logo() ?></div>
<h1>Bienvenue.</h1><p class="mut">Dites-nous qui vous êtes pour commander à votre table.</p>
<div class="tb"><div><small>Votre table</small><b id="tnum">—</b></div><span id="tlab"></span></div>
<form id="f" novalidate>
<div id="tfield" hidden><label for="tin">Numéro de table</label><input id="tin" inputmode="numeric" placeholder="Ex : 7"></div>
<label for="nm">Nom</label><input id="nm" autocomplete="name" placeholder="Ex : Awa Koné" required>
<label for="ph">Numéro de téléphone</label><input id="ph" type="tel" inputmode="tel" autocomplete="tel" placeholder="07 00 00 00 00" required>
<p class="hint">Le numéro permet à l'équipe de distinguer deux clients portant le même nom à une même table.</p>
<p class="err" id="err" role="alert"></p>
<button class="btn" id="go" type="submit">Continuer</button>
</form></main>
<script>
(function () {
  var $ = function (i) { return document.getElementById(i); };
  var qs = new URLSearchParams(location.search), asked = qs.get('table');
  var T = asked || localStorage.getItem('table') || '';
  var list = null, blocked = false;
  function pad(n) { n = parseInt(n, 10); return n > 0 ? String(n).padStart(2, '0') : ''; }
  function show() { $('tnum').textContent = pad(T) || '—'; $('tfield').hidden = !!pad(T); }
  function check() {
    var n = pad(T), has = list && Object.keys(list).length;
    blocked = !!(n && has && !(n in list));
    $('tlab').textContent = (n && list && list[n] && list[n].label) ? list[n].label : '';
    $('err').textContent = blocked ? "Cette table n'existe pas ou n'est plus active. Demandez à un membre de l'équipe." : '';
    $('go').disabled = blocked;
  }
  $('nm').value = localStorage.getItem('cname') || ''; $('ph').value = localStorage.getItem('cphone') || '';
  show();
  fetch('api.php?a=tables').then(function (r) { return r.json(); }).then(function (L) { list = L; check(); }).catch(function () {});
  $('tin').addEventListener('input', function () { T = this.value; $('tnum').textContent = pad(T) || '—'; check(); });
  $('f').addEventListener('submit', function (e) {
    e.preventDefault();
    var nm = $('nm').value.trim(), ph = $('ph').value.trim(), digits = ph.replace(/\D/g, ''), msg = '';
    ['nm', 'ph', 'tin'].forEach(function (i) { $(i).classList.remove('bad'); });
    if (!pad(T)) { msg = 'Indiquez votre numéro de table.'; $('tin').classList.add('bad'); }
    else if (blocked) { return; }
    else if (nm.length < 2) { msg = 'Indiquez votre nom (2 lettres minimum).'; $('nm').classList.add('bad'); }
    else if (!/^\+?[0-9 ().\-]+$/.test(ph) || digits.length < 8 || digits.length > 15) { msg = 'Indiquez un numéro de téléphone valide (8 à 15 chiffres).'; $('ph').classList.add('bad'); }
    $('err').textContent = msg; if (msg) return;
    localStorage.setItem('table', pad(T)); localStorage.setItem('cname', nm); localStorage.setItem('cphone', ph); localStorage.setItem('mode', 'client');
    location.href = 'accueil.php';
  });
})();
</script></body></html>
