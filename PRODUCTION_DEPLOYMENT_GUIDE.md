# 🚀 Deploy Laravel E-commerce to Live Server (cPanel without SSH)

## 📋 Prerequisites Checklist

### ✅ What You Need

-   **cPanel hosting** with PHP 8.1+ support
-   **MySQL database** access through cPanel
-   **File Manager** or FTP access
-   **Domain/subdomain** configured
-   **Composer** support (most hosts have this)

### ✅ Your Application Status

-   ✅ Complete Laravel backend with API endpoints
-   ✅ Stripe payment integration
-   ✅ Category management with image upload
-   ✅ User management and authentication
-   ✅ Order management system
-   ✅ Admin dashboard

---

## 🗂️ Step 1: Prepare Your Application for Production

### 1.1 Update Environment Configuration

Create a production `.env` file:

```env
APP_NAME="Your E-commerce Store"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=your_host_smtp
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Stripe Production Keys
STRIPE_KEY=pk_live_YOUR_LIVE_PUBLISHABLE_KEY
STRIPE_SECRET=sk_live_YOUR_LIVE_SECRET_KEY
STRIPE_WEBHOOK_SECRET=whsec_YOUR_LIVE_WEBHOOK_SECRET

# Frontend URL (if separate)
FRONTEND_URL=https://yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com

# Session and CORS
SESSION_DOMAIN=.yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,api.yourdomain.com
```

### 1.2 Optimize for Production

Create `optimize-for-production.php`:

```php
<?php
// Run these commands before upload
echo "🔧 Optimizing Laravel for Production...\n\n";

// 1. Clear all caches
system('php artisan cache:clear');
system('php artisan config:clear');
system('php artisan route:clear');
system('php artisan view:clear');

// 2. Optimize for production
system('php artisan config:cache');
system('php artisan route:cache');
system('php artisan view:cache');

// 3. Install production dependencies
system('composer install --optimize-autoloader --no-dev');

echo "✅ Production optimization complete!\n";
```

### 1.3 Create Production Database Migration Script

Create `production-setup.php`:

```php
<?php
// This will run on your live server after upload

require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 Setting up production database...\n\n";

try {
    // Run migrations
    echo "1. Running migrations...\n";
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output() . "\n";

    // Create storage link
    echo "2. Creating storage link...\n";
    Artisan::call('storage:link');
    echo Artisan::output() . "\n";

    // Create admin user if not exists
    echo "3. Creating admin user...\n";
    $admin = \App\Models\User::firstOrCreate([
        'email' => 'admin@yourdomain.com'
    ], [
        'name' => 'Admin User',
        'password' => Hash::make('ChangeThisPassword123!'),
        'role' => 'admin',
        'is_active' => true,
        'email_verified_at' => now()
    ]);

    echo "✅ Admin user: admin@yourdomain.com\n";
    echo "✅ Password: ChangeThisPassword123!\n\n";

    // Create sample categories
    echo "4. Creating sample categories...\n";
    $categories = [
        ['name' => 'Electronics', 'description' => 'Electronic gadgets and devices'],
        ['name' => 'Fashion', 'description' => 'Clothing and accessories'],
        ['name' => 'Home & Garden', 'description' => 'Home and garden products'],
    ];

    foreach ($categories as $cat) {
        \App\Models\Category::firstOrCreate(['name' => $cat['name']], $cat);
    }

    echo "✅ Production setup complete!\n";
    echo "🔗 Admin panel: https://yourdomain.com/api/admin\n";
    echo "🔗 API base: https://yourdomain.com/api\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
```

---

## 📂 Step 2: Upload Files to cPanel

### 2.1 Prepare Upload Package

**Create a ZIP file containing:**

```
your-app.zip
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/ (if composer install already done)
├── .env (production version)
├── artisan
├── composer.json
├── composer.lock
├── production-setup.php
└── README-DEPLOYMENT.md
```

### 2.2 Upload via cPanel File Manager

1. **Log into cPanel**
2. **Open File Manager**
3. **Navigate to public_html** (or your domain folder)
4. **Upload your ZIP file**
5. **Extract the ZIP file**
6. **Move Laravel files** to correct location

### 2.3 Correct Directory Structure

