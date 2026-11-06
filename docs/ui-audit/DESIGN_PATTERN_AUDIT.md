# EVALUASI DESIGN PATTERN DAN REUSABLE COMPONENTS
## DISIPLIN PRO: School Student-Discipline Administration SaaS

Dokumen ini menganalisis 12 pola desain (design patterns) yang diterapkan di seluruh aplikasi DISIPLIN PRO, mengidentifikasi variasi implementasi yang saling bertentangan, menunjuk halaman rujukan terbaik, serta merekomendasikan standardisasi komponen bersama (reusable components).

---

### 1. Evaluasi 12 Pola Desain Antarmuka

#### Pattern 1: Page Header Pattern
- **Implementasi Saat Ini:** Header halaman memuat slot breadcrumb di bilah navigasi atas, judul halaman (`h1` ukuran 24 px sampai 28 px dengan tracking-tight), deskripsi fungsi halaman abu-abu (`text-gray-500 text-sm`), dan tombol aksi primer di sebelah kanan.
- **Halaman yang Sudah Menerapkan:** `/master-data`, `/students`, `/discipline-events`, `/discipline-recap`, `/teachers`, `/class-rooms`, `/settings`.
- **Halaman yang Belum Menerapkan:** `/reports/monthly` dan `/reports/classes` memiliki judul yang dipadukan dengan tombol ekspor warna-warni kontras tinggi tanpa breadcrumb formal.
- **Implementasi Terbaik:** `/discipline-events` dan `/discipline-recap`. Menggabungkan judul, deskripsi jelas, indikator pil semester aktif, dan tombol aksi yang proporsional.
- **Rekomendasi Standardisasi:** Buat komponen bersama `<PageHeader>` yang menerima prop `title`, `description`, `badge`, serta slot `#actions`.

#### Pattern 2: Data Listing Pattern
- **Implementasi Saat Ini:** Halaman listing data membungkus konten dalam kartu putih besar (`bg-white rounded-[24px] p-7 shadow-[0_2px_20px_#00000006]`), diawali oleh toolbar penyaringan data, diikuti tabel, dan diakhiri informasi paginasi.
- **Halaman yang Sudah Menerapkan:** `/master-data`, `/students`, `/discipline-events`, `/discipline-recap`, `/teachers`, `/class-rooms`, `/users`.
- **Halaman yang Belum Menerapkan:** `/counseling-records` dan `/follow-ups` menempatkan modal create dan drawer form di dalam file yang sama dengan tabel sehingga file membengkak hingga lebih dari 800 baris.
- **Implementasi Terbaik:** `/master-data` dan `/discipline-events`.
- **Rekomendasi Standardisasi:** Pertahankan kontainer kartu tunggal 24 px dengan pemisahan border halus antara toolbar dan tabel.

#### Pattern 3: DataTable Pattern
- **Implementasi Saat Ini:** Terdapat dua dunia berbeda dalam aplikasi: PrimeVue DataTable Aura dengan responsive stack layout versus tabel HTML konvensional dengan baris manual.
- **Halaman yang Sudah Menerapkan:** `/master-data`, `/students`, `/discipline-events`, `/discipline-recap`.
- **Halaman yang Belum Menerapkan:** 11 halaman operasional (`/counseling-records`, `/follow-ups`, `/teachers`, `/class-rooms`, `/users`, `/audit-logs`, `/academic-years`, `/semesters`, `/reports/monthly`, `/reports/classes`, `/reports` tab arsip).
- **Implementasi Terbaik:** `/master-data` (untuk dataset client-side) dan `/discipline-events` (untuk dataset server-side).
- **Rekomendasi Standardisasi:** Tetapkan PrimeVue DataTable sebagai standar wajib tunggal untuk seluruh representasi data tabular di DISIPLIN PRO.

#### Pattern 4: Toolbar Pattern
- **Implementasi Saat Ini:** Bilah alat di atas tabel berisi kotak pencarian teks di sisi kiri dan tombol aksi atau filter di tengah/kanan.
- **Halaman yang Sudah Menerapkan:** `/master-data`, `/discipline-events`, `/discipline-recap`.
- **Halaman yang Belum Menerapkan:** `/reports/monthly` dan `/reports/classes` menggunakan blok form filter model vertikal lama tanpa search bar instan.
- **Implementasi Terbaik:** `/discipline-events/Index.vue`. Kotak pencarian memiliki icon search di kiri, tombol silang (clear) di kanan, serta input tinggi 36 px berbalut sudut membulat pil (`rounded-full`).
- **Rekomendasi Standardisasi:** Seluruh input pencarian pada toolbar wajib memiliki tinggi 36 px sampai 38 px, font 12 px, sudut `rounded-full`, dan teks placeholder kontekstual (contoh: "Cari nama atau NIP guru...").

