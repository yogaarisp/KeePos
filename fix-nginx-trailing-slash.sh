#!/bin/bash

# ============================================================
# Fix Nginx trailing slash redirect untuk Vue SPA
# Jalankan di server: bash fix-nginx-trailing-slash.sh
# ============================================================

SITE_PATH="/www/wwwroot/pos.keetech.my.id"
NGINX_CONF="/www/server/panel/vhost/nginx/pos.keetech.my.id.conf"

echo "🔧 Fixing Nginx trailing slash redirect..."

# Backup config lama
if [ -f "$NGINX_CONF" ]; then
    cp "$NGINX_CONF" "${NGINX_CONF}.backup.$(date +%Y%m%d_%H%M%S)"
    echo "✅ Backup created"
    echo ""
    echo "📋 Current config:"
    cat "$NGINX_CONF"
else
    echo "❌ Nginx config not found at: $NGINX_CONF"
    echo "🔍 Searching for config..."
    find /www/server/panel/vhost/nginx/ -name "*keetech*" -o -name "*pos*" 2>/dev/null
fi

echo ""
echo "📝 The fix needed in Nginx config:"
echo ""
echo "Add this BEFORE the existing location blocks:"
echo ""
cat << 'NGINX_FIX'
    # Fix: Prevent /app from redirecting to /app/
    # Vue SPA routes - serve index.html directly
    location ~ ^/(app|login|register|forgot-password|reset-password|verify-email)(/.*)?$ {
        try_files $uri /index.html;
    }

    # Remove trailing slash for non-directory requests
    rewrite ^/(.*)/$ /$1 permanent;
NGINX_FIX

echo ""
echo "🔍 To find your Nginx config file:"
echo "   find /www/server/panel/vhost/nginx/ -name '*.conf' | xargs grep -l 'keetech\|pos'"
echo ""
echo "📌 Or check in aaPanel:"
echo "   Website > pos.keetech.my.id > Config"