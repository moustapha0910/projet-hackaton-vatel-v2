# IT Resto

Application web de **commande à table pour restaurants** : le client scanne le QR code de sa table, parcourt la carte, discute avec un sommelier IA, commande, et suit sa commande jusqu'à l'assiette (alerte quand c'est prêt). La cuisine voit les commandes en direct ; l'administrateur gère tables, QR codes, commandes et avis.

Technologies : HTML / CSS / JavaScript, PHP, fichiers JSON (aucune base de données à installer).

## Fonctionnalités
- **Client** : QR par table → nom et téléphone → carte avec filtres d'allergènes → panier → suivi en direct, annulation tant que la cuisine n'a pas commencé, règlement, avis.
- **Sommelier IA** (Gemini, Groq ou Anthropic) avec repli automatique sur un moteur à mots-clés ; le serveur retire toujours les plats contenant un allergène choisi.
- **Alerte « commande prête »** : bandeau, son et vibration dans l'appli, et **notification push** même téléphone verrouillé (Android, et iPhone avec l'appli ajoutée à l'écran d'accueil).
- **Cuisine** (`cuisine.php`) : nouvelles commandes, préparation, carte (plats épuisés, prix), ventes du jour.
- **Administration** (`admin.php`) : tableau de bord, tables et QR (impression), commandes, avis, export CSV, **identité du restaurant**.

## Installation (XAMPP)
1. Copiez le dossier du projet dans `C:\xampp\htdocs\`.
2. Démarrez **Apache** (MySQL n'est pas nécessaire).
3. Ouvrez `http://localhost/<nom-du-dossier>/`.

Les données sont créées automatiquement dans `data/` (`state.json` : carte, commandes, avis). Pour repartir de zéro, supprimez ce fichier.

## Premiers réglages (`config.php`)
- `ADMIN_PASSWORD`, `KITCHEN_PASSWORD` (ou `KITCHEN_PIN`) : **changez les mots de passe par défaut** (`admin123`, `cuisine123`).
- `PUBLIC_URL` : adresse publique du site (utile avec un tunnel) ; elle sert à générer les QR codes.
- `VERIFY_SSL` : mettez `false` si votre XAMPP n'a pas de certificats et que l'IA ou les notifications échouent.
- Clés IA : `GEMINI_KEY`, `GROQ_KEY` ou `ANTHROPIC_KEY` (facultatif). Testez avec `api.php?a=aitest`.

## Changer le nom (revendre à un restaurant)
Connectez-vous à `admin.php` → **Réglages → Identité du restaurant**. Le nom, le slogan et la ville se mettent à jour partout : site, logo, titres des pages, QR codes, appli installée sur les téléphones, sommelier IA, écran cuisine, export CSV.
- **Nom du logiciel** : s'il diffère du nom du restaurant, un discret « Propulsé par … » apparaît en bas du site ; laissez-le vide pour une marque blanche.
- **Icônes de l'appli** : remplacez `img/icon-192.png` et `img/icon-512.png`.
- **Carte, prix et photos** : Cuisine → Carte ; les photos sont dans `img/` (mêmes noms de fichiers) ; vidéo de la bannière : `media/banner.mp4`.
- Valeurs par défaut : `inc/brand.php` (réglages enregistrés dans `data/brand.json`).

## Démo sur téléphones
- **Même Wi-Fi** : ouvrez `http://<IP-du-PC>/<dossier>/` sur le téléphone ; la cuisine sur le PC. Imprimez les QR (`qr.php` ou admin → Tables & QR) depuis l'adresse IP, pas `localhost`.
- **Tunnel (`cloudflared`)** : donne une adresse https, nécessaire pour les notifications push. L'adresse change à chaque lancement : mettez-la dans `PUBLIC_URL` puis réimprimez les QR.

## Notifications push
Les clés se créent automatiquement. Diagnostic (connecté en admin) : `api.php?a=pushstatus`. Renseignez `const VAPID_SUBJECT = 'mailto:vous@exemple.com';` dans `config.php` (Apple contrôle cette adresse).

## Sécurité (résumé)
Le serveur décide de tout : prix recalculés côté serveur, jeton secret par commande, actions de cuisine réservées à la session cuisine, limites anti-abus, textes saisis échappés. `data/` est protégé par `.htaccess` (Apache) et exclu de Git : n'y publiez jamais de données clients.

## Structure
`api.php` (API) · `admin.php` · `cuisine.php` · `menu.php`, `panier.php`, `commande.php` · `bienvenue.php` (arrivée par QR) · `acces.php` (connexion) · `inc/` (code partagé : `store.php`, `auth.php`, `brand.php`, `push.php`…) · `assets/` · `sw.js` + `manifest.php` (appli installable et notifications).
