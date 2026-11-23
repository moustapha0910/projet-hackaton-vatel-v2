/* Accueil · démarrage : vidéo d'ambiance, titres découpés mot à mot, chargement des bibliothèques d'animation. */
(function (A) {
  /* Vidéo d'ambiance du hero */
  try { var hv = A.$('#hv'); if (hv && window.VIDEO_SRC) { hv.muted = true; hv.src = window.VIDEO_SRC; hv.play().catch(function () {}); } } catch (e) {}

  /* Titres [data-split] : chaque mot est enveloppé pour pouvoir monter depuis le bas */
  A.$$('[data-split]').forEach(function (el) {
    el.innerHTML = el.textContent.split(' ').map(function (w) {
      return '<span class="inline-block overflow-hidden align-bottom pb-1"><span class="inline-block w">' + w + '</span></span>';
    }).join(' ');
  });

  /* Burger du hero : s'incline doucement vers la souris (JS simple, sans GSAP : marche aussi hors ligne) */
  if (!A.reduce && window.matchMedia && matchMedia('(pointer: fine)').matches) {
    var hero = A.$('#hero'), tilts = A.$$('.burger-tilt');
    if (hero && tilts.length) {
      tilts.forEach(function (el) { el.style.transition = 'transform .6s cubic-bezier(.2,.7,.2,1)'; });
      hero.addEventListener('pointermove', function (e) {
        var nx = e.clientX / innerWidth - .5, ny = e.clientY / innerHeight - .5;
        tilts.forEach(function (el) { el.style.transform = 'perspective(900px) rotateX(' + (-ny * 10).toFixed(2) + 'deg) rotateY(' + (nx * 14).toFixed(2) + 'deg)'; });
      });
      hero.addEventListener('pointerleave', function () { tilts.forEach(function (el) { el.style.transform = ''; }); });
    }
  }

  /* Animations coupées par le système : on le dit, avec un bouton pour les réactiver */
  if (A.reduce) setTimeout(function () {
    A.banner('Votre système demande de réduire les animations (effets de défilement désactivés).', 'Activer les animations',
      function () { A.store.set('anim', '1'); location.reload(); });
  }, 600);

  /* Charge une liste de scripts de secours : le premier qui répond suffit */
  function load(urls, next) {
    var i = 0;
    (function try_() {
      if (i >= urls.length) return next();
      var s = document.createElement('script');
      s.src = urls[i++]; s.onload = next; s.onerror = try_;
      document.head.appendChild(s);
    })();
  }
  var CDNJS = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/', JSDELIVR = 'https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/';
  load([CDNJS + 'gsap.min.js', JSDELIVR + 'gsap.min.js'], function () {
    load([CDNJS + 'ScrollTrigger.min.js', JSDELIVR + 'ScrollTrigger.min.js'], function () {
      load(['https://cdn.jsdelivr.net/npm/lenis@1.1.20/dist/lenis.min.js'], A.motion.start);
    });
  });
})(window.Accueil);
