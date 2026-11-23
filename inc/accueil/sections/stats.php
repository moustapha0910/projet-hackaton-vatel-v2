<?php /* Section 5 · Chiffres clés (compteurs animés) */ ?>
<section class="py-28">
  <div class="max-w-6xl mx-auto px-5 grid grid-cols-2 md:grid-cols-4 gap-5">
    <?php foreach ($stats as [$n, $label]): ?>
      <div class="stat border-t border-white/20 pt-5">
        <p class="text-5xl font-extrabold"><span class="num" data-n="<?= (int)$n ?>">0</span></p>
        <p class="mt-2 text-white/55 text-sm"><?= ac_h($label) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>
