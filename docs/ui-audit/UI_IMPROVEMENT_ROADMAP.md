# ROADMAP PRIORITAS PENYELARASAN DAN STANDARDISASI UI
## DISIPLIN PRO: Strategic UI Improvement Plan

Dokumen ini menyusun rencana aksi perbaikan antarmuka pengguna DISIPLIN PRO yang disusun ke dalam 5 fase bertahap berdasarkan tingkat dependensi teknis dan urgensi dampak bagi pengguna akhir.

---

### 1. Struktur Roadmap 5 Fase

```
Fase 1: Foundation & Shared Components
  │
  ├──► Fase 2: Standardisasi Data Listing (Migrasi PrimeVue DataTable)
  │      │
  │      ├──► Fase 3: Penyelarasan Formulir & Lembar Detail
  │      │      │
  │      │      └──► Fase 4: Optimasi Pelaporan & Dasbor
  │      │             │
  │      │             └──► Fase 5: Kerapian Responsif & Pembersihan Berkas Usang
```

---

### 2. Rincian Pekerjaan per Fase

#### FASE 1: FOUNDATION & SHARED COMPONENTS (SELESAI)
Fokus pada penyiapan komponen bersama dan standardisasi dialog konfirmasi sebelum migrasi halaman dimulai.

| No | Komponen / Pekerjaan | Masalah Saat Ini | Solusi yang Diimplementasikan | Status | Prioritas | Kompleksitas |
|---|---|---|---|---|---|---|
| 1.1 | Ekstraksi `AppStatusBadge.vue` | Terdapat 6 variasi kelas warna dan bentuk badge status risiko yang berulang dan tidak seragam. | Komponen bersama berhasil dibuat di `resources/js/Components/AppStatusBadge.vue` mendukung seluruh status semantik. | Selesai | P0 | Low |
| 1.2 | Ekstraksi `AppPageHeader.vue` | Header halaman ditulis ulang di setiap file dengan sedikit perbedaan margin dan ukuran judul. | Komponen header bersama berhasil dibuat di `resources/js/Components/AppPageHeader.vue` dengan slot actions dan breadcrumbs. | Selesai | P1 | Low |
| 1.3 | Standardisasi SweetAlert2 Helper | Masih terdapat campuran antara `useConfirm` (ConfirmDialog), `Swal.fire`, dan `alert()`. | Fungsi helper terpadu `confirmDelete`, `confirmAction`, dan `showAlert` berhasil dibuat di `resources/js/utils.ts`. | Selesai | P0 | Low |
| 1.4 | Ekstraksi `AppEmptyState.vue` | Tampilan status kosong (empty state) dibuat manual berulang di 7 file berbeda. | Komponen status kosong terstandarisasi berhasil dibuat di `resources/js/Components/AppEmptyState.vue`. | Selesai | P1 | Low |
| 1.5 | Ekstraksi `AppTableToolbar.vue` | Ukuran search bar dan dropdown filter bervariasi di berbagai halaman. | Komponen toolbar standar (tinggi 36 px, rounded-full) berhasil dibuat di `resources/js/Components/AppTableToolbar.vue`. | Selesai | P0 | Low |
| 1.6 | Ekstraksi `AppFormSection.vue` | Penataan kelompok field form tidak seragam antara pendaftaran siswa dan form master. | Komponen pembungkus section form kartu 24 px berikon berhasil dibuat di `resources/js/Components/AppFormSection.vue`. | Selesai | P1 | Low |
| 1.7 | Ekstraksi `AppDataTableWrapper.vue` | Konfigurasi paginator dan responsive stack layout belum terpusat. | Wrapper PrimeVue DataTable standar berhasil dibuat di `resources/js/Components/AppDataTableWrapper.vue` dengan slot forwarding penuh. | Selesai | P0 | Low |

---

#### FASE 2: STANDARDISASI DATA LISTING (MIGRASI DATATABLE)
Fokus pada migrasi halaman data tabel dari HTML `<table>` manual ke PrimeVue DataTable bertema Aura.

