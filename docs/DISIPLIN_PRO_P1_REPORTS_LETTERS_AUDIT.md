# DISIPLIN PRO — P1 REPORTS & LETTERS AUDIT

**Tanggal Audit:** 09 Oktober 2026
**Fokus:** Modul Laporan dan Surat Pemanggilan (PDF).
**Sumber:** `Disiplin Pro tata tertib siswa V-1.xlsx` & `DISIPLIN_PRO_WEB_IMPLEMENTATION_SPEC.md`

---

## 1. Status Implementasi Aktual

Berdasarkan inspeksi rute (`routes/web.php`), pengontrol (`app/Http/Controllers/`), basis data, dan dependensi (via `composer.json`):

*   **Surat Pemanggilan & Ekspor PDF:** `PARTIALLY IMPLEMENTED (Schema Only)`. Tabel `letters` beserta *Model*-nya sudah ada. Namun, *Controller*, rute, tampilan UI (*Vue*), dan pustaka *PDF Generator* (seperti `dompdf`) sama sekali **belum diimplementasikan**.
*   **Laporan Bulanan (`/reports/monthly`):** `NOT IMPLEMENTED`. Tidak ada rute, *Controller*, maupun antarmuka. 
*   **Rekap Wali Kelas (`/reports/homerooms`):** `NOT IMPLEMENTED`.
*   **Analisis Pelanggaran (`/reports/analytics`):** `NOT IMPLEMENTED`. Hanya ada agregat mentah di `DashboardController`, bukan modul Analisis yang komprehensif.
*   **Ekspor Excel untuk Laporan:** `NOT IMPLEMENTED`. Paket ekspor Excel belum terpasang.

---

## 2. Matriks Traceability (Excel vs Markdown vs Aktual)

| ID | Kebutuhan (Excel / Markdown) | Logika Bisnis & Perilaku | Implementasi Aktual | Status | Risiko & Gap |
|---|---|---|---|---|---|
| **R-01** | Surat Pemanggilan PDF (`SURAT` / `#18. LETTERS`) | Sistem dapat meng-*generate* surat panggilan otomatis (PDF) dengan menyedot data `student_id`, wali kelas, dan menarik daftar tabel historis "RINCIAN CATATAN TERAKHIR". | Hanya ada `create_letters_table` dan `Letter` model. | `PARTIAL` | Tidak ada pustaka PDF, *Controller* atau UI. Otorisasi peran belum diatur di rute. |
| **R-02** | Laporan Bulanan (`LAP_BULANAN` / `#26. REPORTS`) | Laporan agregat yang merangkum jumlah kejadian, poin pelanggaran, poin prestasi, dan poin bersih yang difilter per bulan aktif. | Tidak tersedia. | `MISSING` | Jika fitur ini tidak ada, kepsek/yayasan tidak memiliki laporan agregasi kinerja ketertiban sekolah. |
| **R-03** | Rekap Wali Kelas (`REKAP_WALIKELAS` / `reports/homerooms`) | Mengukur rata-rata poin per siswa bagi tiap wali kelas, jumlah kejadian terbanyak, dan grafik beban kerja guru. | Tidak tersedia. | `MISSING` | Validasi kinerja BK dan wali kelas tidak bisa ditelaah. |
| **R-04** | Analitik Terpusat (`ANALISIS` / `reports/analytics`) | Menampilkan frekuensi top pelanggaran, kategori paling sering dilanggar, dan hari paling rawan *(Trend Analysis)*. | `DashboardController` hanya memiliki daftar top siswa bermasalah. | `MISSING` | Tidak ada modul *Analytics* yang mumpuni. |
| **R-05** | Ekspor Berkas | Laporan harus bisa diunduh sebagai PDF dan/atau Excel. | Tidak ada fungsionalitas ekspor (`maatwebsite/excel` atau `barryvdh/laravel-dompdf` nihil). | `MISSING` | Sulit mencetak arsip laporan tahunan/bulanan. |

---

## 3. Klarifikasi yang Diperlukan (NEEDS CLARIFICATION)

Meskipun sumber sudah dijabarkan, terdapat beberapa zona abu-abu yang perlu keputusan sebelum tahap *coding* dimulai:

1.  **Format Cetak Surat (PDF):** Lembar Excel `SURAT` memuat elemen visual statis (seperti kop surat dan tabel "Rincian Catatan Terakhir"). Apakah kita harus merender HTML ke PDF secara *pixel-perfect* mirip Excel, atau diizinkan merancang *template layout* HTML resmi yang lebih dinamis?
2.  **Batasan Historis di Surat:** Di tabel "Rincian Catatan Terakhir" pada Surat, berapakah batas jumlah historis kejadian yang ditarik? Apakah 5 kejadian terakhir, atau seluruh kejadian di semester aktif?
3.  **Grafik di Excel:** Lembar `LAP_BULANAN` dan `ANALISIS` memiliki *micro-charts* (garis vertikal `||||`). Apakah di aplikasi Web ini kita diwajibkan menggunakan *Chart.js*/*ApexCharts* interaktif, atau sekadar meniru baris vertikal statis?

---

## 4. Prioritas dan Kesiapan Coding

Tidak ada celah keamanan P0 dalam hal Laporan/Surat karena fiturnya sendiri masih kosong. Berikut adalah pengelompokan tingkat lanjut:

### **P1: Kebutuhan Utama Laporan & Surat**
Rekomendasi urutan implementasi yang paling aman dan organis:
1.  **Instalasi Dependensi Pustaka Ekspor:** Pasang `barryvdh/laravel-dompdf` untuk persiapan pembuatan PDF (Surat Pemanggilan).
2.  **Membangun `LetterService` & `ReportController`:** Rancang backend *Service Class* yang dapat melakukan kueri agregat untuk dasbor Analitik, Laporan Bulanan, dan pembentukan metadata Surat.
3.  **Membangun Rute & Otorisasi RBAC:** Tambahkan rute untuk `/reports/*` dan `/letters/*` dengan proteksi otorisasi sesuai peran (Hanya BK, Kepsek, dan Super Admin).
4.  **Menyusun Antarmuka (Vue/Inertia):** Buat komponen filter tabel, paginasi, tombol *Download PDF/Excel*.

### Kriteria Penerimaan Minimum (Acceptance Criteria)
*   Surat Pemanggilan berhasil diunduh dalam bentuk `.pdf` dengan kop nama sekolah dan tabel riwayat.
*   Data di tabel Laporan Bulanan harus cocok (`assertEquals`) dengan data akumulatif tabel historis poin.
*   Role Guru biasa dilarang (*Forbidden*) menghasilkan dan melihat Rekap Global (*Analytics*).
