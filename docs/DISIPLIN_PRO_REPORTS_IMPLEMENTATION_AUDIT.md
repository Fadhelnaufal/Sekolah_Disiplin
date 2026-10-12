# DISIPLIN PRO — REPORTS & ANALYTICS IMPLEMENTATION AUDIT

## 1. Temuan Aktual & Kondisi Repository
Berdasarkan pengecekan statis pada repository:
- **Routes & Pages:** Rute dengan prefix `/reports` sama sekali tidak ada. Di dalam direktori `resources/js/Pages/`, belum ada *folder* `Reports` atau UI untuk laporan bulanan, kelas, dan wali kelas. Rute dasbor existing ada di `/dashboard` dan me-render `Dashboard.vue`.
- **Services & Models:** `StudentPointService` memiliki kapabilitas untuk mengambil poin pelanggaran (`getViolationPoints`), poin prestasi (`getAchievementPoints`), dan poin bersih (`getNetPoints`) per semester. Model `Student`, `DisciplineEvent`, `ClassRoom`, `Teacher`, dan `Semester` saling terhubung dengan baik menggunakan relasi Eloquent (`belongsTo`, `hasMany`).
- **Dashboard Existing:** `DashboardController` telah ada dan mengirim metrik total siswa, pelanggaran periode aktif, agregasi peringatan dini (*early warning*), siswa berisiko, serta aktivitas terbaru ke `Dashboard.vue`.
- **Ekspor PDF:** `barryvdh/laravel-dompdf` sudah terinstal di `composer.json` (Versi 3.1) dan siap digunakan untuk merender PDF.
- **Ekspor Excel:** Belum ada *package* spesifik untuk manipulasi Excel (seperti `maatwebsite/excel` atau `spatie/simple-excel`) pada `composer.json`.

## 2. Matriks Requirement (Status Laporan)
| Requirement | Status | Keterangan |
| :--- | :--- | :--- |
| **Laporan Bulanan** | MISSING | Rute, UI, dan query belum tersedia. |
| **Laporan Kelas** | MISSING | Rute, UI, dan query belum tersedia. |
| **Laporan Wali Kelas** | MISSING | Rute, UI, dan query belum tersedia. |
| **Analitik Disiplin** | PARTIAL | Dasbor baru menampilkan metrik rekapitulasi mentah. Grafik tren bulanan atau kelas absen. |
| **PDF Export** | EXISTING | *Library* `dompdf` tersedia. Logika *controller* belum ada. |
| **Excel Export** | MISSING | *Library* pendukung perlu diinstal. |

## 3. Rencana Implementasi
1. **Instalasi Dependency Ekspor Excel:** Menginstal `spatie/simple-excel` (sangat ringan, mendukung aliran data besar/pagination, meminimalkan penggunaan memori berlebih ketimbang `maatwebsite/excel`) atau mengandalkan keluaran `.csv` natif yang diformat secara aman untuk kompatibilitas Excel standar.
2. **Backend (Controllers & Services):**
   - Membuat `App\Services\ReportService` untuk mengekstrak dan mengagregasi data dari `DisciplineEvent` (total kejadian, poin, dsb).
   - Membuat `App\Http\Controllers\ReportController` dengan 4 fungsi: `monthly`, `classes`, `homerooms`, dan `analytics`.
3. **Frontend (Vue/Inertia):**
   - Membuat *shared components* untuk filter (`SemesterFilter`, `DateFilter`).
   - Membuat 4 halaman Vue di dalam direktori `resources/js/Pages/Reports`.
   - Mengintegrasikan grafik (menggunakan `chart.js` & `vue-chartjs` bila perlu) pada Dasbor & Analitik.
4. **Security & Authorization:**
   - Memanfaatkan *Spatie Permission* dengan proteksi *Middleware* dan `Gate` (atau *Policy*) pada ekspor/baca laporan, seperti `role:Super Admin|BK|Principal` untuk Laporan Bulanan. Wali Kelas hanya dibatasi untuk melihat relasi `class_room` mereka.

## 4. Risiko Keamanan, Performa & Keputusan Teknis
- **Risiko N+1 Query (Performa):** Pada daftar ribuan transaksi atau ratusan siswa, memanggil `getNetPoints` dari `StudentPointService` di dalam *looping array* sangat mahal (menghasilkan *query* basis data baru tiap iterasi).
  *Keputusan Teknis:* `ReportService` harus merombak pemanggilan poin dengan metode SQL Aggregation (`SUM` & `GROUP BY`) langsung pada basis data alih-alih melempar model satu-per-satu ke `StudentPointService`.
- **Risiko Manipulasi Parameter Filter (Keamanan):** *Teacher* berupaya membaca ID kelas di luar wali kelasnya dengan memanipulasi parameter filter `class_id`.
  *Keputusan Teknis:* *Query builder* pada *ReportController* akan selalu di-*(intersect)* atau dipaksa filter `homeroom_teacher_id = auth()->user()->teacher_id` apabila *user* yang masuk adalah wali kelas (bukan Super Admin/BK).
- **Formula Injection Excel:** Serangan injeksi CSV/Excel dari kolom nama siswa atau deskripsi yang sengaja disisipkan tanda `=`.
  *Keputusan Teknis:* Setiap sel data mentah dalam ekspor Excel wajib melewati filter sterilisasi karakter rentan (*prepend* tanda kutip/petik).
