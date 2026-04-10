<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.


---


# 🐓 Sabong Betting System

A comprehensive Laravel-based management system for cockfighting (sabong) betting operations

## Features

- 🎯 **Owner Dashboard** - Real-time statistics and monitoring
- 👥 **User Management** - Admin and teller account management
- 🐓 **Fight Management** - Create, manage, and track cockfighting events
- 💰 **Betting System** - Place and track bets with automated payout calculations
- 📊 **Financial Tracking** - Commission tracking and earnings reports
- 📋 **Audit Logs** - Complete audit trail of all system activities
- 🌓 **Dark Mode** - Light and dark theme support
- 📱 **Responsive Design** - Works on desktop and mobile devices

## Installation

### Requirements

- PHP 8.1 or higher
- Composer
- Node.js 16+ & npm/yarn
- MySQL or compatible database
- Git

### Step 1: Clone the Repository

```bash
git clone <repository-url>
cd sabong-betting-system
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Environment Setup

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Edit `.env` and set your configuration:

- `APP_NAME=Sabong Betting System`
- `APP_URL=http://localhost`
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=sabong_db`
- `DB_USERNAME=root`
- `DB_PASSWORD=` (your password)

### Step 4: Generate Application Key

```bash
php artisan key:generate
```

### Step 5: Create Database

Create a new database for the application:

```bash
# Using MySQL directly
mysql -u root -p -e "CREATE DATABASE sabong_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Step 6: Run Migrations

```bash
php artisan migrate
```

### Step 7: Install JavaScript Dependencies

```bash
npm install
# or
yarn install
```

### Step 8: Build Assets

Build the front-end assets (CSS & JavaScript):

```bash
npm run build
# or
yarn build
```

For development with hot reload:

```bash
npm run dev
# or
yarn dev
```

### Step 9: Start the Application

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

### Step 10: Access Owner Panel

Navigate to `/manage` to access the owner login page.

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Owner/          # Owner panel controllers
│   ├── Requests/           # Form validation requests
│   └── Middleware/         # Custom middleware
├── Models/                 # Database models (User, Fight, Bet, Payout, etc.)
├── Services/               # Business logic services
└── Events/                 # Application events

database/
├── migrations/             # Database schemas
├── factories/              # Model factories for testing
└── seeders/                # Database seeders

resources/
├── views/
│   ├── layouts/            # Layout templates
│   └── owner/              # Owner panel views
├── css/                    # Tailwind CSS
└── js/                     # JavaScript files

routes/
├── web.php                 # Web routes
├── api.php                 # API routes (if applicable)
└── auth.php                # Authentication routes
```

## Key Technologies

- **Laravel 11** - PHP web framework
- **Tailwind CSS** - Utility-first CSS framework
- **Alpine.js** - JavaScript framework for interactivity
- **MySQL** - Database management
- **Vite** - Frontend build tool
- **Blade** - Laravel templating engine

## Configuration

### Database Connection

Update your `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sabong_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Running Queue Worker (if applicable)

If the application uses queued jobs:

```bash
php artisan queue:work
```

### Broadcasting Setup

The application uses Laravel Reverb for real-time updates. Ensure it's configured in `.env`:

```env
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
```

## Development

### Running Tests

```bash
php artisan test
```

### Code Style

```bash
php artisan pint
```

### Database Seeding

To seed the database with sample data:

```bash
php artisan db:seed
```

## Troubleshooting

### Dark Mode Not Working

1. Ensure `darkMode: 'class'` is set in `tailwind.config.js`
2. Rebuild CSS: `npm run build`
3. Clear caches: `php artisan config:clear && php artisan cache:clear`

### Migration Issues

If migrations fail, reset the database:

```bash
php artisan migrate:reset
php artisan migrate
```

### Permission Issues

If you encounter permission issues on Linux/Mac:

```bash
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## Support & Contributing

For issues or feature requests, please open an issue on GitHub.


## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