```
public_html/
├── api/              # Your Laravel public folder content goes here
│   ├── index.php     # Laravel's public/index.php
│   └── storage/      # Symlink to storage
├── app/              # Laravel app folder
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
├── artisan
└── production-setup.php
```

**Important:** The `public` folder content should go in `public_html/api/` (or your API subdomain)

---

## 🗄️ Step 3: Database Setup

### 3.1 Create Database in cPanel

1. **Go to MySQL Databases**
2. **Create new database:** `yourdomain_ecommerce`
3. **Create database user:** `yourdomain_api`
4. **Set strong password**
5. **Add user to database** with ALL PRIVILEGES

### 3.2 Update .env File

Update your uploaded `.env` file with database credentials:

```env
DB_HOST=localhost
DB_DATABASE=yourdomain_ecommerce
DB_USERNAME=yourdomain_api
DB_PASSWORD=your_strong_password
```

### 3.3 Run Database Setup

1. **Open File Manager**
2. **Navigate to your Laravel root**
3. **Run production setup:**
    - Go to: `https://yourdomain.com/production-setup.php`
    - Or use cPanel Terminal (if available)

---

## 🔧 Step 4: Configure Web Server

### 4.1 Create .htaccess for API

In `public_html/api/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# CORS Headers for API
<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"

    # Handle preflight requests
    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ $1 [R=200,L]
</IfModule>

# PHP Configuration
<IfModule mod_php.c>
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value memory_limit 256M
    php_value max_execution_time 300
</IfModule>
```

### 4.2 Root .htaccess (if needed)

In `public_html/.htaccess`:

```apache
# Redirect API requests to Laravel
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/api/
RewriteRule ^api/(.*)$ /api/index.php [L]

# Redirect to HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 🔒 Step 5: Security and Optimization

### 5.1 Set Proper Permissions

Via cPanel File Manager, set permissions:

```
Folders: 755
Files: 644
storage/ and bootstrap/cache/: 775
```

### 5.2 Secure .env File

Create `public_html/.htaccess` with:

```apache
# Protect .env file
<Files .env>
    Order allow,deny
    Deny from all
</Files>

# Protect other sensitive files
<FilesMatch "(composer\.json|composer\.lock|package\.json|\.git)">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 5.3 Configure Cron Jobs (if available)

Add to cPanel Cron Jobs:

```bash
# Laravel scheduler (every minute)
* * * * * cd /home/yourusername/public_html && php artisan schedule:run >> /dev/null 2>&1

# Clear logs weekly
0 0 * * 0 cd /home/yourusername/public_html && php artisan log:clear
```

---

## 🌐 Step 6: Domain and SSL Configuration

### 6.1 Point Domain to API

**Option A: Subdomain**

-   Create subdomain: `api.yourdomain.com`
-   Point to your Laravel folder

**Option B: Path-based**

-   Use: `yourdomain.com/api/`
-   Configure .htaccess redirects

### 6.2 Enable SSL

1. **Go to SSL/TLS in cPanel**
2. **Enable "Let's Encrypt SSL"** (free)
3. **Or upload SSL certificate** if you have one
4. **Force HTTPS redirect**

---

## 📧 Step 7: Configure Email and Notifications

### 7.1 Email Setup

Update `.env` with cPanel email settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="Your Store"
```

### 7.2 Test Email Configuration

Create `test-email.php`:

```php
<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test email
Mail::raw('Test email from production server', function ($message) {
    $message->to('your-email@example.com')
            ->subject('Laravel Production Test');
});

echo "Test email sent!";
```

---

## 💳 Step 8: Configure Stripe for Production

### 8.1 Update Stripe Keys

1. **Log into Stripe Dashboard**
2. **Switch to Live mode**
3. **Get Live API keys**
4. **Update .env file:**

```env
STRIPE_KEY=pk_live_YOUR_LIVE_KEY
STRIPE_SECRET=sk_live_YOUR_LIVE_SECRET
STRIPE_WEBHOOK_SECRET=whsec_YOUR_LIVE_WEBHOOK
```

### 8.2 Configure Webhooks

1. **Stripe Dashboard → Webhooks**
2. **Add endpoint:** `https://yourdomain.com/api/webhooks/stripe`
3. **Select events:** `payment_intent.succeeded`, `payment_intent.payment_failed`
4. **Copy webhook secret** to .env

