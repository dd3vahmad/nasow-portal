# NASOW – Backend Server

This is the backend API server for the **NASOW Website**, built with [Laravel](https://laravel.com/) and powered by a MySQL database. It handles authentication, user and admin management, service worker profiles, document handling, notifications, and other core business logic.

## Tech Stack

-   **Framework:** Laravel 12+
-   **Language:** PHP 8.4+
-   **Database:** MySQL 8+
-   **Authentication:** Sanctum (API token-based)
-   **Mail:** Laravel Mail with Markdown templates
-   **File Storage:** Cloudinary
-   **Others:** Laravel Scheduler, Queues (Redis or DB), Validation, Logging, Rate Limiting

## Project Structure

```
├── app/ # Application logic
├── database/ # Migrations, seeders, factories
├── routes/ # Route files (api.php, web.php)
├── config/ # Config files
├── resources/views/ # Blade + mail templates
├── storage/ # Logs, user uploads, compiled views
├── tests/ # Unit and feature tests
├── public/ # Publicly accessible assets
└── .env.example # Sample environment configuration

```

## Getting Started

### 1. Clone the Repository

```bash
git clone https://github.com/dd3vahmad/nasow-portal.git
cd nasow-portal

```

### 2. Install Dependencies

```bash
composer install

```

### 3. Configure Environment

```bash
cp .env.example .env

-- Update the variables

DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null

```

### 4. Generate Application Key

```bash
php artisan key:generate

```

### 5. Run migrations and Seeders

```bash
php artisan migrate --seed

```

### 6. Start the Develoment Server

```bash
php artisan serve
```
