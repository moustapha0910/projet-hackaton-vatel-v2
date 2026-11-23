<?php require_once __DIR__ . '/../brand.php'; /* Transition de page, barre de progression et en-tête fixe */ ?>
<div id="cur" class="fixed inset-0 z-[100] bg-[#FF0000] pointer-events-none"></div>
<div id="bar" class="fixed top-0 left-0 right-0 h-[3px] bg-[#FF0000] origin-left scale-x-0 z-[90]"></div>

<header class="fixed top-0 inset-x-0 z-50 bg-navy/90 border-b border-white/10" style="padding-top:env(safe-area-inset-top,0px)">
  <div class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
    <a href="#hero" data-scroll class="font-display text-xl font-semibold leading-none"><?= brand_logo('<em class="text-[#FF0000] font-medium">', '</em>') ?></a>

    <nav class="hidden md:flex gap-8 text-sm text-white/70">
      <a href="#stage" data-scroll class="hover:text-white">La carte</a>
      <a href="#sommelier" data-scroll class="hover:text-white">Le sommelier</a>
      <a href="#signatures" data-scroll class="hover:text-white">Les plats</a>
    </nav>

    <div class="flex items-center gap-2 sm:gap-3">
      <span id="tbp" class="hidden sm:inline-flex items-center rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-xs text-sky">Table 07</span>
      <a href="panier.php" data-go id="cw" aria-label="Mon panier" class="relative grid h-10 w-10 place-items-center rounded-md border border-white/15 bg-white/5 hover:bg-white/10 transition">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"/></svg>
        <span id="cc" class="absolute -right-1.5 -top-1.5 hidden min-w-[18px] rounded-full bg-[#FF0000] px-1 text-center text-[11px] font-bold leading-[18px]">0</span>
      </a>
      <a href="menu.php" data-go id="cmd" class="px-4 py-2 rounded-md bg-[#FF0000] text-sm font-semibold shadow-[0_8px_24px_rgba(255,0,0,.35)]">Commander</a>
    </div>
  </div>
</header>
