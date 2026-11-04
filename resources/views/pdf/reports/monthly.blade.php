<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Rekapitulasi Kedisiplinan Siswa</title>
    @include('pdf.partials.styles')
</head>
<body>

    @include('pdf.partials.kop', ['school' => $school])

    <div class="document-header-area">
        <h2 class="document-title">LAPORAN REKAPITULASI KEDISIPLINAN SISWA</h2>
        <div class="document-number">
            Periode: {{ $semester->name }} &bull; Tahun Ajaran: {{ $semester->academicYear->name }}
        </div>
    </div>

    @php
        $totalStudents = count($students);
        $totalViolations = $students->sum('violation_count');
        $totalViolationPoints = $students->sum('violation_points');
        $totalAchievements = $students->sum('achievement_count');
        $totalAchievementPoints = $students->sum('achievement_points');
        $totalNetPoints = $students->sum('net_points');
        $avgNetPoints = $totalStudents > 0 ? round($totalNetPoints / $totalStudents, 1) : 0;
    @endphp

    <!-- Summary Statistics Cards -->
    <table class="point-boxes-table no-break" style="margin-bottom: 18px;">
        <tr>
            <td class="point-box-td" style="width: 20%;">
                <div class="point-box-label">Total Siswa</div>
                <div class="point-box-val">{{ $totalStudents }}</div>
            </td>
            <td class="point-box-td" style="width: 20%;">
                <div class="point-box-label">Pelanggaran</div>
                <div class="point-box-val" style="color: #DC2626;">{{ $totalViolations }} <span style="font-size: 8.5pt; font-weight: normal;">({{ $totalViolationPoints }} poin)</span></div>
            </td>
            <td class="point-box-td" style="width: 20%;">
                <div class="point-box-label">Prestasi</div>
                <div class="point-box-val" style="color: #059669;">{{ $totalAchievements }} <span style="font-size: 8.5pt; font-weight: normal;">({{ $totalAchievementPoints }} poin)</span></div>
            </td>
            <td class="point-box-td" style="width: 20%;">
                <div class="point-box-label">Total Poin Bersih</div>
                <div class="point-box-val">{{ $totalNetPoints }} poin</div>
            </td>
            <td class="point-box-td" style="width: 20%;">
                <div class="point-box-label">Rata-rata / Siswa</div>
                <div class="point-box-val">{{ $avgNetPoints }} poin</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 28px;">No</th>
                <th style="width: 70px;">NIS</th>
                <th>Nama Siswa</th>
                <th style="width: 65px;">Kelas</th>
                <th style="width: 50px;">Jml Pel.</th>
                <th style="width: 55px;">Poin Pel.</th>
                <th style="width: 50px;">Jml Pres.</th>
                <th style="width: 55px;">Poin Pres.</th>
                <th style="width: 65px;">Poin Bersih</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td style="text-align: center;">{{ $student->nis }}</td>
                <td><strong>{{ $student->name }}</strong></td>
                <td style="text-align: center;">{{ $student->classRoom ? $student->classRoom->name : '-' }}</td>
                <td style="text-align: center;">{{ $student->violation_count }}</td>
                <td style="text-align: center; color: #DC2626;">{{ $student->violation_points }}</td>
                <td style="text-align: center;">{{ $student->achievement_count }}</td>
                <td style="text-align: center; color: #059669;">{{ $student->achievement_points }}</td>
                <td style="text-align: center; font-weight: bold;">{{ $student->net_points }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center; padding: 12px; color: #6B7280;">Tidak ada data kedisiplinan siswa pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        @if($totalStudents > 0)
        <tfoot>
            <tr style="background-color: #F9FAFB; font-weight: bold;">
                <td colspan="4" style="text-align: right; padding-right: 10px;">TOTAL:</td>
                <td style="text-align: center;">{{ $totalViolations }}</td>
                <td style="text-align: center; color: #DC2626;">{{ $totalViolationPoints }}</td>
                <td style="text-align: center;">{{ $totalAchievements }}</td>
                <td style="text-align: center; color: #059669;">{{ $totalAchievementPoints }}</td>
                <td style="text-align: center;">{{ $totalNetPoints }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    @include('pdf.partials.signatory', ['school' => $school])

</body>
</html>
