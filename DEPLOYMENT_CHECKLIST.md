# 🚀 cPanel Deployment Checklist

## 📋 Pre-Upload Preparation

### ✅ Step 1: Prepare Files

-   [ ] Run `composer install --optimize-autoloader --no-dev`
-   [ ] Copy `env.production` to `.env` and configure
-   [ ] Generate new APP_KEY: `php artisan key:generate`
-   [ ] Clear all caches: `php artisan cache:clear && php artisan config:clear`
-   [ ] Test locally one final time

### ✅ Step 2: Create Upload Package

Create ZIP with these files:

-   [ ] `app/` folder
-   [ ] `bootstrap/` folder
-   [ ] `config/` folder
-   [ ] `database/` folder
-   [ ] `public/` folder
-   [ ] `resources/` folder
-   [ ] `routes/` folder
-   [ ] `storage/` folder
-   [ ] `vendor/` folder (if composer install done)
-   [ ] `.env` (production configured)
-   [ ] `artisan` file
-   [ ] `composer.json`
-   [ ] `composer.lock`
-   [ ] `production-setup.php`

---

## 🌐 cPanel Setup

### ✅ Step 3: Database Setup

-   [ ] Login to cPanel
-   [ ] Go to "MySQL Databases"
-   [ ] Create database: `yourdomain_ecommerce`
-   [ ] Create user: `yourdomain_api`
-   [ ] Set strong password
-   [ ] Add user to database with ALL PRIVILEGES
-   [ ] Note down: host, database name, username, password

### ✅ Step 4: File Upload

-   [ ] Open cPanel File Manager
-   [ ] Navigate to `public_html` (or your domain folder)
-   [ ] Upload your ZIP file
-   [ ] Extract ZIP file
-   [ ] Move files to correct structure:
    ```
    public_html/
    ├── api/                 # Laravel's public folder contents
    │   ├── index.php
    │   └── .htaccess
    ├── app/                 # Laravel app folder
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

### ✅ Step 5: Configure .env File

Update `.env` with your cPanel details:

```env
DB_HOST=localhost
DB_DATABASE=yourdomain_ecommerce
DB_USERNAME=yourdomain_api
DB_PASSWORD=your_strong_password
APP_URL=https://yourdomain.com
```

### ✅ Step 6: Set File Permissions

In cPanel File Manager, set permissions:

-   [ ] Folders: 755
-   [ ] Files: 644
-   [ ] `storage/` folder: 775
-   [ ] `bootstrap/cache/` folder: 775

---

## 🔧 Configuration

### ✅ Step 7: Run Production Setup

-   [ ] Visit: `https://yourdomain.com/production-setup.php`
-   [ ] Follow the setup wizard
-   [ ] Note down admin credentials
-   [ ] Verify all checks pass

### ✅ Step 8: Configure .htaccess

Create `public_html/api/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    Options -MultiViews -Indexes
    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
</IfModule>
```

### ✅ Step 9: SSL Configuration

-   [ ] Go to cPanel "SSL/TLS"
-   [ ] Enable "Let's Encrypt SSL" (free)
-   [ ] Force HTTPS redirect
-   [ ] Verify SSL certificate works

---

## 🔒 Security Setup

### ✅ Step 10: Secure Sensitive Files

Create `public_html/.htaccess`:

```apache
<Files .env>
    Order allow,deny
    Deny from all
</Files>

<FilesMatch "(composer\.json|composer\.lock|artisan)">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### ✅ Step 11: Update Admin Password

-   [ ] Login with generated admin credentials
-   [ ] Immediately change password
-   [ ] Test admin panel functionality

---

## 💳 Payment Setup

### ✅ Step 12: Configure Stripe Live Mode

-   [ ] Login to Stripe Dashboard
-   [ ] Switch to "Live" mode
-   [ ] Copy Live API keys
-   [ ] Update `.env` file:
    ```env
    STRIPE_KEY=pk_live_...
    STRIPE_SECRET=sk_live_...
    ```

### ✅ Step 13: Setup Webhooks

-   [ ] Stripe Dashboard → Webhooks
-   [ ] Add endpoint: `https://yourdomain.com/api/webhooks/stripe`
-   [ ] Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`
-   [ ] Copy webhook secret to `.env`

---

## 📧 Email Configuration

### ✅ Step 14: Setup Email

-   [ ] Create email account in cPanel
-   [ ] Update `.env` with SMTP settings:
    ```env
    MAIL_HOST=mail.yourdomain.com
    MAIL_USERNAME=noreply@yourdomain.com
    MAIL_PASSWORD=your_email_password
    ```
-   [ ] Test email functionality

---

## 🧪 Testing

### ✅ Step 15: Test All Endpoints

-   [ ] `GET https://yourdomain.com/api/categories` - Should return categories
-   [ ] `POST https://yourdomain.com/api/auth/login` - Admin login
-   [ ] `GET https://yourdomain.com/api/admin/dashboard/stats` - Dashboard
-   [ ] `GET https://yourdomain.com/api/products` - Products
-   [ ] Test image uploads for categories

### ✅ Step 16: Test Payment Flow

-   [ ] Create test product
-   [ ] Add to cart
-   [ ] Process payment with Stripe test card
-   [ ] Verify order creation
-   [ ] Check webhook processing

---

## 📊 Post-Launch

### ✅ Step 17: Monitoring Setup

-   [ ] Check error logs: `storage/logs/laravel.log`
-   [ ] Set up log rotation if available
-   [ ] Monitor server resources
-   [ ] Set up backup routine

### ✅ Step 18: Frontend Integration

Update your React frontend:

```javascript
// Update API base URL
const API_BASE_URL = "https://yourdomain.com/api";

// Test categories endpoint
fetch(`${API_BASE_URL}/categories`)
    .then((response) => response.json())
    .then((data) => console.log(data));
```

### ✅ Step 19: Clean Up

-   [ ] Delete `production-setup.php` file
-   [ ] Remove any test files
-   [ ] Secure file permissions
-   [ ] Document admin credentials safely

---

## 🎉 Launch Complete!

### Your Live URLs:

-   **API Base:** `https://yourdomain.com/api`
-   **Categories:** `https://yourdomain.com/api/categories`
-   **Admin Dashboard:** Use API endpoints with admin token
-   **Payment Processing:** Live Stripe integration active

### Admin Credentials:

-   **Email:** admin@yourdomain.com
-   **Password:** [Generated during setup]

### Important Notes:

1. **Change admin password immediately**
2. **Monitor error logs regularly**
3. **Keep Laravel updated**
4. **Backup database regularly**
5. **Monitor Stripe transactions**

**Your e-commerce backend is now live! 🚀**

### Need Help?

-   Check Laravel logs: `storage/logs/laravel.log`
-   Verify .env configuration
-   Test API endpoints individually
-   Check database connectivity
-   Verify file permissions

### Support:

-   Laravel Documentation: https://laravel.com/docs
-   Stripe Documentation: https://stripe.com/docs
-   Your hosting provider's support
