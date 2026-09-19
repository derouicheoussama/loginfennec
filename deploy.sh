#!/bin/bash
# ═══════════════════════════════════════════════════════════
# 🦊 LoginFennec Pro — Déploiement GitHub
# Usage : bash deploy.sh
# Prérequis : gh auth login (GitHub CLI authentifié)
# ═══════════════════════════════════════════════════════════

set -e

echo "🦊 LoginFennec Pro — Déploiement GitHub"
echo "════════════════════════════════════════"

# Vérifie que gh est authentifié
if ! gh auth status &>/dev/null; then
  echo "❌ GitHub CLI non authentifié. Lancez : gh auth login"
  exit 1
fi

echo "✅ GitHub CLI authentifié"

# Crée le dépôt s'il n'existe pas
if ! gh repo view derouicheoussama/loginfennec &>/dev/null; then
  echo "📦 Création du dépôt..."
  gh repo create loginfennec --public --source=. --push
  echo "✅ Dépôt créé et code poussé"
else
  echo "✅ Dépôt existant"
  git push origin main
fi

# Construit le zip de production
echo "📦 Construction du zip..."
php -l loginfennec.php > /dev/null
echo "✅ Syntaxe PHP OK"

# Publie le tag
VERSION=$(grep -oP "define\('LOGINFENNEC_VERSION', '\K[^']+" loginfennec.php)
echo "🏷️  Version détectée : $VERSION"
git tag "v$VERSION" 2>/dev/null || echo "(tag déjà existant)"
git push origin "v$VERSION" 2>/dev/null || true

echo ""
echo "🎉 Déploiement terminé !"
echo "   Dépôt   : https://github.com/derouicheoussama/loginfennec"
echo "   Release : https://github.com/derouicheoussama/loginfennec/releases/tag/v$VERSION"
echo "   Mises à jour : servies par WordPress.org après publication du plugin"
