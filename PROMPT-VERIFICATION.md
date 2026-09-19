# PROMPT — Vérification complète de LoginFennec Pro

> Copiez tout le bloc ci-dessous dans une session ZCode (dossier `loginfennec/`)
> pour faire vérifier que **chaque section, chaque option et chaque fonctionnalité
> fonctionne**, avant toute mise en production.

---

Tu es un ingénieur QA spécialisé WordPress. Vérifie intégralement le plugin
**LoginFennec Pro** (dossier courant). Le plugin est un customizer de page de
connexion + sécurité : 12 onglets d'administration, connexion par SMS (OTP),
restriction GEO, SEO, couleurs de l'admin, licence Pro, essai 7 jours.
Version attendue : lue dans `loginfennec.php` (`LOGINFENNEC_VERSION`).
PHP 8.3 portable : `C:/Users/Derouiche Oussama/Plugins COD Algérien/.tools/php/php.exe`.

Exécute les phases dans l'ordre. N'ignore aucun échec : un seul KO = rapport ÉCHEC.

## Phase 0 — Constance et syntaxe
1. `php -l` sur `loginfennec.php`, tous les fichiers de `includes/`, `uninstall.php`.
2. `node --check` sur `assets/js/admin.js` et `assets/js/sms-login.js`.
3. Version identique dans : en-tête du fichier principal, constante
   `LOGINFENNEC_VERSION`, `Stable tag:` de `readme.txt`.

## Phase 1 — Harnais de fumée (4 modes obligatoires)
Lancer `php _smoke.cjs.php` avec les modes : (rien), `enabled`, `sms`, `geo`.
Chaque mode doit finir sur « TOUS LES TESTS PASSENT ». Ce harnais couvre :
chargement réel des hooks (`plugins_loaded`/`init`/`wp_loaded`/`admin_init`
avec invocation effective des callbacks — toute classe ou fonction manquante
y apparaît en « ERREUR HOOK »), rendu des panneaux (SMS, Admin, Sécurité,
Extras, Styles, Formulaire, page Pro, page À propos), sanitization complète,
couleurs admin (aperçu paramétrable), SMS (normalisation téléphone, cycle OTP
complet, verrouillage après 3 échecs, rate limit, envoi webhook, connexion,
journal, redirections validées), GEO (normalisation de liste, blocage,
fail-open, liste blanche), SEO (noindex, titre), secrets (chiffrement
AES-256-GCM roundtrip, falsifié → vide, hérité en clair, champ vide conservé,
chiffrement à l'écriture), sauvegarde partielle (champ fourni, absent
conservé, sentinelles 0/1).

## Phase 2 — Inventaire exhaustif des réglages
Extraire la liste complète des clés de `lnf_get_defaults()` +
`lnf_field_spec()` dans `includes/settings.php`. Pour CHAQUE clé, vérifier :
1. elle est rendue dans l'interface (`includes/admin.php`, helper
   `field_*` appelé avec cette clé) ;
2. elle figure dans la spec de sanitization adaptée à son type
   (`key`/`bool`/`url`/`text`/`color`/`int` avec bornes) ;
3. elle est consommée quelque part (`includes/login-appearance.php`,
   `sms-login.php`, `geo-login.php`, `admin-colors.php`, `recaptcha.php`,
   `login-security.php`) — une clé jamais lue = option morte = signaler.
Rapporter la matrice clé → UI → spec → consommation.

## Phase 3 — Protocole fonctionnel par onglet (WordPress réel)
Sur une installation WordPress de test (installer `build/loginfennec-<version>.zip`),
vérifier onglet par onglet. Pour chaque option : changer la valeur →
« Enregistrer » → recharger → **la valeur persiste** → effet visible.

- **Tableau de bord** : score de sécurité, statistiques, journal (badge SMS
  affiché), bannières d'essai, alerte `max_input_vars` si limite < 200,
  notice mode sans échec si actif.
- **Styles** : chacun des 10 presets change l'aperçu en direct ; `preset`
  persiste.
- **Logo** : image (médiathèque), texte, dimensions, masquage ; rendu réel.
- **Arrière-plan** : couleur, dégradé (angle), image (position, flou,
  luminosité, saturation, voile + opacité) ; rendu réel.
