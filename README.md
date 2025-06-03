# 🚀 Online Student Registration System - Setup Guide

Bu qo'llanma loyihani 0 dan to'liq ishga tushirish uchun step-by-step yo'riqnoma.

## 📋 Talablar

### Server Talablari:
- **PHP**: 7.4 yoki undan yuqori
- **MySQL**: 5.7 yoki undan yuqori
- **Apache**: mod_rewrite yoqilgan
- **Disk space**: Kamida 100MB

### PHP Extensions:
- PDO
- pdo_mysql
- mbstring
- openssl
- json
- curl
- gd (rasm ishlash uchun)

## 🔧 Installation

### 1. Project Files Setup

```bash
# Project papkasini yarating
mkdir online-student-system
cd online-student-system

# Barcha fayllarni download qiling yoki copy qiling
```

### 2. Folder Structure Yaratish

```
online-student-system/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   ├── images/
│   └── uploads/
├── includes/
│   ├── config.php
│   ├── functions.php
│   ├── header.php
│   └── footer.php
├── admin/
│   ├── index.php
│   ├── students.php
│   ├── courses.php
│   └── quizzes.php
├── index.php
├── login.php
├── register.php
├── dashboard.php
├── quiz.php
├── profile.php
├── database.sql
├── .htaccess
└── README.md
```

### 3. Database Setup

**MySQL da database yarating:**
```sql
CREATE DATABASE online_student_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Database faylini import qiling:**
```bash
mysql -u root -p online_student_db < database.sql
```

Yoki phpMyAdmin orqali `database.sql` faylini import qiling.

### 4. Configuration

**includes/config.php faylini tahrirlang:**

```php
// Database connection settings
$host = 'localhost';
$dbname = 'online_student_db';  
$username = 'root';
$password = 'yourpassword';  // O'z parolingizni kiriting
```

**Base URL ni sozlang:**
```php
define('BASE_URL', 'http://localhost/online-student-system');
```

### 5. File Permissions

**Linux/Mac:**
```bash
chmod 755 assets/uploads/
chmod 644 includes/config.php
chmod 644 .htaccess
```

**Windows:**
- `assets/uploads/` papkasiga write permission bering
- IIS foydalanuvchilari uchun web.config yarating

### 6. Apache Configuration

**.htaccess fayli mavjudligini tekshiring va mod_rewrite yoqilganligini tasdiqlang:**

```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

### 7. Test Installation

**Brauzerda quyidagi URL'ni oching:**
```
http://localhost/online-student-system
```

Agar hammasi to'g'ri sozlangan bo'lsa, home page ko'rinishi kerak.

## 🎯 First Steps

### Default Admin Account

System bilan birga default admin akkaunt yaratiladi:

- **Username**: admin
- **Email**: admin@example.com
- **Password**: admin123

**⚠️ DIQQAT**: Production da bu parolni albatta o'zgartiring!

### Test Student Account Yaratish

1. `http://localhost/online-student-system/register.php` ga o'ting
2. Barcha ma'lumotlarni to'ldiring
3. Submit qiling
4. Login qiling va dashboard ko'ring

### Sample Data

Database da allaqachon quyidagilar mavjud:
- 4 ta sample course
- 1 ta sample quiz (5 ta savol bilan)
- Default admin user

## 🔧 Configuration Options

### Email Settings (ixtiyoriy)

Email functionality uchun `includes/config.php` ga qo'shing:

```php
// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'Student Portal');
```

### File Upload Settings

```php
// Upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
```

### Security Settings

```php
// Security
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('ENABLE_CSRF', true);
```

## 🛠️ Customization

### Logo O'zgartirish

1. Logoni `assets/images/logo.png` ga joylashtiring
2. `includes/header.php` da logo yo'lini yangilang:
```php
<img src="assets/images/logo.png" alt="Logo" class="navbar-logo">
```

### Colors va Theme

`assets/css/style.css` da ranglarni o'zgartiring:

```css
:root {
    --primary-color: #your-color;
    --success-color: #your-color;
    /* ... */
}
```

### Language Localization

1. `includes/lang/` papkasini yarating
2. Har bir til uchun fayl yarating (`uz.php`, `en.php`)
3. `includes/functions.php` ga translation function qo'shing

### Database Table Customization

Qo'shimcha maydonlar kerak bo'lsa:

```sql
-- Students jadvaliga yangi maydon qo'shish
ALTER TABLE students ADD COLUMN telegram_username VARCHAR(100);

-- Yangi jadval yaratish
CREATE TABLE student_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    quiz_id INT,
    certificate_code VARCHAR(50),
    issued_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
);
```

## 🚨 Troubleshooting

### Common Issues va Yechimlar

**1. Database Connection Error**
```
Error: Database connection failed
```
**Yechim:**
- MySQL service ishga tushirilganligini tekshiring
- `includes/config.php` da database credentials to'g'riligini tekshiring
- Database mavjudligini tasdiqlang

**2. Permission Denied (uploads)**
```
Error: Could not upload file
```
**Yechim:**
```bash
chmod 755 assets/uploads/
chown www-data:www-data assets/uploads/  # Linux
```

**3. .htaccess Rules ishlamayapti**
```
404 Error on clean URLs
```
**Yechim:**
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

**4. PHP Errors**
```
Fatal error: Call to undefined function
```
**Yechim:**
- PHP version tekshiring (7.4+ kerak)
- Kerakli extensions o'rnatilganligini tekshiring

