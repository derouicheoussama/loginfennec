# Canaux de mises à jour — LoginFennec Pro

**Double canal** : le plugin respecte la priorité WordPress.org (mises à
jour natives dès la publication — le canal secondaire **s'efface
automatiquement** quand wp.org sert l'extension), et offre un canal
instantané pour vos clients directs.

## Canal GitHub Releases (instantané, recommandé avant wp.org)

La release GitHub (`v*`) publie automatiquement : `loginfennec.zip`,
`update.json` (version + checksum SHA-256) et `loginfennec.zip.sha256`.
URL stable du canal = **toujours la dernière release** :

```
https://github.com/derouicheoussama/loginfennec/releases/latest/download/update.json
```

**Côté client (wp-config.php)** :
```php
define( 'LOGINFENNEC_UPDATE_SERVER', 'https://github.com/derouicheoussama/loginfennec/releases/latest/download/update.json' );
```

**Publier une version = pousser un tag** :
```bash
git tag v4.1.1 && git push origin v4.1.1
# → GitHub Action : build du zip, SHA-256, update.json, release
# → les clients voient la mise à jour dans leur dashboard (cache 12 h,
#   ou immédiatement via « Vérifier les mises à jour » du dashboard)
```

## Canal auto-hébergé (serveur statique HTTPS) — variante

# Canal de mises à jour auto-hébergé — LoginFennec Pro

Canal optionnel pour distribuer les mises à jour **dans le dashboard
WordPress** de vos clients directs, avant la publication wp.org (et pour
une future édition Pro). Désactivé par défaut : tant que la constante
n'est pas définie, le plugin n'appelle aucun serveur.

## Côté client (wp-config.php du client)

```php
define( 'LOGINFENNEC_UPDATE_SERVER', 'https://updates.derouicheoussama.com/loginfennec/update.json' );
// Optionnel — si le serveur lie les téléchargements aux licences :
define( 'LOGINFENNEC_LICENSE_KEY', 'XXXX-XXXX-XXXX-XXXX' );
```

Sécurité garantie par le code :
- **HTTPS obligatoire** — tout autre schéma laisse le canal inactif ;
- **SHA-256 vérifié** — le zip est téléchargé, son empreinte comparée à
  celle publiée dans `update.json` ; si elle diffère, l'installation est
  **refusée** (aucun fichier modifié) ;
- **Aucune donnée du site ne sort** — une seule requête GET, 12 h de cache
  (15 min de silence après une erreur), clé de licence optionnelle en
  paramètre.

## Côté serveur (hébergement statique HTTPS suffisant)

Trois fichiers par version, dans un dossier public :

```
https://updates.derouicheoussama.com/loginfennec/
├── update.json                     ← métadonnées de la version courante
├── loginfennec-<version>.zip       ← le zip de production
└── loginfennec-<version>.zip.sha256
```

### update.json

```json
{
	"name": "LoginFennec Pro",
	"version": "3.9.0",
	"download_url": "https://updates.derouicheoussama.com/loginfennec/loginfennec-3.9.0.zip",
	"checksum": "<sha256 hex du zip>",
	"requires": "5.2",
	"requires_php": "7.2",
	"tested": "6.7",
	"url": "https://www.derouicheoussama.com/loginfennec",
	"last_updated": "2026-09-20 12:00:00",
	"sections": {
		"description": "<p>Personnalisez et sécurisez votre page de connexion.</p>",
		"changelog": "<ul><li>3.9.0 — …</li></ul>"
	}
}
```

`checksum` **doit** être l'empreinte SHA-256 exacte du zip (sans `sha256:`,
en minuscules) — c'est elle qui déclenche le refus d'installation si le
fichier est altéré.

### Procédure de publication d'une version

```bash
# 1. Build local (zip + vérification de structure)
node tools/build-local.mjs

# 2. Empreinte
sha256sum build/loginfennec-<version>.zip

# 3. Publier (scp/rsync vers votre hébergement HTTPS)
#    - le zip
#    - le .sha256 (archive)
#    - update.json mis à jour (version + checksum + changelog)
```

Les clients voient la mise à jour dans **Extensions → Mise à jour
disponible** en moins de 12 h (ou immédiatement via « Vérifier les mises
à jour » du dashboard LoginFennec, qui rafraîchit le transient).

## Règles importantes

1. **Ne jamais rétrograder** `version` sous la version déjà publiée :
   les clients ne recevraient pas l'offre de mise à jour (comparaison
   `version_compare`).
2. **WordPress.org prime automatiquement** : si le dépôt officiel propose
   la mise à jour, le canal secondaire s'efface (testé à chaque cycle de
   mise à jour dans `inject_update`). Aucun conflit possible.
3. **Transition « dépôt GitHub privé » après la publication wp.org** :
   - les installs **sans** constante reçoivent les mises à jour wp.org
     natives — rien à faire ;
   - les installs **avec** constante : le canal GitHub devient silencieux
     (l'URL des releases privées répond 404 → cache d'erreur 15 min, pas
     d'offre) et wp.org prend le relais sans rupture. Recommandation :
     demander aux clients de retirer la constante lors d'une annonce.
   - Le checksum SHA-256 continue de protéger le paquet wp.org zip si
     vous servez un jour une édition Pro via ce même canal.
4. **WordPress.org d'abord pour la version gratuite** : une fois le
   plugin accepté sur wp.org, la version gratuite reçoit les mises à jour
   natives et ce canal devient inutile pour elle (gardez-le pour une
   édition Pro sous licence).
3. **Paquet wp.org** : si vous publiez sur wp.org, excluez
   `includes/class-updater.php` du paquet soumis (modèle : ZIP wporg
   d'Infinity MigrateX). Le ZIP « clients » garde le module (inactif
   sans constante).
4. La clé de licence, si définie, transite en paramètre HTTPS vers
   VOTRE serveur uniquement.