#### Pattern 5: Filter Pattern
- **Implementasi Saat Ini:** Dropdown filter untuk menyaring data per entitas (kelas, semester, status, kategori).
- **Halaman yang Sudah Menerapkan:** `/discipline-events`, `/discipline-recap`, `/students`, `/master-data`.
- **Halaman yang Belum Menerapkan:** `/teachers`, `/class-rooms`, `/academic-years` sama sekali belum memiliki filter status aktif/non-aktif di toolbar.
- **Implementasi Terbaik:** `/discipline-recap`. Mengombinasikan dropdown semester, kelas, status risiko disiplin, dan rentang tanggal yang dapat diciutkan (collapsible).
- **Rekomendasi Standardisasi:** Sediakan tombol `Reset Filter` teks bergaris bawah yang hanya muncul saat filter dalam kondisi aktif.

#### Pattern 6: Form Pattern
- **Implementasi Saat Ini:** Terdapat dua corak form yang tidak sinkron:
  1. Corak Registration Workspace (`Students/Form.vue`): Grid 12 kolom lebar dengan live preview di kolom kanan.
  2. Corak Formulir Kartu Tunggal Terpusat (`Teachers/Form.vue`, `ClassRooms/Form.vue`, `MasterData` forms): Lebar sempit `max-w-3xl` dengan tombol Simpan di bawah.
- **Halaman yang Sudah Menerapkan Workspace Modern:** `Students/Form.vue`.
- **Halaman yang Belum Menerapkan Workspace Modern:** `Teachers/Form.vue`, `ClassRooms/Form.vue`, `AcademicYears/Form.vue`, `Semesters/Form.vue`.
- **Implementasi Terbaik:**
  - Untuk entitas kompleks (banyak field): Gunakan Workspace 12 kolom seperti `Students/Form.vue`.
  - Untuk entitas sederhana (1-4 field): Gunakan kartu terpusat rapi seperti `ClassRooms/Form.vue`, namun dengan input tinggi 42 px, rounded-xl, dan label konsisten.
- **Rekomendasi Standardisasi:** Samakan styling border input (`border-gray-200 focus:ring-[#001AFF] focus:border-transparent`) dan penanda kesalahan validasi merah di bawah input terkait.

#### Pattern 7: Detail Page Pattern
- **Implementasi Saat Ini:** Halaman detail menyajikan data profil entitas di sisi kiri dan linimasa kejadian atau rekam jejak di sisi kanan.
- **Halaman yang Sudah Menerapkan:** `/students/{id}` dan `/counseling-records/{id}`.
- **Implementasi Terbaik:** `/students/{id}` (`Students/Show.vue`). Kartu identitas siswa dan ringkasan poin di kolom kiri, serta riwayat pelanggaran/prestasi dan pembinaan BK berbasis tab di kolom kanan.
- **Rekomendasi Standardisasi:** Jadikan pola 2 kolom (Ringkasan Profil + Linimasa Tab) sebagai standar resmi untuk seluruh halaman detail entitas.

#### Pattern 8: Dashboard Pattern
- **Implementasi Saat Ini:** Dasbor berbasis peran (Role-based Dashboard) yang menampilkan kartu selamat datang personal, 4 kartu KPI ringkasan, grafik tren garis bulanan, serta tabel daftar siswa prioritas pembinaan.
- **Halaman yang Menerapkan:** `/dashboard` dan `/reports/analytics`.
- **Implementasi Terbaik:** `/dashboard` (`Dashboard.vue`). Sangat kaya konteks sesuai peran yang login (Super Admin, Tata Tertib, BK, Guru, Kepala Sekolah).
- **Rekomendasi Standardisasi:** Pertahankan arsitektur dashboard ini dan pastikan kartu KPI menggunakan token warna semantik (`indigo`, `rose`, `emerald`, `amber`).

#### Pattern 9: Modal & Dialog Pattern
- **Implementasi Saat Ini:** Menggunakan PrimeVue `Dialog` dengan opsi modal dan header terstruktur.
- **Halaman yang Sudah Menerapkan:** `/discipline-events` (modal detail kejadian), `/discipline-recap` (modal ringkasan siswa), `/follow-ups` (modal buat/ubah rencana), `/users` (modal ubah kata sandi).
- **Implementasi Terbaik:** `/discipline-events/Index.vue` dan `/discipline-recap/Index.vue`. Dialog menggunakan prop `:breakpoints="{ '960px': '75vw', '640px': '92vw' }"`, header yang jelas, dan tombol aksi terstruktur di footer dialog.
- **Rekomendasi Standardisasi:** Seluruh dialog wajib memiliki lebar maksimal terdefinisi (`width: 560px` atau `640px`) dan tombol tutup/batal yang mudah dijangkau.

