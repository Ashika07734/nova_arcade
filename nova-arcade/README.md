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

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Deployment

This app is a Laravel 11 project with Vite assets, database-backed queues/sessions, scheduled jobs, and Reverb websocket support. A production server needs the web app, a queue worker, and a scheduler cron. If you enable live broadcasting in production, add a Reverb process as well.

### Render

For Render, use the included [render.yaml](render.yaml) blueprint and [Dockerfile](Dockerfile). The blueprint creates a Postgres database plus three services:

- `nova-arcade-web` for the public Laravel app
- `nova-arcade-worker` for queued jobs
- `nova-arcade-scheduler` for Laravel's scheduled tasks

Render will prompt you for `APP_KEY` and `APP_URL` the first time you create the blueprint. After the first deploy, the web service runs migrations before start-up and the worker/cron services reuse the same application key.

The current blueprint is a safe first pass for deploying the main application loop. If you want live websocket broadcasting on Render as well, I can add a dedicated Reverb service next.

### Production checklist

1. Install server dependencies:
	- PHP 8.2+
	- Composer
	- Node.js 18+ or 20+
	- A database such as MySQL or PostgreSQL
2. Copy `.env.example` to `.env` and set production values:
	- `APP_ENV=production`
	- `APP_DEBUG=false`
	- `APP_URL=https://your-domain.com`
	- database credentials
	- `QUEUE_CONNECTION=database`
	- `CACHE_STORE=database`
	- `SESSION_DRIVER=database`
	- Reverb settings if you are using websockets in production
3. Install and build the app:
	- `composer install --no-dev --optimize-autoloader`
	- `npm ci`
	- `npm run build`
4. Prepare Laravel:
	- `php artisan key:generate`
	- `php artisan storage:link`
	- `php artisan migrate --force`
	- `php artisan config:cache`
	- `php artisan route:cache`
	- `php artisan view:cache`
5. Run the long-lived services:
	- queue worker: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`
	- scheduler: add a cron entry for `php artisan schedule:run` every minute
	- Reverb: `php artisan reverb:start`

### Suggested server layout

- Web server: Nginx or Apache pointing at the `public/` directory.
- Process manager: Supervisor or systemd for the queue worker and Reverb process.
- Cron: one entry per minute for Laravel scheduling.

### Notes for this repo

- The default `.env.example` uses SQLite and local logging for development. Swap those settings for your production database and logging strategy before going live.
- Game cleanup, leaderboard updates, and daily missions are scheduled in the console kernel, so skipping the cron job will leave those tasks inactive.
- Reverb is the default broadcast driver in this codebase, so real-time features depend on the websocket service being available.
- The included Render blueprint does not start Reverb yet, so broadcast-backed features will need a follow-up service before production use.
