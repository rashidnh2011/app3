# 🚀 Hostinger Production Deployment Guide

## 📋 Pre-Deployment Checklist

### 1. Domain Configuration
- [ ] Update `src/config/api.ts` with your actual domain
- [ ] Update `api/config/cors.php` with your domain
- [ ] Configure DNS settings in Hostinger panel

### 2. Database Setup
- [ ] Import the SQL schema from `supabase/migrations/20250623103109_dark_disk.sql`
- [ ] Verify database credentials in `api/config/database.php`
- [ ] Test database connection

### 3. File Upload
- [ ] Upload all `api/` files to your hosting root or subdirectory
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Create `api/logs/` directory with write permissions (777)

## 🔧 Hostinger Specific Setup

### 1. File Manager Setup
```
public_html/
├── api/                    # Upload all API files here
│   ├── config/
│   ├── models/
│   ├── auth/
│   ├── products/
│   ├── categories/
│   ├── cart/
│   ├── orders/
│   ├── admin/
│   ├── logs/              # Create this directory
│   ├── .htaccess
│   └── health.php
└── dist/                  # Upload built frontend files here
    ├── index.html
    ├── assets/
    └── ...
```

### 2. Database Import
1. Go to Hostinger hPanel → Databases → phpMyAdmin
2. Select your database: `u992617610_autriq`
3. Import the SQL file from `supabase/migrations/20250623103109_dark_disk.sql`
4. Verify tables are created successfully

### 3. PHP Configuration
Add to your `.htaccess` or contact Hostinger support:
```apache
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value memory_limit 256M
```

## 🌐 Frontend Build & Deploy

### 1. Update Configuration
```bash
# Update your domain in src/config/api.ts
BASE_URL: 'https://yourdomain.com/api'
```

### 2. Build for Production
```bash
npm run build:prod
```

### 3. Upload to Hostinger
- Upload contents of `dist/` folder to `public_html/`
- Or upload to subdirectory like `public_html/autriq/`

## 🔐 Security Configuration

### 1. Environment Variables
Create `api/config/.env` (optional):
```env
DB_HOST=localhost
DB_NAME=u992617610_autriq
DB_USER=u992617610_autriq
DB_PASS=Autriq@7343
JWT_SECRET=your-super-secret-jwt-key
```

### 2. File Permissions
```bash
# Set proper permissions
chmod 755 api/
chmod 644 api/*.php
chmod 644 api/*/*.php
chmod 777 api/logs/
```

### 3. SSL Certificate
- Enable SSL in Hostinger panel
- Force HTTPS redirects
- Update CORS origins to use HTTPS

## 🧪 Testing Checklist

### 1. API Health Check
Visit: `https://yourdomain.com/api/health.php`
Should return:
```json
{
  "status": "ok",
  "checks": {
    "database": "healthy"
  }
}
```

### 2. Frontend Tests
- [ ] Homepage loads correctly
- [ ] Admin login works (`admin@autriq.com` / `admin123`)
- [ ] User registration works
- [ ] Product browsing works
- [ ] Cart functionality works
- [ ] Order placement works

### 3. Database Tests
- [ ] Admin can add products
- [ ] Products appear on homepage
- [ ] Cart persists items
- [ ] Orders are saved correctly

## 🚨 Troubleshooting

### Common Issues

1. **CORS Errors**
   - Check domain in `api/config/cors.php`
   - Verify SSL certificate is active

2. **Database Connection Failed**
   - Verify credentials in `api/config/database.php`
   - Check database exists in Hostinger panel

3. **500 Internal Server Error**
   - Check `api/logs/error.log`
   - Verify file permissions
   - Check PHP version compatibility

4. **API Not Found (404)**
   - Verify `.htaccess` is uploaded
   - Check file paths are correct

### Debug Mode
To enable debug mode temporarily:
```php
// In api/config/environment.php
define('ENVIRONMENT', 'development');
```

## 📊 Monitoring

### 1. Error Logs
Check: `api/logs/error.log` for PHP errors

### 2. Health Monitoring
Set up monitoring for: `https://yourdomain.com/api/health.php`

### 3. Performance
- Monitor page load times
- Check database query performance
- Monitor server resources

## 🔄 Updates

### 1. Code Updates
```bash
# Build new version
npm run build:prod

# Upload new dist/ files
# Upload any API changes
```

### 2. Database Updates
- Always backup before schema changes
- Test migrations on staging first

## 📞 Support

- **Hostinger Support**: For hosting-related issues
- **Database Issues**: Check phpMyAdmin in hPanel
- **SSL Issues**: Hostinger SSL management
- **Domain Issues**: DNS settings in hPanel

---

**🎉 Your Autriq e-commerce platform is now ready for production!**