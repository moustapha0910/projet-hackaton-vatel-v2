/* Accueil · session du client : numéro de table, nom, mode visite, pastille du panier. */
(function (A) {
  var pad = function (n) { return String(parseInt(n, 10) || '').padStart(2, '0'); };
  var qTable = new URLSearchParams(location.search).get('table');

  /* QR scanné sans être passé par l'écran de bienvenue : on y va d'abord */
  if (qTable && !A.store.get('cname')) { location.replace('bienvenue.php?table=' + encodeURIComponent(qTable)); return; }
  if (qTable && A.store.get('cname')) { A.store.set('table', pad(qTable)); A.store.set('mode', 'client'); }

  var name = A.store.get('cname'), rawTable = A.store.get('table');
  var table = rawTable ? pad(rawTable) : '';
  var client = !!(name && rawTable && A.store.get('mode') !== 'visiteur');

  var S = A.session = { client: client, name: name, table: table };

  var tbp = A.$('#tbp'), tbh = A.$('#tbh'), cw = A.$('#cw'), cc = A.$('#cc'), cmd = A.$('#cmd');

  /* Pastille « nombre d'articles » sur l'icône du panier */
  S.readCart = function () { try { return JSON.parse(A.store.get('cart', '[]')) || []; } catch (e) { return []; } };
  S.updateCartBadge = function () {
    var n = S.readCart().reduce(function (a, l) { return a + (l.q || 0); }, 0);
    if (!cc) return;
    cc.textContent = n; cc.classList.toggle('hidden', !n);
  };

  if (client) {
    if (tbp) tbp.textContent = 'Table ' + table + ' · ' + name;
    if (tbh) tbh.textContent = 'Bienvenue ' + name + ' · Table ' + table;
    S.updateCartBadge();
    /* Nom de la zone (Terrasse, Salon VIP…) si l'admin en a donné un */
    fetch('api.php?a=tables').then(function (r) { return r.json(); }).then(function (L) {
      var m = L && L[table];
      if (m && m.label) {
        if (tbp) tbp.textContent = 'Table ' + table + ' · ' + m.label + ' · ' + name;
        if (tbh) tbh.textContent = 'Bienvenue ' + name + ' · Table ' + table + ' · ' + m.label;
      }
    }).catch(function () {});
  } else {
    if (tbp) tbp.remove();
    if (cw) cw.remove();
    if (tbh) tbh.textContent = 'Visite libre · Cuisine ivoirienne';
    if (cmd) cmd.textContent = 'Voir la carte';
  }
})(window.Accueil);
