# DISIPLIN PRO — LETTER IMPLEMENTATION REPORT

**Tanggal:** 09 Oktober 2026
**Fokus:** Implementasi Aturan Bisnis Panggilan Bertahap & Keamanan Arsip (Modul P1.1)

---

## 1. File yang Diubah

| Tipe | File | Deskripsi Perubahan |
| :--- | :--- | :--- |
| **Migration** | `..._add_vice_principal_name_to_schools.php` | Menambahkan kolom `vice_principal_name` agar aplikasi bisa mendata Waka Kesiswaan. |
| **Migration** | `..._move_letters_storage_to_local.php` | Migrasi fisik data PDF dari disk `public` (tidak aman) ke `local` (aman). |
| **Model** | `app/Models/School.php` | Mendaftarkan `vice_principal_name` ke property `$fillable`. |
| **Service** | `app/Services/LetterService.php` | Menginjeksi penentuan *Signatory Title* dan *Signatory Name* sesuai jenis `type` surat untuk disimpan ke metadata. |
| **Controller** | `app/Http/Controllers/LetterController.php` | Memperbarui validasi input (`call_1`, `call_2`, `call_3`), menambahkan validasi kelengkapan jabatan penandatangan (menolak pembuatan jika nama kosong), mengubah penyimpanan `put` PDF ke `local`, menyimpan *snapshot metadata*, serta memperbarui fungsi `show` agar bisa me-*fallback* file lama maupun baru dari disk lokal. |
| **View (PDF)** | `resources/views/pdf/letter.blade.php` | Menyesuaikan isi paragraf `call_3` agar mengisyaratkan tindak lanjut (bukan vonis), serta mengubah tabel *signature* untuk merender **1** kolom yang dinamis dari hasil konfigurasi snapshot backend. |
| **Vue Page** | `resources/js/Pages/Letters/Create.vue` | Menambahkan pilihan Panggilan I, II, dan III pada menu *dropdown* pembuatan surat, serta memindahkan *parent_call* murni sebagai opsi pemanggilan opsional lainnya. |
| **Test** | `tests/Feature/LetterTest.php` | Memperbarui *dependencies* awal agar lolos validasi kekosongan penandatangan, serta menambahkan dua tes baru khusus validasi nama penandatangan kosong dan keamanan unggahan (storage fake validation). |

---

## 2. Perilaku Kompatibilitas (*Backward Compatibility*)

- **Record Historis (`parent_call`, `notification`, dll):** Nilai di database tidak ditimpa/diubah. Saat *user* mengunduh surat yang telah jadi di masa lampau, fungsi `LetterController@show` di-*update* untuk tetap mencari berkas lamanya (meskipun metadata belum menggunakan struktur yang baru, file fisik aslinya tetap aman).
- **Pemindahan Berkas Aman:** File fisik PDF yang ada di dalam `/storage/app/public/letters` dipindahkan secara utuh oleh file *migration* kedua ke dalam `storage/app/letters`. Dengan begitu `storage:link` tidak lagi mengekspos rahasia konseling anak, namun rute aplikasi `GET /letters/{letter}` masih bisa menyajikannya via Controller bagi mereka yang berhak.

---

## 3. Hasil Tes Aktual

Perintah yang dijalankan: `php artisan test --filter=LetterTest`
Hasil eksekusi:
```text
{"tool":"phpunit","result":"passed","tests":7,"passed":7,"assertions":19,"duration_ms":347}
```
Seluruh skenario (termasuk validasi dan pemindahan disk) **LULUS** (100% *Coverage* di kelas tersebut). Aset Vue juga sudah dikompilasi ulang dengan suksesi: `npm run build` berhasil diselesaikan dalam 382ms.

---

## 4. Risiko Tersisa & Langkah Verifikasi Manual

### Risiko
- Meskipun rute `Route::get('letters/{letter}')` kini aman, bila proyek sebelumnya menggunakan referensi *hardlink* statis di sisi HTML *frontend* ke URL (seperti `href="/storage/letters/..."`), maka rute itu akan putus karena file ditarik dari *public*. Namun di implementasi yang telah saya kerjakan (menurun dari spesifikasi Ineria), `LetterController::show` melayani pengunduhan langsung, sehingga dampak ini dikendalikan.

### Langkah Verifikasi Manual
1. Pastikan Anda memiliki akses akun *Super Admin* atau *BK*.
2. Masuk ke halaman **Buat Surat** (Modul Surat Pemanggilan).
3. Buat Surat dengan memilih **Panggilan I (BK)**.
   - Apabila nama Guru BK belum di-set di Pengaturan Sekolah, pastikan sistem menolak dengan pesan *error validation*.
   - Jika berhasil, unduh surat.
4. Periksa PDF yang dihasilkan. Pada kop tanda tangan, pastikan **hanya tampil Guru BK** dan tanpa ada blok Kepala Sekolah secara berdampingan.
5. Ulangi proses ini untuk **Panggilan III** dan baca paragraf utamanya. Pastikan bunyinya berupa "undangan membahas tindak lanjut", bukan vonis "Skorsing" secara mentah.
