<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Rekapitulasi Kelas</title>
    @include('pdf.partials.styles')
</head>
<body>

    @include('pdf.partials.kop', ['school' => $school])

    <div class="document-header-area">
        <h2 class="document-title">LAPORAN REKAPITULASI KEDISIPLINAN PER KELAS</h2>
        <div class="document-number">
            Periode: {{ $semester->name }} &bull; Tahun Ajaran: {{ $semester->academicYear->name }}
        </div>
    </div>

    @php
        $totalClasses = count($classes);
        $totalStudents = $classes->sum('students_count');
        $totalViolations = $classes->sum('total_violation_count');
        $totalViolationPoints = $classes->sum('total_violation_points');
        $totalAchievements = $classes->sum('total_achievement_count');
    @endphp

    <!-- Summary Statistics Cards -->
    <table class="point-boxes-table no-break" style="margin-bottom: 18px;">
        <tr>
            <td class="point-box-td">
                <div class="point-box-label">Total Kelas</div>
                <div class="point-box-val">{{ $totalClasses }}</div>
            </td>
            <td class="point-box-td">
                <div class="point-box-label">Total Siswa</div>
                <div class="point-box-val">{{ $totalStudents }}</div>
            </td>
            <td class="point-box-td">
                <div class="point-box-label">Pelanggaran</div>
                <div class="point-box-val" style="color: #DC2626;">{{ $totalViolations }} <span style="font-size: 8.5pt; font-weight: normal;">({{ $totalViolationPoints }} poin)</span></div>
            </td>
            <td class="point-box-td">
                <div class="point-box-label">Prestasi</div>
                <div class="point-box-val" style="color: #059669;">{{ $totalAchievements }} kejadian</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th>Nama Kelas</th>
                <th style="width: 80px;">Total Siswa</th>
                <th style="width: 80px;">Jml Pelanggaran</th>
                <th style="width: 90px;">Poin Pelanggaran</th>
                <th style="width: 80px;">Jml Prestasi</th>
                <th style="width: 100px;">Rata-rata Poin Bersih</th>
            </tr>
        </thead>
        <tbody>
            @forelse($classes as $index => $class)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td><strong>{{ $class->name }}</strong></td>
                <td style="text-align: center;">{{ $class->students_count }}</td>
                <td style="text-align: center;">{{ $class->total_violation_count }}</td>
                <td style="text-align: center; color: #DC2626;">{{ $class->total_violation_points }}</td>
                <td style="text-align: center; color: #059669;">{{ $class->total_achievement_count }}</td>
                <td style="text-align: center; font-weight: bold;">{{ $class->average_net_points }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 12px; color: #6B7280;">Tidak ada data kelas pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        @if($totalClasses > 0)
        <tfoot>
            <tr style="background-color: #F9FAFB; font-weight: bold;">
                <td colspan="2" style="text-align: right; padding-right: 10px;">TOTAL:</td>
                <td style="text-align: center;">{{ $totalStudents }}</td>
                <td style="text-align: center;">{{ $totalViolations }}</td>
                <td style="text-align: center; color: #DC2626;">{{ $totalViolationPoints }}</td>
                <td style="text-align: center; color: #059669;">{{ $totalAchievements }}</td>
                <td style="text-align: center;">-</td>
            </tr>
        </tfoot>
        @endif
    </table>

    @include('pdf.partials.signatory', ['school' => $school])

</body>
</html>
