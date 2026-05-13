#!/bin/bash

# ============================================================
# Fix storage symlink agar logo tenant bisa diakses via URL
# Jalankan di server: bash fix-storage-symlink.sh
# ============================================================

SITE_PATH="/www/wwwroot/pos.keetech.my.id"

echo "🔧 Fixing storage symlink..."

cd "$SITE_PATH"

# Cek apakah symlink sudah ada
if [ -L "public/storage" ]; then
    echo "✅ Symlink public/storage sudah ada"
    ls -la public/storage
else
    echo "❌ Symlink belum ada, membuat..."
    
    # Pastikan folder storage/app/public ada
    mkdir -p storage/app/public/tenants/logos
    mkdir -p storage/app/public/tenants/favicons
    mkdir -p storage/app/public/platform
    
    # Buat symlink
    ln -s "$SITE_PATH/storage/app/public" "$SITE_PATH/public/storage"
    
    echo "✅ Symlink berhasil dibuat"
fi

# Set permissions
chown -R www:www storage/
chmod -R 775 storage/
chown -R www:www public/storage 2>/dev/null || true

echo ""
echo "📁 Isi storage/app/public:"
ls -la storage/app/public/

echo ""
echo "📁 Isi tenants/logos (jika ada):"
ls -la storage/app/public/tenants/logos/ 2>/dev/null || echo "(kosong)"

echo ""
echo "🧪 Test akses URL:"
echo "curl -I https://pos.keetech.my.id/storage/tenants/logos/"

echo ""
echo "✅ Done! Coba refresh halaman POS."