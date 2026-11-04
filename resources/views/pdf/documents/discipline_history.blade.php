<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject ?? 'Laporan Rekam Jejak Kedisiplinan Siswa' }}</title>
    @include('pdf.partials.styles')
</head>
<body>

    @include('pdf.partials.kop', ['school' => $school])

    <div class="document-header-area">
        <h2 class="document-title">{{ strtoupper($subject ?? 'LAPORAN REKAM JEJAK KEDISIPLINAN SISWA') }}</h2>
        <div class="document-number">Nomor: {{ $letter_number ?? '..... / TATIB / ' . date('Y') }}</div>
        <div style="font-size: 9pt; color: #4B5563; margin-top: 2px;">
            Semester: {{ $semester->name ?? 'Aktif' }} · Tahun Pelajaran {{ $semester->academicYear?->name ?? date('Y') . '/' . (date('Y') + 1) }}
        </div>
    </div>

    <!-- Student Info in 2 Columns -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9.5pt;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                <table class="info-table" style="margin: 0;">
                    <tr>
                        <td style="width: 90px; color: #4B5563;">Nama Siswa</td>
                        <td style="width: 10px;">:</td>
                        <td><strong>{{ $student->name }}</strong></td>
                    </tr>
                    <tr>
                        <td style="color: #4B5563;">NIS</td>
                        <td>:</td>
                        <td>{{ $student->nis }}</td>
                    </tr>
                    <tr>
                        <td style="color: #4B5563;">Kelas / Rombel</td>
                        <td>:</td>
                        <td>{{ $student->classRoom ? $student->classRoom->name : '-' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                <table class="info-table" style="margin: 0;">
                    <tr>
                        <td style="width: 100px; color: #4B5563;">Wali Kelas</td>
                        <td style="width: 10px;">:</td>
                        <td>{{ $student->classRoom?->homeroomTeacher?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="color: #4B5563;">Orang Tua / Wali</td>
                        <td>:</td>
                        <td>{{ $student->parent_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="color: #4B5563;">Status Siswa</td>
                        <td>:</td>
                        <td>
                            <span style="font-weight: 500; color: {{ $student->status === 'Active' || $student->status === 'aktif' ? '#059669' : '#DC2626' }};">
                                {{ ucfirst($student->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 4 Stat Summary Cards -->
    <table class="point-boxes-table no-break" style="margin-bottom: 14px;">
        <tr>
            <td class="point-box-td" style="width: 25%;">
                <div class="point-box-label">Poin Pelanggaran</div>
                <div class="point-box-val" style="color: #DC2626;">{{ $violation_points ?? 0 }}</div>
            </td>
            <td class="point-box-td" style="width: 25%;">
                <div class="point-box-label">Poin Prestasi</div>
                <div class="point-box-val" style="color: #059669;">{{ $achievement_points ?? 0 }}</div>
            </td>
            <td class="point-box-td" style="width: 25%;">
                <div class="point-box-label">Poin Bersih (Net)</div>
                <div class="point-box-val" style="color: #1E40AF;">{{ $net_points ?? 0 }}</div>
            </td>
            <td class="point-box-td" style="width: 25%;">
                <div class="point-box-label">Status Evaluasi</div>
                <div class="point-box-val" style="font-size: 10pt; color: {{ isset($sanction) && $sanction ? '#B45309' : '#059669' }};">
                    {{ isset($sanction) && $sanction ? $sanction->level : 'Tertib' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Chronological Discipline Events Table -->
    <div style="margin-bottom: 12px;">
        <div style="font-size: 10pt; font-weight: bold; color: #111827; margin-bottom: 5px;">
            A. Riwayat Kronologis Kejadian Ketertiban & Prestasi
        </div>
        <table class="data-table" style="font-size: 8.5pt;">
            <thead>
                <tr>
                    <th style="width: 25px; text-align: center;">No</th>
                    <th style="width: 70px; text-align: center;">Tanggal</th>
                    <th style="width: 75px; text-align: center;">Kategori</th>
                    <th>Uraian Kejadian</th>
                    <th style="width: 80px; text-align: center;">Tipe</th>
                    <th style="width: 50px; text-align: center;">Poin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($all_events ?? [] as $idx => $evt)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td style="text-align: center;">{{ $evt['date'] }}</td>
                        <td style="text-align: center;">{{ $evt['category'] ?? '-' }}</td>
                        <td>
                            <strong>{{ $evt['name'] }}</strong>
                            @if(!empty($evt['description']))
                                <div style="font-size: 7.5pt; color: #6B7280;">{{ $evt['description'] }}</div>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($evt['type'] === 'violation')
                                <span style="color: #DC2626; font-weight: 500;">Pelanggaran</span>
                            @else
                                <span style="color: #059669; font-weight: 500;">Prestasi</span>
                            @endif
                        </td>
                        <td style="text-align: center; font-weight: bold; color: {{ $evt['type'] === 'violation' ? '#DC2626' : '#059669' }};">
                            {{ $evt['points'] > 0 && $evt['type'] === 'achievement' ? '+' : '' }}{{ $evt['points'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #6B7280; padding: 12px; font-style: italic;">
                            Tidak ada catatan pelanggaran maupun prestasi pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background-color: #F9FAFB; font-weight: bold;">
                    <td colspan="5" style="text-align: right; padding-right: 8px;">Total Poin Akumulasi Bersih:</td>
                    <td style="text-align: center; color: #1E40AF;">{{ $net_points ?? 0 }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Formal Administrative Follow-up Actions (Excludes private counseling notes) -->
    @if(isset($follow_ups) && count($follow_ups) > 0)
    <div style="margin-bottom: 12px;" class="no-break">
        <div style="font-size: 10pt; font-weight: bold; color: #111827; margin-bottom: 5px;">
            B. Rekapitulasi Pembinaan & Tindak Lanjut Administratif
        </div>
        <table class="data-table" style="font-size: 8.5pt;">
            <thead>
                <tr>
                    <th style="width: 25px; text-align: center;">No</th>
                    <th style="width: 80px; text-align: center;">Batas Waktu</th>
                    <th>Rencana Tindak Lanjut</th>
                    <th style="width: 100px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($follow_ups as $fIdx => $fu)
                    <tr>
                        <td style="text-align: center;">{{ $fIdx + 1 }}</td>
                        <td style="text-align: center;">{{ $fu['due_date'] ?? '-' }}</td>
                        <td>
                            <strong>{{ $fu['title'] }}</strong>
                            @if(!empty($fu['action_plan']))
                                <div style="font-size: 7.5pt; color: #6B7280;">{{ $fu['action_plan'] }}</div>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <span style="font-weight: 500; color: {{ $fu['status'] === 'completed' ? '#059669' : '#B45309' }};">
                                {{ ucfirst($fu['status']) }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Notes & Recommendations -->
    <div style="margin-bottom: 16px; font-size: 9.5pt; line-height: 1.45;" class="no-break">
        <div style="font-weight: bold; margin-bottom: 3px;">C. Catatan & Rekomendasi Tim Ketertiban:</div>
        <div style="background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 4px; padding: 8px 12px; font-style: italic;">
            {{ !empty($evaluation_notes) ? $evaluation_notes : 'Peserta didik terus mendapatkan pendampingan berkala dari Wali Kelas dan Guru Bimbingan Konseling agar senantiasa menjunjung tinggi norma kedisiplinan sekolah.' }}
        </div>
    </div>

    <!-- Dual Signatory Block -->
    @php
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $formattedDate = $date ?? (date('j') . ' ' . ($months[(int)date('n')] ?? date('F')) . ' ' . date('Y'));
        $city = $school->letter_city ?? ($school->city ?? 'Ponorogo');
    @endphp

    <table style="width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-top: 10px;" class="no-break">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-left: 20px;">
                <div>Mengetahui / Memeriksa,</div>
                <div style="font-weight: 500;">Guru BK / Wali Kelas</div>
                <div style="height: 52px;"></div>
                <div style="font-weight: bold; text-decoration: underline;">
                    {{ $student->classRoom?->homeroomTeacher?->name ?? ($school->counseling_officer_name ?: '........................................') }}
                </div>
                @if($student->classRoom?->homeroomTeacher?->nip)
                    <div style="font-size: 8.5pt; color: #4B5563;">NIP. {{ $student->classRoom->homeroomTeacher->nip }}</div>
                @endif
            </td>
            <td style="width: 50%; vertical-align: top; padding-right: 20px; text-align: right;">
                <div class="signatory-box" style="float: right;">
                    <div>{{ $city }}, {{ $formattedDate }}</div>
                    <div style="font-weight: 500;">{{ $school->signer_title ?: 'Kepala Sekolah' }}</div>

                    <div style="position: relative; min-height: 52px; margin: 4px 0;">
                        @if(($school->show_stamp ?? false) && !empty($school->stamp_src))
                            <img src="{{ $school->stamp_src }}" alt="Stempel" class="stamp-image" style="left: -15px; top: 0;">
                        @endif
                        @if(($school->show_signature_image ?? true) && !empty($school->signature_src))
                            <img src="{{ $school->signature_src }}" alt="Tanda Tangan" class="signature-image">
                        @else
                            <div style="height: 50px;"></div>
                        @endif
                    </div>

                    <div class="signatory-name">{{ $school->principal_name ?: '.......................' }}</div>
                    @if(!empty($school->principal_nip))
                        <div class="signatory-nip">NIP. {{ $school->principal_nip }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
