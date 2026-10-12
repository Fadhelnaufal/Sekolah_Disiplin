# DISIPLIN PRO — LETTER SECURITY HOTFIX REPORT

**Tanggal Perbaikan:** 09 Oktober 2026
**Fokus:** Perbaikan Keamanan File Arsip PDF (Modul P1.1)

---

## 1. Lingkup Masalah yang Diperbaiki

Berdasarkan laporan *Post-Implementation Audit*, terdapat dua kerentanan keamanan yang segera ditangani melalui *hotfix* ini:
1. **Public Archive Exposure:** File PDF historis masih tersisa di direktori `storage/app/public/letters` setelah operasi perpindahan disk, yang menyebabkan file tersebut bisa diakses publik (tanpa autentikasi) jika *symlink* aktif.
2. **Orphaned File via DB Transaction Failure:** PDF yang telah lebih dulu dibangkitkan dan ditulis ke dalam disk `local` tidak ikut dibersihkan (tertinggal sebagai file yatim) ketika blok penulisan record ke *database* (`DB::transaction`) mengalami kegagalan.

---

## 2. File yang Diubah dan Ditambahkan

| Tipe | File | Deskripsi Perbaikan |
| :--- | :--- | :--- |
| **Migration** | `..._cleanup_public_letters_storage.php` | Menambahkan *migration* korektif baru yang bersifat *idempotent* dan memverifikasi kesamaan ukuran file antara disk `public` dan `local`. File baru dihapus dari direktori publik **hanya setelah** validasi salinan di disk `local` berhasil. Jika direktori kosong, foldernya akan dihapus sepenuhnya. |
| **Controller** | `app/Http/Controllers/LetterController.php` | Menambahkan struktur `try...catch` yang membungkus `DB::transaction`. Jika transaksi *database* mengalami *Exception*, sistem akan segera menghapus artefak PDF sementara yang tertulis di disk `local`. |
| **Test** | `tests/Feature/LetterTest.php` | Menambahkan tes baru `test_cleans_up_orphaned_file_on_db_failure` yang mensimulasikan gangguan simpan *database* (melalui event `Model::saving`), kemudian memverifikasi bahwa *disk fake local* sudah bersih secara otomatis tanpa meninggalkan PDF yatim. |

---

## 3. Strategi Keamanan Konsistensi

- **Verifikasi Sebelum Penghapusan:** Migrasi pembersihan PDF arsip publik (*cleanup migration*) diimplementasikan sedemikian rupa untuk tidak langsung menghapus file yang ada. Sistem memeriksa ketersediaannya di *disk* privat dan menimbang *size file* yang ada sebelum merusak arsip aslinya, sehingga data tidak akan hilang bila terjadi gangguan penyalinan.
- **Transactional Rollback for Storage:** Kegagalan pengiriman ke *database* sekarang tidak hanya melakukan *rollback* pada SQL, namun juga melakukan proses *rollback* fisik dengan menghapus file PDF yang sempat tercetak dan tersimpan secara temporer sebelum di-commit secara penuh.

---

## 4. Hasil Audit dan Tes Pasca-Perbaikan

*   **Pengecekan Manual Disk:** Perintah validasi memastikan direktori `storage/app/public/letters` kini telah **KOSONG / TIDAK ADA LAGI**. URL statis untuk mengakses file PDF publik sekarang secara efektif mati (status 404). Semua file rahasia berhasil dimutasi ke *storage* yang di-*serve* ketat oleh *policy*.
*   **Pengujian PHPUnit:**
    `php artisan test --filter=LetterTest`
    *Hasil Eksekusi:*
    `{"tool":"phpunit","result":"passed","tests":8,"passed":8,"assertions":21,"duration_ms":407}`
    Tes memvalidasi bahwa blok transaksi *catch* berfungsi baik, serta semua perilaku *legacy* terdahulu tetap tak terganggu (100% *Pass*).

---

## 5. Kesimpulan dan Status Risiko

Sistem sudah **AMAN (SECURE)**. Hak akses *role* (Super Admin, BK, Principal, Tata Tertib) tidak diperluas dan tetap setia pada spesifikasi awal DISIPLIN PRO. Seluruh rekomendasi perbaikan minor maupun tinggi yang tercatat pada laporan audit sebelumnya **sudah ditutup secara tuntas.**
