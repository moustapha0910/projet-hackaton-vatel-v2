/* Accueil · utilitaires partagés. Tout le code de la page vit dans l'espace de noms window.Accueil. */
window.Accueil = window.Accueil || {};
(function (A) {
  A.$  = function (sel, root) { return (root || document).querySelector(sel); };
  A.$$ = function (sel, root) { return Array.from((root || document).querySelectorAll(sel)); };

  /* localStorage sans erreur (navigation privée, stockage bloqué…) */
  A.store = {
    get: function (k, def) { try { var v = localStorage.getItem(k); return v === null ? (def === undefined ? '' : def) : v; } catch (e) { return def === undefined ? '' : def; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  };

  /* Animations réduites : demandées par le système, sauf si le client les a réactivées */
  A.osReduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  A.reduce = A.osReduce && A.store.get('anim') !== '1';
  document.documentElement.classList.toggle('no-anim', !!A.reduce); /* le CSS s'en sert (le bouton « Activer les animations » est ainsi pris en compte) */

  A.fcfa = function (n) { return new Intl.NumberFormat('fr-FR').format(n).replace(/[  ]/g, ' ') + ' FCFA'; };

  /* Petit message en bas de l'écran (bouton facultatif) */
  A.banner = function (message, label, onClick) {
    var d = document.createElement('div');
    d.className = 'fixed left-1/2 -translate-x-1/2 bottom-5 z-[95] flex items-center gap-3 rounded-lg border border-white/15 bg-card/95 backdrop-blur px-4 py-3 text-sm max-w-[92vw]';
    d.innerHTML = '<span>' + message + '</span>' + (label ? '<button class="px-3 py-1.5 rounded-md bg-[#FF0000] font-bold">' + label + '</button>' : '');
    if (label) d.querySelector('button').onclick = onClick;
    document.body.appendChild(d);
  };
})(window.Accueil);
