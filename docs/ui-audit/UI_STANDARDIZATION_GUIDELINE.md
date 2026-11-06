# PANDUAN STANDARDISASI DESAIN ANTARMUKA PENGGUNA
## DISIPLIN PRO: Official UI Design Specification

Dokumen ini merupakan panduan spesifikasi dan standardisasi resmi untuk antarmuka pengguna DISIPLIN PRO. Seluruh pengembangan fitur baru dan pembenahan tampilan yang sudah ada wajib berpedoman pada nilai token, ukuran komponen, dan aturan interaksi di bawah ini.

---

### 1. Standar Tipografi

Aplikasi menggunakan font keluarga **`Outfit`** (didefinisikan dalam Tailwind sebagai `font-sans`).

| Elemen Tipografi | Kelas Tailwind | Ukuran / Bobot | Kegunaan |
|---|---|---|---|
| **Page Title** | `text-[26px] sm:text-[28px] font-bold text-gray-900 tracking-tight leading-tight` | 28 px, Bold (700) | Judul utama setiap halaman |
| **Section Title** | `text-base sm:text-lg font-bold text-gray-900 tracking-tight` | 16-18 px, Bold (700) | Judul kelompok informasi / kartu form |
| **Subsection / Card Title** | `text-sm sm:text-base font-bold text-gray-900` | 14-16 px, Bold (700) | Judul kartu kecil atau dialog header |
| **Body Text** | `text-sm text-gray-700 leading-relaxed` | 14 px, Normal (400) | Teks isi, deskripsi umum, notulensi kasus |
| **Field Label** | `text-xs font-bold text-gray-700 uppercase tracking-wider` | 12 px, Bold (700) | Label di atas input form |
| **Helper Text** | `text-[11px] text-gray-400 leading-normal` | 11 px, Normal (400) | Petunjuk pengisian di bawah input form |
| **Table Header** | `text-xs font-semibold text-gray-400 uppercase tracking-wider` | 12 px, SemiBold (600) | Judul kolom pada tabel data (`th`) |
| **Table Data (Primary)** | `text-xs sm:text-sm font-bold text-gray-900` | 12-14 px, Bold (700) | Nama siswa, nama guru, nama pelanggaran |
| **Table Data (Secondary)**| `text-xs font-medium text-gray-600` | 12 px, Medium (500) | Kelas, tanggal, kategori, email |
| **Data Code / ID** | `font-mono text-xs text-gray-500` | 12 px, Monospace | NIS, NIP, Kode Pelanggaran, ID Kasus |
| **Badge Label** | `text-[10px] sm:text-[11px] font-bold uppercase tracking-wider` | 10-11 px, Bold (700) | Status siswa, peran, tingkat kerahasiaan |

---

### 2. Standar Spasi dan Ruang Tata Letak

| Kategori Spasi | Nilai Standar | Kelas Tailwind | Keterangan |
|---|---|---|---|
| **Page Max Width** | 1400 px | `max-w-[1400px] mx-auto` | Lebar maksimum konten utama |
| **Page Padding** | 16 px sampai 32 px | `px-4 sm:px-6 lg:px-8` | Padding horizontal layout aplikasi |
| **Section Gap** | 24 px | `space-y-6` | Jarak antar-kartu atau antar-blok vertikal |
| **Card Padding** | 24 px sampai 28 px | `p-6 sm:p-7` | Padding internal kartu utama 24 px |
| **Compact Card Padding** | 16 px sampai 20 px | `p-4 sm:p-5` | Padding kartu statistik KPI |
| **Toolbar Gap** | 10 px sampai 16 px | `gap-2.5 sm:gap-4` | Jarak antar-input dan dropdown di toolbar |
| **Form Fields Gap** | 16 px sampai 20 px | `gap-4 sm:gap-5` atau `space-y-4` | Jarak antar-field isian form |
| **Table Cell Padding** | Vertikal 14 px | `py-3.5` atau `py-4` | Jarak atas-bawah baris data tabel |

---

### 3. Standar Ukuran Komponen dan Radius

#### A. Tombol Aksi (Buttons)
- **Primary Pill Action (Tambah / Input Baru):**
  - Kelas: `px-5 py-2.5 rounded-full bg-[#001AFF] hover:bg-[#0015cc] text-white text-xs font-semibold shadow-xs flex items-center gap-1.5 cursor-pointer transition-all`
  - Tinggi terhitung: 36 px sampai 38 px.
- **Secondary Action (Batal / Unduh / Ekspor):**
  - Kelas: `px-4 py-2 rounded-full border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-xs flex items-center gap-1.5 cursor-pointer transition-all`
  - Tinggi terhitung: 36 px.
- **Table Row Action (Ikon Aksi Bulat):**
  - Kelas: `w-7 h-7 rounded-full bg-gray-100 hover:bg-indigo-50 text-gray-600 hover:text-[#001AFF] flex items-center justify-center transition-colors cursor-pointer`
  - Ukuran: 28 px x 28 px bulat sempurna.

