# DISIPLIN PRO — LETTER 403 AUDIT

**Tanggal Audit:** 09 Oktober 2026
**Konteks:** Error `403 — User does not have the right roles.` saat mengakses `/letters` dan `/letters/create`.

---

## 1. Penyebab Pasti 403

Penyebab akses ditolak (403) bermula dari pengamanan ganda yang diterapkan pada aplikasi:
1. **Spatie Role Middleware:** Pada `routes/web.php` baris **61**, terdapat blok rute:
   ```php
   Route::middleware(['role:Super Admin|BK|Principal|Tata Tertib'])->group(function () {
       Route::resource('letters', \App\Http\Controllers\LetterController::class)...
   });
   ```
   Middleware ini akan secara otomatis melemparkan `UnauthorizedException` ("User does not have the right roles.") apabila *user* tidak memiliki minimal salah satu peran di atas.
2. **Laravel Policy:** Apabila middleware rute lolos (atau dihapus), aplikasi tetap memblokir pada `app/Http/Controllers/LetterController.php` baris **31** dan **40** dengan perintah `$this->authorize('viewAny', Letter::class);`. Hal ini memicu pengecekan pada `app/Policies/LetterPolicy.php` baris **16** dan **32** yang juga hanya meluluskan role `['Super Admin', 'BK', 'Principal', 'Tata Tertib']`.

## 2. Role Aktual Akun yang Sedang Login

Berdasarkan inspeksi terhadap tabel *sessions* dan data *User* menggunakan `tinker`:
*   Sesi aktif saat ini menunjuk pada **User ID: 2**.
*   User ID 2 adalah akun dengan email `guru@disiplin.pro` (Budi Santoso, S.Pd).
*   Role yang dimiliki akun tersebut adalah **`Teacher`**.

Karena peran `Teacher` tidak termasuk dalam daftar otorisasi rute dan *policy* untuk modul Surat, Spatie Permission secara tepat memblokir pergerakan URL tersebut dengan kode `403`.

## 3. Role yang Saat Ini Diizinkan

Sesuai dengan implementasi rute dan *policy* di backend (yang juga konsisten dengan analisis kebutuhan P1), peran yang **berhak** mengakses fitur ini adalah:
*   `Super Admin`
*   `BK`
*   `Principal`
*   `Tata Tertib`

## 4. Perbedaan Aturan Route, Policy, dan Sidebar

*   **Route vs Policy:** Keduanya sepenuhnya **konsisten**. Keduanya menggunakan himpunan peran perlindungan yang sama.
*   **Sidebar Navigation:** Terdapat kekosongan implementasi di *frontend*. Menu navigasi untuk "Surat Pemanggilan" **belum ditambahkan sama sekali** di dalam `resources/js/Layouts/AuthenticatedLayout.vue`.
Hal ini menyebabkan pengguna yang masuk secara sah (seperti BK) tidak bisa menemukan tautan Surat, sementara *user* yang salah (seperti `Teacher`) yang menguji masuk melalui *URL Bar* secara manual (mengetikkan `/letters`) dipukul mundur oleh keamanan 403 Backend.

## 5. Rekomendasi Perbaikan Terkecil

Sistem backend **berjalan sebagaimana mestinya** (tidak ada cacat keamanan—seorang `Teacher` memang seharusnya tidak berhak merender dan melihat surat peringatan siswa secara massal). Tidak disarankan mengubah hak rute tersebut.

Untuk melengkapi fitur agar UX berjalan mulus, **rekomendasi perbaikan (UI/UX) adalah:**

1. **Tambahkan Navigasi:** Menambahkan tautan menu "Surat Pemanggilan" pada blok "Operasional" di berkas `resources/js/Layouts/AuthenticatedLayout.vue` dengan perisai tampilan kondisional bersyarat (*v-if*):
   ```html
   <Link 
       v-if="$page.props.auth.roles.some(r => ['Super Admin', 'BK', 'Principal', 'Tata Tertib'].includes(r))"
       href="/letters"
   >
       Surat Pemanggilan
   </Link>
   ```
2. **Uji Coba Ganti Akun:** Pengguna *development* yang sedang menguji saat ini disarankan untuk menggunakan akun `bk@disiplin.pro` atau `admin@disiplin.pro` alih-alih `guru@disiplin.pro` saat berinteraksi dengan modul Surat.
