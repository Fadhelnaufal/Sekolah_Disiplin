<?php

namespace App\Services\Document;

class DocumentDefinitionRegistry
{
    /**
     * Get definitions for all document types.
     */
    public static function getDefinitions(): array
    {
        return [
            DocumentType::PARENT_NOTIFICATION => [
                'type' => DocumentType::PARENT_NOTIFICATION,
                'label' => 'Surat Pemberitahuan Orang Tua',
                'short_label' => 'Pemberitahuan',
                'description' => 'Surat resmi kepada orang tua/wali mengenai catatan ketertiban dan perkembangan kedisiplinan siswa.',
                'category' => 'letter',
                'default_subject' => 'Pemberitahuan Perkembangan Kedisiplinan Siswa',
                'view_path' => 'pdf.documents.parent_notification',
                'fields' => [
                    [
                        'name' => 'subject',
                        'label' => 'Perihal Surat',
                        'type' => 'text',
                        'required' => true,
                        'default' => 'Pemberitahuan Perkembangan Kedisiplinan Siswa',
                        'placeholder' => 'Contoh: Pemberitahuan Pelanggaran Tata Tertib',
                    ],
                    [
                        'name' => 'incident_summary',
                        'label' => 'Ringkasan Kejadian / Permasalahan',
                        'type' => 'textarea',
                        'required' => false,
                        'default' => '',
                        'placeholder' => 'Jelaskan ringkasan kejadian pelanggaran atau akumulasi poin yang perlu diketahui orang tua...',
                        'help' => 'Jika dikosongkan, sistem akan otomatis merangkum dari kejadian kedisiplinan terbaru.',
                    ],
                    [
                        'name' => 'school_action',
                        'label' => 'Tindakan yang Telah Dilakukan Sekolah',
                        'type' => 'textarea',
                        'required' => false,
                        'default' => 'Pihak sekolah telah memberikan pembinaan lisan dan bimbingan awal melalui Tim Ketertiban dan Wali Kelas.',
                        'placeholder' => 'Contoh: Pembinaan oleh Guru BK, teguran pertama, dsb.',
                    ],
                    [
                        'name' => 'expected_follow_up',
                        'label' => 'Tindak Lanjut yang Diharapkan dari Orang Tua',
                        'type' => 'textarea',
                        'required' => false,
                        'default' => 'Diharapkan orang tua/wali dapat memberikan pendampingan, pengawasan waktu belajar, serta pembiasaan tertib di rumah.',
                        'placeholder' => 'Harapan kolaborasi orang tua dengan pihak sekolah...',
                    ],
                    [
                        'name' => 'notes',
                        'label' => 'Catatan Tambahan (Opsional)',
                        'type' => 'text',
                        'required' => false,
                        'default' => '',
                        'placeholder' => 'Catatan khusus bila diperlukan',
                    ],
                ],
                'validation_rules' => [
                    'subject' => 'required|string|max:255',
                    'incident_summary' => 'nullable|string|max:1000',
                    'school_action' => 'nullable|string|max:1000',
                    'expected_follow_up' => 'nullable|string|max:1000',
                    'notes' => 'nullable|string|max:500',
                ],
            ],

            DocumentType::STUDENT_AGREEMENT => [
                'type' => DocumentType::STUDENT_AGREEMENT,
                'label' => 'Surat Perjanjian Siswa',
                'short_label' => 'Perjanjian Siswa',
                'description' => 'Surat pernyataan komitmen tertulis siswa untuk mematuhi tata tertib dengan saksi wali kelas, BK, dan orang tua.',
                'category' => 'agreement',
                'default_subject' => 'Surat Perjanjian dan Pernyataan Disiplin Siswa',
                'view_path' => 'pdf.documents.student_agreement',
                'fields' => [
                    [
                        'name' => 'subject',
                        'label' => 'Perihal Dokumen',
                        'type' => 'text',
                        'required' => true,
                        'default' => 'Surat Perjanjian dan Pernyataan Disiplin Siswa',
                        'placeholder' => 'Surat Perjanjian Siswa',
                    ],
                    [
                        'name' => 'problem_summary',
                        'label' => 'Ringkasan Permasalahan / Pelanggaran',
                        'type' => 'textarea',
                        'required' => true,
                        'default' => '',
                        'placeholder' => 'Uraikan permasalahan atau jenis pelanggaran yang telah dilakukan siswa...',
                    ],
                    [
                        'name' => 'student_commitments',
                        'label' => 'Butir-Butir Komitmen Siswa',
                        'type' => 'textarea',
                        'required' => true,
                        'default' => "1. Menyadari dan mengakui kesalahan serta berjanji tidak akan mengulangi perbuatan tersebut.\n2. Siap mematuhi seluruh peraturan dan tata tertib yang berlaku di sekolah dengan penuh tanggung jawab.\n3. Bersedia menerima sanksi yang lebih berat sesuai ketentuan tata tertib apabila di kemudian hari melanggar kembali.",
                        'placeholder' => 'Tuliskan butir komitmen per baris...',
                        'help' => 'Tuliskan poin-poin komitmen secara jelas dan dapat dipertanggungjawabkan.',
                    ],
                    [
                        'name' => 'guidance_plan',
                        'label' => 'Bentuk Pembinaan & Pemantauan',
                        'type' => 'textarea',
                        'required' => false,
                        'default' => 'Siswa bersedia mengikuti bimbingan konseling berkala serta pemantauan kedisiplinan mingguan oleh Wali Kelas dan Guru BK.',
                        'placeholder' => 'Contoh: Pemantauan kehadiran harian oleh wali kelas...',
                    ],
                    [
                        'name' => 'evaluation_period',
                        'label' => 'Periode Masa Evaluasi',
                        'type' => 'text',
                        'required' => true,
                        'default' => '30 (tiga puluh) hari kalender',
                        'placeholder' => 'Contoh: 30 hari kalender / 1 Semester',
                    ],
                    [
                        'name' => 'sanction_consequence',
                        'label' => 'Konsekuensi Sanksi Berdasarkan Aturan Sekolah',
                        'type' => 'textarea',
                        'required' => false,
                        'default' => 'Apabila melanggar perjanjian ini, siswa siap menerima sanksi peningkatan ke tingkat pemanggilan berikutnya atau skorsing sesuai aturan tata tertib sekolah.',
                        'placeholder' => 'Konsekuensi resmi sesuai regulasi sekolah...',
                    ],
                ],
                'validation_rules' => [
                    'subject' => 'required|string|max:255',
                    'problem_summary' => 'required|string|max:1000',
                    'student_commitments' => 'required|string|max:1500',
                    'guidance_plan' => 'nullable|string|max:1000',
                    'evaluation_period' => 'required|string|max:100',
                    'sanction_consequence' => 'nullable|string|max:1000',
                ],
            ],

            DocumentType::PARENT_CALL => [
                'type' => DocumentType::PARENT_CALL,
                'label' => 'Surat Panggilan Orang Tua',
                'short_label' => 'Panggilan Orang Tua',
                'description' => 'Surat pemanggilan resmi kepada orang tua/wali peserta didik untuk hadir ke sekolah (Panggilan I, II, III).',
                'category' => 'call',
                'default_subject' => 'Panggilan Orang Tua / Wali Peserta Didik',
                'view_path' => 'pdf.documents.parent_call',
                'fields' => [
                    [
                        'name' => 'call_level',
                        'label' => 'Tingkat Panggilan',
                        'type' => 'select',
                        'required' => true,
                        'default' => 'call_1',
                        'options' => [
                            ['value' => 'call_1', 'label' => 'Panggilan I (Koordinator / Guru BK)'],
                            ['value' => 'call_2', 'label' => 'Panggilan II (Wakil Kepala Sekolah Bidang Kesiswaan)'],
                            ['value' => 'call_3', 'label' => 'Panggilan III / Terakhir (Kepala Sekolah)'],
                            ['value' => 'call_general', 'label' => 'Panggilan Umum / Pertemuan Khusus'],
                        ],
                    ],
                    [
                        'name' => 'subject',
                        'label' => 'Perihal Surat',
                        'type' => 'text',
                        'required' => true,
                        'default' => 'Surat Panggilan Orang Tua / Wali Peserta Didik',
                        'placeholder' => 'Perihal surat pemanggilan',
                    ],
                    [
                        'name' => 'reason',
                        'label' => 'Alasan Pemanggilan',
                        'type' => 'textarea',
                        'required' => true,
                        'default' => 'Membicarakan perkembangan sikap dan pembinaan kedisiplinan peserta didik di sekolah sehubungan dengan akumulasi catatan tata tertib.',
                        'placeholder' => 'Uraikan alasan pemanggilan...',
                    ],
                    [
                        'name' => 'meeting_day_date',
                        'label' => 'Hari dan Tanggal Pertemuan',
                        'type' => 'text',
                        'required' => true,
                        'default' => '',
                        'placeholder' => 'Contoh: Senin, 19 Oktober 2026',
                    ],
                    [
                        'name' => 'meeting_time',
                        'label' => 'Waktu / Jam Pertemuan',
                        'type' => 'text',
                        'required' => true,
                        'default' => '08.30 WIB s/d selesai',
                        'placeholder' => 'Contoh: 09.00 WIB s/d selesai',
                    ],
                    [
                        'name' => 'meeting_venue',
                        'label' => 'Tempat Pertemuan',
                        'type' => 'text',
                        'required' => true,
                        'default' => 'Ruang Bimbingan dan Konseling (BK)',
                        'placeholder' => 'Contoh: Ruang Bimbingan Konseling / Ruang Waka Kesiswaan',
                    ],
                    [
                        'name' => 'contact_person',
                        'label' => 'Narahubung / Kontak Konfirmasi',
                        'type' => 'text',
                        'required' => false,
                        'default' => '',
                        'placeholder' => 'Contoh: Guru BK (0812-xxxx-xxxx)',
                    ],
                ],
                'validation_rules' => [
                    'call_level' => 'required|string|in:call_1,call_2,call_3,call_general,parent_call',
                    'subject' => 'required|string|max:255',
                    'reason' => 'required|string|max:1000',
                    'meeting_day_date' => 'required|string|max:100',
                    'meeting_time' => 'required|string|max:100',
                    'meeting_venue' => 'required|string|max:150',
                    'contact_person' => 'nullable|string|max:100',
                ],
            ],

            DocumentType::DISCIPLINE_HISTORY => [
                'type' => DocumentType::DISCIPLINE_HISTORY,
                'label' => 'Riwayat Kedisiplinan Siswa',
                'short_label' => 'Riwayat Siswa',
                'description' => 'Laporan individual resmi rekam jejak ketertiban, akumulasi poin pelanggaran dan prestasi, serta tindak lanjut pembinaan.',
                'category' => 'report',
                'default_subject' => 'Laporan Rekam Jejak Kedisiplinan Siswa',
                'view_path' => 'pdf.documents.discipline_history',
                'fields' => [
                    [
                        'name' => 'subject',
                        'label' => 'Judul Laporan',
                        'type' => 'text',
                        'required' => true,
                        'default' => 'Laporan Rekam Jejak Kedisiplinan Siswa',
                        'placeholder' => 'Laporan Rekam Jejak Kedisiplinan Siswa',
                    ],
                    [
                        'name' => 'include_achievements',
                        'label' => 'Sertakan Data Prestasi Siswa',
                        'type' => 'select',
                        'required' => true,
                        'default' => '1',
                        'options' => [
                            ['value' => '1', 'label' => 'Ya, tampilkan prestasi sebagai pengurang poin'],
                            ['value' => '0', 'label' => 'Tidak, hanya tampilkan pelanggaran'],
                        ],
                    ],
                    [
                        'name' => 'evaluation_notes',
                        'label' => 'Catatan Evaluasi & Rekomendasi Tim Ketertiban',
                        'type' => 'textarea',
                        'required' => false,
                        'default' => 'Peserta didik dalam pemantauan berkala tim ketertiban sekolah dan bimbingan wali kelas.',
                        'placeholder' => 'Catatan evaluasi perkembangan kedisiplinan siswa...',
                    ],
                ],
                'validation_rules' => [
                    'subject' => 'required|string|max:255',
                    'include_achievements' => 'nullable|boolean',
                    'evaluation_notes' => 'nullable|string|max:1000',
                ],
            ],
        ];
    }

    /**
     * Get definition for a specific document type.
     */
    public static function get(string $type): ?array
    {
        $normalized = DocumentType::normalize($type);
        $defs = self::getDefinitions();

        return $defs[$normalized] ?? null;
    }
}
