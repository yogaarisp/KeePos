#!/bin/bash
# 🔄 Quick Update Script untuk Kee POS Premium di aaPanel
# Copy-paste script ini ke terminal server aaPanel

set -e

echo "🔄 Starting Kee POS Premium update..."

# Detect website directory (adjust if needed)
if [ -z "$1" ]; then
    echo "Usage: $0 /www/wwwroot/your-domain.com"
    echo "Or run from website directory"
    if [ -f "backend/artisan" ]; then
        WEBROOT=$(pwd)
    else
        echo "❌ Please specify website directory or run from website root"
        exit 1
    fi
else
    WEBROOT=$1
fi

cd "$WEBROOT"
echo "📁 Working in: $WEBROOT"

# Backup current version
echo "📦 Creating backup..."
cp -r . ../backup_$(date +%Y%m%d_%H%M%S)

# Pull latest changes
echo "📥 Pulling latest changes from GitHub..."
if git pull origin main; then
    echo "✅ Git pull successful"
else
    echo "⚠️ Git pull failed, trying force reset..."
    git fetch origin
    git reset --hard origin/main
fi

# Update backend
echo "⚙️ Updating backend..."
cd backend

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations and seed pricing data
php artisan migrate --force
php artisan db:seed --class=PricingSeeder --force

# Clear and cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Update frontend
echo "🎨 Updating frontend..."
cd ../frontend
npm install
npm run build

# Fix permissions
echo "🔐 Fixing permissions..."
cd ..
chown -R www:www .
chmod -R 775 backend/storage backend/bootstrap/cache

# Test API
echo "🧪 Testing pricing API..."
if curl -s "https://$(basename $WEBROOT)/api/subscriptions/plans/public" | grep -q "99000"; then
    echo "✅ Pricing API working correctly!"
else
    echo "⚠️ API test failed, but update completed. Check manually."
fi

echo ""
echo "🎉 Update completed successfully!"
echo "📊 New pricing: FREE (Rp 0), BASIC (Rp 99,000), PRO (Rp 249,000)"
echo "🌐 Test your website: https://$(basename $WEBROOT)"
echo ""