- **Formulaire** : 7 thèmes ; largeur/padding/radius/opacité/flou ; couleurs
  (fond, texte, labels, inputs, focus, bouton, hover, liens) ; **CONTRAST
  OBLIGATOIRE** : pour des couleurs de bouton claires (ex. `#f9f0d8`) le
  texte du bouton doit devenir sombre, pour des couleurs sombres (ex.
  `#00305e`) il doit rester blanc — tester au moins 4 fonds extrêmes.
- **Liens / Réseaux sociaux / Copyright** : masquages, textes, URLs ;
  icônes cercle/arrondi/carré, mode marques officielles, couleurs.
- **Extras** : bienvenue, typographie (4 familles + Google Fonts avec
  `preconnect`), animation, placeholders/labels, redirection, CSS/JS
  personnalisés, **auto_update** (visibilité dans Extensions → Mises à jour
  automatiques), **safe_mode** (active → page de connexion native, sécurité
  OFF, notice dashboard visible ; désactive → tout revient), SEO (noindex
  présent dans le `<head>` de wp-login, titre d'onglet personnalisé).
- **Sécurité** : 5 échecs → blocage IP avec durée ×2 à chaque récidive,
  message personnalisé, honeypot (POST avec `lnf_hp` rempli → refusé),
  `?author=N` redirigé, REST users fermé aux visiteurs, XML-RPC, mots de
  passe d'application, alertes e-mail, journal 50 événements, export CSV
  (ouvrir : en-têtes corrects, champs échappés), liste blanche IP.
- **SMS** : activer + webhook de test (https://webhook.site) ; champ
  téléphone dans le profil ; panneau « Se connecter par SMS » sur
  wp-login : numéro → code reçu → connexion ; mauvais code ×3 → verrouillé ;
  renvoi bloqué 60 s ; 10e code/heure → refusé ; numéro inconnu → même
  message qu'un numéro valide (anti-énumération) ; secrets (token/pass)
  masqués dans le HTML (jamais dans la source de la page) ; champ secret
  vidé + enregistré → clé conservée.
- **GEO** : activer avec `DZ` ; `add_filter('lnf_geo_country', fn() => 'US')`
  → connexion refusée + journal `GEO:US` ; `DZ` → autorisée ; IP en liste
  blanche → jamais bloquée ; service injoignable → autorisée (fail-open).
- **Admin** : interrupteur + 4 palettes + sélecteurs → **aperçu instantané
  du vrai menu** ; Enregistrer → recharger → **les couleurs tiennent**
  (vérifier que `<style id="lnf-admin-colors">` est bien APRÈS `colors.css`
  dans le `<head>`) ; barre d'admin côté front.
- **Pro** : comparatif Gratuit vs Pro complet (5 groupes), packs/prix,
  assistant d'achat (CCP/PayPal/carte), activation de licence (mock API).

## Phase 4 — Anti-fuite et sécurité transverse
1. Export JSON : vérifier l'ABSENCE des clés `lnf_secret_keys()`.
2. Import d'un export : les secrets du site restent en place.
3. Base de données : les 4 secrets sont stockés avec le préfixe `lnfenc1:`
   (chiffrés), jamais en clair.
4. Aucun fichier dev dans le zip (`build-local` → vérifier entrées :
   pas de `_*`, `tools/`, `brand/`, `README.md`, `PRODUCTION.md`,
   `deploy.sh`, dotfiles ; entrées toutes en slash, dossier racine
   `loginfennec/`, `loginfennec/loginfennec.php` présent).
5. `index.php` « Silence is golden » présents dans includes/, assets/,
   assets/css, assets/js, assets/img, languages/.
6. Toutes les sorties AJAX ont nonce + capacité ; tous les `$_POST/$_GET`
   sont unslashed + sanitizés ; toutes les échos d'admin sont échappés.

## Phase 5 — Conformité wp.org
1. Plugin Check (PCP) : 0 erreur. Signaler tout WARNING nouveau.
2. `readme.txt` : description courte ≤ 150 caractères, 5 tags, sections
   Description/Installation/FAQ/Screenshots/Changelog/Privacy complètes.
3. En-tête : Text Domain `loginfennec`, Requires PHP 7.2, pas d'`Update URI`.
4. Toutes les chaînes traduisibles utilisent le text domain `loginfennec`.

## Rapport attendu
Un tableau final : **Domaine / Tests exécutés / Résultat (OK·KO) / Détail
des échecs avec fichier:ligne**. Verdict global : PRÊT POUR PRODUCTION
seulement si les 5 phases sont vertes. Corriger directement tout ce qui
est corrigeable (avec lint + harnais relancés après chaque correction) et
lister ce qui nécessite une décision.
