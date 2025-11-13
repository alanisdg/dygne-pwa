#!/bin/bash
# === CONFIG ===

SERVER="alanisdg@161.35.235.139"
PROJECT_PATH="/var/www/dygne-pwa"
BRANCH="main" 
# === PASO 1: Commit y push local ===
echo "📦 Haciendo commit y push en rama $BRANCH..."
git add .
git commit -m "🚀 Deploy automático $(date '+%Y-%m-%d %H:%M:%S')" || echo "Nada nuevo que commitear"
git push origin $BRANCH

# === PASO 2: Conectarse al servidor y desplegar ===
echo "🌐 Conectando a $SERVER..."
ssh $SERVER << EOF
    echo "📁 Entrando a $PROJECT_PATH"
    cd $PROJECT_PATH

    echo "📥 Actualizando desde git..."
    git checkout $BRANCH
    git pull origin $BRANCH

    npm run build

    echo "✅ Deploy completado!"
EOF