**5. Session Issues**
```
Session could not be started
```
**Yechim:**
- `/tmp` papkasiga write permission bor yoki yo'qligini tekshiring
- `php.ini` da session settings tekshiring

### Debug Mode

Development paytida debug mode yoqing:

```php
// includes/config.php ga qo'shing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('DEBUG_MODE', true);
```

### Log Files

Error loglarni tekshiring:

```bash
# Apache error log
tail -f /var/log/apache2/error.log

# PHP error log  
tail -f /var/log/php_errors.log

# Custom application log
tail -f logs/app.log
```

## 🔒 Security Checklist

### Production uchun Security

**1. Default Passwords o'zgartiring:**
- Admin akkaunt parolini o'zgartiring
- Database user parolini kuchaytiring

**2. File Permissions:**
```bash
chmod 644 includes/config.php
chmod 755 assets/uploads/
chmod 644 .htaccess
```

**3. Sensitive Files himoyalang:**
- `includes/config.php` to'g'ridan-to'g'ri access ni bloklang
- Database backup fayllarini web directory dan tashqariga joylashtiring

**4. HTTPS yoqing:**
```apache
# .htaccess ga qo'shing
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**5. SQL Injection himoyasi:**
- Prepared statements ishlatilganligini tekshiring
- User input sanitization qiling

## 📊 Performance Optimization

### Database Optimization

**Indexes qo'shing:**
```sql
CREATE INDEX idx_students_email ON students(email);
CREATE INDEX idx_quiz_attempts_student ON quiz_attempts(student_id);
CREATE INDEX idx_quiz_attempts_quiz ON quiz_attempts(quiz_id);
```

**Query optimization:**
```sql
-- Yomon
SELECT * FROM students WHERE email = 'user@example.com';

-- Yaxshi  
SELECT id, student_id, first_name, last_name FROM students WHERE email = 'user@example.com';
```

### Caching

**File caching:**
```php
// Simple cache implementation
function cache_get($key) {
    $file = "cache/" . md5($key) . ".cache";
    if (file_exists($file) && (time() - filemtime($file)) < 3600) {
        return unserialize(file_get_contents($file));
    }
    return false;
}

function cache_set($key, $data) {
    $file = "cache/" . md5($key) . ".cache";
    file_put_contents($file, serialize($data));
}
```

### Assets Optimization

**CSS/JS minification:**
```bash
# Install minification tools
npm install -g uglify-js clean-css-cli

# Minify files
uglifyjs assets/js/main.js -o assets/js/main.min.js
cleancss assets/css/style.css -o assets/css/style.min.css
```

## 🚀 Deployment

### Production Deployment

**1. Environment Setup:**
```php
// includes/config.php
define('ENVIRONMENT', 'production');

if (ENVIRONMENT === 'production') {
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
```

**2. Database Migration:**
```bash
# Production database yaratish
mysql -u root -p -e "CREATE DATABASE online_student_prod"

# Data export/import
mysqldump -u root -p online_student_db > backup.sql
mysql -u root -p online_student_prod < backup.sql
```

**3. File Transfer:**
```bash
# rsync bilan
rsync -avz --exclude='.git' ./ user@server:/var/www/html/

# SCP bilan
scp -r online-student-system/ user@server:/var/www/html/
```

### Backup Strategy

**Automated backup script:**
```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"

# Database backup
mysqldump -u root -p online_student_db > $BACKUP_DIR/db_$DATE.sql

# Files backup  
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/online-student-system

# Keep only last 7 days
find $BACKUP_DIR -name "*.sql" -older +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -older +7 -delete
```

**Crontab entry:**
```bash
# Daily backup at 2 AM
0 2 * * * /path/to/backup.sh
```

## 📈 Monitoring

### Basic Monitoring

**Log rotation:**
```bash
# /etc/logrotate.d/online-student-system
/var/www/html/online-student-system/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
}
```

**Health check script:**
```php
// health-check.php
<?php
$checks = [
    'database' => check_database(),
    'uploads' => is_writable('assets/uploads/'),
    'disk_space' => (disk_free_space('.') > 1024*1024*100) // 100MB
];

echo json_encode($checks);

function check_database() {
    try {
        require_once 'includes/config.php';
        $pdo->query('SELECT 1');
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
```

## 🆘 Support

### Getting Help

**Documentation:**
- Bu README fayl
- Code comments
- Database schema documentation

**Community:**
- GitHub Issues
- Stack Overflow
- PHP communities

**Professional Support:**
- Email: support@example.com
- Phone: +998 90 123 45 67

### Bug Reporting

Issue ochishda quyidagilarni kiriting:

1. **Environment details:**
    - PHP version
    - MySQL version
    - OS version
    - Browser version

2. **Steps to reproduce:**
    - Qanday qilib xato yuzaga kelganini batafsil yozing

3. **Expected vs Actual behavior:**
    - Nima bo'lishi kerak edi
    - Aslida nima bo'ldi

4. **Error logs:**
    - PHP error logs
    - Apache error logs
    - Browser console errors

---

**🎉 Tabriklaymiz! Loyihangiz tayyor!**

Bu guide boyicha loyihani to'liq sozlab, ishga tushira olasiz. Qo'shimcha savollar bo'lsa, documentation ni tekshiring yoki support ga murojaat qiling.