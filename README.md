# BCS Joining Documents

BCS declaration ও joining-letter document generator। অ্যাপটি PHP 8.1+, MySQL এবং responsive HTML/CSS/JavaScript দিয়ে তৈরি। Admin panel থেকে সবার জন্য একই BCS তথ্য—যেমন BCS নম্বর, পরীক্ষার সাল, যোগদানের তারিখ, প্রজ্ঞাপনের তারিখ, PSC তারিখ এবং ক্যাডার পরিচিতি নম্বর দেখানো/লুকানো—পরিবর্তন করা যায়।

## প্রয়োজনীয় সফটওয়্যার

- PHP 8.1 বা পরবর্তী সংস্করণ
- Apache অথবা অন্য PHP-compatible web server
- MySQL 5.7+/MariaDB 10.4+
- PHP `pdo_mysql` extension
- Browser: Chrome, Edge, Firefox অথবা Safari

XAMPP বা WAMP ব্যবহার করলে Apache ও MySQL চালু করলেই স্থানীয়ভাবে অ্যাপ চালানো যাবে। Production server-এ PHP ও MySQL credentials অবশ্যই নিরাপদভাবে configure করুন।

## XAMPP দিয়ে দ্রুত চালু করা

### ১. Project directory রাখুন

সবচেয়ে সহজ পদ্ধতিতে project folder-টি XAMPP-এর web root-এ রাখুন:

```text
C:\xampp\htdocs\bcs46
```

যদি project-এর বর্তমান location `B:\bcs46` রাখতে চান, তাহলে Apache Alias/VirtualHost configure করে ওই folder-কে serve করতে হবে।

### ২. Apache ও MySQL চালু করুন

XAMPP Control Panel থেকে:

1. Apache — Start
2. MySQL — Start

### ৩. Database তৈরি ও schema import করুন

phpMyAdmin খুলুন:

```text
http://localhost/phpmyadmin
```

তারপর [database/schema.sql](B:/bcs46/database/schema.sql) import করুন। এই schema `bcs_joining` database এবং নিচের table তৈরি করে:

- `site_settings` — website-এর shared settings
- `admin_users` — admin login account

phpMyAdmin-এ import করার বিকল্প পদ্ধতি:

```bash
mysql -u root -p < database/schema.sql
```

Password না থাকলে XAMPP-এর default installation-এ `-p` বাদ দিয়ে চালানো যায়:

```bash
mysql -u root < database/schema.sql
```

### Database সম্পূর্ণ reset করার command

পুরোনো database, table ও data মুছে নতুন database, schema, default settings এবং configured admin account তৈরি করতে project root থেকে চালান:

```bash
php database/reset.php
```

Commandটি প্রথমে `RESET` confirmation চাইবে। Non-interactive/automatic reset-এর জন্য:

```bash
php database/reset.php --force
```

এটি destructive command—শুধু backup নেওয়ার পর ব্যবহার করুন। Database name ও admin credentials [config/config.php](B:/bcs46/config/config.php) থেকে নেওয়া হবে।

### ৪. Database configuration করুন

[config/config.example.php](B:/bcs46/config/config.example.php) কপি করে একই folder-এ [config/config.php](B:/bcs46/config/config.php) তৈরি করুন। এরপর database settings যাচাই করুন:

```php
'db' => [
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => 'bcs_joining',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
],
```

`config/config.php` source control-এ commit করবেন না। এতে database password এবং admin credential থাকতে পারে। এই ফাইলটি `.gitignore`-এ রাখা আছে।

### ৫. Website খুলুন

```text
http://localhost/bcs46/
```

যদি project সরাসরি `htdocs`-এ অন্য নামে রাখা হয়, URL-এর `bcs46` অংশটি সেই folder name দিয়ে বদলাবেন।

## Routing / URL তালিকা

Apache-এর `mod_rewrite` চালু থাকলে friendly route ব্যবহার করুন। ফাইলের নাম সরাসরি URL-এ না লিখে নিচের route-গুলো ব্যবহার করা যাবে।

| URL | File | কাজ |
|---|---|---|
| `/bcs46/` অথবা `/bcs46/declaration` | [index.php](B:/bcs46/index.php) | অঙ্গীকারনামা ও declaration generator |
| `/bcs46/joining-letter` | [joining-letter.php](B:/bcs46/joining-letter.php) | যোগদানপত্র generator; Word/PDF download ও preview |
| `/bcs46/admin` | [admin.php](B:/bcs46/admin.php) | Admin login এবং shared settings management |
| `/bcs46/logout` | [logout.php](B:/bcs46/logout.php) | Admin session logout |
| `/bcs46/database/schema.sql` | [schema.sql](B:/bcs46/database/schema.sql) | Database setup file; browser থেকে serve করার জন্য নয় |

