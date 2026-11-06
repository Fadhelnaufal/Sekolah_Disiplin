# AUDIT IMPLEMENTASI TABEL DATA DAN STANDARDISASI DATATABLE
## DISIPLIN PRO: School Student-Discipline Administration SaaS

Dokumen ini menyajikan audit mendalam terhadap seluruh halaman di aplikasi DISIPLIN PRO yang menampilkan informasi dalam bentuk tabel data. Evaluasi ini membandingkan implementasi aktual terhadap standar acuan PrimeVue DataTable Aura (`MasterData/Index.vue`) dan menetapkan rekomendasi tindakan konkret untuk setiap halaman.

---

### 1. Tabel Matriks Audit Komponen Tabel Data

| Halaman | Jenis Data | Implementasi Saat Ini | PrimeVue DataTable | Pagination | Sorting | Search | Filter | Rekomendasi |
|---|---|---|---|---|---|---|---|---|
| `/master-data` | Master Pelanggaran, Prestasi, Sanksi | PrimeVue DataTable + Column | Ya | Client (10, 25, 50) | Ya (removableSort) | Ya (Client Live) | Kategori | Keep (Benchmark Acuan) |
| `/students` | Direktori Siswa & Poin Netto | PrimeVue DataTable + Column | Ya | Server Paginator | Ya (Server/Client) | Ya (Debounced) | Kelas, Status Risiko | Keep |
| `/discipline-events` | Catatan Kejadian Disiplin Siswa | PrimeVue DataTable + Column | Ya | Server (10, 15, 25, 50) | Ya (removableSort) | Ya (Debounced) | Jenis, Kelas, Semester | Keep |
| `/discipline-recap` | Rekapitulasi Poin & Status Siswa | PrimeVue DataTable + Column | Ya | Client (10, 15, 25, 50) | Ya (removableSort) | Ya (Debounced) | Semester, Kelas, Status, Periode | Keep |
| `/counseling-records` | Kasus Pembinaan Konseling Siswa | HTML `<table>` manual | Tidak | Server Links Manual | Tidak Ada | Ya (Debounced) | Kategori, Status Kasus | Migrate (Prioritas P0) |
| `/follow-ups` | Monitoring Rencana Tindak Lanjut | HTML `<table>` manual | Tidak | Server Links Manual | Tidak Ada | Ya (Debounced) | Tab Status Kasus | Migrate (Prioritas P0) |
| `/teachers` | Direktori Data Tenaga Pendidik | HTML `<table>` manual | Tidak | Server Links Manual | Tidak Ada | Ya (Debounced) | Tidak Ada | Migrate (Prioritas P1) |
| `/class-rooms` | Direktori Rombongan Belajar | HTML `<table>` manual | Tidak | Server Links Manual | Tidak Ada | Ya (Debounced) | Tidak Ada | Migrate (Prioritas P1) |
| `/users` | Akun Pengguna & Hak Akses | HTML `<table>` manual | Tidak | Server Links Manual | Tidak Ada | Ya (Debounced) | Peran (Role) | Migrate (Prioritas P1) |
| `/audit-logs` | Jejak Rekam Aktivitas Pengguna | HTML `<table>` + Baris Expand | Tidak | Server Links Manual | Tidak Ada | Ya (Debounced) | Aksi, Rentang Tanggal | Migrate (Prioritas P1) |
| `/reports/monthly` | Rekapitulasi Kejadian Bulanan | HTML `<table>` manual | Tidak | Tidak Ada | Tidak Ada | Tidak Ada | Semester, Kelas, Tanggal | Migrate / Konsolidasi (P1) |
| `/reports/classes` | Rekapitulasi Agregat per Kelas | HTML `<table>` manual | Tidak | Tidak Ada | Tidak Ada | Tidak Ada | Semester | Migrate (Prioritas P1) |
| `/reports` (Arsip) | Riwayat Penerbitan Surat Resmi | HTML `<table>` manual (Tab Arsip) | Tidak | Server Links Manual | Tidak Ada | Tidak Ada | Kategori Surat | Migrate (Prioritas P1) |
| `/academic-years` | Daftar Tahun Ajaran Sekolah | HTML `<table>` manual | Tidak | Server Links Manual | Tidak Ada | Ya (Debounced) | Tidak Ada | Migrate (Prioritas P2) |
| `/semesters` | Daftar Semester & Status Aktif | HTML `<table>` manual | Tidak | Server Links Manual | Tidak Ada | Ya (Debounced) | Tahun Ajaran | Migrate (Prioritas P2) |
| `/students/{id}/print-card`| Pratinjau Cetak Kartu Disiplin | HTML `<table>` khusus cetak | Tidak | Tidak Berlaku | Tidak Berlaku | Tidak Berlaku | Tidak Berlaku | Not Recommended (Print Doc) |
| `/imports` (Preview) | Validasi Data Unggahan Excel | HTML `<table>` preview sheet | Tidak | Tidak Berlaku | Tidak Berlaku | Tidak Berlaku | Sheet Type | Not Recommended (Stepper Preview) |
| `/violations` (Legacy) | Master Pelanggaran (Teralihkan) | PrimeVue DataTable | Ya | Server Links Manual | Tidak Ada | Ya | Tidak Ada | Not Recommended (Hapus Berkas) |
| `/achievements` (Legacy)| Master Prestasi (Teralihkan) | PrimeVue DataTable | Ya | Server Links Manual | Tidak Ada | Ya | Tidak Ada | Not Recommended (Hapus Berkas) |
| `/sanction-rules` (Legacy)| Master Aturan Sanksi (Teralihkan) | HTML `<table>` manual | Tidak | Tidak Ada | Tidak Ada | Tidak Ada | Tidak Ada | Not Recommended (Hapus Berkas) |
| `/letters` (Legacy) | Riwayat Surat (Teralihkan) | HTML `<table>` manual | Tidak | Server Links Manual | Tidak Ada | Tidak Ada | Tidak Ada | Not Recommended (Hapus Berkas) |

