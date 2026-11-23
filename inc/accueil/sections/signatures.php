<?php /* Section 4 · Défilement horizontal des plats de la maison. Un clic sur un plat ouvre sa fiche. */ ?>
<section id="hs" class="relative h-screen overflow-hidden flex flex-col justify-center">
  <div class="px-5 max-w-6xl mx-auto w-full mb-8">
    <p id="signatures" class="text-xs tracking-[.18em] uppercase text-[#FF0000] mb-3">À la carte</p>
    <h2 class="font-display text-4xl md:text-5xl font-semibold tracking-[-.01em]">Les plats de la maison.</h2>
    <p class="mt-3 text-sm text-white/45">Touchez un plat pour voir sa composition.</p>
  </div>

  <div id="track" class="flex gap-6 pl-5 md:pl-[max(1.25rem,calc((100vw-72rem)/2))] w-max">
    <?php foreach ($trackDishes as $i => $d): ?>
      <article class="dish-card shrink-0 w-[78vw] md:w-[34vw] rounded-[24px] overflow-hidden border border-white/10 bg-card" data-dish="<?= (int)$d['id'] ?>" role="button" tabindex="0" aria-label="Voir <?= ac_h($d['n']) ?>">
        <div class="aspect-[538/238] bg-navy overflow-hidden"><img src="<?= ac_h($d['img']) ?>" alt="<?= ac_h($d['n']) ?>" decoding="async" class="block w-full h-full object-cover"></div>
        <div class="p-5 md:p-6">
          <p class="text-xs tracking-[.25em] text-white/50">N°<?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></p>
          <h3 class="font-display text-2xl font-semibold"><?= ac_h($d['n']) ?></h3>
          <p class="text-sky"><?= ac_fcfa($d['p']) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
