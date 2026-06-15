## API Documentation (Lengkap)

Dokumentasi berikut memberikan detail endpoint, contoh request (curl), contoh response sukses dan error, serta catatan penggunaan untuk endpoint utama aplikasi.
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

## API Documentation (Lengkap)

Dokumentasi berikut memberikan detail endpoint, contoh request (curl), contoh response sukses dan error, serta catatan penggunaan untuk endpoint utama aplikasi.

Base URL
- Local (development): `http://localhost` (sesuaikan port/host, mis. `http://127.0.0.1:8000`)

Header Umum
- `Accept: application/json`
- `Content-Type: application/json` untuk request JSON biasa.
- `Authorization: Bearer <ACCESS_TOKEN>` untuk endpoint yang butuh autentikasi (Sanctum).

## Format Respons

Ada dua pola respons utama dari API ini (lihat trait `app/Traits/ServiceResponse.php`):

### Contoh Request / Response Lengkap

1) Register (POST `/api/register`)

Request (JSON):

```json
{
	"first_name": "Budi",
	"last_name": "Santoso",
	"email": "budi@example.com",
	"password": "secret123",
	"password_confirmation": "secret123"
}
```

Curl:

```bash
curl -X POST http://localhost/api/register \
	-H "Content-Type: application/json" \
	-d '{"first_name":"Budi","last_name":"Santoso","email":"budi@example.com","password":"secret123","password_confirmation":"secret123"}'
```

Response (201):

```json
{
	"success": true,
	"code": 201,
	"message": "registration successful",
	"access_token": "<TOKEN>",
	"token_type": "Bearer",
	"expires_in": 1440,
	"user": {
		"id": "...",
		"first_name": "Budi",
		"last_name": "Santoso",
		"email": "budi@example.com",
		// other user fields
	}
}
```

2) Login (POST `/api/login`)

Request (JSON):

```json
{
	"email": "budi@example.com",
	"password": "secret123"
}
```

Curl:

```bash
curl -X POST http://localhost/api/login \
	-H "Content-Type: application/json" \
	-d '{"email":"budi@example.com","password":"secret123"}'
```

Response (200):

```json
{
	"success": true,
	"code": 200,
	"message": "login successful",
	"access_token": "<TOKEN>",
	"token_type": "Bearer",
	"expires_in": 1440
}
```

3) Get Profile (GET `/api/users/profile`) — protected

Curl:

```bash
curl -X GET http://localhost/api/users/profile \
	-H "Authorization: Bearer <TOKEN>" \
	-H "Accept: application/json"
```

Response (200):

```json
{
	"success": true,
	"code": 200,
	"message": "success",
	"data": {
		"id": "...",
		"first_name": "Budi",
		"last_name": "Santoso",
		"email": "budi@example.com",
		// other profile fields
	}
}
```

4) Buat Kategori (POST `/api/categories`)

Curl:

```bash
curl -X POST http://localhost/api/categories \
	-H "Authorization: Bearer <TOKEN>" \
	-H "Content-Type: application/json" \
	-d '{"title":"Cleaning"}'
```

Response (200):

```json
{
	"success": true,
	"code": 200,
	"message": "success",
	"data": {
		"id": "...",
		"title": "Cleaning",
		"slug": "cleaning"
	}
}
```

5) Buat Post Tipe `request` (POST `/api/posts/request`) — contoh multipart (upload gambar)

Field ringkasan (form-data/multipart):
- `title` (string)
- `type` = `request`
- `description` (string)
- `category_id` (uuid)
- `min_price` (numeric)
- `max_price` (numeric)
- `deadline` (date)
- `method_service`, `province`, `regency`, `district`, `village`, `address_details`
- `images[]` (file) — hingga 5 file
- `location[latitude]`, `location[longitude]`
- `published_until` (date)

Curl (multipart):