---

### 2. Analisis Kesenjangan Implementasi

#### A. Halaman yang Berstatus 'Keep' (4 Halaman Aktif)
Keempat halaman ini (`/master-data`, `/students`, `/discipline-events`, dan `/discipline-recap`) telah memenuhi seluruh standar emas aplikasi DISIPLIN PRO:
1. Menggunakan PrimeVue `DataTable` dan `Column` dengan tema Aura.
2. Memiliki styling tabel modern tanpa border vertikal (`p-datatable-sm`, border pembagi horizontal halus `border-gray-50`, dan efek hover `hover:bg-gray-50/60`).
3. Memiliki tata letak responsif bertumpuk (`responsiveLayout="stack"`, `breakpoint="768px"`) sehingga tampilan mobile tidak mengalami horizontal overflow yang merusak estetika.
4. Memiliki kontrol paginasi standar lengkap dengan pemilih ukuran halaman (10, 15, 25, 50).
5. Memiliki empty state yang ramah dan informatif saat hasil pencarian atau penyaringan nihil.

#### B. Halaman Prioritas Utama Migrasi: Bimbingan Konseling dan Tindak Lanjut (P0)
- **`/counseling-records`:** Ini adalah buku kerja harian bagi Guru BK. Data kasus pembinaan memiliki banyak atribut (nama siswa, kelas, konselor, kategori topik, tanggal pelaksanaan, status kasus, dan tingkat kerahasiaan). Menggunakan tabel HTML biasa membuat staf BK kesulitan melakukan sorting per tanggal atau per nama siswa. Paginasi tautan manual juga memicu reload halaman penuh yang mengganggu alur kerja.
  - *Rekomendasi:* Migrasi ke PrimeVue DataTable dengan kolom: No, Tanggal Sesi, Siswa & Kelas, Konselor / Pencatat, Topik / Kategori, Tingkat Kerahasiaan, Status Kasus, dan Kolom Aksi (Lihat Kasus, Edit, Notifikasi WhatsApp).