---

## 🧪 Step 9: Testing Your Live Application

### 9.1 API Endpoints Test

Create `test-live-api.php`:

```php
<?php
echo "🧪 Testing Live API Endpoints\n";
echo "==============================\n\n";

$baseUrl = 'https://yourdomain.com/api';

// Test health
$response = file_get_contents($baseUrl . '/categories');
if ($response) {
    echo "✅ Categories endpoint working\n";
} else {
    echo "❌ Categories endpoint failed\n";
}

// Test admin login
$loginData = json_encode([
    'email' => 'admin@yourdomain.com',
    'password' => 'ChangeThisPassword123!'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $loginData
    ]
]);

$response = file_get_contents($baseUrl . '/auth/login', false, $context);
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success']) {
        echo "✅ Admin login working\n";
        echo "🔑 Token: " . substr($data['data']['token'], 0, 20) . "...\n";
    } else {
        echo "❌ Admin login failed\n";
    }
} else {
    echo "❌ Cannot connect to login endpoint\n";
}

echo "\n🌐 Your API is live at: $baseUrl\n";
```

### 9.2 Frontend Configuration

Update your React frontend API base URL:

```javascript
// In your React app
const API_BASE_URL = "https://yourdomain.com/api";

// Example usage
const response = await fetch(`${API_BASE_URL}/categories`);
const categories = await response.json();
```

---

## 📊 Step 10: Production Monitoring and Maintenance

### 10.1 Log Monitoring

Create `check-logs.php`:

```php
<?php
echo "📊 Recent Error Logs\n";
echo "===================\n";

$logFile = 'storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLines = array_slice($lines, -50); // Last 50 lines
    echo implode("\n", $recentLines);
} else {
    echo "No log file found\n";
}
?>
```

### 10.2 Backup Script

Create `backup-database.php`:

```php
<?php
$host = 'localhost';
$user = 'yourdomain_api';
$pass = 'your_password';
$db = 'yourdomain_ecommerce';

$backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

$command = "mysqldump -h $host -u $user -p$pass $db > $backup_file";
system($command);

echo "Database backed up to: $backup_file\n";
?>
```

---

## 🎉 Step 11: Go Live Checklist

### ✅ Pre-Launch Checklist

-   [ ] Database migrated successfully
-   [ ] Admin user created and accessible
-   [ ] SSL certificate active
-   [ ] Stripe live keys configured
-   [ ] Email notifications working
-   [ ] File permissions set correctly
-   [ ] .env file secured
-   [ ] API endpoints responding
-   [ ] Image uploads working
-   [ ] Category management functional
-   [ ] Payment processing tested

### ✅ Post-Launch Tasks

-   [ ] Monitor error logs daily
-   [ ] Set up daily database backups
-   [ ] Configure monitoring alerts
-   [ ] Update DNS records if needed
-   [ ] Test all critical user flows
-   [ ] Monitor server resources
-   [ ] Update documentation

---

## 🚨 Troubleshooting Common Issues

### Issue 1: 500 Internal Server Error

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check PHP error logs in cPanel
```

### Issue 2: Database Connection Error

```bash
# Verify .env database credentials
# Check database user privileges
# Ensure database exists
```

### Issue 3: File Permission Issues

```bash
# Set correct permissions
chmod 775 storage/
chmod 775 bootstrap/cache/
```

### Issue 4: Stripe Webhooks Not Working

```bash
# Check webhook URL is accessible
# Verify webhook secret matches
# Check firewall settings
```

---

## 📞 Support and Maintenance

### Your Live Application URLs:

-   **API Base:** `https://yourdomain.com/api`
-   **Admin Login:** `admin@yourdomain.com` / `ChangeThisPassword123!`
-   **Categories:** `https://yourdomain.com/api/categories`
-   **Admin Panel:** `https://yourdomain.com/api/admin/dashboard/stats`

### Important Files to Monitor:

-   `storage/logs/laravel.log` - Application logs
-   `.env` - Environment configuration
-   `public_html/api/.htaccess` - Web server config

**Your e-commerce backend is now live and production-ready!** 🚀

Remember to:

1. **Change default admin password** immediately
2. **Monitor logs** regularly
3. **Keep Laravel updated**
4. **Backup database** regularly
5. **Monitor server resources**
