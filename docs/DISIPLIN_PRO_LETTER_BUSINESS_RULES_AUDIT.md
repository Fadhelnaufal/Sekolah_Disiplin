# DISIPLIN PRO — LETTER BUSINESS RULES AUDIT

**Tanggal Audit:** 09 Oktober 2026
**Konteks:** Audit kesesuaian implementasi fitur Surat Pemanggilan (Modul P1.1) terhadap aturan penandatanganan dinamis dan keamanan arsip.

---

## 1. Temuan Utama & Status Kesesuaian

### A. Tahap Panggilan (I, II, III)
**Status: Belum Tersedia**
*   **File/Baris:** `resources/js/Pages/Letters/Create.vue` (baris 61-64), `LetterController.php` (baris 58).
*   **Temuan:** Form dan validasi controller hanya mengenali nilai `parent_call`. Aplikasi belum membedakan jenjang panggilan menjadi Panggilan I, Panggilan II, dan Panggilan III.

### B. Dinamika Penandatangan
**Status: Sebagian Sesuai (Hardcoded Layout)**
*   **File/Baris:** `resources/views/pdf/letter.blade.php` (baris 151-164).
*   **Temuan:** Penandatangan selalu mencetak dua kolom: **Kepala Sekolah** dan **Petugas / Guru BK** secara bersamaan. Identitas belum dipilih secara dinamis berdasarkan tahap surat (misal: Panggilan I hanya BK, Panggilan II Wakil Kepala Sekolah).

### C. Identitas/Jabatan di Database
**Status: Perlu Penambahan**
*   **File/Baris:** Schema tabel `schools` (`database/migrations/2026_10_09_030807_create_schools_table.php`).
*   **Temuan:** Database mengambil identitas `principal_name` dan `counseling_officer_name` dari tabel `schools`. Tidak ada field untuk **Wakil Kepala Sekolah Bidang Kesiswaan** (yang dibutuhkan untuk Panggilan II).

### D. Penerbit Surat (Institusi Sekolah)
**Status: Sesuai**
*   **File/Baris:** `resources/views/pdf/letter.blade.php` (baris 81-87).
*   **Temuan:** Kop surat merender `$school->name`, alamat, dan telepon dengan baik. Surat secara resmi dikeluarkan atas nama entitas sekolah.

### E. Aturan Sanksi dari MASTER_SANKSI
**Status: Sesuai**
*   **File/Baris:** `app/Services/LetterService.php` (baris 33) memanggil `SanctionEngineService::evaluate()`.
*   **Temuan:** Modul surat dengan tepat menggunakan `SanctionEngineService` untuk mencocokkan total `net_points` terhadap aturan berjenjang di tabel `sanction_rules` tanpa mem-hardcode threshold. 

### F. Akses Role BK dan Tata Tertib
**Status: Sesuai**
*   **File/Baris:** `routes/web.php` (baris 61) dan `LetterPolicy.php` (baris 16, 32).
*   **Temuan:** Otorisasi tepat sasaran membatasi kepada role *Super Admin, BK, Principal, dan Tata Tertib*.

### G. Keamanan Penyimpanan PDF
**Status: Terdapat Celah Risiko Akses Publik (Perlu Keputusan)**
*   **File/Baris:** `app/Http/Controllers/LetterController.php` (baris 73).
*   **Temuan:** Penyimpanan menggunakan `Storage::disk('public')->put(...)`. Meskipun aplikasi mengontrol rute `/letters/{letter}` dengan *Policy*, jika perintah `php artisan storage:link` dijalankan di server, URL absolut PDF tersebut bisa diakses secara langsung dari luar tanpa otentikasi. PDF rahasia sebaiknya disimpan di *disk local* (non-public).

### H. Test Coverage untuk Tahap Panggilan
**Status: Belum Tersedia**
*   **File/Baris:** `tests/Feature/LetterTest.php`
*   **Temuan:** Tes sudah memeriksa kebenaran nilai poin kejadian, tapi belum menguji perubahan logis penandatangan antara Panggilan I, II, dan III.

---

## 2. Gap antara Spesifikasi Awal vs Aturan Bisnis Baru

1. **Gap Identitas:** Aturan baru mensyaratkan Waka Kesiswaan untuk Panggilan II, namun master data `School` saat ini belum memiliki field tersebut.
2. **Gap Historis Identitas:** Saat ini template PDF membaca `$school->principal_name` secara *live*. Jika pergantian kepala sekolah terjadi tahun depan, surat yang dicetak ulang dari arsip tahun lalu akan menampilkan nama kepala sekolah yang baru, padahal secara legal ini menyalahi aturan historis. Identitas penandatangan harus di-snapshot (disimpan) ke `metadata` JSON di tabel `letters`.
3. **Gap Status Sanksi Panggilan III:** Surat harus secara dinamis menyesuaikan deskripsi jika mencapai Panggilan III agar bunyinya tidak mengikat keputusan final (harus dibedah di *template blade*).

---

## 3. Rekomendasi Implementasi Minimal yang Aman

1. Tambahkan kolom `vice_principal_name` pada tabel `schools` via *migration* baru.
2. Sesuaikan *dropdown option* di `Create.vue` dan validasi controller untuk memecah tipe menjadi `call_1`, `call_2`, dan `call_3` (Panggilan I, II, III).
3. Modifikasi `LetterService` untuk men-snapshot *Signatory Title* dan *Signatory Name* ke dalam JSON `metadata` saat `Letter` di-generate.
4. Perbarui `letter.blade.php` agar blok tanda tangan menggunakan variabel snapshot tersebut dan mengatur tata letak yang sesuai.
5. Ubah penyimpanan `LetterController` dari `disk('public')` ke `disk('local')` agar berkas PDF tidak bocor via tautan *symlink*.

---

## 4. Daftar Komponen yang Perlu Diubah Jika Dilanjutkan

*   **Migration:** Buat satu migration baru untuk tabel `schools` (`add_vice_principal_name_to_schools_table`).
*   **Model:** `app/Models/School.php` (tambahkan *fillable*).
*   **Controller:** `app/Http/Controllers/LetterController.php` (ubah disk *storage*, validasi *enum* type).
*   **Service:** `app/Services/LetterService.php` (tambahkan pemetaan logika tahapan penandatangan ke snapshot `metadata`).
*   **Page (Vue):** `resources/js/Pages/Letters/Create.vue` (perbarui input jenis surat).
*   **View (PDF):** `resources/views/pdf/letter.blade.php` (render penandatangan dinamis dari `$metadata`).
*   **Test:** `tests/Feature/LetterTest.php` (uji tahap dan periksa disk *local*).
