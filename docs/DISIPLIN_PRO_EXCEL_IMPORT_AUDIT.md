# DISIPLIN PRO — EXCEL IMPORT PRE-IMPLEMENTATION AUDIT & DESIGN LOCK

Dokumen ini merangkum hasil audit teknis mendalam atas workbook `Disiplin Pro tata tertib siswa V-1.xlsx`, kecocokannya dengan spesifikasi database `PostgreSQL` saat ini, serta **Keputusan Desain (Design Lock)** final sebelum masuk ke fase implementasi.

---

## 1. Temuan Struktur Aktual & Evaluasi Design Lock

### 1.1 DATA_SISWA (`students`)
- **Status Mapping:** `VERIFIED`
- **Keputusan Desain:** `CONFIRMED`
- **Aturan Implementasi:**
  - Pengguna diwajibkan memilih **Tahun Ajaran** dari antarmuka web sebelum mengunggah.
  - Pencarian `KELAS` (mis. "7A") hanya akan disesuaikan terhadap tabel `class_rooms` yang bernaung di bawah `academic_year_id` yang dipilih pengguna tersebut.
  - Jika kelas tidak ditemukan, baris akan ditolak/invalid pada halaman preview dengan alasan jelas ("Kelas 7A tidak ditemukan di Tahun Ajaran 2026/2027"). Sistem **TIDAK AKAN** membuat kelas gaib secara otomatis.
  - **Relasi Database:** Terkonfirmasi. `students.class_room_id` akan terikat pada kelas spesifik di tahun ajaran tertentu. Siswa tidak dipindahkan antar kelas secara diam-diam. Jika NIS sudah ada, aplikasi akan menolak perubahan kelas (atau memerlukan strategi kenaikan kelas yang berada di luar cakupan Excel Import).

### 1.2 CATATAN (`discipline_events`)
- **Status Mapping:** `VERIFIED`
- **Keputusan Desain:** `CONFIRMED`
- **Aturan Implementasi:**
  - `semester_id` ditentukan secara dinamis berdasarkan kolom `event_date` (TANGGAL) dibandingkan dengan rentang `start_date` dan `end_date` pada tabel `semesters`.
  - Jika tanggal jatuh pada tepat satu semester, semester itu yang dipakai. Jika gagal/lebih dari satu, baris ditandai **invalid** pada preview.
  - **Proteksi Histori (Poin):** Nilai `POIN` di Excel akan disimpan langsung ke kolom `points_effect`. Tidak ada proses *lookup* ulang ke `violations.points` atau `achievements.deduction_points` untuk menjaga integritas data historis apabila Master diubah di masa depan.
  - **Deduplikasi (Append-Only):** Mengingat tidak ada ID unik dari Excel, deduplikasi akan memakai identifikasi gabungan *(Composite Match)*: `[student_id, event_date, violation_id, description]`. Jika ditemukan kombinasi identik di DB, baris diabaikan (SKIP), **bukan** dihapus atau di-upsert.

### 1.3 PENCATAT (User Identifier)
- **Status Mapping:** `BLOCKED` / `NEEDS SCHEMA CHANGE`
- **Keputusan Desain:** Sistem akan mencoba mencocokkan nama teks di Excel dengan `users.name` secara persis (setelah normalisasi spasi). Jika tidak ada / ambigu, sistem menggunakan `user_id` dari Admin pengimpor (akun aktif) untuk mengisi field wajib `recorded_by`.
- **Blocker / Masalah Skema:** Aturan desain mewajibkan penyimpanan nama asli "PENCATAT" dari Excel agar jejak historis asli tidak hilang meskipun akun di-*fallback* ke pengimpor.
- **Kondisi Aktual Database:** Tabel `discipline_events` dan `counseling_records` **TIDAK MEMILIKI** kolom seperti `original_recorder_name` atau kolom fleksibel seperti `metadata (JSON)`.
- **Kebutuhan Migration:** Diperlukan *Migration* baru untuk menambahkan kolom `original_recorder_name` (string, nullable) pada `discipline_events` dan `counseling_records` guna mengamankan data historis nama guru/staf.

### 1.4 MASTER DATA (`MASTER_PELANGGARAN`, `MASTER_PRESTASI`, `MASTER_SANKSI`)
- **Status Mapping:** `VERIFIED`
- **Keputusan Desain:** `CONFIRMED`
- **Aturan Implementasi:** 
  - Update (*Upsert*) diizinkan berdasarkan pencocokan **Natural Key** yang divalidasi (`code` untuk pelanggaran/prestasi, `level` untuk sanksi). 
  - Preview konfirmasi perubahan wajib dimunculkan. 
  - Update master **TIDAK AKAN** memicu kalkulasi ulang (`points_effect`) untuk transaksi historis.

### 1.5 SETUP (`schools`)
- **Status Mapping:** `VERIFIED`
- **Keputusan Desain:** `CONFIRMED`
- **Aturan Implementasi:** Dibatasi sangat ketat. Parameter tidak menimpa sekolah saat ini tanpa konfirmasi *checkbox* eksplisit di layar preview karena dampak perubahan global sangat besar.