#### B. Masukan Formulir dan Toolbar (Inputs & Selects)
- **Toolbar Search Input:**
  - Tinggi: 36 px sampai 38 px (`py-2`).
  - Radius: Sudut membulat penuh (`rounded-full`).
  - Ikon: `pi pi-search` absolut sisi kiri (`left-3.5 text-xs text-gray-400`).
  - Latar: `bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-[#6366F1]`.
- **Toolbar Dropdown Select:**
  - Tinggi: 36 px sampai 38 px (`py-2 px-3.5`).
  - Radius: Sudut membulat penuh (`rounded-full`).
  - Latar: `bg-gray-50 border border-gray-200 text-xs text-gray-700`.
- **Standard Form Input & Select (di dalam lembar formulir):**
  - Tinggi: 42 px sampai 44 px (`py-2.5 px-3.5` atau `py-3 px-4`).
  - Radius: `rounded-xl`.
  - Fokus: `focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#001AFF] focus:border-transparent`.

#### C. Kelengkungan Sudut (Border Radius Scale)
- **Main Content Cards:** `rounded-[24px]` (identitas visual khas DISIPLIN PRO).
- **KPI Summary Cards:** `rounded-[24px]`.
- **Form Section Cards:** `rounded-[24px]`.
- **Modal Dialogs:** `rounded-[24px]` atau `rounded-3xl`.
- **Inputs & Dropdowns (Form):** `rounded-xl`.
- **Inputs & Dropdowns (Toolbar):** `rounded-full`.
- **Buttons (Utama & Sekunder):** `rounded-full`.
- **Status Badges:** `rounded-full` (dengan dot indicator bulat) atau `rounded-md`.

---

### 4. Standar PrimeVue DataTable

Seluruh tabel data di aplikasi DISIPLIN PRO wajib mengikuti konfigurasi berikut:

```vue
<DataTable
    :value="items"
    :paginator="items.length > 0"
    :rows="15"
    :rowsPerPageOptions="[10, 15, 25, 50]"
    paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
    currentPageReportTemplate="Menampilkan {first} sampai {last} dari {totalRecords} data"
    responsiveLayout="stack"
    breakpoint="768px"
    class="p-datatable-sm"
    removableSort
    emptyMessage="Tidak ada data yang sesuai dengan filter pencarian."
>
    <!-- Kolom 1: Nomor Urut (Selalu Rata Tengah) -->
    <Column header="No" class="w-14 text-center py-3.5 pl-2 text-xs font-mono text-gray-400">
        <template #body="slotProps">
            {{ slotProps.index + 1 }}
        </template>
    </Column>

    <!-- Kolom Identitas Pokok (Selalu Rata Kiri) -->
    <Column field="name" header="Nama Entitas" sortable class="py-3.5 min-w-[200px]" />

    <!-- Kolom Angka / Metrik (Selalu Rata Tengah) -->
    <Column field="points" header="Poin" sortable class="py-3.5 text-center w-32" />

    <!-- Kolom Status (Selalu Rata Tengah) -->
    <Column field="status" header="Status" sortable class="py-3.5 text-center w-36" />

    <!-- Kolom Aksi (Selalu Rata Kanan atau Tengah) -->
    <Column header="Aksi" class="py-3.5 text-center w-32 pr-2">
        <template #body="slotProps">
            <div class="inline-flex items-center gap-1.5 justify-center">
                <!-- Action Buttons -->
            </div>
        </template>
    </Column>
</DataTable>
```

---

### 5. Standar Semantic Status Colors

Wajib menggunakan kombinasi warna semantik yang telah ditetapkan dalam `DESIGN.md`:

| Status | Makna Operasional | Background | Teks | Border |
|---|---|---|---|---|
| **Aman / Sukses** | 0 Poin Bersih, Akun Aktif, Prestasi Disetujui | `bg-emerald-50` | `text-emerald-700` | `border-emerald-200` |
| **Perhatian** | 1 - 24 Poin Bersih, Kasus Diproses | `bg-amber-50` | `text-amber-700` | `border-amber-200` |
| **Waspada** | 25 - 49 Poin Bersih, Panggilan Ortu I | `bg-orange-50` | `text-orange-700` | `border-orange-200` |
| **Kritis / Bahaya** | >= 50 Poin Bersih, Sanksi Berat, Terlambat (Overdue) | `bg-red-50` | `text-red-700` | `border-red-200` |
| **Netral / Arsip** | Selesai, Diarsipkan, Riwayat Sesi | `bg-gray-100` | `text-gray-700` | `border-gray-200` |

---

### 6. Standar Interaksi dan Konfirmasi

