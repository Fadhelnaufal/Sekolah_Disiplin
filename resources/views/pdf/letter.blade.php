<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Pemanggilan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid black;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .school-name {
            font-size: 16pt;
            font-weight: bold;
            margin: 0;
        }
        .school-address {
            font-size: 10pt;
            margin: 0;
        }
        .content {
            margin-top: 20px;
        }
        .text-right {
            text-align: right;
        }
        .letter-number {
            margin-bottom: 20px;
        }
        .table-info {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .table-info td {
            vertical-align: top;
            padding: 2px 0;
        }
        .table-info td:first-child {
            width: 150px;
        }
        .table-history {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .table-history th, .table-history td {
            border: 1px solid black;
            padding: 5px 10px;
            text-align: left;
        }
        .table-history th {
            background-color: #f2f2f2;
        }
        .signature-area {
            margin-top: 50px;
            width: 100%;
        }
        .signature-area td {
            width: 50%;
            text-align: center;
        }
        .signature-name {
            margin-top: 80px;
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="header">
    <p class="school-name">{{ $school->name }}</p>
    <p class="school-address">{{ $school->address }}, {{ $school->city }}</p>
    @if($school->phone)
        <p class="school-address">Telp: {{ $school->phone }}</p>
    @endif
</div>

<div class="content">
    <div class="letter-number">
        Nomor: {{ $letter_number ?? '..... / TATIB / 2026/2027' }}<br>
        Perihal: Pemberitahuan / Pemanggilan<br>
    </div>

    <div class="recipient">
        Kepada Yth.<br>
        Bapak/Ibu Orang Tua / Wali dari:<br>
        <table class="table-info">
            <tr>
                <td>Nama Siswa</td>
                <td>: {{ $student->name }}</td>
            </tr>
            <tr>
                <td>NIS / Kelas</td>
                <td>: {{ $student->nis }} / {{ $student->classRoom ? $student->classRoom->name : '-' }}</td>
            </tr>
            <tr>
                <td>Wali Kelas</td>
                <td>: {{ $student->classRoom && $student->classRoom->homeroomTeacher ? $student->classRoom->homeroomTeacher->name : '-' }}</td>
            </tr>
        </table>
    </div>

    <p>Dengan hormat,</p>
    <p>
        Bersama surat ini kami sampaikan bahwa berdasarkan catatan tata tertib sekolah, ananda tersebut di atas saat ini memiliki poin ketidakdisiplinan/pelanggaran bersih sebesar <strong>{{ $net_points }} poin</strong>.
        @if($type === 'call_3')
            Oleh karena itu, ananda telah mencapai batas untuk <strong>Panggilan III / Terakhir</strong>. Kami mengharap kehadiran Bapak/Ibu untuk membahas tindak lanjut evaluasi kedisiplinan sesuai dengan prosedur dan tata tertib sekolah yang berlaku.
        @elseif($sanction)
            Oleh karena itu, ananda termasuk dalam tingkat {{ $sanction->level }} dengan tindakan: <strong>{{ $sanction->action }}</strong>.
        @endif
    </p>

    <p>Sebagai bahan pertimbangan, berikut adalah rincian catatan disiplin terbaru:</p>

    <table class="table-history">
        <thead>
            <tr>
                <th>NO</th>
                <th>TANGGAL</th>
                <th>JENIS KEJADIAN</th>
                <th>POIN</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recent_events as $index => $event)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $event['date'] }}</td>
                    <td>{{ $event['description'] }}</td>
                    <td>{{ $event['points'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">Tidak ada catatan terbaru.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p>Demikian surat pemberitahuan ini kami sampaikan. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.</p>

    <table class="signature-area">
        <tr>
            <td></td>
            <td>
                {{ $school->city }}, {{ $date }}<br>
                {{ $signatory_title ?? 'Kepala Sekolah' }}
                <div class="signature-name">{{ $signatory_name ?? '.......................' }}</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