| No | Halaman yang Terdampak | Masalah Saat Ini | Solusi yang Direkomendasikan | Dependensi | Prioritas | Kompleksitas |
|---|---|---|---|---|---|---|
| 2.1 | `/counseling-records` (`CounselingRecords/Index.vue`) | Buku kasus BK masih menggunakan tabel HTML biasa, tautan paginasi manual lambat, dan tidak memiliki sorting kolom. | Migrasi ke PrimeVue DataTable dengan filter kategori, status kasus, sorting tanggal sesi, dan tombol notifikasi WA. | Fase 1 (1.1, 1.3) | P0 | High |
| 2.2 | `/follow-ups` (`FollowUps/Index.vue`) | Monitoring tindak lanjut menggunakan tabel HTML biasa, tanpa filter penugasan guru di toolbar utama. | Migrasi ke PrimeVue DataTable lengkap dengan indikator tenggat waktu (overdue warning) dan paginator dinamis. | Fase 1 (1.1, 1.3) | P0 | Medium |
| 2.3 | `/teachers` (`Teachers/Index.vue`) | Daftar guru menggunakan tabel HTML manual dan `useConfirm` untuk konfirmasi hapus. | Migrasi ke PrimeVue DataTable, tambahkan filter status aktif, dan gunakan konfirmasi hapus SweetAlert2. | Fase 1 (1.2, 1.3) | P1 | Medium |
| 2.4 | `/class-rooms` (`ClassRooms/Index.vue`) | Data rombel menggunakan tabel HTML manual dan `useConfirm`. | Migrasi ke PrimeVue DataTable dengan sorting tingkat kelas dan abjad nama rombel. | Fase 1 (1.2, 1.3) | P1 | Medium |
| 2.5 | `/users` (`Users/Index.vue`) | Manajemen akun pengguna menggunakan tabel HTML manual dengan paginasi tautan manual. | Migrasi ke PrimeVue DataTable dengan kolom: Pengguna & Email, Peran (Badge), Tanggal Terdaftar, dan Aksi Akun. | Fase 1 (1.1, 1.3) | P1 | Medium |
| 2.6 | `/audit-logs` (`AuditLogs/Index.vue`) | Log audit menggunakan script manipulasi array manual untuk baris collapsible. | Migrasi ke PrimeVue DataTable memanfaatkan fitur bawaan `rowExpansion` untuk pratinjau data lama dan baru (JSON delta). | Fase 1 (1.2) | P1 | Medium |
| 2.7 | `/academic-years` & `/semesters` | Halaman konfigurasi periode sekolah masih menggunakan tabel HTML manual. | Migrasi ke PrimeVue DataTable dengan tombol aksi switch status aktif/non-aktif yang intuitif. | Fase 1 (1.3) | P2 | Medium |
| 2.8 | `/reports` (Tab Arsip Surat) | Daftar riwayat surat terbit pada tab arsip masih menggunakan tabel HTML sederhana. | Terapkan PrimeVue DataTable pada sub-komponen arsip surat lengkap dengan tombol unduh berkas Word/PDF. | Fase 1 (1.1) | P1 | Medium |

---

#### FASE 3: PENYELARASAN FORMULIR & LEMBAR DETAIL
Fokus pada penyeragaman pengalaman pengguna saat menginput dan memutakhirkan data.

| No | Halaman yang Terdampak | Masalah Saat Ini | Solusi yang Direkomendasikan | Dependensi | Prioritas | Kompleksitas |
|---|---|---|---|---|---|---|
| 3.1 | `/discipline-events/create` (`DisciplineEvents/Create.vue`) | Menggunakan fungsi `alert()` bawaan browser ketika validasi massal gagal atau nomor WA kosong. | Ganti seluruh panggilan `alert()` dengan SweetAlert2 modal dan PrimeVue Toast yang lebih profesional. | utils SweetAlert2 | P1 | Low |
| 3.2 | Form Master Data (`Violations/Form.vue`, `Achievements/Form.vue`, `SanctionRules/Form.vue`) | Layout form kartu tunggal belum memiliki penataan section yang rapi dan icon pembantu. | Perbarui tampilan kartu form dengan section header yang jelas, tinggi input 42 px, dan tombol Simpan dengan status loading. | Fase 1 (1.2) | P2 | Low |
| 3.3 | Form Master Akademik (`Teachers/Form.vue`, `ClassRooms/Form.vue`, `AcademicYears/Form.vue`, `Semesters/Form.vue`) | Menggunakan ukuran input sempit dan label kecil tanpa helper text yang memadai. | Tingkatkan hierarki visual form, tambahkan helper text penjelasan format NIP/NPSN, dan pasang indikator proses submit. | Fase 1 (1.2) | P2 | Low |
| 3.4 | `/students/{id}` (`Students/Show.vue`) | Lembar profil detail siswa sudah sangat baik, namun linimasa kejadiannya masih menggunakan komponen feed manual. | Pertahankan format feed linimasa, namun sinkronkan badge status risiko dengan komponen `AppStatusBadge.vue`. | Fase 1 (1.1) | P2 | Low |

