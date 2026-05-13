#!/bin/bash

# Konfigurasi Nama Folder Production
PROJECT_NAME="POS_KEE_Production"
BUILD_DIR="./$PROJECT_NAME"

echo "🚀 Memulai proses build production $PROJECT_NAME..."

# 1. Bersihkan folder lama jika ada
if [ -d "$BUILD_DIR" ]; then
    echo "🗑️  Membersihkan folder lama..."
    rm -rf "$BUILD_DIR"
fi

# 2. Buat folder struktur
mkdir -p "$BUILD_DIR"

# 3. BUILD FRONTEND
echo "📦 Membangun Frontend (Vue/Vite)..."
cd frontend
npm install
npm run build
cd ..

# 4. COPY BACKEND KE PRODUCTION
echo "📂 Menyalin file Backend (Laravel)..."
# Kita abaikan folder yang tidak perlu untuk production agar folder tidak terlalu berat
# Jika kamu menggunakan Git Bash di Windows, rsync biasanya sudah ada. 
# Jika tidak, kita gunakan cp sebagai alternatif.
if command -v rsync >/dev/null 2>&1; then
    rsync -avq backend/ "$BUILD_DIR" \
        --exclude ".git" \
        --exclude "node_modules" \
        --exclude "storage/logs/*.log" \
        --exclude "storage/framework/sessions/*" \
        --exclude "storage/framework/cache/*" \
        --exclude "storage/framework/views/*.php" \
        --exclude "public/storage" \
        --exclude ".env"
else
    echo "⚠️  rsync tidak ditemukan, menggunakan cp -R (mungkin menyalin file sampah)..."
    cp -R backend/. "$BUILD_DIR/"
    # Hapus file sampah manual jika pakai cp
    rm -rf "$BUILD_DIR/.git"
    rm -rf "$BUILD_DIR/node_modules"
    rm -rf "$BUILD_DIR/storage/logs/*.log"
fi


# 5. PINDAHKAN FRONTEND DIST KE BACKEND PUBLIC
# Umumnya Laravel akan melayani frontend dari public
echo "🚚 Memindahkan hasil build frontend ke folder public backend..."
mkdir -p "$BUILD_DIR/public/app"
cp -R frontend/dist/* "$BUILD_DIR/public/"

# 6. ZIP FOLDER PRODUCTION
echo "🗜️  Mengompres folder ke 'project.zip'..."
ZIP_NAME="project.zip"

# Hapus zip lama jika ada
if [ -f "$ZIP_NAME" ]; then
    rm "$ZIP_NAME"
fi

if command -v zip >/dev/null 2>&1; then
    zip -r "$ZIP_NAME" "$PROJECT_NAME" >/dev/null
else
    echo "⚠️  zip command tidak ditemukan, mencoba menggunakan PowerShell..."
    # Gunakan PowerShell untuk zip jika di Windows
    powershell.exe -Command "Compress-Archive -Path '$PROJECT_NAME\*' -DestinationPath '$ZIP_NAME' -Force"
fi

# 7. PENUTUP
if [ -f "$ZIP_NAME" ]; then
    echo "✅ Selesai! File '$ZIP_NAME' siap dikirim ke VPS."
    echo "🗑️  Menghapus folder sementara..."
    rm -rf "$PROJECT_NAME"
else
    echo "❌ Gagal membuat file zip."
fi
