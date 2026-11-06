# INVENTARISASI HALAMAN DAN STATUS KONSISTENSI ANTARMUKA
## DISIPLIN PRO: School Student-Discipline Administration SaaS

Dokumen ini memetakan seluruh file antarmuka pengguna (Vue Page Components) yang berada di dalam direktori `resources/js/Pages`, rute terkait di `routes/web.php`, jenis halaman, status konsistensi saat ini, implementasi tabel data, serta tingkat prioritas perbaikan.

---

### 1. Tabel Inventarisasi Seluruh Halaman

| No | Modul | Halaman | Route | Jenis Halaman | Status UI | DataTable | Tingkat Konsistensi | Prioritas |
|---|---|---|---|---|---|---|---|---|
| 1 | Autentikasi | Halaman Selamat Datang | `/` | Other | Consistent | Not Applicable | High | P3 |
| 2 | Autentikasi | Halaman Masuk Akun | `/login` | Other | Consistent | Not Applicable | High | P3 |
| 3 | Autentikasi | Halaman Registrasi Akun (Legacy) | `/register` | Other | Not Applicable | Not Applicable | Low | P3 |
| 4 | Dashboard | Ringkasan Eksekutif & Aktivitas | `/dashboard` | Dashboard | Consistent | Not Applicable | High | P2 |
| 5 | Data Siswa | Direktori Induk Siswa | `/students` | Data List | Consistent | PrimeVue DataTable | High | P2 |
| 6 | Data Siswa | Registrasi & Edit Siswa | `/students/create`, `/students/{id}/edit` | Create Form / Edit Form | Consistent | Not Applicable | High | P2 |
| 7 | Data Siswa | Lembar Profil & Linimasa Siswa | `/students/{id}` | Detail | Consistent | Not Applicable | High | P2 |
| 8 | Data Siswa | Lembar Cetak Kartu Disiplin | `/students/{id}/print-card` | Detail | Consistent | Not Applicable (Print) | High | P3 |
| 9 | Kejadian Disiplin | Jurnal Catatan Kejadian Disiplin | `/discipline-events` | Data List | Consistent | PrimeVue DataTable | High | P2 |
| 10 | Kejadian Disiplin | Formulir Pencatatan Kejadian | `/discipline-events/create` | Create Form | Partially Consistent | Not Applicable | Medium | P1 |
| 11 | Rekapitulasi Disiplin | Rekapitulasi Poin & Status Siswa | `/discipline-recap` | Recap | Consistent | PrimeVue DataTable | High | P2 |
| 12 | Master Data | Manajemen Pelanggaran, Prestasi, Sanksi | `/master-data` | Data List | Consistent (Benchmark) | PrimeVue DataTable | High | P2 |
| 13 | Master Data | Form Tambah/Ubah Pelanggaran | `/violations/create`, `/violations/{id}/edit` | Create Form / Edit Form | Partially Consistent | Not Applicable | Medium | P2 |
| 14 | Master Data | Daftar Pelanggaran (Teralihkan) | `/violations` (redirect `/master-data`) | Data List | Partially Consistent (Redundant) | PrimeVue DataTable | Medium | P3 |
| 15 | Master Data | Form Tambah/Ubah Prestasi | `/achievements/create`, `/achievements/{id}/edit` | Create Form / Edit Form | Partially Consistent | Not Applicable | Medium | P2 |
| 16 | Master Data | Daftar Prestasi (Teralihkan) | `/achievements` (redirect `/master-data`) | Data List | Partially Consistent (Redundant) | PrimeVue DataTable | Medium | P3 |
| 17 | Master Data | Form Tambah/Ubah Aturan Sanksi | `/sanction-rules/create`, `/sanction-rules/{id}/edit` | Create Form / Edit Form | Partially Consistent | Not Applicable | Medium | P2 |
| 18 | Master Data | Daftar Aturan Sanksi (Teralihkan) | `/sanction-rules` (redirect `/master-data`) | Data List | Inconsistent (Redundant) | HTML Table | Low | P3 |
| 19 | Bimbingan Konseling | Buku Kasus Pembinaan Siswa | `/counseling-records` | Data List | Partially Consistent | HTML Table (Recommended Migrate) | Medium | P0 |
| 20 | Bimbingan Konseling | Penjadwalan Kasus Baru | `/counseling-records/create` | Create Form | Partially Consistent | Not Applicable | Medium | P2 |
| 21 | Bimbingan Konseling | Pemutakhiran Rekaman Kasus | `/counseling-records/{id}/edit` | Edit Form | Partially Consistent | Not Applicable | Medium | P2 |
| 22 | Bimbingan Konseling | Lembar Detail Kasus & Notulensi | `/counseling-records/{id}` | Detail | Consistent | Not Applicable | High | P2 |
| 23 | Tindak Lanjut | Monitoring Tindak Lanjut Disiplin | `/follow-ups` | Data List | Partially Consistent | HTML Table (Recommended Migrate) | Medium | P0 |
| 24 | Surat & Dokumen | Generator & Manajemen Surat Resmi | `/reports` | Report | Partially Consistent | HTML Table (Tab Arsip) | Medium | P1 |
| 25 | Surat & Dokumen | Pembuatan Surat Panggilan Mandiri | `/letters/create` | Create Form | Partially Consistent | Not Applicable | Medium | P2 |
| 26 | Surat & Dokumen | Arsip Surat Mandiri (Teralihkan) | `/letters` (delegates `/reports?tab=template`) | Report | Partially Consistent (Redundant) | HTML Table | Medium | P3 |
| 27 | Pelaporan | Rekapitulasi Kejadian Bulanan | `/reports/monthly` | Report | Inconsistent | HTML Table (Recommended Migrate) | Low | P1 |
| 28 | Pelaporan | Rekapitulasi Berbasis Kelas | `/reports/classes`, `/reports/homerooms` | Report | Inconsistent | HTML Table (Recommended Migrate) | Low | P1 |
| 29 | Pelaporan | Dasbor Visualisasi Tren & KPI | `/reports/analytics` | Dashboard | Consistent | Not Applicable | High | P2 |
| 30 | Manajemen Kelas | Direktori Rombongan Belajar | `/class-rooms` | Data List | Partially Consistent | HTML Table (Recommended Migrate) | Medium | P1 |
| 31 | Manajemen Kelas | Form Tambah/Ubah Kelas | `/class-rooms/create`, `/class-rooms/{id}/edit` | Create Form / Edit Form | Partially Consistent | Not Applicable | Medium | P2 |
| 32 | Manajemen Guru | Direktori Tenaga Pendidik | `/teachers` | Data List | Partially Consistent | HTML Table (Recommended Migrate) | Medium | P1 |
| 33 | Manajemen Guru | Form Tambah/Ubah Guru | `/teachers/create`, `/teachers/{id}/edit` | Create Form / Edit Form | Partially Consistent | Not Applicable | Medium | P2 |
| 34 | Administrasi Akun | Manajemen Akun Pengguna | `/users` | Data List | Partially Consistent | HTML Table (Recommended Migrate) | Medium | P1 |
| 35 | Audit Keamanan | Log Aktivitas & Jejak Audit | `/audit-logs` | Data List | Partially Consistent | HTML Table (Recommended Migrate) | Medium | P1 |
| 36 | Konfigurasi | Manajemen Tahun Ajaran | `/academic-years` | Data List | Partially Consistent | HTML Table (Recommended Migrate) | Medium | P2 |
| 37 | Konfigurasi | Form Tambah/Ubah Tahun Ajaran | `/academic-years/create`, `/academic-years/{id}/edit` | Create Form / Edit Form | Partially Consistent | Not Applicable | Medium | P2 |
| 38 | Konfigurasi | Manajemen Semester Sekolah | `/semesters` | Data List | Partially Consistent | HTML Table (Recommended Migrate) | Medium | P2 |
| 39 | Konfigurasi | Form Tambah/Ubah Semester | `/semesters/create`, `/semesters/{id}/edit` | Create Form / Edit Form | Partially Consistent | Not Applicable | Medium | P2 |
| 40 | Migrasi Data | Import Berkas Excel & Validasi | `/imports` | Other | Consistent | HTML Table (Preview Only) | High | P3 |
| 41 | Profil Pengguna | Pengaturan Profil & Kata Sandi | `/profile` | Settings | Consistent | Not Applicable | High | P3 |
| 42 | Konfigurasi | Pengaturan Institusi & Ambang Poin | `/settings` | Settings | Consistent | Not Applicable | High | P2 |

