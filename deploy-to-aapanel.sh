#!/bin/bash

# 🚀 Kee POS Premium - aaPanel Deployment Script
# Usage: ./deploy-to-aapanel.sh your-domain.com

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if domain is provided
if [ -z "$1" ]; then
    print_error "Usage: $0 <domain-name>"
    print_error "Example: $0 keepos.yourdomain.com"
    exit 1
fi

DOMAIN=$1
WEBROOT="/www/wwwroot/$DOMAIN"
REPO_URL="https://github.com/yogaarisp/KeePos.git"

print_status "🚀 Starting Kee POS Premium deployment for domain: $DOMAIN"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "Please run this script as root (use sudo)"
    exit 1
fi

# Step 1: Install required packages
print_status "📦 Installing required packages..."

# Detect OS and install packages
if command -v yum &> /dev/null; then
    # CentOS/RHEL
    yum update -y
    yum install -y git curl wget unzip
    
    # Install Node.js 18
    if ! command -v node &> /dev/null; then
        curl -fsSL https://rpm.nodesource.com/setup_18.x | bash -
        yum install -y nodejs
    fi
    
elif command -v apt &> /dev/null; then
    # Ubuntu/Debian
    apt update -y
    apt install -y git curl wget unzip
    
    # Install Node.js 18
    if ! command -v node &> /dev/null; then
        curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
        apt install -y nodejs
    fi
else
    print_error "Unsupported operating system"
    exit 1
fi

# Install Composer if not exists
if ! command -v composer &> /dev/null; then
    print_status "📦 Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

print_success "✅ Required packages installed"

# Step 2: Create website directory and clone repository
print_status "📁 Setting up website directory..."

# Create webroot if not exists
mkdir -p "$WEBROOT"
cd "$WEBROOT"

# Backup existing files if any
if [ "$(ls -A $WEBROOT)" ]; then
    print_warning "Directory not empty, creating backup..."
    BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    mv * "$BACKUP_DIR/" 2>/dev/null || true
fi

# Clone repository
print_status "📥 Cloning repository from GitHub..."
git clone "$REPO_URL" .

print_success "✅ Repository cloned successfully"

# Step 3: Set proper permissions
print_status "🔐 Setting file permissions..."
chown -R www:www "$WEBROOT"
find "$WEBROOT" -type f -exec chmod 644 {} \;
find "$WEBROOT" -type d -exec chmod 755 {} \;

# Step 4: Setup Backend (Laravel)
print_status "⚙️ Setting up Laravel backend..."
cd "$WEBROOT/backend"

# Install PHP dependencies
print_status "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Setup environment file
if [ ! -f .env ]; then
    print_status "📝 Creating environment configuration..."
    cp .env.example .env
    
    # Generate application key
    php artisan key:generate --force
    
    print_warning "⚠️  IMPORTANT: Please edit backend/.env file with your database and other configurations!"
    print_warning "   Database settings, Midtrans keys, SMTP settings, etc."
fi

# Set Laravel storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www:www storage bootstrap/cache

print_success "✅ Backend setup completed"

# Step 5: Setup Frontend (Vue.js)
print_status "🎨 Setting up Vue.js frontend..."
cd "$WEBROOT/frontend"

# Install Node dependencies
print_status "📦 Installing Node.js dependencies..."
npm install

# Create frontend environment file
if [ ! -f .env ]; then
    print_status "📝 Creating frontend environment..."
    cat > .env << EOF
VITE_API_URL=https://$DOMAIN/api
VITE_APP_NAME="Kee POS Premium"
VITE_APP_URL=https://$DOMAIN
EOF
fi

# Build for production
print_status "🔨 Building frontend for production..."
npm run build

print_success "✅ Frontend setup completed"

# Step 6: Create Nginx configuration
print_status "🌐 Creating Nginx configuration..."
NGINX_CONFIG="/www/server/panel/vhost/nginx/$DOMAIN.conf"

cat > "$NGINX_CONFIG" << EOF
server {
    listen 80;
    listen 443 ssl http2;
    server_name $DOMAIN www.$DOMAIN;
    
    root $WEBROOT/frontend/dist;
    index index.html index.php;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
    # API routes (Laravel backend)
    location /api {
        alias $WEBROOT/backend/public;
        try_files \$uri \$uri/ @api;
        
        location ~ \.php\$ {
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $WEBROOT/backend/public/index.php;
            fastcgi_pass unix:/tmp/php-cgi-81.sock;
            fastcgi_index index.php;
        }
    }
    
    location @api {
        rewrite /api/(.*)\$ /api/index.php?/\$1 last;
    }
    
    # Frontend routes (Vue.js SPA)
    location / {
        try_files \$uri \$uri/ /index.html;
    }
    
    # Static assets caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Security - Hide sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /(vendor|storage|bootstrap|database|tests|artisan) {
        deny all;
    }
}
EOF

# Test and reload Nginx
print_status "🔄 Testing and reloading Nginx..."
nginx -t && systemctl reload nginx

print_success "✅ Nginx configuration created and reloaded"

# Step 7: Create deployment script for future updates
print_status "📜 Creating deployment script for future updates..."
cat > "$WEBROOT/deploy.sh" << 'EOF'
#!/bin/bash
set -e

WEBROOT=$(dirname "$0")
cd "$WEBROOT"

echo "🚀 Starting deployment..."

# Pull latest changes
git pull origin main

# Backend updates
cd backend
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend updates
cd ../frontend
npm install
npm run build

# Set permissions
cd ..
chown -R www:www .
chmod -R 775 backend/storage backend/bootstrap/cache

echo "✅ Deployment completed!"
EOF

chmod +x "$WEBROOT/deploy.sh"

print_success "✅ Deployment script created at $WEBROOT/deploy.sh"

# Step 8: Final setup
print_status "🏁 Final setup..."
cd "$WEBROOT"
chown -R www:www .

# Create logs directory
mkdir -p logs
chown www:www logs

print_success "🎉 Deployment completed successfully!"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎉 Kee POS Premium has been deployed successfully!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
print_success "✅ Website URL: https://$DOMAIN"
print_success "✅ API URL: https://$DOMAIN/api"
print_success "✅ Files location: $WEBROOT"
echo ""
print_warning "⚠️  NEXT STEPS REQUIRED:"
echo "   1. Edit backend/.env file with your database credentials"
echo "   2. Run database migrations: cd $WEBROOT/backend && php artisan migrate --force"
echo "   3. Seed pricing data: php artisan db:seed --class=PricingSeeder --force"
echo "   4. Setup SSL certificate in aaPanel (Website → SSL)"
echo "   5. Test the website: https://$DOMAIN"
echo ""
print_status "📖 For detailed instructions, see: AAPANEL_DEPLOYMENT_GUIDE.md"
print_status "🔄 For future updates, run: $WEBROOT/deploy.sh"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
EOF