```bash
curl -X POST http://localhost/api/posts/request \
	-H "Authorization: Bearer <TOKEN>" \
	-F "title=Butuh jasa bersih rumah" \
	-F "type=request" \
	-F "description=Membersihkan 3 kamar" \
	-F "category_id=<CATEGORY_UUID>" \
	-F "min_price=100000" \
	-F "max_price=200000" \
	-F "deadline=2026-07-01" \
	-F "method_service=onsite" \
	-F "province=Jawa Barat" \
	-F "regency=Bogor" \
	-F "district=.." \
	-F "village=.." \
	-F "address_details=Jl. Contoh No.1" \
	-F "images[]=@/path/to/img1.jpg" \
	-F "images[]=@/path/to/img2.jpg" \
	-F "location[latitude]=-6.6" \
	-F "location[longitude]=106.7" \
	-F "published_until=2026-07-10"
```

Response (200):

```json
{
	"success": true,
	"code": 200,
	"message": "success",
	"data": {
		"id": "<POST_UUID>",
		"title": "Butuh jasa bersih rumah",
		"type": "request",
		// other post details
	}
}
```

6) Buat Post Tipe `service` (POST `/api/posts/service`) — multipart, fields serupa namun berbeda beberapa field seperti `base_price`, `time_start`, `time_end`, `experience_years`, `status`.

7) Apply For Job (POST `/api/posts/apply`)

Request (JSON):

```json
{
	"post_id": "<POST_UUID>",
	"offered_price": 150000
}
```

Curl:

```bash
curl -X POST http://localhost/api/posts/apply \
	-H "Authorization: Bearer <TOKEN>" \
	-H "Content-Type: application/json" \
	-d '{"post_id":"<POST_UUID>","offered_price":150000}'
```

Response (200):

```json
{
	"success": true,
	"code": 200,
	"message": "success",
	"data": {
		"offer_id": "<OFFER_UUID>",
		"post_id": "<POST_UUID>",
		"offered_price": 150000,
		// other offer fields
	}
}
```

8) Book Helper (POST `/api/posts/book-helper`) — sama format dengan apply.

9) Get Offers For Post (GET `/api/offers/post/{postId}`)

Curl:

```bash
curl -X GET http://localhost/api/offers/post/<POST_UUID> \
	-H "Authorization: Bearer <TOKEN>"
```

Response (200):

```json
{
	"success": true,
	"code": 200,
	"message": "success",
	"data": [ /* array of offers */ ]
}
```

10) Send Message (POST `/api/offers/{offerId}/messages`)

Request (JSON):

```json
{
	"content": "Halo, saya tertarik",
	"type": "text"
}
```

Curl:

```bash
curl -X POST http://localhost/api/offers/<OFFER_UUID>/messages \
	-H "Authorization: Bearer <TOKEN>" \
	-H "Content-Type: application/json" \
	-d '{"content":"Halo, saya tertarik","type":"text"}'
```

Response (200): contoh pesan

```json
{
	"id": "<MESSAGE_UUID>",
	"offer_id": "<OFFER_UUID>",
	"user_id": "<USER_UUID>",
	"content": "Halo, saya tertarik",
	"type": "text",
	"created_at": "2026-06-16T12:34:56Z"
}
```

## Error Handling Singkat

- 401 Unauthorized: token tidak dikirim atau tidak valid.
- 403 Forbidden: user tidak berhak mengakses resource tertentu.
- 404 Not Found: resource tidak ditemukan.
- 422 Unprocessable Entity: error validasi input (lihat contoh di atas).

---

Jika Anda ingin, saya bisa:
- Menambahkan `Postman` collection atau `HTTPie` examples.
- Membuat contoh payload lengkap untuk `service` post.
- Menulis skrip singkat untuk meng-generate token dan memanggil endpoint secara otomatis.

Beritahu endpoint mana yang ingin Anda jadikan contoh lengkap berikutnya.
- Semua response service mengikuti struktur `ServiceResponse`:

Success (non-auth):

```json
{
	"success": true,
	"code": 200,
	"message": "success",
	"data": { /* objek/data tergantung endpoint */ }
}
```

Auth response (login/register):

```json
{
	"success": true,
	"code": 200,
	"message": "login successful",
	"access_token": "<TOKEN>",
	"token_type": "Bearer",
	"expires_in": 1440,
	"user": { /* user object on register */ }
}
```

Error response contoh:

```json
{
	"success": false,
	"code": 400,
	"message": "validation error",
	"data": { /* optional details */ }
}
```

