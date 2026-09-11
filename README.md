# LoginFennec Pro – Personnalisation page login et Security

Plugin WordPress : personnalisation complète de la page de connexion avec aperçu en direct, protection anti force brute et achat intégré (CCP/BaridiMob, PayPal, carte).

> Version 2.3.1 · Auteur : Derouiche Oussama · Licence GPL v2 ou ultérieure · Mascotte : fennec 🦊

## Fonctionnalités

| Catégorie | Détails |
|---|---|
| **Logo** | Image **ou texte**, largeur/hauteur, lien cliquable, masquage complet |
| **Arrière-plan** | Couleur, dégradé (angle), image (cover/contain/motif) avec position, flou, luminosité, saturation, voile coloré |
| **Styles & thèmes** | **10 styles** en 1 clic (Effet verre → Royal) + **7 thèmes d'interface** du formulaire |
| **Formulaire** | Opacité, flou, arrondis, largeur, hauteur des champs, placeholders et libellés personnalisés |
| **Redirection** | Page de destination après connexion |
| **Liens** | Texte/cible de « Retour au site », masquage des liens (mot de passe perdu, enregistrement) |
| **Réseaux sociaux** | Facebook, X, Instagram, LinkedIn, YouTube, e-mail — **couleurs officielles des marques** |
| **Copyright + extras** | Mention `{year}`/`{sitename}`, message d'accueil, 4 polices, animations d'entrée, **CSS + JS personnalisés** |
| **Sécurité** | Blocage des tentatives **avec escalade ×2 par récidive**, honeypot, anti-énumération, XML-RPC, mots de passe d'application, liste blanche IP, erreurs génériques |
| **Journal & stats** | 50 derniers événements (export CSV), score de sécurité /5, statistiques dashboard |
| **Licence & achat** | Achat intégré : **BaridiMob/CCP, PayPal, carte** — détails et statut de licence en direct |
| **Dashboard** | Onglets modernes, aperçu en direct plein écran, installateur guidé en 4 étapes |
| **Mises à jour** | Updater GitHub intégré (bascule auto vers WordPress.org) + GitHub Action de release |

## Installation

1. Copiez le dossier `loginfennec/` dans `wp-content/plugins/`.
2. Activez l'extension dans **Extensions**.
3. Ouvrez le menu **LoginFennec Pro** (icône pinceau) et personnalisez — l'aperçu réagit en direct.
4. Cliquez sur **Enregistrer**.

## Structure du code

```
loginfennec/
├── loginfennec.php      # Bootstrap, constantes, hooks
├── includes/
│   ├── settings.php             # Défauts, accesseurs, assainissement
│   ├── login-appearance.php     # CSS dynamique, logo, liens, social, copyright
│   ├── login-security.php       # Limitation des tentatives de connexion
│   ├── github-updater.php       # Mises à jour via GitHub Releases
│   └── admin.php                # Dashboard, onglets, aperçu, page À propos
├── assets/
│   ├── css/admin.css            # Design du dashboard
│   └── js/admin.js              # Aperçu en direct, presets, onglets
├── .github/workflows/release.yml # Zip automatique à chaque tag v*
├── languages/                   # Traductions (pot inclus)
├── readme.txt                   # Fichier WordPress.org
└── uninstall.php                # Nettoyage complet (multisite inclus)
```

## Avant de publier sur GitHub — 3 endroits à adapter

Le dépôt est préconfiguré sur `derouicheoussama/loginfennec`. Si vous publiez sous un autre compte, remplacez-le dans :

1. `loginfennec.php` — en-têtes `Plugin URI`, `Author URI`, `Update URI` et la constante `LOGINFENNEC_GITHUB_REPO`.
2. `includes/github-updater.php` — profil GitHub dans `plugin_info()`.
3. `includes/admin.php` — liens de la page « À propos ».

Le lien du bouton de don est personnalisable sans toucher au code :

```php
add_filter( 'loginfennec_donate_url', fn() => 'https://www.paypal.com/donate?business=votre-email' );
```

## Publier sur GitHub (active les mises à jour)

```bash
cd loginfennec
git init && git add -A && git commit -m "LoginFennec Pro 1.0.0"

# Avec GitHub CLI :
gh repo create loginfennec --public --source=. --push

# Sans GitHub CLI : créez le dépôt vide sur github.com puis :
git remote add origin https://github.com/VOTRE-COMPTE/loginfennec.git
git branch -M main && git push -u origin main

# Publier une version (le zip est construit automatiquement par l'action) :
git tag v1.0.0 && git push origin v1.0.0
```

