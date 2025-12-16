# Limit Order Exchange – Backend (Laravel)

## Requirements
- PHP 8.2+
- MySQL / PostgreSQL
- Composer

## Setup
```bash
git clone <repo>
cd backend
composer install
cp .env.example .env
php artisan key:generate

Configure .env:
Database
Pusher credentials

php artisan migrate --seed
php artisan serve

Authentication
Laravel Sanctum (cookie-based)
Core Features

Atomic limit-order matching

Balance & asset locking

1.5% commission

Real-time trade broadcasting (Pusher)

Refresh-safe API design

Test Accounts

buyer@test.com
password123

seller@test.com
password123
