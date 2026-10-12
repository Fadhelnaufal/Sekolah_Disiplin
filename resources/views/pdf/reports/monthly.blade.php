<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Bulanan</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .school-name { font-size: 18px; font-weight: bold; margin: 0; }
        .school-address { font-size: 12px; margin: 5px 0; }
        .title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <p class="school-name">{{ $school->name ?? 'NAMA SEKOLAH' }}</p>
        <p class="school-address">{{ $school->address ?? 'ALAMAT SEKOLAH' }}</p>
    </div>

    <div class="title">
        LAPORAN REKAPITULASI POIN SISWA<br>
        <span style="font-size: 12px; font-weight: normal;">Periode: {{ $semester->name }} ({{ $semester->academicYear->name }})</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th class="text-center">Jml Pelanggaran</th>
                <th class="text-center">Poin Pelanggaran</th>
                <th class="text-center">Jml Prestasi</th>
                <th class="text-center">Poin Prestasi</th>
                <th class="text-center">Poin Bersih</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $index => $student)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $student->nis }}</td>
                <td>{{ $student->name }}</td>
                <td>{{ $student->classRoom ? $student->classRoom->name : '-' }}</td>
                <td class="text-center">{{ $student->violation_count }}</td>
                <td class="text-center">{{ $student->violation_points }}</td>
                <td class="text-center">{{ $student->achievement_count }}</td>
                <td class="text-center">{{ $student->achievement_points }}</td>
                <td class="text-center" style="font-weight: bold;">{{ $student->net_points }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: right;">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