`/bcs46/declaration` route-টি মূল page-এর alias। `/bcs46/` homepage হিসেবে একই generator খুলবে।

Friendly routes কাজ করার জন্য Apache-এ `mod_rewrite` enabled থাকতে হবে এবং project folder-এর `.htaccess` ব্যবহার করার অনুমতি থাকতে হবে। XAMPP-এ সাধারণত এটি আগে থেকেই enabled থাকে। Apache configuration-এ প্রয়োজনে নিচের সেটিংস নিশ্চিত করুন:

```apache
AllowOverride All
```

যদি `mod_rewrite` বা `.htaccess` support না থাকে, তাহলে compatibility-এর জন্য সরাসরি PHP entrypoint-ও কাজ করবে—যেমন `index.php`, `joining-letter.php`, `admin.php` এবং `logout.php`।

মূল declaration page এবং joining-letter page—দুটিই একই `site_settings` table থেকে data নেয়। তাই admin panel-এ পরিবর্তন করলে পরবর্তী visitor এবং generated document-এ সেই data ব্যবহার হবে।

## Responsive sidebar ও একবারের তথ্য প্রবেশ

সব public page-এ responsive sidebar আছে। Sidebar থেকে সরাসরি homepage, joining-letter এবং admin panel-এ যাওয়া যায়। Desktop-এ sidebar দেখা যায়; ফোন বা ছোট tablet-এ menu button চাপলে sidebar খুলবে।

Declaration form-এ দেওয়া সাধারণ candidate তথ্য browser-এর Local Storage-এ shared profile হিসেবে সংরক্ষিত হয়। তাই একই browser-এ:

- নাম
- মেধাক্রম
- রেজিস্ট্রেশন নম্বর
- ক্যাডার
- পদ

একবার দিলেই joining-letter form-এ স্বয়ংক্রিয়ভাবে বসে যাবে। Joining-letter page থেকে এই তথ্য পরিবর্তন করলেও declaration form-এ তা পুনরায় ব্যবহার করা যাবে। এই profile server বা database-এ upload হয় না।

## Admin panel setup

Admin panel খুলুন:

```text
http://localhost/bcs46/admin
```

Login করার পর নিচের তথ্য পরিবর্তন করা যায়:

- কততম BCS
- BCS পরীক্ষার সাল
- যোগদানের তারিখ
- সরকারি প্রজ্ঞাপনের তারিখ
- PSC/কমিশন সংক্রান্ত তারিখ
- Generated document-এ ক্যাডার পরিচিতি নম্বর দেখানো হবে কি না
- Developer profile-এর নাম, পরিচয়, কর্মস্থল ও BCS পরিচিতি
- WhatsApp/Facebook link এবং developer image upload

Admin panel-এর Developer section-এ প্রতিটি পরিবর্তনের live preview দেখা যাবে। Image upload-এর সর্বোচ্চ size ২ MB; JPG, PNG ও WebP সমর্থিত। Developer settings `site_settings` table-এ সংরক্ষিত হয়। পুরোনো database হলে application startup-এ প্রয়োজনীয় column migration স্বয়ংক্রিয়ভাবে চালায়।

এই project-এ [config/config.php](B:/bcs46/config/config.php)-তে admin email ও password hash configured আছে। প্রথম request-এ application ওই credential দিয়ে `admin_users` table-এর প্রথম admin account create/update করে। Password সরাসরি database-এ plain text হিসেবে রাখা হয় না।

নতুন server-এ [config/config.php](B:/bcs46/config/config.php)-তে admin credential না থাকলে application চালুর আগে নিরাপদ password hash সেট করুন। PHP দিয়ে hash তৈরি করা যায়:

```bash
php -r "echo password_hash('আপনার-নতুন-পাসওয়ার্ড', PASSWORD_DEFAULT), PHP_EOL;"
```

উৎপন্ন hash-টি `password_hash` value হিসেবে রাখুন।

## Environment variables দিয়ে configuration

Production বা hosting server-এ [config/config.php](B:/bcs46/config/config.php)-তে secret না রেখে environment variable ব্যবহার করা যায়:

```text
BCS_DB_HOST=127.0.0.1
BCS_DB_PORT=3306
BCS_DB_NAME=bcs_joining
BCS_DB_USER=root
BCS_DB_PASS=your-database-password
BCS_ADMIN_EMAIL=admin@example.com
BCS_ADMIN_PASSWORD_HASH=your-password-hash
```

[config/config.example.php](B:/bcs46/config/config.example.php) এই environment variable-গুলো পড়ার জন্য প্রস্তুত।

## Developer profile configuration

Developer পরিচিতি sidebar-এর মাধ্যমে খোলা যায়। Profile image হিসেবে [developer.jpeg](B:/bcs46/public/assets/developer.jpeg) ব্যবহার করা হয়েছে। Facebook button দেখাতে [config/config.php](B:/bcs46/config/config.php)-তে সঠিক profile/page URL বসান, অথবা environment variable ব্যবহার করুন:

```text
BCS_DEVELOPER_FACEBOOK=https://www.facebook.com/your-profile
```

URL না দেওয়া থাকলে Facebook button গোপন থাকবে; ভুল বা অনুমানভিত্তিক profile-এ visitor পাঠানো হবে না।

## Project structure

```text
bcs46/
├── index.php                         # Main declaration generator route
├── joining-letter.php                # Joining-letter generator route
├── admin.php                         # Admin login/settings route
├── logout.php                        # Logout route
├── app/
│   └── bootstrap.php                 # Session, PDO, settings ও auth helpers
├── config/
│   ├── config.php                    # Local secrets; commit করবেন না
│   └── config.example.php            # Configuration template
├── database/
│   └── schema.sql                    # MySQL schema ও seed data
├── resources/
│   ├── document-generator.html       # Declaration generator UI
│   └── joining-letter.html           # Joining-letter UI ও document builder
├── public/assets/                    # Favicon ও social preview image
│   ├── navigation.css                # Responsive sidebar styling
│   ├── navigation.js                 # Shared route navigation
│   └── shared-profile.js             # Declaration/joining form data sync
├── legacy/                           # পুরোনো Node/static preview files
├── .htaccess                         # Directory index ও access protection
└── README.md                         # এই setup guide
```

## Node.js সম্পর্কে

বর্তমান production app PHP + MySQL ভিত্তিক, তাই সাধারণ ব্যবহারের জন্য Node.js বা `npm install` প্রয়োজন নেই। পুরোনো preview/testing-এর জন্য [legacy/node-preview/server.js](B:/bcs46/legacy/node-preview/server.js) রাখা আছে। এটি production route নয়।

## Common problems ও সমাধান

### `Unknown database` বা database connection error

- MySQL চালু আছে কি না যাচাই করুন।
- [config/config.php](B:/bcs46/config/config.php)-এর database name, user, password ও port যাচাই করুন।
- [database/schema.sql](B:/bcs46/database/schema.sql) আবার import করুন।

### `could not find driver`

PHP configuration-এ `pdo_mysql` চালু করুন। XAMPP-এ `C:\xampp\php\php.ini` খুলে নিচের extension uncomment করে Apache restart করুন:

```ini
extension=pdo_mysql
```

### Apache page খুলছে না

- Apache running কি না দেখুন।
- Port 80 অন্য কোনো service ব্যবহার করলে Apache-এর port পরিবর্তন করে URL-এ সেই port ব্যবহার করুন, যেমন `http://localhost:8080/bcs46/`।
- Project folder অবশ্যই Apache-এর document root বা configured Alias-এর মধ্যে থাকতে হবে।

### Admin login কাজ করছে না

- [config/config.php](B:/bcs46/config/config.php)-এর admin email ও password hash যাচাই করুন।
- `admin_users` table-এর প্রথম account application startup-এ sync হয়।
- Password পরিবর্তনের পর browser session থেকে logout করে আবার login করুন।

### Bengali text বা date সঠিক দেখাচ্ছে না

Database ও HTML দুটিতেই `utf8mb4` ব্যবহার করা হচ্ছে কি না যাচাই করুন। Browser-এর encoding UTF-8 রাখুন।

## Production security checklist

- `config/config.php` public download হতে দেবেন না। Project-এর `.htaccess` ইতোমধ্যে sensitive folders block করে।
- HTTPS চালু করুন।
- MySQL root account-এর বদলে application-এর জন্য আলাদা limited user ব্যবহার করুন।
- Strong admin password এবং secure password hash ব্যবহার করুন।
- নিয়মিত database backup রাখুন।
- Production server-এ error display বন্ধ রাখুন এবং error log সংরক্ষণ করুন।

