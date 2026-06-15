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

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## API Documentation

Bagian ini menjelaskan endpoint yang tersedia pada aplikasi dan contoh cara menggunakannya.

Base URL
- Local (development): `http://localhost` (sesuaikan port dan host Anda, mis. `http://127.0.0.1:8000`)

Headers Umum
- `Content-Type: application/json` untuk request JSON.
- `Authorization: Bearer <access_token>` untuk endpoint yang membutuhkan autentikasi (Sanctum token).

Autentikasi
- POST `/login` — Login pengguna.
	- Body (JSON): `email`, `password`.
	- Response: `access_token`, `token_type`, `expires_in`.
- POST `/register` — Registrasi pengguna baru.
	- Body (JSON): `first_name`, `last_name`, `email`, `password`, `password_confirmation`.
	- Response: user data + `access_token`.

Endpoint (butuh autentikasi setelah login)

- Users
	- GET `/users/profile` — Dapatkan profil pengguna yang sedang login.
	- GET `/users` — Daftar semua pengguna.
	- PUT `/users` — Update data pengguna (fields: `first_name`, `last_name`, `email`, `phone`, `province`, `district`, `sub_district`, `village`, `neighborhood_unit`).
	- GET `/users/first-name/{name}` — Cari pengguna berdasarkan nama depan.
	- GET `/users/last-name/{name}` — Cari pengguna berdasarkan nama belakang.
	- GET `/users/{id}` — Dapatkan pengguna berdasarkan ID.

- Categories
	- GET `/categories` — Daftar kategori.
	- POST `/categories` — Buat kategori baru. Body: `title` (required).
	- GET `/categories/slug/{slug}` — Dapatkan kategori berdasarkan slug.
	- GET `/categories/{id}` — Dapatkan kategori berdasarkan ID.
	- DELETE `/categories/{id}` — Hapus kategori.

- Posts
	- GET `/posts` — Daftar semua posting.
	- GET `/posts/total` — Total postingan per user (atau metrik terkait).
	- POST `/posts/request` — Buat posting tipe `request`.
		- Fields (ringkasan): `title`, `type` (`request`), `description`, `category_id`, `min_price`, `max_price`, `deadline`, `method_service`, `province`, `regency`, `district`, `village`, `address_details`, `images` (file[]), `location` (latitude/longitude), `published_until`.
	- POST `/posts/service` — Buat posting tipe `service`.
		- Fields (ringkasan): `title`, `type` (`service`), `description`, `category_id`, `base_price`, `time_start`, `time_end`, `portfolio_url` (opsional), `experience_years`, alamat, `status`, `images` (file[]), `location`.
	- GET `/posts/request` — Ambil semua posting request dengan detail.
	- GET `/posts/service` — Ambil semua posting service dengan detail.
	- DELETE `/posts/{id}` — Hapus posting (body: `id` sebagai UUID).
	- POST `/posts/apply` — Apply untuk job pada post. Body: `post_id`, `offered_price`.
	- POST `/posts/book-helper` — Booking helper/service. Body: `post_id`, `offered_price`.

- Offers
	- GET `/offers/post/{postId}` — Dapatkan semua offer untuk sebuah post.

- Messages
	- POST `/offers/{offerId}/messages` — Kirim pesan terkait offer.
		- Body: `content` (required), `type` (opsional, mis. `text`).

Contoh Penggunaan (curl)

- Login dan simpan token

```bash
curl -X POST http://localhost/api/login \
	-H "Content-Type: application/json" \
	-d '{"email":"user@example.com","password":"secret"}'
```

Response sukses contoh:

```json
{
	"success": true,
	"data": {
		"access_token": "<TOKEN>",
		"token_type": "Bearer",
		"expires_in": 1440
	}
}
```

- Akses endpoint yang dilindungi (contoh: profile)

```bash
curl -X GET http://localhost/api/users/profile \
	-H "Authorization: Bearer <TOKEN>" \
	-H "Accept: application/json"
```

- Contoh membuat kategori

```bash
curl -X POST http://localhost/api/categories \
	-H "Authorization: Bearer <TOKEN>" \
	-H "Content-Type: application/json" \
	-d '{"title":"Cleaning"}'
```

Catatan
- Untuk endpoint yang menerima upload file (contoh: `images` pada pembuatan post), gunakan `multipart/form-data` dan kirim file melalui form field `images[]`.
- Validasi dan format field lebih lengkap dapat dilihat di controller terkait (`app/Http/Controllers/Api/*`) dan service layer (`app/Service/*`).

Jika Anda ingin, saya bisa menambahkan contoh request/response lengkap untuk endpoint tertentu (mis. membuat post `request`/`service`).