Le zip `loginfennec.zip` est alors attaché à la release : c'est lui que le module de mise à jour télécharge sur tous vos sites.

## Publier sur WordPress.org — checklist complète

Le plugin est préparé conformément aux [règles du répertoire officiel](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) :

- [x] `readme.txt` au format officiel (donate link, tags, Tested up to, FAQ, changelog, licence)
- [x] Text domain `loginfennec` + fichier `.pot` à jour dans `languages/`
- [x] Sécurité : nonces, capabilities, échappement des sorties, assainissement des entrées
- [x] Données supprimées à la désinstallation (options + transients, multisite inclus)
- [x] Pas d'en-tête `Update URI` : l'updater utilise GitHub automatiquement **puis bascule seul vers WordPress.org** dès que le plugin y est détecté (vérification toutes les 12 h) — aucune action requise après l'acceptation
- [x] Bannières et icônes générées dans `wporg-assets/` (banner-772x250, banner-1544x500, icon-128x128, icon-256x256)

**Avant de soumettre, à votre charge :**

1. Créez votre compte [WordPress.org](https://login.wordpress.org/register) et notez votre identifiant : remplacez `Contributors: derouicheoussama` dans `readme.txt` par votre pseudo exact.
2. Mettez votre vraie URL PayPal dans `Donate link:` (readme.txt) et dans le filtre `loginfennec_donate_url`.
3. Capturez 4 à 6 **captures d'écran** réelles du dashboard et de la page de connexion personnalisée : PNG 1200×900 nommés `screenshot-1.png` … `screenshot-6.png`, à déposer dans le dossier `assets/` du dépôt SVN (pas dans le plugin).
4. (Conseillé) Installez l'outil officiel [Plugin Check](https://wordpress.org/plugins/plugin-check/) sur un site de test et lancez-le contre le plugin : il détecte les derniers détails exigés par l'équipe de revue.

**Soumission :**

1. Uploadez le zip sur [wordpress.org/plugins/developers/add](https://wordpress.org/plugins/developers/add/).
2. Après la revue (quelques jours), vous recevez un dépôt SVN : `https://plugins.svn.wordpress.org/loginfennec/`.
3. Publiez le code + les assets :

```bash
svn co https://plugins.svn.wordpress.org/loginfennec/ loginfennec-svn
cd loginfennec-svn
# trunk = code du plugin (sans .git, tools, wporg-assets)
# assets = wporg-assets/* + vos captures screenshot-N.png
svn add . --force && svn ci -m "LoginFennec Pro 1.2.0"
# tag de la version :
svn cp trunk tags/1.2.0 && svn ci -m "Tag 1.2.0"
```

Dès que la version existe sur WordPress.org, l'updater intégré s'efface automatiquement : le bouton « Installer » de `wp-admin/plugin-install.php` et les mises à jour viennent du référentiel officiel, sans conflit avec GitHub.

## Notes techniques

- Text domain : `loginfennec` (traductions dans `languages/`).
- Les données stockées : une option de réglages, le journal des tentatives (purge auto à 24 h), un transient de cache GitHub (6 h). Tout est supprimé à la désinstallation.
- La sécurité utilise `REMOTE_ADDR` ; derrière un reverse proxy, configurez votre serveur pour exposer la vraie IP.
- Aucune dépendance externe, aucune requête de télémétrie.

## Développement dans VS Code

Le projet est du PHP/JS/CSS standard : ouvrez le dossier dans **VS Code** (Fichier → Ouvrir le dossier) et tout fonctionne.

1. Installez les extensions recommandées (VS Code les proposera automatiquement via `.vscode/extensions.json`) :
   - **Intelephense** — autocomplétion et analyse PHP ;
   - **PHP Sniffer** (+ PHP SAB) — applique les standards WordPress via `phpcs.xml.dist` ;
   - **EditorConfig** — respecte `.editorconfig` (tabs, fins de ligne).
2. Un **PHP portable 8.3** est inclus dans `_php/php/php.exe` (référencé dans `.vscode/settings.json`) — rien à installer.
3. Commandes utiles :
   - `node tools/generate-assets.mjs` / `node tools/generate-logo.mjs` / `node tools/generate-brand-pro.mjs` — régénère les visuels ;
   - `_php/php/php.exe -l <fichier>` — vérifie la syntaxe ;
   - `composer install && composer lint:standards` — PHPCS complet (si Composer installé).

## Outils de développement (VS Code)