1. **Konfirmasi Penghapusan (SweetAlert2 Standard):**
   Setiap aksi penghapusan permanen atau tindakan berisiko wajib menggunakan SweetAlert2 dengan format terstruktur:
   - Judul: "Konfirmasi Hapus [Nama Entitas]"
   - Icon: "warning"
   - Tombol Konfirmasi: `confirmButtonColor: '#EF4444'` dengan teks "Ya, Hapus"
   - Tombol Batal: `cancelButtonColor: '#6B7280'` dengan teks "Batal"
2. **Notifikasi Flash (PrimeVue Toast):**
   Pesan sukses dan peringatan pasca aksi CRUD dikirimkan melalui flash session controller Laravel dan otomatis dirender oleh PrimeVue Toast di pojok kanan atas layar dengan durasi 4000 ms.
3. **Status Memproses (Loading State):**
   Seluruh tombol submit form wajib mengikat `:disabled="form.processing"` dan menampilkan icon spinner ketika data sedang dikirim ke server.

---

### 7. Fondasi Komponen Bersama (Reusable Components Phase 1)

Untuk menjamin kemudahan migrasi dan keseragaman antarmuka, enam komponen fondasi telah diimplementasikan di `resources/js/Components/`:

#### A. `AppPageHeader.vue`
Menyeragamkan bilah judul, deskripsi, badge penanda aktif, dan tombol aksi utama.
- **Props:**
  - `title` (string, wajib)
  - `description` (string, opsional)
  - `badge` (string, opsional)
  - `badgeIcon` (string, opsional)
  - `breadcrumbs` (Array<{ label: string, href?: string }>, opsional)
- **Slots:**
  - `#actions`: Area tombol aksi primer dan sekunder di sisi kanan.
  - `#badge`: Slot custom badge jika membutuhkan styling khusus.
  - `#default`: Keterangan tambahan atau metrik di bawah deskripsi.

#### B. `AppTableToolbar.vue`
Menyeragamkan bilah pencarian debounced, slot filter dropdown, tombol reset, dan penghitung data.
- **Props:**
  - `modelValue` (string, v-model pencarian teks)
  - `searchPlaceholder` (string, default: "Cari data...")
  - `searchWidth` (string, default: "w-full sm:w-64 md:w-72")
  - `totalRecords` (number, opsional)
  - `filteredRecords` (number, opsional)
  - `recordLabel` (string, default: "data")
  - `hasActiveFilters` (boolean, opsional)
- **Slots:**
  - `#filters`: Dropdown select penyaringan data.
  - `#actions`: Tombol aksi tambahan di sisi kanan toolbar (misal ekspor).
- **Events:**
  - `@update:modelValue`: Pembaruan nilai teks pencarian.
  - `@reset`: Dipicu ketika tombol "Reset Filter" diklik.

#### C. `AppStatusBadge.vue`
Menyeragamkan seluruh badge status risiko kedisiplinan, entitas aktif/nonaktif, dan progres kasus.
- **Props:**
  - `status` (string | number, case-insensitive)
  - `label` (string, teks label override opsional)
  - `dot` (boolean, default: true, dot indikator berwarna)
  - `size` ('sm' | 'md', default: 'sm')

#### D. `AppEmptyState.vue`
Menyeragamkan tampilan status kosong saat tabel atau linimasa tidak memiliki rekaman data.
- **Props:**
  - `icon` (string, default: "pi pi-inbox")
  - `title` (string, default: "Tidak ada data ditemukan")
  - `description` (string, deskripsi penjelas dan saran tindakan)
  - `actionLabel` (string, label tombol aksi opsional)
  - `actionHref` (string, link tombol aksi opsional)
  - `actionIcon` (string, icon tombol aksi)
  - `bordered` (boolean, default: false)
- **Events:**
  - `@action`: Dipicu saat tombol aksi ditekan.

#### E. `AppFormSection.vue`
Menyeragamkan kartu pembungkus kelompok field formulir dengan judul, deskripsi, dan badge penanda wajib diisi.
- **Props:**
  - `title` (string, wajib)
  - `description` (string, opsional)
  - `icon` (string, opsional)
  - `badge` (string, opsional)
  - `required` (boolean, default: false)
- **Slots:**
  - `#default`: Komponen field isian form.
  - `#header-action`: Aksi kustom di sisi kanan header section.

#### F. `AppDataTableWrapper.vue`
Wrapper PrimeVue DataTable standar yang telah dikonfigurasi dengan paginator bawaan, responsif stack layout (`768px`), sorting, dan integrasi otomatis dengan `AppEmptyState`.
- Mendukung seluruh slot dan kolom native PrimeVue `<Column>` secara penuh melalui slot forwarding.

#### G. Centralized SweetAlert2 Helpers (`resources/js/utils.ts`)
- `confirmDelete({ title, text, confirmButtonText, cancelButtonText })`: Dialog konfirmasi penghapusan permanen merah `#EF4444`.
- `confirmAction({ title, text, icon, confirmButtonText, cancelButtonText, confirmButtonColor })`: Dialog konfirmasi aksi penting.
- `showAlert({ title, text, icon, confirmButtonText })`: Dialog modal informasi / status.