#### Pattern 10: Notification & Confirmation Pattern
- **Implementasi Saat Ini:** Terjadi inkonsistensi antara SweetAlert2 (`Swal.fire`), PrimeVue ConfirmDialog (`useConfirm`), dan alert browser native (`alert()`).
- **Halaman Pengguna SweetAlert2:** `/master-data`, `/students`, `/discipline-events`, `/counseling-records/show`.
- **Halaman Pengguna useConfirm:** `/teachers`, `/class-rooms`, `/academic-years`, `/semesters`, `/users`, `/follow-ups`.
- **Halaman Pengguna alert native:** `/discipline-events/create`.
- **Implementasi Terbaik:** SweetAlert2 pada `MasterData/Index.vue` dan `DisciplineEvents/Index.vue` untuk konfirmasi hapus, serta PrimeVue Toast (`useToast`) pada `AuthenticatedLayout.vue` untuk notifikasi flash session.
- **Rekomendasi Standardisasi:**
  1. Hapus seluruh penggunaan `alert()` bawaan browser.
  2. Standarisasi seluruh dialog konfirmasi hapus dan tindakan kritis menggunakan SweetAlert2 dengan warna merah `#EF4444` dan pembatalan abu-abu `#6B7280`.
  3. Gunakan PrimeVue Toast untuk pesan sukses/gagal yang bersifat non-blocking.

#### Pattern 11: Empty State Pattern
- **Implementasi Saat Ini:** Area visual yang muncul saat tabel tidak memiliki data, dilengkapi icon berlatar warna lembut, judul ramah, dan kalimat penjelas.
- **Halaman yang Sudah Menerapkan:** `/discipline-events`, `/discipline-recap`, `/master-data`, `/students`, `/counseling-records`.
- **Halaman yang Belum Menerapkan:** `/reports/monthly` dan `/reports/classes` hanya menampilkan baris tabel kosong tanpa pesan bantuan.
- **Implementasi Terbaik:** `/discipline-recap/Index.vue` (icon bar chart indigo, judul jelas, dan instruksi penyetelan filter).
- **Rekomendasi Standardisasi:** Seluruh empty state tabel wajib memuat icon PrimeIcons berukuran 24 px di dalam kontainer `w-14 h-14 rounded-2xl bg-indigo-50 text-[#001AFF]`.

#### Pattern 12: Loading State Pattern
- **Implementasi Saat Ini:** Menggunakan indikator pemrosesan tombol Inertia (`form.processing`) yang mendisposisikan tombol ke status `disabled:opacity-50` serta memunculkan spinner kecil.
- **Halaman yang Sudah Menerapkan:** `/students/Form.vue`, `/auth/Login.vue`.
- **Halaman yang Belum Menerapkan:** Sebagian besar form klasik belum menampilkan animasi spinner saat request sedang berlangsung.
- **Rekomendasi Standardisasi:** Tambahkan atribut `disabled="form.processing"` dan icon putar `pi pi-spin pi-spinner` pada seluruh tombol Simpan saat status form sedang diproses.

---

### 2. Peluang Ekstraksi Reusable Components

Untuk memangkas redundansi kode dan menjamin konsistensi 100%, berikut 5 komponen bersama yang direkomendasikan untuk diekstraksi ke direktori `resources/js/Components/`:

1. **`AppPageHeader.vue`:** Komponen header halaman standar yang menyatukan breadcrumb, judul halaman, deskripsi, badge status, dan tombol aksi.
2. **`AppStatusBadge.vue`:** Komponen badge status semantik (Aman, Perhatian, Waspada, Kritis, Aktif, Nonaktif) yang menjamin keseragaman warna dan bentuk pil di seluruh aplikasi.
3. **`AppTableToolbar.vue`:** Komponen toolbar tabel standar yang menyatukan search bar debounced, slot dropdown filter, tombol reset filter, dan penghitung jumlah data.
4. **`AppEmptyState.vue`:** Komponen status kosong seragam untuk tabel atau linimasa yang tidak memiliki rekaman data.
5. **`AppFormSection.vue`:** Kartu pembungkus section form lengkap dengan icon, judul section, deskripsi pembantu, dan penanda wajib diisi.

