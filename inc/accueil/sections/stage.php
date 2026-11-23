<?php /* Section 2 · Carrousel 3D (épinglé pendant le défilement). Un clic sur un plat ouvre sa fiche. */ ?>
<section id="stage" class="relative h-screen overflow-hidden flex flex-col items-center justify-center">
  <p class="absolute top-24 text-xs tracking-[.18em] uppercase text-white/50">Quelques plats de ce soir</p>

  <div class="persp w-full h-[400px] md:h-[540px] relative">
    <div id="ring" class="ring absolute inset-0">
      <?php foreach ($ringDishes as $i => $d): ?>
        <div class="cr" style="--i:<?= $i ?>" data-dish="<?= (int)$d['id'] ?>" role="button" tabindex="0" aria-label="Voir <?= ac_h($d['n']) ?>">
          <img src="<?= ac_h($d['img']) ?>" alt="<?= ac_h($d['n']) ?>" decoding="async"><span><?= ac_h($d['n']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="absolute bottom-[9vh] inset-x-0 text-center px-6 h-24">
    <?php foreach ($steps as [$num, $title, $text]): ?>
      <div class="cap absolute inset-x-6">
        <p class="text-white/40 text-sm tracking-widest"><?= $num ?></p>
        <h3 class="font-display text-2xl md:text-3xl font-semibold"><?= ac_h($title) ?></h3>
        <p class="text-white/60"><?= ac_h($text) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>
