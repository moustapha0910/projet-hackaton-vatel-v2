/* Accueil · fiche plat. Un clic sur un plat du carrousel 3D ou du défilement horizontal l'ouvre en grand.
   Animation : la fiche « sort » de la carte cliquée (position et taille), puis son contenu apparaît en cascade. */
(function (A) {
  var root = A.$('#dishModal'), dataEl = A.$('#dishData');
  if (!root || !dataEl) return;

  var panel = A.$('.dm-panel', root), scroller = A.$('.dm-scroll', root), backdrop = A.$('.dm-backdrop', root);
  var el = {
    img: A.$('#dmImg'), sig: A.$('#dmSig'), cat: A.$('#dmCat'), cat2: A.$('#dmCat2'), title: A.$('#dmTitle'), price: A.$('#dmPrice'),
    time: A.$('#dmTime'), ing: A.$('#dmIng'), tags: A.$('#dmTags'), tagsBox: A.$('#dmTagsBox'), allergens: A.$('#dmAllergens'),
    add: A.$('#dmAdd'), note: A.$('#dmNote')
  };
  var dishes = {};
  try { JSON.parse(dataEl.textContent).forEach(function (d) { dishes[d.id] = d; }); } catch (e) { return; }

  var TAG_LABELS = { 'épicé': 'Épicé', copieux: 'Copieux', 'léger': 'Léger', partager: 'À partager', viande: 'Viande', poisson: 'Poisson', 'sucré': 'Sucré', boisson: 'Boisson' };
  var EASE = 'cubic-bezier(.22,1,.36,1)';
  var current = null, trigger = null, busy = false;

  /* ---------- Remplissage ---------- */
  function ingredients(text) { /* « Avocat, crevettes et citron. » → 3 éléments */
    return String(text || '').replace(/\.\s*$/, '').split(/,\s*|\s+et\s+/).map(function (s) { return s.trim(); }).filter(Boolean)
      .map(function (s) { return s.charAt(0).toUpperCase() + s.slice(1); });
  }
  function chips(list, cls) {
    return list.map(function (t) { var li = document.createElement('li'); if (cls) li.className = cls; li.textContent = t; return li; });
  }
  function fill(d) {
    el.img.src = d.img; el.img.alt = d.n;
    el.sig.hidden = !d.sig;
    el.cat.textContent = d.c; el.cat2.textContent = d.c;
    el.title.textContent = d.n;
    el.price.textContent = A.fcfa(d.p);
    el.time.textContent = d.m ? d.m + ' min' : '—';
    el.ing.replaceChildren.apply(el.ing, ingredients(d.d).map(function (t) { var li = document.createElement('li'); li.textContent = t; return li; }));
    var tags = (d.t || []).filter(function (t) { return TAG_LABELS[t]; }).map(function (t) { return TAG_LABELS[t]; });
    el.tags.replaceChildren.apply(el.tags, chips(tags)); el.tagsBox.hidden = !tags.length;
    var al = d.a || [];
    el.allergens.replaceChildren.apply(el.allergens, al.length ? chips(al, 'warn') : chips(['Aucun allergène majeur déclaré'], 'ok'));
    resetAction();
  }

  /* ---------- Bouton « Ajouter au panier » ---------- */
  function resetAction() {
    var client = A.session && A.session.client;
    el.add.hidden = !client; el.add.disabled = false; el.add.textContent = 'Ajouter au panier';
    el.note.hidden = client; el.note.textContent = client ? '' : 'Pour commander, scannez le QR code de votre table.';
  }
  el.add.addEventListener('click', function () {
    if (!current) return;
    var cart = A.session.readCart(), line = cart.filter(function (l) { return l.id == current.id; })[0];
    if (line) line.q += 1; else cart.push({ id: current.id, q: 1, note: '' });
    A.store.set('cart', JSON.stringify(cart));
    A.session.updateCartBadge();
    el.add.textContent = 'Ajouté ✓';
    el.note.hidden = false; el.note.innerHTML = current.n + ' est dans votre panier. <a href="panier.php">Voir mon panier</a>';
  });

  /* ---------- Animations (Web Animations API : fonctionne sans GSAP) ---------- */
  function flipFrom(rect) { /* transformation qui place la fiche sur la carte cliquée */
    var p = panel.getBoundingClientRect();
    var s = Math.max(.3, Math.min(rect.width / p.width, 1));
    var dx = rect.left + rect.width / 2 - (p.left + p.width / 2), dy = rect.top + rect.height / 2 - (p.top + p.height / 2);
    return 'translate(' + dx + 'px,' + dy + 'px) scale(' + s + ')';
  }
  function sourceRect() { return trigger && document.contains(trigger) ? trigger.getBoundingClientRect() : null; }
  function visible(r) { return r && r.bottom > 0 && r.top < innerHeight && r.right > 0 && r.left < innerWidth; }

  function animateIn() {
    if (A.reduce) return;
    var r = sourceRect();
    backdrop.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 280, easing: 'ease-out' });
    panel.animate(
      [{ transform: visible(r) ? flipFrom(r) : 'translateY(40px) scale(.94)', opacity: 0 }, { opacity: 1, offset: .35 }, { transform: 'none', opacity: 1 }],
      { duration: 560, easing: EASE });
    A.$$('.dm-media img, .dm-body > *', root).forEach(function (n, i) {
      n.animate([{ opacity: 0, transform: n.tagName === 'IMG' ? 'scale(1.12)' : 'translateY(16px)' }, { opacity: 1, transform: 'none' }],
        { duration: 520, delay: n.tagName === 'IMG' ? 0 : 220 + i * 55, easing: EASE, fill: 'backwards' });
    });
  }
  function animateOut(done) {
    if (A.reduce) return done();
    var r = sourceRect();
    backdrop.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 260, fill: 'forwards' });
    panel.animate([{ transform: 'none', opacity: 1 }, { transform: visible(r) ? flipFrom(r) : 'translateY(30px) scale(.95)', opacity: 0 }],
      { duration: 320, easing: 'cubic-bezier(.5,0,.75,0)', fill: 'forwards' }).onfinish = done;
  }

  /* ---------- Ouverture / fermeture ---------- */
  function lockScroll(on) {
    if (A.lenis) on ? A.lenis.stop() : A.lenis.start();
  }
  function open(id, from) {
    var d = dishes[id]; if (!d || busy || !root.hidden) return;
    current = d; trigger = from; fill(d);
    root.hidden = false; scroller.scrollTop = 0; lockScroll(true);
    animateIn();
    panel.focus({ preventScroll: true });
  }
  function close() {
    if (root.hidden || busy) return;
    busy = true;
    animateOut(function () {
      root.hidden = true; busy = false; lockScroll(false);
      backdrop.getAnimations().concat(panel.getAnimations()).forEach(function (a) { a.cancel(); });
      if (trigger && document.contains(trigger)) trigger.focus({ preventScroll: true });
      current = null;
    });
  }
  A.dishModal = { open: open, close: close };

  /* ---------- Événements ---------- */
  document.addEventListener('click', function (e) {
    var card = e.target.closest('[data-dish]');
    if (card && root.hidden) { open(card.dataset.dish, card); return; }
    if (!root.hidden && (e.target.closest('.dm-x') || e.target === scroller || e.target === backdrop)) close();
  });
  document.addEventListener('keydown', function (e) {
    if (root.hidden) {
      var c = e.target.closest && e.target.closest('[data-dish]');
      if (c && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); open(c.dataset.dish, c); }
      return;
    }
    if (e.key === 'Escape') { close(); return; }
    if (e.key === 'Tab') { /* le focus reste dans la fenêtre */
      var f = A.$$('button:not([hidden]):not([disabled]), a[href]', panel);
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && (document.activeElement === first || document.activeElement === panel)) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });
  /* Sans Lenis, la page derrière ne doit pas défiler quand la fiche est ouverte */
  root.addEventListener('wheel', function (e) { if (scroller.scrollHeight <= scroller.clientHeight) e.preventDefault(); }, { passive: false });
  root.addEventListener('touchmove', function (e) { if (scroller.scrollHeight <= scroller.clientHeight) e.preventDefault(); }, { passive: false });
})(window.Accueil);
