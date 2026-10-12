# DISIPLIN PRO — IMPLEMENTATION INVENTORY

## 1. Daftar Fitur Selesai (IMPLEMENTED)

| Modul | Deskripsi | Lokasi File / Bukti |
| :--- | :--- | :--- |
| **RBAC & Autentikasi** | Login, otorisasi berbasis *Role* (Spatie), blokade hak akses berjenjang. | `routes/web.php`, `database/seeders/RoleSeeder.php` |
| **Master Akademik** | CRUD Tahun Akademik & Semester. | `app/Http/Controllers/AcademicYearController.php`, `SemesterController.php` |
| **Master Organisasi** | CRUD Guru, Ruang Kelas, Relasi Wali Kelas. | `TeacherController.php`, `ClassRoomController.php` |
| **Master Siswa** | CRUD Data Siswa & Hubungan Kelas. | `StudentController.php` |
| **Master Disiplin** | CRUD Indeks Pelanggaran & Prestasi (poin). | `ViolationController.php`, `AchievementController.php` |
| **Mesin Sanksi (Rules)** | Ambang batas poin untuk memicu tindakan sanksi secara otomatis. | `app/Services/SanctionEngineService.php`, `SanctionRuleController.php` |
| **Pencatatan Kejadian** | Form input pencatatan kasus indisipliner / prestasi beserta kalkulasi poin. | `DisciplineEventController.php`, `StudentPointService.php` |
| **Pembinaan (Counseling)** | Penanganan intervensi siswa oleh Guru BK berdasarkan evaluasi poin. | `CounselingRecordController.php` |
| **Surat Pemanggilan** | Surat peringatan dinamis berbasis PDF berdasar tahap (Panggilan I, II, III). | `LetterController.php`, `app/Services/LetterService.php`, `resources/views/pdf/letter.blade.php` |

## 2. Daftar Fitur Parsial (PARTIAL)

| Modul | Kekurangan | Lokasi File / Bukti |
| :--- | :--- | :--- |
| **School Settings (Setup)** | Konfigurasi profil sekolah telah tertuang dalam tabel `schools`, dan dipakai dalam PDF Surat. Namun **tidak ada rute/form UI** bagi Super Admin untuk menyunting identitas sekolah secara bebas. | `app/Models/School.php`, ketiadaan `SchoolController.php` |
| **Dashboard** | Rute `dashboard` terbuka dan me-render `Dashboard.vue`, tetapi sebagian agregasi poin / peringatan dini (*early warning*) mungkin belum di-query ke dalam *chart*/grafik analitik sesuai spesifikasi awal. | `app/Http/Controllers/DashboardController.php`, `Dashboard.vue` |

## 3. Daftar Fitur Belum Dikerjakan (NOT IMPLEMENTED)

| Modul | Kebutuhan Deskripsi | Ketergantungan (Dependency) |
| :--- | :--- | :--- |
| **Reports (Laporan)** | Halaman rekapitulasi Bulanan, Rekap Kelas, dan Rekap Wali Kelas dengan filter. | Membutuhkan *Point Engine* yang stabil. |
| **Data Analytics** | Grafik frekuensi pelanggaran berdasarkan kategori, kelas, tren harian/bulanan. | Membutuhkan UI Chart (misal *Chart.js*). |
| **Semester Closing** | Mekanisme validasi penutupan semester, pembekuan/pencetakan nilai bersih (*snapshot*), dan status *Read-Only*. | Tabel `semester_snapshots` (baru). |
| **Excel Import / Migration**| Sistem otomatis menelan data lama (siswa, master, transaksi) dari Spreadsheet ke *Database*. | *Package* `maatwebsite/excel` atau serupa. |
| **Audit Logs** | Pelacakan otomatis histori perubahan data penting, *actor*, nilai lama, nilai baru. | Setup *Middleware* / `spatie/laravel-activitylog`. |
| **Export Rekapitulasi** | Unduh laporan berformat Excel (.xlsx) atau PDF rekap. | Bergantung dengan Modul Laporan. |

## 4. Daftar Fitur Gagal / Terblokir

* *NIHIL*. Tidak ditemukan modul terbengkalai dengan *error* blokade kritikal. Semua modul yang dikerjakan sudah mencapai titik stabil. Tes sistem utama berlalu dengan sukses (100% *Pass* pada Modul Utama).