---

### 2. Keterangan Klasifikasi

#### A. Definisi Jenis Halaman
- **Dashboard:** Halaman ringkasan analitik, KPI metrics, grafik visualisasi tren, dan jalan pintas aktivitas cepat.
- **Data List:** Halaman operasional utama yang menampilkan kumpulan data entitas sistem yang dapat dicari, disortir, dan disaring.
- **Create Form:** Formulir penginputan data entitas baru.
- **Edit Form:** Formulir pemutakhiran data entitas yang sudah ada di basis data.
- **Detail:** Halaman paparan rinci satu entitas tunggal lengkap dengan histori linimasa dan metadata relasi.
- **Report:** Halaman kompilasi laporan cetak, generator surat, atau rekapitulasi data agregat.
- **Recap:** Halaman analitik rekapitulasi komprehensif data siswa berbasis semester.
- **Settings:** Halaman konfigurasi profil sekolah, preferensi aplikasi, dan parameter batas ambang.
- **Other:** Halaman khusus otentikasi atau wizard migrasi data import.

#### B. Definisi Status UI
- **Consistent (13 halaman):** Halaman yang telah sepenuhnya mematuhi standar desain resmi (`DESIGN.md`), layout padding `max-w-[1400px]`, container kartu `rounded-[24px]`, ambient shadow, PrimeVue DataTable (bila berbentuk tabel), dan feedback SweetAlert2.
- **Partially Consistent (21 halaman):** Halaman yang telah mengadopsi sebagian token visual (seperti kartu putih dan font sans), namun masih memiliki disparitas penting pada komponen tabel (masih HTML tabel), pagination manual, ukuran toolbar yang berbeda, atau menggunakan PrimeVue ConfirmDialog/alert browser.
- **Inconsistent (3 halaman):** Halaman yang tampilan visualnya masih terasa seperti lembar laporan cetak mentah atau tabel bawaan lama tanpa pagination dan pencarian (`Reports/Monthly.vue`, `Reports/Classes.vue`, `SanctionRules/Index.vue`).
- **Not Applicable / Redundant (5 halaman):** Halaman yang secara fungsional tidak lagi dirender oleh sistem karena dialihkan langsung oleh controller ke halaman terpusat atau dinonaktifkan demi alasan kebijakan institusi.
- **Not Verified (0 halaman):** Seluruh halaman berhasil diinspeksi secara aktual di basis kode.

#### C. Definisi Tingkat Prioritas Perbaikan
- **P0 (Critical):** Halaman operasional harian berinteraksi tinggi yang sangat membutuhkan standardisasi PrimeVue DataTable dan perbaikan UX (Pembinaan BK `/counseling-records` dan Tindak Lanjut `/follow-ups`).
- **P1 (High):** Halaman data master dan laporan yang sering diakses staf tata usaha dan guru (`/class-rooms`, `/teachers`, `/users`, `/audit-logs`, `/reports/monthly`, `/reports/classes`, serta formulir `/discipline-events/create`).
- **P2 (Medium):** Halaman konfigurasi periodik dan formulir entitas tunggal (`/academic-years`, `/semesters`, `/settings`, dan form master data).
- **P3 (Low):** Halaman dengan status konsisten penuh, halaman statis pendukung, atau pembersihan berkas redundant.