---

#### FASE 4: OPTIMASI PELAPORAN & DASBOR
Fokus pada konsolidasi modul laporan agar tidak terjadi duplikasi fitur dengan halaman rekapitulasi.

| No | Halaman yang Terdampak | Masalah Saat Ini | Solusi yang Direkomendasikan | Dependensi | Prioritas | Kompleksitas |
|---|---|---|---|---|---|---|
| 4.1 | `/reports/monthly` (`Reports/Monthly.vue`) | Tampilan masih berupa tabel statis kaku tanpa pagination dan search, fungsinya tumpang tindih dengan `/discipline-recap`. | Opsi A: Migrasikan tabel ke PrimeVue DataTable. Opsi B: Alihkan navigasi menu ke `/discipline-recap` yang sudah jauh lebih lengkap. | Fase 2 | P1 | Medium |
| 4.2 | `/reports/classes` (`Reports/Classes.vue`) | Laporan agregat rombel belum memiliki fitur pencarian nama kelas dan sorting rata-rata poin. | Migrasikan tabel agregat kelas ke PrimeVue DataTable dengan kolom ringkasan pelanggaran, prestasi, dan poin bersih kelas. | Fase 2 | P1 | Medium |
| 4.3 | `/reports` (`Reports/Index.vue`) | File berukuran sangat besar (1560 baris) yang menggabungkan generator surat, template editor, dan arsip dokumen. | Pecah (refactor) menjadi 3 sub-komponen terpisah: `LetterGeneratorTab.vue`, `LetterTemplateTab.vue`, dan `LetterArchiveTab.vue`. | None | P2 | High |
| 4.4 | `/dashboard` (`Dashboard.vue`) | Tabel daftar siswa prioritas pembinaan pada dashboard belum memiliki tautan cepat langsung ke modal pembinaan BK. | Tambahkan tombol aksi cepat (Shortcut Quick Counseling) pada setiap baris siswa prioritas di dashboard. | Fase 2 (2.1) | P2 | Low |

---

#### FASE 5: KERAPIAN RESPONSIF & PEMBERSIHAN BERKAS USANG
Fokus pada pengujian tampilan lintas perangkat dan pembersihan file redundant yang membingungkan pengembang.

| No | Pekerjaan / Berkas | Masalah Saat Ini | Solusi yang Direkomendasikan | Dependensi | Prioritas | Kompleksitas |
|---|---|---|---|---|---|---|
| 5.1 | Pembersihan Berkas Usang (Legacy Redundant Pages) | Terdapat 5 berkas Vue yang sudah tidak pernah dirender oleh controller (`Violations/Index.vue`, `Achievements/Index.vue`, `SanctionRules/Index.vue`, `Letters/Index.vue`, `Auth/Register.vue`). | Hapus kelima berkas usang tersebut secara aman dari direktori `resources/js/Pages/` untuk menjaga kerapian codebase. | None | P3 | Low |
| 5.2 | Audit Responsif Mobile & Tablet | Sejumlah tabel pada layar ponsel di bawah 640 px berpotensi terpotong jika responsiveLayout stack belum aktif. | Pastikan seluruh PrimeVue DataTable yang baru dimigrasi telah memasang atribut `responsiveLayout="stack"` dan `breakpoint="768px"`. | Fase 2 | P1 | Medium |
| 5.3 | Verifikasi Akhir Linting & Formatter | Memastikan seluruh berkas Vue dan TypeScript lolos uji kompilasi Vite dan linter. | Jalankan `npm run build` dan `vendor/bin/pint --dirty --format agent` secara menyeluruh. | Fase 1 - 5 | P1 | Low |