### 1.6 PEMBINAAN (`counseling_records`)
- **Status Mapping:** `VERIFIED`
- **Keputusan Desain:** `CONFIRMED`
- **Aturan Implementasi:** Mirip dengan `CATATAN`. Strategi deduplikasi menggunakan pencocokan `[student_id, date_scheduled/date_completed, reason]`. Ditolak jika ada duplikat, tidak ada upsert. Pencatat (Pengurus) menggunakan mekanisme *fallback* ke pengimpor namun harus menyimpan nama asli (menunggu Migration).

---

## 2. Dependency dan Urutan Import

Import tidak bisa (dan tidak boleh) dihajar dalam satu transaksi raksasa demi keamanan dan kecepatan. Urutan pemrosesan logis:

1. **Layer 0 (Opsional):** `SETUP` (Menginisialisasi config sekolah).
2. **Layer 1 (Independent):** `MASTER_PELANGGARAN`, `MASTER_PRESTASI`, `MASTER_SANKSI`.
3. **Layer 2 (Tergantung Layer 1):** `DATA_SISWA` (Mengunci NIS, butuh pemilihan UI Tahun Ajaran).
4. **Layer 3 (Tergantung Layer 1, 2, & Waktu):** `CATATAN`, `PEMBINAAN` (Mengunci histori).

---

## 3. Strategi Transaksi dan Validasi Besar

- **Validasi Per-Baris:** Tiap *row* Excel akan dimuat ke memori (dibaca via *streaming chunk*). Sistem akan menembakkan aturan validasi Laravel (contoh: `required|date`, `exists:class_rooms,name`, dsb).
- **Preview UI:** Hasil validasi memori tidak langsung ditanam ke DB, namun di-*cache* dan diturunkan ke UI Inertia untuk di-ACC oleh pengguna. Layar ini menampilkan status: *X Baris Siap, Y Baris Error (Akan Dilewati), Z Baris Duplikat (Akan Dilewati).*
- **Batasan Transaksi (Chunking):** Data dieksekusi dengan `DB::transaction()` per-500 baris. Jika dalam 500 baris terjadi kegagalan fatal, 500 baris tersebut batal, tetapi tidak merusak 500 baris lain yang sudah sukses di *chunk* sebelumnya. Ini mencegah 1 sel invalid menghancurkan 2.000 data sah.

---

## 4. Matriks Role dan Permission

Sesuai aturan keamanan:
- **Super Admin:** Dapat memproses (Upload & Commit) semua jenis sheet.
- **Tata Tertib / Admin Kesiswaan:** Hanya dapat mengunggah dan *commit* `DATA_SISWA`, `CATATAN`, `MASTER_PELANGGARAN`, `MASTER_PRESTASI`, dan `MASTER_SANKSI`.
- **Guru BK:** Hanya dapat mengunggah `PEMBINAAN` dan `CATATAN`.
- **Wali Kelas / Teacher:** **TIDAK DIIZINKAN** mengakses fitur Import massal, untuk mengamankan data dan memfokuskan guru pada *input* interaktif per-siswa di kelasnya saja.

---

## 5. Rencana Arsitektur & Daftar File yang Dibutuhkan

Fitur impor tidak akan dieksekusi mentah di Controller, melainkan melalui layanan terpisah:

1. `ExcelImportService.php` (Baru) → Orkestrator utama, membaca OpenSpout *stream*.
2. `ImportController.php` (Baru) → Menangani *upload file*, validasi form, dan *dispatch* ke *Service*.
3. **Migration Tambahan (Blokir):** `add_original_recorder_to_events_and_counseling.php` (Menunggu *Approval* Anda untuk merilis ini guna mengegolkan syarat penyimpanan identitas pencatat).
4. `resources/js/Pages/Imports/Index.vue` (Baru) → UI Wizard 3 Tahap (Upload, Preview & Pemetaan, Commit Result).
5. `tests/Feature/ExcelImportTest.php` (Baru) → Uji skenario duplikasi, penolakan baris, dan proteksi histori poin.

---

## 6. Laporan Akhir Tahap Audit

**Temuan Blocker:**
Keputusan desain mewajibkan penyimpanan nama penginput asli (`PENCATAT`) dari file Excel agar otentisitas histori tidak luntur saat direlasikan secara paksa *(fallback)* ke pengguna yang meng-upload. Mengingat skema saat ini di `discipline_events` dan `counseling_records` tidak memiliki kolom untuk itu, satu buah **Schema Migration** diperlukan sebelum fitur Excel Import bisa ditulis.

**Pertanyaan untuk Dilanjutkan:**
Apakah saya memiliki persetujuan Anda untuk menulis `migration` penambahan kolom `original_recorder_name` tersebut, lalu melangkah ke penulisan *code* Excel Import?
