# Infinity Customizer – Login Customizer & Security

Plugin WordPress : personnalisation complète de la page de connexion avec aperçu en direct et protection contre les tentatives de mot de passe.

> Version 1.0.0 · Auteur : Derouiche Oussama · Licence GPL v2 ou ultérieure

## Fonctionnalités

| Catégorie | Détails |
|---|---|
| **Logo** | Image personnalisée, largeur/hauteur, lien cliquable, masquage complet |
| **Arrière-plan** | Couleur unie, dégradé (angle 0-360°), image (cover/contain/motif) |
| **Flou & opacité** | Flou de l'image de fond, voile coloré réglable, effet verre du formulaire (backdrop-filter) |
| **Styles modernes** | 6 presets en 1 clic : Effet verre, Minimal, Sombre, Coucher de soleil, Océan, Forêt |
| **Formulaire** | Opacité, flou, arrondis, largeur, padding, ombre, couleurs (textes, champs, bouton, liens) |
| **Liens** | Texte/cible de « Retour au site », masquage de « Mot de passe perdu ? », « S'enregistrer », « Retour au site » |
| **Réseaux sociaux** | Facebook, X, Instagram, LinkedIn, YouTube, e-mail — cercle/arrondi/carré, taille et couleurs |
| **Copyright** | Ligne personnalisée avec variables `{year}` et `{sitename}` |
| **Sécurité** | Blocage des tentatives (IP + identifiant), durée configurable, messages personnalisables, erreurs génériques, sélecteur de langue, en-têtes HTTP |
| **Dashboard** | Onglets modernes, aperçu en direct (bureau/tablette/mobile), presets, médiathèque |
| **À propos** | Page dédiée : développeur, fonctionnalités, bouton **Faire un don**, état du système |
| **Mises à jour** | Updater GitHub intégré + GitHub Action de release |

## Installation

1. Copiez le dossier `infinity-customizer/` dans `wp-content/plugins/`.
2. Activez l'extension dans **Extensions**.
3. Ouvrez le menu **Infinity Customizer** (icône pinceau) et personnalisez — l'aperçu réagit en direct.
4. Cliquez sur **Enregistrer**.

## Structure du code

```
infinity-customizer/
├── infinity-customizer.php      # Bootstrap, constantes, hooks
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

Le dépôt est préconfiguré sur `derouiche-oussama/infinity-customizer`. Si vous publiez sous un autre compte, remplacez-le dans :

1. `infinity-customizer.php` — en-têtes `Plugin URI`, `Author URI`, `Update URI` et la constante `INFINITY_CUSTOMIZER_GITHUB_REPO`.
2. `includes/github-updater.php` — profil GitHub dans `plugin_info()`.
3. `includes/admin.php` — liens de la page « À propos ».

Le lien du bouton de don est personnalisable sans toucher au code :

```php
add_filter( 'infinity_customizer_donate_url', fn() => 'https://www.paypal.com/donate?business=votre-email' );
```

## Publier sur GitHub (active les mises à jour)

```bash
cd infinity-customizer
git init && git add -A && git commit -m "Infinity Customizer 1.0.0"

# Avec GitHub CLI :
gh repo create infinity-customizer --public --source=. --push

# Sans GitHub CLI : créez le dépôt vide sur github.com puis :
git remote add origin https://github.com/VOTRE-COMPTE/infinity-customizer.git
git branch -M main && git push -u origin main

# Publier une version (le zip est construit automatiquement par l'action) :
git tag v1.0.0 && git push origin v1.0.0
```

Le zip `infinity-customizer.zip` est alors attaché à la release : c'est lui que le module de mise à jour télécharge sur tous vos sites.

## Publier sur WordPress.org

1. Créez un compte sur [wordpress.org](https://login.wordpress.org/register) puis [soumettez le plugin](https://wordpress.org/plugins/developers/add/) (uploadez le zip).
2. Après validation, vous recevez un dépôt SVN : `https://plugins.svn.wordpress.org/infinity-customizer/`.
3. Publiez :

```bash
svn co https://plugins.svn.wordpress.org/infinity-customizer/ infinity-customizer-svn
# copiez trunk/ (code), assets/ (bannières + captures d'écran 1280x900),
# tags/1.0.0/ (copie du code de la version)
svn add . --force && svn ci -m "Infinity Customizer 1.0.0"
```

4. Une fois publié sur WordPress.org, basculez les mises à jour vers le référentiel officiel :

```php
add_filter( 'infinity_customizer_update_source', fn() => 'wordpress' );
```

(ou supprimez l'en-tête `Update URI` du fichier principal).

## Notes techniques

- Text domain : `infinity-customizer` (traductions dans `languages/`).
- Les données stockées : une option de réglages, le journal des tentatives (purge auto à 24 h), un transient de cache GitHub (6 h). Tout est supprimé à la désinstallation.
- La sécurité utilise `REMOTE_ADDR` ; derrière un reverse proxy, configurez votre serveur pour exposer la vraie IP.
- Aucune dépendance externe, aucune requête de télémétrie.
