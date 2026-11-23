<?php /* Fenêtre « fiche plat » : remplie et animée par assets/accueil/modal.js */ ?>
<div id="dishModal" class="dm" hidden>
  <div class="dm-backdrop" data-dm-close></div>
  <div class="dm-scroll" data-dm-close>
    <article class="dm-panel" role="dialog" aria-modal="true" aria-labelledby="dmTitle" tabindex="-1">
      <button type="button" class="dm-x" data-dm-close aria-label="Fermer la fiche du plat">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>

      <div class="dm-media"><img id="dmImg" alt=""><span id="dmSig" class="dm-sig" hidden>Signature</span></div>

      <div class="dm-body">
        <p id="dmCat" class="dm-cat"></p>
        <div class="dm-head"><h3 id="dmTitle" class="font-display"></h3><p id="dmPrice" class="dm-price"></p></div>

        <dl class="dm-facts">
          <div><dt>Préparation</dt><dd id="dmTime"></dd></div>
          <div><dt>Catégorie</dt><dd id="dmCat2"></dd></div>
          <div><dt>Disponibilité</dt><dd>Ce soir</dd></div>
        </dl>

        <section class="dm-block"><h4>Composition</h4><ul id="dmIng" class="dm-list"></ul></section>
        <section id="dmTagsBox" class="dm-block"><h4>Caractère</h4><ul id="dmTags" class="dm-chips"></ul></section>
        <section class="dm-block"><h4>Allergènes</h4><ul id="dmAllergens" class="dm-chips"></ul></section>

        <div class="dm-actions">
          <button type="button" id="dmAdd" class="dm-btn dm-btn-red">Ajouter au panier</button>
          <a id="dmMenu" href="menu.php" data-go class="dm-btn dm-btn-ghost">Voir toute la carte</a>
        </div>
        <p id="dmNote" class="dm-note" hidden></p>
      </div>
    </article>
  </div>
</div>
