<?php require_once __DIR__ . '/../brand.php'; ?>
<footer class="border-t border-white/10 py-8 text-center text-sm text-white/40"><?= brand_h('name') ?> · <?= brand_h('tagline') ?> · <?= brand_h('city') ?><?= brand_credit() ?></footer>

<?php require __DIR__ . '/dish-modal.php'; ?>

<!-- Fiches des plats affichés sur la page (lues par modal.js) -->
<script type="application/json" id="dishData"><?= $dishJson ?></script>

<!-- Scripts : l'ordre compte (chaque fichier s'appuie sur le précédent) -->
<script>window.VIDEO_SRC = 'media/banner.mp4';</script>
<script src="assets/accueil/core.js"></script>
<script src="assets/accueil/session.js"></script>
<script src="assets/accueil/modal.js"></script>
<script src="assets/accueil/motion.js"></script>
<script src="assets/accueil/boot.js"></script>
</body>
</html>
