<?php /* Section 3 · Présentation du sommelier (maquette de conversation) */ ?>
<section id="sommelier" class="relative py-32 overflow-hidden">
  <div class="max-w-6xl mx-auto px-5 grid md:grid-cols-2 gap-14 items-center">
    <div>
      <p class="text-xs tracking-[.18em] uppercase text-[#FF0000] mb-4">Le sommelier</p>
      <h2 class="tilt font-display text-4xl md:text-5xl font-semibold tracking-[-.01em] leading-[1.08]">Vous hésitez ? Demandez.</h2>
      <p class="mt-6 text-white/65 text-lg">Donnez un budget, une envie, le nombre de convives. Il ne propose que ce qui est disponible ce soir, écarte vos allergènes et remplit le panier. Rien n'est envoyé en cuisine sans votre validation.</p>
      <a href="chat.php" data-go class="inline-block mt-8 px-7 py-4 rounded-lg bg-[#FF0000] font-semibold shadow-[0_12px_34px_rgba(255,0,0,.3)]">Ouvrir le chat</a>
    </div>

    <div id="mock" class="tilt rounded-[28px] border border-white/10 bg-card p-5 space-y-3">
      <div class="msg ml-auto max-w-[80%] bg-[#FF0000] rounded-lg rounded-tr-sm px-4 py-3 text-sm">J'ai 6 000 FCFA, quelque chose de léger.</div>
      <div class="msg max-w-[85%] bg-navy/60 border border-white/10 rounded-lg rounded-tl-sm px-4 py-3 text-sm">Avec 6 000 FCFA, je vous propose un alloco bien doré et un dessert frais. Rien de lourd.</div>
      <?php foreach ([[8, 'Alloco &amp; œuf'], [4, 'Ananas rôti à la vanille']] as [$id, $label]): ?>
        <div class="msg flex items-center gap-3 bg-navy/60 border border-white/10 rounded-lg p-2 max-w-[330px]">
          <img src="img/<?= $id ?>.jpg" alt="" decoding="async" class="w-20 h-9 rounded-md object-cover">
          <p class="text-sm flex-1"><b><?= $label ?></b><br><span class="text-white/50">3 000 FCFA</span></p>
        </div>
      <?php endforeach; ?>
      <div class="msg flex items-center justify-between gap-3 bg-navy/60 border border-white/10 rounded-md px-4 py-3 text-sm max-w-[330px]">
        <span>Total <b>6 000 FCFA</b></span><span class="px-3 py-2 rounded-md bg-[#FF0000] font-bold text-xs">Tout commander</span>
      </div>
    </div>
  </div>
</section>
