# DISIPLIN PRO — RECOMMENDED ROADMAP

Dokumen ini merinci langkah-langkah implementasi prioritas (P1) yang tersisa untuk membawa sistem DISIPLIN PRO mencapai kelulusan akhir (*Production Ready*). Pengerjaan direkomendasikan berjalan sesuai hirarki linier berikut untuk meminimalisasi konflik dependensi.

---

## TAHAP 1: Modul Laporan & Analitik (Reports)
Laporan adalah kelemahan terbesar sistem saat ini karena sama sekali belum ada. Laporan menjadi nyawa bagi Kepala Sekolah dan BK.
*   **Target Pekerjaan:**
    1. Membuat `ReportService.php` dan `ReportController.php`.
    2. Membuat antarmuka *Laporan Bulanan* (agregasi kejadian berdasar bulan kalender).
    3. Membuat antarmuka *Laporan Rekap Kelas* & *Rekap Wali Kelas*.
    4. Implementasi Grafik Analitik ringan di halaman *Dashboard* (kategori pelanggaran, daftar *attention required*).
*   **Acceptance Criteria (Kriteria Lulus):**
    - Rute `/reports/monthly`, `/reports/classes`, `/reports/homerooms` dapat diakses dan di-filter dengan akurat berdasar *Academic Year* dan *Semester*.
    - Tabel menampilkan total siswa, poin pelanggaran, deduksi, dan poin bersih.
*   **Dependensi:** Modul `StudentPointService` (Sudah Tersedia).

## TAHAP 2: Sistem Import Data Eksternal (Excel Import)
Fitur *on-boarding* krusial bagi administrasi agar institusi dapat memigrasikan rekap *Spreadsheet Excel* mereka ke aplikasi berbasis web dengan instan.
*   **Target Pekerjaan:**
    1. Membangun alat *importer* CSV/Excel untuk master data (Siswa, Kelas, Guru).
    2. Membangun import transaksi historis (`discipline_events` dan `counseling_records`).
*   **Acceptance Criteria (Kriteria Lulus):**
    - Tersedia fitur GUI untuk mengunggah dokumen (*upload*).
    - Data yang rusak (*missing reference*) tidak merusak *database* (ada validasi skema).
    - Skrip kalkulasi poin tetap mencerminkan saldo yang tepat dari masa lalu.

## TAHAP 3: Tutup Semester (Semester Closing & Snapshots)
Fitur *safety net* akademis. Memastikan angka semester lalu tidak bocor ketika pergantian tahun ajaran.
*   **Target Pekerjaan:**
    1. Migrasi tabel `semester_snapshots`.
    2. Menambahkan status *(Active, Closing, Closed)* di skema `semesters`.
    3. Fungsi *freeze* / pembekuan data transaksi poin dari semester tutup.
*   **Acceptance Criteria (Kriteria Lulus):**
    - Akses POST / PUT transaksi pada semester dengan status `Closed` otomatis tertolak oleh *backend* dengan *error 403 Forbidden/Conflict*.
    - Semua siswa mendapatkan rekap akumulasi nilai *net_points* permanen di *database snapshot*.

## TAHAP 4: School Settings & Audit Logs (Penyempurnaan Akhir)
Finalisasi *governance* administratif dan kontrol keamanan sistem.
*   **Target Pekerjaan:**
    1. Membuat GUI / *Menu Setting* bagi *Super Admin* untuk mengedit entitas `schools` (Kop surat, nama Kepsek, nama aplikasi, dll).
    2. Menyematkan perpustakaan rekam jejak histori (misalnya `spatie/laravel-activitylog`).
*   **Acceptance Criteria (Kriteria Lulus):**
    - Identitas surat langsung tercermin dari form di antarmuka sistem tanpa bantuan kueri terminal/DB.
    - Ada menu *Log Audit* hanya-baca bagi Super Admin untuk memeriksa perubahan konfigurasi.

---

## Kriteria Verifikasi Produksi Akhir (Siap Staging)
1. **End-to-End Excel Test:** Import 1 buku Excel asli DISIPLIN PRO secara penuh, dan komparasikan nilai bersih antara sistem web vs kalkulator di Excel secara berjejer.
2. **Keamanan Rute (Security Pass):** Seluruh URL fungsional divalidasi oleh otorisasi (dilarang *Guest*, dan blok akses antar-*Role*).
3. **No Phantom Bugs:** Pengujian (`php artisan test`) memberikan konfirmasi persentase 100% kelulusan, *Log server* berjalan sunyi (*error-free*).
