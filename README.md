<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## CloudKazi Elimu student and parent portals

### Portal features

- Student and parent portals display fee account summaries and recorded payment history. Receipt pages include a PDF download and show a payment-time balance only for payments whose balance snapshots were recorded.
- The student and parent diary combines published school announcements, class assignments, examinations, and published exam timetables. Announcement read state is saved per user; assignment downloads are authorized against the student's class and school.
- Academic performance pages filter recorded results by academic year, term, and examination. CBC achievement levels remain separate from 8-4-4 numerical marks; missing results are not presented as zero.
- Student practice is school-managed. Authorized staff create chapters and questions, and students can only access published content for their class. The application does not seed or claim to provide an official question bank.
- Parent child links and portal data are restricted to the authenticated parent's school.

### Database changes and deployment

The portal additions use additive migrations:

- `2026_10_25_000001_create_student_learning_practice_tables.php` creates learning chapter, question, attempt, progress, and announcement-read tables.
- `2026_10_25_000002_add_fee_payment_balance_snapshots.php` adds nullable `balance_before` and `balance_after` columns to `fee_payments`.

Back up the target database and verify the environment/database name before applying migrations with `php artisan migrate`. Do not use `migrate:fresh` or `migrate:refresh` on a database containing school or user records. Existing payments keep null snapshots because their historical balances cannot be reconstructed reliably; new payments record the outstanding invoice balance immediately before and after settlement.

### Portal routes and access

Routes are registered in `routes/web.php` and use the application's authenticated, active-school context and `school.feature` middleware:

- Student: `/student/diary`, `/student/performance`, and `/student/practice`
- Parent: `/parent/diary` and `/parent/performance`
- Staff content management: `/practice/content` (chapter and question creation/editing)
- Diary resources: announcement-read and assignment-attachment routes
- Receipts: `/finance/receipt/{id}`; add `?download=1` to download its PDF

Controllers enforce the additional record-level rules: students can only practice within their own class; staff content access is limited to their school and, for teachers, assigned subjects/classes; parents can only select linked children belonging to their school; and assignment downloads require a published assignment available to that child.

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

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# cloudkazi-elimu
School management system