1) Autentikasi
- POST `/api/login`
	- Body (JSON): `email` (string), `password` (string)
	- Contoh:

```bash
curl -X POST http://localhost/api/login \
	-H "Accept: application/json" \
	-H "Content-Type: application/json" \
	-d '{"email":"user@example.com","password":"secret"}'
```

- POST `/api/register`
	- Body (JSON): `first_name`, `last_name`, `email`, `password`, `password_confirmation`.
	- Contoh:

```bash
curl -X POST http://localhost/api/register \
	-H "Accept: application/json" \
	-H "Content-Type: application/json" \
	-d '{"first_name":"Budi","last_name":"Santoso","email":"budi@example.com","password":"secret123","password_confirmation":"secret123"}'
```

2) Penggunaan token
- Setelah login/register, ambil `access_token` dari response. Sertakan header `Authorization: Bearer <TOKEN>` pada semua request yang dilindungi.

3) Users
- GET `/api/users/profile` — Ambil profil user yang sedang login.
	- Contoh:

```bash
curl -X GET http://localhost/api/users/profile \
	-H "Accept: application/json" \
	-H "Authorization: Bearer <TOKEN>"
```

- GET `/api/users` — Daftar semua user.
- PUT `/api/users` — Update user (fields: `first_name`, `last_name`, `email`, `phone`, `province`, `district`, `sub_district`, `village`, `neighborhood_unit`).
	- Contoh update:

```bash
curl -X PUT http://localhost/api/users \
	-H "Accept: application/json" \
	-H "Authorization: Bearer <TOKEN>" \
	-H "Content-Type: application/json" \
	-d '{"first_name":"Budi","phone":"08123456789"}'
```

- GET `/api/users/first-name/{name}`
- GET `/api/users/last-name/{name}`
- GET `/api/users/{id}`

4) Categories
- GET `/api/categories` — Daftar kategori.
- POST `/api/categories` — Buat kategori.
	- Body JSON: `title` (required)
	- Contoh:

```bash
curl -X POST http://localhost/api/categories \
	-H "Accept: application/json" \
	-H "Authorization: Bearer <TOKEN>" \
	-H "Content-Type: application/json" \
	-d '{"title":"Cleaning"}'
```

- GET `/api/categories/slug/{slug}`
- GET `/api/categories/{id}`
- DELETE `/api/categories/{id}`

5) Posts (Request & Service)
Endpoint utama untuk membuat posting ada dua tipe: `request` dan `service`.

- GET `/api/posts` — Semua posting.
- GET `/api/posts/total` — Statistik total posting per user (tergantung implementasi service).

- POST `/api/posts/request` — Buat posting tipe `request` (gunakan multipart/form-data untuk upload image).
	- Required fields (ringkasan):
		- `title` (string)
		- `type` = `request`
		- `description` (string)
		- `category_id` (uuid)
		- `min_price` (numeric)
		- `max_price` (numeric, >= min_price)
		- `deadline` (date)
		- `method_service` (string)
		- `province`, `regency`, `district`, `village`, `address_details` (string)
		- `images[]` (file, max 5)
		- `location[latitude]`, `location[longitude]` (numeric)
		- `published_until` (date)

	- Contoh curl (multipart):

```bash
curl -X POST http://localhost/api/posts/request \
	-H "Authorization: Bearer <TOKEN>" \
	-F "title=Butuh tukang bersih rumah" \
	-F "type=request" \
	-F "description=Membersihkan rumah 3 kamar" \
	-F "category_id=<CATEGORY_UUID>" \
	-F "min_price=100000" \
	-F "max_price=200000" \
	-F "deadline=2026-07-01" \
	-F "method_service=onsite" \
	-F "province=Jawa Barat" \
	-F "regency=Bogor" \
	-F "district=..." \
	-F "village=..." \
	-F "address_details=Perumahan X" \
	-F "location[latitude]=-6.5" \
	-F "location[longitude]=106.8" \
	-F "published_until=2026-07-01" \
	-F "images[]=@/path/to/photo1.jpg" \
	-F "images[]=@/path/to/photo2.jpg"
```

	- Contoh response sukses:

