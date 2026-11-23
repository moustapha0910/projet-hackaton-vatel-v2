<?php
$heroBurger = is_file(__DIR__ . '/../../../img/burger-cutout.png'); // burger détouré (fond transparent) affiché à droite du titre
if (!function_exists('ac_burger')) {
  /* Burger + ombre au sol. Animé par assets/accueil.css (flottement) et assets/accueil/motion.js (chute à l'arrivée, inclinaison à la souris). */
  function ac_burger($size) {
    return '<div class="burger-tilt relative ' . $size . '"><div class="burger-drop"><picture>'
         . '<source srcset="img/burger-cutout.webp" type="image/webp">'
         . '<img src="img/burger-cutout.png" alt="" width="837" height="597" decoding="async" class="burger-float"></picture></div>'
         . '<div class="burger-shadow"></div></div>';
  }
}
?>
<?php
/* Section 1 · En-tête. Texte à gauche, bloc de 3 photos à droite (grand écran).
   Les photos forment UN seul bloc (data-parallax) : elles gardent toujours le même écart entre elles. */
$collage = [ // [classes de largeur / décalage / rotation]
    ['w-[88%] ml-auto rotate-3',   'eager'],
    ['w-[80%] mr-auto -rotate-3',  'eager'],
    ['w-[72%] ml-auto rotate-2',   'eager'],
];
?>
<section id="hero" class="relative min-h-screen flex items-center overflow-hidden glow">
  <video id="hv" class="absolute inset-0 w-full h-full object-cover opacity-30" muted loop playsinline preload="auto" aria-hidden="true"></video>
  <div class="absolute inset-0 bg-gradient-to-b from-navy/40 via-transparent to-navy"></div>

  <?php if ($heroDishes && !$heroBurger): ?>
  <div class="hidden xl:block absolute inset-0 pointer-events-none" aria-hidden="true">
    <div class="relative h-full max-w-6xl mx-auto px-5 flex items-center justify-end">
      <div id="heroCollage" data-parallax="-8" class="hero-collage flex flex-col gap-3.5">
        <?php foreach ($heroDishes as $i => $d): ?>
          <img src="<?= ac_h($d['img']) ?>" alt="" decoding="async" class="<?= $collage[$i][0] ?> aspect-[538/238] object-cover rounded-2xl border border-white/15 shadow-[0_18px_40px_rgba(0,0,0,.35)]">
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($heroBurger): ?>
<div class="hidden lg:block absolute inset-y-0 right-0 w-[46%] pointer-events-none" aria-hidden="true">
  <div class="hero-fade relative h-full flex items-center justify-center" data-parallax="-8">
    <div class="burger-glow absolute w-[78%] aspect-square rounded-full"></div>
    <?= ac_burger('w-[96%] max-w-[780px]') ?>
  </div>
</div>
<?php endif; ?>
<div id="heroTxt" class="relative max-w-6xl mx-auto px-5 pt-28 pb-16 w-full">
    <?php if ($heroBurger): ?><div class="lg:hidden hero-fade flex justify-center -mt-6 mb-2" aria-hidden="true"><?= ac_burger('w-[80%] max-w-sm') ?></div><?php endif; ?>
<p class="hero-fade inline-flex items-center gap-2 text-xs tracking-[.25em] uppercase border border-white/15 rounded-full px-4 py-2 bg-white/5 mb-8">
      <i class="w-2 h-2 rounded-full bg-[#FF0000] animate-pulse"></i><span id="tbh">Cuisine ivoirienne · Abidjan</span>
    </p>
    <h1 data-split class="font-display text-5xl sm:text-6xl md:text-7xl font-semibold tracking-[-.015em] leading-[1.03] max-w-3xl lg:max-w-[54%]">Choisissez à table. La cuisine s'en occupe.</h1>
    <p class="hero-fade mt-8 max-w-xl text-lg text-white/70">Scannez le QR de votre table, parcourez la carte du soir et suivez votre commande jusqu'à l'assiette. Un assistant virtuel est là si vous hésitez.</p>
    <div class="hero-fade mt-10 flex flex-wrap gap-4">
      <a href="menu.php" data-go class="px-7 py-4 rounded-lg bg-[#FF0000] font-semibold shadow-[0_12px_34px_rgba(255,0,0,.35)] hover:-translate-y-0.5 transition">Voir la carte</a>
      <a href="chat.php" data-go class="px-7 py-4 rounded-lg border border-white/20 bg-white/5 font-bold hover:bg-white/10 transition">Demander à notre assistant</a>
    </div>
    <p class="hero-fade mt-14 text-xs tracking-[.18em] uppercase text-white/40">Faites défiler ↓</p>
  </div>
</section>
