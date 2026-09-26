# Politique de sécurité — LoginFennec Pro

## Signaler une vulnérabilité

Signalement **privé** (jamais en issue publique) :

- **E-mail** : contact@derouicheoussama.com (objet : `[SECURITY] LoginFennec`)
- **GitHub Security Advisory** : onglet *Security → Report a vulnerability* du dépôt

Réponse sous 72 h. Crédit du rapporteur dans les notes de version (sur demande).

## Périmètre

Inclut le plugin `loginfennec/` et le serveur de licences `infinity-license-server/`.

Hors périmètre : versions antérieures à la dernière stable, sites non à jour, injections depuis wp-config compromise (le secret des licences y vivrait de toute façon).

## Garanties actuelles

- Secrets (clés SMS, reCAPTCHA) chiffrés au repos (AES-256-GCM, clé dérivée du sel `auth` + graine par site) — jamais affichés, jamais exportés
- Canal de mise à jour secondaire : HTTPS obligatoire + **SHA-256 du zip vérifié avant installation**
- Réponses du serveur de licences signables HMAC SHA-256
- OTP SMS : compteur d'essais atomique (anti brute-force parallèle), plafond par IP, anti-énumération
- 2FA TOTP : secret chiffré, codes de secours hachés usage unique
- Toutes les entrées admin : nonces + capacités ; toutes les sorties échappées

## Versions supportées

Seule la dernière version stable reçoit les correctifs de sécurité.
