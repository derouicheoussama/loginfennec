# 🚀 Mise en production — LoginFennec Pro 2.3.1

> Guide pas-à-pas pour publier le plugin (GitHub + vente + WordPress.org).
> Vos coordonnées de paiement sont déjà intégrées dans le code.

---

## ✅ État actuel (prêt)

| Élément | État |
|---|---|
| Code validé (lint PHP 8.3 réel + harnais complet) | ✅ |
| Paiement **BaridiMob / CCP** (compte 0014480375 clé 46) | ✅ intégré |
| Paiement **PayPal** (payment@derouiche.dev, _xclick direct) | ✅ intégré |
| Paiement **carte bancaire** | ⚙️ nécessite `LOGINFENNEC_CHECKOUT_URL` |
| Licence (clé + packs 1/5 sites, annuelle/à vie, expiration, révocation) | ✅ |
| Garde anti-doublon (anciens dossiers loginfence / infinity-loginshield) | ✅ |
| Assets WordPress.org (fennec : bannières + icônes) | ✅ dans `wporg-assets/` |

---

## 1. Tester avant de publier (5 minutes)

1. Installez `loginfennec-2.3.1.zip` sur un site de test (ou le vôtre) ;
2. **Extensions** → vérifiez qu'il n'y a qu'**une seule** entrée LoginFennec Pro ;
3. Ouvrez **Passer en Pro** → choisissez un pack → tuile **BaridiMob / CCP** → vérifiez le compte affiché ;
4. Collez la clé de test `LNF-PRO-TEST-2026` → **Activer Pro** → le panneau « Votre licence Pro » doit apparaître (statut Active, expiration affichée) ;
5. Onglet **Sécurité** → champ **Alerte e-mail** (🔒 Pro) : renseignez votre e-mail, provoquez 5 échecs de connexion → vous devez recevoir l'alerte ;
6. Dashboard → vérifiez **score de sécurité** et **journal**.

## 2. Activer les paiements réels

### BaridiMob / CCP — déjà actif ✅
Les acheteurs voient votre compte `0014480375 clé 46` (titulaire Derouiche Oussama), copient, paient via BaridiMob et vous envoient la preuve. Vous envoyez ensuite une clé (ex. `LNF-2026-XXXX-XXXX` unique par client) qu'ils activent dans le plugin.

### PayPal — déjà actif ✅
Le montant converti (taux `LOGINFENNEC_PAYPAL_RATE`, 0.0075 par défaut) est payé sur **payment@derouiche.dev** via PayPal _xclick.

### Carte bancaire — optionnel
```php
// wp-config.php
define( 'LOGINFENNEC_CHECKOUT_URL', 'https://votre-lien-lemonsqueezy-ou-stripe' );
```

## 3. Publier sur GitHub (active les mises à jour)

```bash
gh auth login
cd loginfennec
gh repo create loginfennec --public --source=. --push
git tag v2.3.1 && git push origin v2.3.1
```

→ L'action GitHub construit `loginfennec.zip` et l'attache à la release :
**tous les sites ayant la v2.3.1 reçoivent la proposition de mise à jour automatiquement.**

## 4. Soumettre à WordPress.org

1. Compte créé + pseudo `derouicheoussama` (déjà dans le readme ✅) ;
2. **Plugin Check** installé sur un site de test → lancer l'audit → corriger les dernières remarques éventuelles ;
3. Captures d'écran réelles (4 à 6, PNG 1200×900) : dashboard, page Pro avec packs, page de connexion personnalisée, journal de sécurité ;
4. Soumettre le zip : https://wordpress.org/plugins/developers/add/
5. Après acceptation → SVN :
   ```bash
   svn co https://plugins.svn.wordpress.org/loginfennec/ lf-svn
   cd lf-svn
   # trunk ← code du plugin · assets ← wporg-assets/ + vos captures
   svn add . --force && svn ci -m "LoginFennec Pro 2.3.1"
   svn cp trunk tags/2.3.1 && svn ci -m "Tag 2.3.1"
   ```
6. **Rien d'autre** : les mises à jour sont servies par wp.org et la fiche apparaîtra dans `plugin-install.php`.

## 5. Ventes — mémo des packs

| Pack | Annuelle | À vie | Sites | Mises à jour |
|---|---|---|---|---|
| Pro — 1 site | 3 900 DA | 6 800 DA | 1 | 1 an / à vie |
| Pro — 5 sites | 6 800 DA | 7 600 DA | 5 | 1 an / à vie |

Prix modifiables dans `includes/license.php` → `lnf_license_plans()` (ou filtre `loginfennec_license_plans`).
Lien de vente personnalisable : filtre `loginfennec_pro_url`.

---

## 🔐 Rappels sécurité production

- La v2.1.1+ contient le **garde anti-doublon** : si un ancien dossier (`loginfence`, `infinity-loginshield`) traîne encore, plus d'erreur critique — un message demande sa suppression ;
- Une seule copie du plugin doit rester dans `wp-content/plugins/` : **`loginfennec`** ;
- En cas de pépin : l'e-mail de récupération WordPress indique le fichier exact en cause.
