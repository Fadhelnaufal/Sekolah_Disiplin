<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps<{
    student: any;
    semester: any;
    school: any;
    headmaster: string;
    summary: {
        violation_points: number;
        achievement_points: number;
        net_points: number;
        risk_status: string;
    };
    events: any[];
    printDate: string;
}>();

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }).format(d);
};

// Automatically open print dialog
onMounted(() => {
    // Small delay to ensure styles are loaded
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <Head title="Cetak Kartu Kedisiplinan Siswa" />

    <div class="print-container max-w-[210mm] mx-auto bg-white min-h-[297mm] p-8 text-black font-serif">
        
        <!-- Action Buttons (Hidden in Print) -->
        <div class="print:hidden mb-6 flex justify-end space-x-4 border-b pb-4">
            <button onclick="window.history.back()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-sm font-sans">
                Kembali
            </button>
            <button onclick="window.print()" class="px-4 py-2 bg-[#1D3557] text-white rounded hover:bg-[#0D1B2A] transition text-sm font-sans">
                Cetak Dokumen (A4)
            </button>
        </div>

        <!-- Document Header (Kop Surat) -->
        <div class="text-center border-b-2 border-black pb-4 mb-6">
            <h1 class="text-xl font-bold uppercase tracking-wider">{{ school?.name || 'SEKOLAH' }}</h1>
            <p class="text-sm mt-1">{{ school?.address || 'Alamat Sekolah' }}</p>
        </div>

        <!-- Title -->
        <div class="text-center mb-8">
            <h2 class="text-lg font-bold uppercase underline">KARTU KEDISIPLINAN SISWA</h2>
        </div>

        <!-- Student Identity -->
        <table class="w-full text-sm mb-8">
            <tbody>
                <tr>
                    <td class="w-40 font-semibold py-1">Nama Siswa</td>
                    <td class="w-4 py-1">:</td>
                    <td class="py-1 font-bold">{{ student.name }}</td>
                    <td class="w-40 font-semibold py-1">Tahun Ajaran</td>
                    <td class="w-4 py-1">:</td>
                    <td class="py-1">{{ student.class_room?.academic_year?.name || '-' }}</td>
                </tr>
                <tr>
                    <td class="font-semibold py-1">NIS / NISN</td>
                    <td class="py-1">:</td>
                    <td class="py-1">{{ student.nis }}</td>
                    <td class="font-semibold py-1">Semester</td>
                    <td class="py-1">:</td>
                    <td class="py-1">{{ semester.name }} ({{ semester.status === 'Closed' ? 'Selesai' : 'Berjalan' }})</td>
                </tr>
                <tr>
                    <td class="font-semibold py-1">Kelas</td>
                    <td class="py-1">:</td>
                    <td class="py-1">{{ student.class_room?.name || '-' }}</td>
                    <td class="font-semibold py-1">Status Siswa</td>
                    <td class="py-1">:</td>
                    <td class="py-1">{{ student.status === 'Active' ? 'Aktif' : 'Tidak Aktif' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Point Summary -->
        <div class="mb-8 p-4 border border-black rounded-lg bg-gray-50 print:bg-transparent">
            <h3 class="font-bold mb-3 border-b border-gray-300 pb-2">Rangkuman Poin Kedisiplinan</h3>
            <div class="grid grid-cols-4 gap-4 text-center">
                <div>
                    <div class="text-xs text-gray-600 uppercase font-bold">Poin Pelanggaran</div>
                    <div class="text-xl font-bold text-red-600 print:text-black">{{ summary.violation_points }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase font-bold">Poin Prestasi</div>
                    <div class="text-xl font-bold text-green-600 print:text-black">{{ summary.achievement_points }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase font-bold">Poin Akhir (Net)</div>
                    <div class="text-xl font-bold">{{ summary.net_points }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-600 uppercase font-bold">Status Kedisiplinan</div>
                    <div class="text-base font-bold mt-1">{{ summary.risk_status }}</div>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="mb-12">
            <h3 class="font-bold mb-3">Rincian Catatan Kedisiplinan & Prestasi</h3>
            <table class="w-full text-sm border-collapse border border-black">
                <thead>
                    <tr class="bg-gray-100 print:bg-transparent">
                        <th class="border border-black px-3 py-2 text-center w-12">No</th>
                        <th class="border border-black px-3 py-2 text-left w-32">Tanggal</th>
                        <th class="border border-black px-3 py-2 text-left">Uraian / Keterangan</th>
                        <th class="border border-black px-3 py-2 text-center w-24">Jenis</th>
                        <th class="border border-black px-3 py-2 text-center w-20">Poin</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(ev, idx) in events" :key="ev.id">
                        <td class="border border-black px-3 py-2 text-center">{{ idx + 1 }}</td>
                        <td class="border border-black px-3 py-2">{{ formatDate(ev.event_date) }}</td>
                        <td class="border border-black px-3 py-2">
                            <div class="font-bold">{{ ev.event_type === 'violation' ? ev.violation?.name : ev.achievement?.name }}</div>
                            <div v-if="ev.description" class="text-xs mt-1 text-gray-700 print:text-black">{{ ev.description }}</div>
                        </td>
                        <td class="border border-black px-3 py-2 text-center">
                            {{ ev.event_type === 'violation' ? 'Pelanggaran' : 'Prestasi' }}
                        </td>
                        <td class="border border-black px-3 py-2 text-center font-bold">
                            {{ ev.event_type === 'violation' ? '+' : '' }}{{ ev.points_effect }}
                        </td>
                    </tr>
                    <tr v-if="events.length === 0">
                        <td colspan="5" class="border border-black px-3 py-6 text-center italic text-gray-600 print:text-black">
                            Siswa tidak memiliki catatan kedisiplinan atau prestasi pada semester ini.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Signatures -->
        <div class="mt-16 w-full text-sm">
            <div class="text-right mb-8">
                Ditetapkan di: ...........................<br>
                Tanggal: {{ printDate }}
            </div>
            
            <div class="grid grid-cols-2 gap-16 mb-24">
                <div class="text-center">
                    Mengetahui,<br>
                    Orang Tua / Wali Siswa
                    <br><br><br><br>
                    ( ......................................... )
                </div>
                <div class="text-center">
                    <br>
                    Wali Kelas
                    <br><br><br><br>
                    ( ......................................... )
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-16">
                <div class="text-center">
                    <br>
                    Siswa Yang Bersangkutan
                    <br><br><br><br>
                    ( <span class="font-bold">{{ student.name }}</span> )
                </div>
                <div class="text-center">
                    Mengetahui,<br>
                    Kepala Sekolah
                    <br><br><br><br>
                    <span class="font-bold underline">{{ headmaster }}</span>
                </div>
            </div>
        </div>

    </div>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 15mm;
    }
    body {
        margin: 0;
        padding: 0;
        background: white;
    }
    .print-container {
        width: 100%;
        max-width: none;
        margin: 0;
        padding: 0;
        box-shadow: none;
    }
    .print\:hidden {
        display: none !important;
    }
    .print\:bg-transparent {
        background-color: transparent !important;
    }
    .print\:text-black {
        color: black !important;
    }
    
    /* Ensure colors print properly if needed */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
