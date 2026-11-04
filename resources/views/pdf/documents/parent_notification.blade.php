<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject ?? 'Surat Pemberitahuan Orang Tua' }}</title>
    @include('pdf.partials.styles')
</head>
<body>

    @include('pdf.partials.kop', ['school' => $school])

    <div class="document-header-area">
        <h2 class="document-title">{{ strtoupper($subject ?? 'SURAT PEMBERITAHUAN ORANG TUA / WALI') }}</h2>
        <div class="document-number">Nomor: {{ $letter_number ?? '..... / TATIB / ' . date('Y') }}</div>
    </div>

    <div class="recipient" style="margin-bottom: 12px; font-size: 10.5pt;">
        Kepada Yth.<br>
        <strong>Bapak/Ibu Orang Tua / Wali Peserta Didik</strong><br>
        Dari: <strong>{{ $student->name }}</strong> (Kelas: {{ $student->classRoom ? $student->classRoom->name : '-' }})<br>
        di Tempat
    </div>

    <p style="margin: 6px 0; font-size: 10.5pt;">Dengan hormat,</p>
    <p style="margin: 6px 0 10px 0; font-size: 10.5pt; text-align: justify; line-height: 1.45;">
        Sehubungan dengan program pembinaan karakter, ketertiban, dan pemantauan perkembangan kedisiplinan peserta didik di sekolah, bersama ini kami sampaikan informasi mengenai:
    </p>

    <table class="info-table">
        <tr>
            <td class="label-col">Nama Siswa</td>
            <td class="colon-col">:</td>
            <td><strong>{{ $student->name }}</strong></td>
        </tr>
        <tr>
            <td class="label-col">NIS</td>
            <td class="colon-col">:</td>
            <td>{{ $student->nis }}</td>
        </tr>
        <tr>
            <td class="label-col">Kelas / Rombel</td>
            <td class="colon-col">:</td>
            <td>{{ $student->classRoom ? $student->classRoom->name : '-' }}</td>
        </tr>
        <tr>
            <td class="label-col">Nama Orang Tua / Wali</td>
            <td class="colon-col">:</td>
            <td>{{ $student->parent_name ?? 'Orang Tua / Wali Siswa' }}</td>
        </tr>
        @if($student->classRoom && $student->classRoom->homeroomTeacher)
        <tr>
            <td class="label-col">Wali Kelas</td>
            <td class="colon-col">:</td>
            <td>{{ $student->classRoom->homeroomTeacher->name }}</td>
        </tr>
        @endif
    </table>

    <table class="point-boxes-table no-break" style="margin-top: 10px; margin-bottom: 12px;">
        <tr>
            <td class="point-box-td">
                <div class="point-box-label">Pelanggaran</div>
                <div class="point-box-val" style="color: #DC2626;">{{ $violation_points ?? 0 }} poin</div>
            </td>
            <td class="point-box-td">
                <div class="point-box-label">Prestasi</div>
                <div class="point-box-val" style="color: #059669;">{{ $achievement_points ?? 0 }} poin</div>
            </td>
            <td class="point-box-td">
                <div class="point-box-label">Poin Bersih</div>
                <div class="point-box-val">{{ $net_points ?? 0 }} poin</div>
            </td>
        </tr>
    </table>

    <div style="margin: 10px 0; font-size: 10.5pt; line-height: 1.45;">
        <p style="margin: 4px 0;"><strong>1. Ringkasan Permasalahan / Kejadian:</strong></p>
        <p style="text-align: justify; margin: 2px 0 8px 15px;">
            {{ !empty($incident_summary) ? $incident_summary : ('Peserta didik memiliki akumulasi catatan ketidakdisiplinan sebesar ' . ($net_points ?? 0) . ' poin pada semester ini yang memerlukan perhatian dan pembinaan bersama.') }}
        </p>

        <p style="margin: 4px 0;"><strong>2. Tindakan Pembinaan Sekolah:</strong></p>
        <p style="text-align: justify; margin: 2px 0 8px 15px;">
            {{ !empty($school_action) ? $school_action : 'Pihak sekolah melalui Wali Kelas dan Guru Bimbingan Konseling telah memberikan teguran lisan, bimbingan awal, dan pemantauan tata tertib.' }}
        </p>

        <p style="margin: 4px 0;"><strong>3. Tindak Lanjut yang Diharapkan dari Orang Tua:</strong></p>
        <p style="text-align: justify; margin: 2px 0 8px 15px;">
            {{ !empty($expected_follow_up) ? $expected_follow_up : 'Kami mengharapkan kerja sama Bapak/Ibu untuk memberikan pendampingan, pengingat, dan pengawasan kedisiplinan belajar peserta didik selama di rumah.' }}
        </p>
    </div>

    @if(isset($recent_events) && count($recent_events) > 0)
        <p style="margin: 10px 0 4px 0; font-size: 10pt; font-weight: bold;">Rincian Kejadian Terbaru:</p>
        <table class="data-table no-break" style="font-size: 9pt; margin-bottom: 10px;">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 80px;">Tanggal</th>
                    <th>Kejadian / Pelanggaran</th>
                    <th style="width: 50px;">Poin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recent_events as $index => $event)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td style="text-align: center;">{{ $event['date'] }}</td>
                        <td>{{ $event['description'] }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $event['points'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p style="margin: 12px 0 18px 0; font-size: 10.5pt; line-height: 1.45;">
        Demikian surat pemberitahuan ini kami sampaikan demi kelancaran proses pendidikan ananda. Atas perhatian dan kerja sama yang baik, kami ucapkan terima kasih.
    </p>

    @include('pdf.partials.signatory', [
        'school' => $school,
        'signatory_title' => $signatory_title ?? null,
        'signatory_name' => $signatory_name ?? null,
        'date_string' => $date ?? null,
    ])

</body>
</html>