```json
{
	"success": true,
	"code": 201,
	"message": "post created",
	"data": {
		"id": "<POST_UUID>",
		"title": "Butuh tukang bersih rumah",
		"type": "request",
		"status": "active",
		"created_at": "2026-06-16T08:00:00Z"
	}
}
```

- POST `/api/posts/service` — Buat posting tipe `service` (multipart/form-data).
	- Required fields (ringkasan):
		- `title`, `type`=`service`, `description`, `category_id`
		- `base_price` (numeric)
		- `time_start` (H:i), `time_end` (H:i)
		- `experience_years` (integer)
		- alamat (`province`, `regency`, `district`, `village`, `address_details`)
		- `status` (`active`|`inactive`)
		- `images[]` (file[])
		- `location[latitude]`, `location[longitude]`

	- Contoh curl (multipart):

```bash
curl -X POST http://localhost/api/posts/service \
	-H "Authorization: Bearer <TOKEN>" \
	-F "title=Tukang pangkas rumput" \
	-F "type=service" \
	-F "description=Jasa pangkas rumput & perawatan halaman" \
	-F "category_id=<CATEGORY_UUID>" \
	-F "base_price=150000" \
	-F "time_start=09:00" \
	-F "time_end=12:00" \
	-F "experience_years=3" \
	-F "status=active" \
	-F "province=Jawa Barat" \
	-F "regency=Bogor" \
	-F "district=..." \
	-F "village=..." \
	-F "address_details=Jalan Mawar 10" \
	-F "location[latitude]=-6.5" \
	-F "location[longitude]=106.8" \
	-F "images[]=@/path/to/portfolio1.jpg"
```

- GET `/api/posts/request` — Ambil semua posting request (dengan detail request).
- GET `/api/posts/service` — Ambil semua posting service (dengan detail service).
- DELETE `/api/posts/{id}` — Hapus posting (validasi: `id` harus UUID). Contoh:

```bash
curl -X DELETE http://localhost/api/posts/<POST_UUID> \
	-H "Accept: application/json" \
	-H "Authorization: Bearer <TOKEN>"
```

6) Offers
- POST `/api/posts/apply` — Apply untuk job pada post.
	- Body JSON: `post_id`, `offered_price`.

```bash
curl -X POST http://localhost/api/posts/apply \
	-H "Accept: application/json" \
	-H "Authorization: Bearer <TOKEN>" \
	-H "Content-Type: application/json" \
	-d '{"post_id":"<POST_UUID>","offered_price":120000}'
```

- POST `/api/posts/book-helper` — Book / hire helper service (body: `post_id`, `offered_price`).
- GET `/api/offers/post/{postId}` — Ambil semua offer untuk sebuah post.

7) Messages
- POST `/api/offers/{offerId}/messages` — Kirim pesan terkait offer.
	- Body JSON: `content` (required), `type` (opsional, e.g. `text`).
	- Hanya `helper` atau `requester` yang terlibat di offer yang dapat mengirim pesan.

```bash
curl -X POST http://localhost/api/offers/<OFFER_ID>/messages \
	-H "Accept: application/json" \
	-H "Authorization: Bearer <TOKEN>" \
	-H "Content-Type: application/json" \
	-d '{"content":"Saya tertarik mengerjakan ini","type":"text"}'
```

8) Contoh response error umum
- Invalid credentials (login):

```json
{
	"success": false,
	"code": 401,
	"message": "invalid credentials",
	"data": []
}
```

- Validation error (contoh): status 422 atau 400 tergantung implementasi validator/handler.

Catatan penting
- Untuk upload file gunakan `multipart/form-data` dan kirim files sebagai `images[]`.
- Semua endpoint yang memodifikasi atau mengembalikan data sensitif dijaga oleh middleware `auth:sanctum` — pastikan token valid.
- Lihat implementasi validasi dan response payload di `app/Http/Controllers/Api/*` dan `app/Service/*` untuk rincian field dan aturan validasi.

Butuh contoh lengkap request/response untuk endpoint tertentu (mis. contoh payload lengkap saat membuat `request` post termasuk semua field dan response detail)? Saya bisa menambahkan contoh lengkap berikutnya.