- **`/follow-ups`:** Modul pengawalan sanksi dan tindak lanjut siswa. Saat ini menggunakan tabel HTML manual dengan tab status (Pending, In Progress, Completed, Overdue).
  - *Rekomendasi:* Migrasi ke PrimeVue DataTable dengan integrasi badge status tenggat waktu (termasuk penanda visual merah jika terlambat/overdue) dan sortir dinamis berdasarkan due date.

#### C. Halaman Prioritas Tinggi Migrasi: Data Master Administrasi (P1)
- **`/teachers` dan `/class-rooms`:** Merupakan modul data master utama. Jumlah rekaman guru dan rombel pada sekolah menengah berkisar antara 20 sampai 100 entitas. Sangat ideal dikelola menggunakan PrimeVue DataTable dengan fitur pencarian cepat dan pengurutan abjad nama atau NIP.
- **`/users`:** Manajemen akun pengguna memiliki variasi hak akses (Super Admin, Tata Tertib, BK, Guru, Kepala Sekolah). PrimeVue DataTable akan mempermudah filtering akun per peran dan sorting tanggal pembuatan akun.
- **`/audit-logs`:** Memuat catatan rekam jejak sistem. Menggunakan PrimeVue DataTable dengan fitur bawaan baris ekspansi (`rowExpansion`) akan jauh lebih elegan dan bersih dibandingkan manipulasi array `expandedLogs` manual berbasis script yang saat ini digunakan pada HTML table.
- **`/reports/monthly` dan `/reports/classes`:** Dua halaman ini masih menggunakan tabel HTML sederhana tanpa paginator dan tanpa pencarian. Khusus `/reports/monthly`, fungsinya sebagian besar telah disempurnakan oleh `/discipline-recap`. Jika tetap dipertahankan sebagai laporan bulanan formal, maka tabelnya wajib dimigrasi ke PrimeVue DataTable agar memiliki standar visual yang sama.

#### D. Halaman yang 'Not Recommended' untuk DataTable
- **`/students/{id}/print-card`:** Lembar cetak kartu poin kedisiplinan siswa dirancang khusus untuk dicetak fisik pada kertas ukuran A4 atau F4 dengan format kop surat resmi dan tabel bordered monokrom. Menggunakan PrimeVue DataTable pada halaman cetak justru akan memuat javascript widget yang tidak diperlukan saat window.print() dijalankan.
- **`/imports` (Tab Pratinjau):** Tabel pratinjau import data Excel hanya bersifat sementara (transient) untuk memvalidasi kolom berkas sebelum dilakukan commit basis data.

---

### 3. Standar Konfigurasi PrimeVue DataTable Aplikasi

Untuk seluruh halaman yang akan dimigrasikan, gunakan konfigurasi standar berikut:

```vue
<DataTable
    :value="items"
    :paginator="items.length > 10"
    :rows="rowsPerPage"
    :rowsPerPageOptions="[10, 15, 25, 50]"
    paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
    currentPageReportTemplate="Menampilkan {first} sampai {last} dari {totalRecords} data"
    responsiveLayout="stack"
    breakpoint="768px"
    class="p-datatable-sm"
    removableSort
    emptyMessage="Tidak ada data yang sesuai dengan kriteria pencarian."
>
    <!-- Column Definitions -->
</DataTable>
```

Aturan penataan header dan sel kolom:
- Header (`th`): `text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100 pb-3`
- Baris Data (`tr`): `hover:bg-gray-50/60 transition-colors border-b border-gray-50`
- Rata kiri (`text-left`) untuk kolom teks dan identitas (Nama, NIS, NIP, Kategori).
- Rata tengah (`text-center`) untuk kolom angka, poin, status badge, dan tanggal.
- Rata kanan (`text-right`) untuk kolom aksi baris.

