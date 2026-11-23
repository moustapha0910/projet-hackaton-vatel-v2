/* Accueil · animations de défilement (GSAP, ScrollTrigger, Lenis). Appelé par boot.js une fois les bibliothèques chargées. */
(function (A) {
  A.motion = {};

  A.motion.start = function () {
    if (!(window.gsap && window.ScrollTrigger)) { A.motion.warn(); return; }
    gsap.registerPlugin(ScrollTrigger);
    var reduce = A.reduce, lenis = null;

    /* --- Défilement fluide (Lenis) --- */
    if (window.Lenis && !reduce) {
      lenis = new Lenis({ lerp: .1 });
      lenis.on('scroll', ScrollTrigger.update);
      gsap.ticker.add(function (t) { lenis.raf(t * 1000); });
      gsap.ticker.lagSmoothing(0);
      A.lenis = lenis; /* modal.js s'en sert pour bloquer le défilement */
    }
    var scrub = lenis ? true : .6;

    /* --- Liens d'ancre et transition rouge entre les pages --- */
    A.$$('[data-scroll]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var t = A.$(a.getAttribute('href'));
        lenis ? lenis.scrollTo(t, { offset: -60 }) : t.scrollIntoView({ behavior: 'smooth' });
      });
    });
    A.$$('[data-go]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault(); var u = a.href;
        gsap.set('#cur', { yPercent: 100 });
        gsap.to('#cur', { yPercent: 0, duration: .7, ease: 'power4.inOut', onComplete: function () { location.href = u; } });
      });
    });
    addEventListener('pageshow', function (e) { if (e.persisted) gsap.set('#cur', { yPercent: -100 }); });
    gsap.fromTo('#cur', { yPercent: 0 }, { yPercent: -100, duration: .9, ease: 'power4.inOut', delay: .1 });
    gsap.to('#bar', { scaleX: 1, ease: 'none', scrollTrigger: { scrub: .3, start: 0, end: 'max' } });

    /* --- 1. Hero --- */
    gsap.from('#hero .w', { yPercent: 110, duration: 1.1, stagger: .08, ease: 'power4.out', delay: .5 });
    gsap.from('.hero-fade', { y: 30, opacity: 0, duration: 1, stagger: .12, delay: .9, ease: 'power3.out' });
    A.$$('[data-parallax]').forEach(function (el) { /* le bloc de photos monte d'un seul tenant */
      gsap.to(el, { yPercent: +el.dataset.parallax, ease: 'none', scrollTrigger: { trigger: '#hero', start: 'top top', end: 'bottom top', scrub: true } });
    });
    gsap.to('#heroTxt', { scale: .92, opacity: 0, y: -50, ease: 'none', scrollTrigger: { trigger: '#hero', start: '55% top', end: 'bottom top', scrub: true } });

    /* --- 2. Carrousel 3D épinglé --- */
    if (!reduce) {
      var tl = gsap.timeline({ scrollTrigger: { trigger: '#stage', start: 'top top', end: '+=320%', scrub: scrub, pin: true, anticipatePin: 1 } });
      tl.fromTo('#ring', { rotationY: 0, rotationX: 16, scale: .75 }, { rotationY: -360, rotationX: -6, scale: 1, duration: 3, ease: 'none' }, 0);
      A.$$('.cap').forEach(function (c, i) {
        tl.fromTo(c, { autoAlpha: 0, y: 30 }, { autoAlpha: 1, y: 0, duration: .35 }, i + .08);
        if (i < 2) tl.to(c, { autoAlpha: 0, y: -30, duration: .35 }, i + .85);
      });
    } else {
      A.$$('.cap').forEach(function (c) { c.style.position = 'static'; });
    }

    /* --- 3. Sommelier --- */
    gsap.fromTo('.tilt', { rotationX: 30, y: 60, opacity: 0, transformPerspective: 900 },
      { rotationX: 0, y: 0, opacity: 1, ease: 'none', scrollTrigger: { trigger: '#sommelier', start: 'top 85%', end: 'top 35%', scrub: true } });
    gsap.from('.msg', { y: 26, opacity: 0, stagger: .3, duration: .7, ease: 'power3.out', scrollTrigger: { trigger: '#mock', start: 'top 70%' } });

    /* --- 4. Défilement horizontal épinglé --- */
    var track = A.$('#track');
    if (!reduce && track) {
      gsap.to(track, { x: function () { return -(track.scrollWidth - innerWidth + 40); }, ease: 'none',
        scrollTrigger: { trigger: '#hs', start: 'top top', end: function () { return '+=' + track.scrollWidth; }, scrub: scrub, pin: true, anticipatePin: 1, invalidateOnRefresh: true } });
    }

    /* --- 5. Chiffres clés --- */
    A.$$('.num').forEach(function (el) {
      var o = { v: 0 };
      gsap.to(o, { v: +el.dataset.n, duration: 1.6, ease: 'power2.out', scrollTrigger: { trigger: el, start: 'top 90%', once: true }, onUpdate: function () { el.textContent = Math.round(o.v); } });
    });
    gsap.from('.stat', { y: 50, opacity: 0, stagger: .12, duration: .8, ease: 'power3.out', scrollTrigger: { trigger: '.stat', start: 'top 88%' } });

    /* --- 6. Appel à l'action --- */
    gsap.from('#cta .w', { yPercent: 110, stagger: .08, duration: 1, ease: 'power4.out', scrollTrigger: { trigger: '#cta', start: 'top 70%' } });
  };

  /* Bibliothèques d'animation introuvables : on retire le rideau rouge pour que la page reste utilisable */
  A.motion.warn = function () {
    var c = A.$('#cur'); if (c) c.style.display = 'none';
    A.banner('Les bibliothèques d’animation n’ont pas pu se charger (connexion ou blocage réseau). Ouvrez la console (F12) pour le détail.');
  };
})(window.Accueil);
