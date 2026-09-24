# 🦊 LoginFennec Pro — Personnalisation page login et Security

Plugin WordPress : **personnalisez et sécurisez votre page de connexion** depuis un dashboard moderne avec aperçu en direct — et connectez vos utilisateurs par SMS.

![Banner](wporg-assets/banner-1544x500.png)

> **v4.1.0** · PHP 7.2+ · WordPress 5.2+ · Licence GPL v2 ou ultérieure
> Auteur : Derouiche Oussama · [derouicheoussama.com](https://www.derouicheoussama.com) · Signature « ∞ Infinity Coder » dans chaque fichier

---

## ✨ Fonctionnalités

### 🎨 Personnalisation (10 styles, 7 thèmes)
- Logo image ou texte, arrière-plan (couleur, dégradé, image avec flou/luminosité/saturation/voile), 10 styles en 1 clic + 7 designs de formulaire indépendants
- **Layout deux colonnes** avec image latérale
- **Contraste automatique** : jamais d'écriture sombre sur un bouton sombre, pour toutes les couleurs et tous les styles
- Icônes sociales aux couleurs officielles des marques, message de bienvenue, copyright `{year}`/`{sitename}`, animations (respect prefers-reduced-motion), CSS + JS personnalisés
- **20+ Google Fonts** ou 4 familles génériques, taille du texte, hauteur des champs

### 📱 Connexion par SMS (OTP)
- Numéro de téléphone + code à usage unique — **Twilio, Vonage ou webhook HTTP générique** (passerelles locales)
- Codes hachés salés à usage unique, **compteur d'essais atomique** (anti brute-force parallèle), délai anti-DoS posé avant l'appel passerelle, plafond horaire par IP, anti-énumération

### 🔐 Double authentification (2FA)
- **TOTP RFC 6238** compatible Authy, Google Authenticator, Duo Mobile, Microsoft Authenticator (code 30 s)
- Association par **QR code rendu localement** (aucun service tiers), 8 codes de secours à usage unique, secret **chiffré au repos** (AES-256-GCM)
- Connexion en deux temps : mot de passe → code → tableau de bord

### 🛡️ Sécurité
- Blocage force brute avec **escalade ×2 par récidive**, honeypot, anti-énumération, blocage du balayage d'auteurs, XML-RPC et mots de passe d'application désactivables
- **Restriction GEO par pays** (geojs.io HTTPS sans clé, cache 24 h, fail-open, liste blanche IP prioritaire)
- **reCAPTCHA v3 non contournable**, alertes e-mail, journal 50 événements + export CSV, score de sécurité, en-têtes de sécurité

### 🔎 SEO & Admin
- `noindex, nofollow` sur la page de connexion (recommandé, actif par défaut) + titre d'onglet personnalisable
- **Couleurs de l'admin WordPress** : menu latéral, survol, élément actif, barre d'admin, couleur d'accent — avec **aperçu en temps réel** et 4 palettes prêtes à l'emploi
- Mode white-label, mode sans échec de dépannage

### ⚡ Qualité
- Secrets (clés SMS, reCAPTCHA) **chiffrés au repos** (AES-256-GCM), jamais affichés, jamais exportés
- Aucune donnée envoyée à des tiers par défaut (voir `readme.txt` → Privacy)
- Translation-ready, multisite-compatible

## 📦 Installation

**Depuis le zip :** Extensions → Ajouter → Téléverser `loginfennec.zip` → Activer. L'assistant d'onboarding s'ouvre.

**Pour vos clients (mises à jour instantanées dans le dashboard)** — dans `wp-config.php` :
```php
define( 'LOGINFENNEC_UPDATE_SERVER', 'https://github.com/derouicheoussama/loginfennec/releases/latest/download/update.json' );
```
Chaque tag `v*` publié déclenche la release (zip + `update.json` + checksum SHA-256) ; les clients voient la mise à jour dans **Extensions → Mise à jour disponible**, vérifiée par **SHA-256 avant installation**.

## 🔄 Canaux de mises à jour (double)

| Canal | Public | Sécurité | Statut |
|---|---|---|---|
| **WordPress.org** | Tous | Signature native wp.org | Dès la publication (prioritaire : le canal GitHub **s'efface automatiquement**) |
| **GitHub Releases** | Clients directs | HTTPS + **SHA-256 vérifié avant installation** + clé de licence optionnelle | Instantané à chaque tag `v*` |

Après publication wp.org, le dépôt GitHub peut passer **privé** sans aucune rupture : le canal GitHub devient silencieux (échec = pas d'offre) et wp.org prend le relais. Détails : [`docs/UPDATE-SERVER.md`](docs/UPDATE-SERVER.md).

## 🏗️ Architecture

```
loginfennec/
├── loginfennec.php              # Bootstrap, constantes, migration legacy one-shot
├── includes/
│   ├── settings.php             # Défauts, spec de sanitization, secrets chiffrés AES-256-GCM
│   ├── admin.php                # Dashboard (~3000 l.), onglets, aperçu live, comparatif Pro
│   ├── login-appearance.php     # CSS dynamique de la page de connexion (contraste auto)
│   ├── login-security.php       # Force brute, liste blanche, alertes, journal
│   ├── two-factor.php           # 2FA TOTP (RFC 6238), QR local, codes de secours
│   ├── sms-login.php            # Connexion SMS OTP, passerelles, anti-abus
│   ├── geo-login.php            # Restriction par pays (geojs.io, fail-open)
│   ├── recaptcha.php            # reCAPTCHA v3 non contournable
│   ├── admin-colors.php         # Couleurs admin WP + aperçu temps réel
│   ├── trial.php                # Essai 7 jours (sécurité JAMAIS coupée)
│   ├── integrity.php            # Surveillance des signatures de fichiers
│   ├── class-updater.php        # Canal auto-hébergé/GitHub (off par défaut)
│   └── license.php              # Licences, CCP/BaridiMob, PayPal, webhook
├── assets/                      # CSS/JS du dashboard + page de connexion (qrcode MIT inclus)
├── tools/
│   ├── build-local.mjs          # Build local (zip clients + zip wp.org, structure vérifiée)
│   ├── generate-logo-v2.mjs     # Identité v2 (serrure + fennec)
│   ├── audit.mjs                # Audit croisé réglages↔spec↔UI↔consommation↔uninstall
│   └── build-zip.ps1            # Compression zip conforme (slashs)
├── _smoke.cjs.php               # Harnais : 7 modes, ~130 assertions
├── PROMPT-VERIFICATION.md       # Prompt QA complet (5 phases)
└── docs/UPDATE-SERVER.md        # Contrat du canal de mises à jour
```

## 🧪 Développement

```bash
# Harnais de fumée (PHP 8.x portable recommandé) — 7 modes
php _smoke.cjs.php && php _smoke.cjs.php enabled && php _smoke.cjs.php sms \
  && php _smoke.cjs.php geo && php _smoke.cjs.php safe && php _smoke.cjs.php updater \
  && php _smoke.cjs.php 2fa

# Audit croisé (defaults ↔ spec ↔ UI ↔ consommation ↔ uninstall)
node tools/audit.mjs

# Build local : loginfennec-<v>.zip (clients) + loginfennec-<v>-wporg.zip
node tools/build-local.mjs

# Lint JS
node --check assets/js/admin.js && node --check assets/js/sms-login.js
```

**Release** : pousser un tag `v4.1.1` → la GitHub Action construit le zip, calcule le SHA-256, génère `update.json` et publie la release → mises à jour instantanées pour les clients configurés.

## 📄 Licence & mentions

GPL v2 ou ultérieure. Mascotte : **fennec** 🦊 (renard du désert algérien). Identité v2 « serrure + fennec » — régénération via `tools/generate-logo-v2.mjs`.

« ∞ Infinity Coder » — [Derouiche Oussama](https://www.derouicheoussama.com) · [GitHub](https://github.com/derouicheoussama) · [Plugin sur WordPress.org](https://wordpress.org/plugins/loginfennec/) (bientôt)
