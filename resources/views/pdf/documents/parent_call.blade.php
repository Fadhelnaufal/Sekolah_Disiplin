<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject ?? 'Surat Pemanggilan Orang Tua' }}</title>
    @include('pdf.partials.styles')
</head>
<body>

    @include('pdf.partials.kop', ['school' => $school])

    <!-- Metadata Surat: Nomor, Lamp., Hal di Kiri; Kota & Tanggal di Kanan -->
    <table style="width: 100%; margin-bottom: 14px; border-collapse: collapse;">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="width: 60px; vertical-align: top; font-size: 10.5pt;">Nomor</td>
                        <td style="width: 15px; vertical-align: top; font-size: 10.5pt;">:</td>
                        <td style="vertical-align: top; font-size: 10.5pt;">{{ $letter_number ?? '..... / TATIB / ' . date('Y') }}</td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; font-size: 10.5pt;">Lamp.</td>
                        <td style="vertical-align: top; font-size: 10.5pt;">:</td>
                        <td style="vertical-align: top; font-size: 10.5pt;">-</td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; font-size: 10.5pt;">Hal</td>
                        <td style="vertical-align: top; font-size: 10.5pt;">:</td>
                        <td style="vertical-align: top; font-size: 10.5pt;"><strong>{{ $subject ?? 'Pemanggilan Orang Tua' }}</strong></td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%; vertical-align: top; text-align: right; font-size: 10.5pt;">
                {{ $school->letter_city ?? ($school->city ?? 'Ponorogo') }}, {{ $date ?? date('d F Y') }}
            </td>
        </tr>
    </table>

    <!-- Penerima Surat -->
    <div style="margin-bottom: 14px; font-size: 10.5pt; line-height: 1.45;">
        Kepada :<br>
        Yth. Bapak/Ibu Wali Murid dari <strong>{{ $student->name }}</strong><br>
        Di <br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $student->address ?? ($student->parent_address ?? 'Tempat') }}
    </div>

    <!-- Salam & Pembuka -->
    <p style="margin: 0 0 8px 0; font-size: 10.5pt;">Assalamu’alaikum Wr. Wb.</p>
    <p style="margin: 0 0 8px 0; font-size: 10.5pt; text-align: justify; line-height: 1.45;">
        Mengharap dengan hormat atas kehadiran Bapak/Ibu wali murid dari :
    </p>

    <!-- Data Siswa -->
    <table style="width: 100%; margin-left: 15px; margin-bottom: 12px; border-collapse: collapse;">
        <tr>
            <td style="width: 120px; vertical-align: top; font-size: 10.5pt;">Nama Siswa</td>
            <td style="width: 15px; vertical-align: top; font-size: 10.5pt;">:</td>
            <td style="font-size: 10.5pt;"><strong>{{ $student->name }}</strong></td>
        </tr>
        <tr>
            <td style="vertical-align: top; font-size: 10.5pt;">Kelas</td>
            <td style="vertical-align: top; font-size: 10.5pt;">:</td>
            <td style="font-size: 10.5pt;">{{ $student->classRoom ? $student->classRoom->name : '-' }}</td>
        </tr>
        <tr>
            <td style="vertical-align: top; font-size: 10.5pt;">NIS / NISN</td>
            <td style="vertical-align: top; font-size: 10.5pt;">:</td>
            <td style="font-size: 10.5pt;">{{ $student->nis ?? '-' }}</td>
        </tr>
        <tr>
            <td style="vertical-align: top; font-size: 10.5pt;">Alamat</td>
            <td style="vertical-align: top; font-size: 10.5pt;">:</td>
            <td style="font-size: 10.5pt;">{{ $student->address ?? ($student->parent_address ?? 'Tempat tinggal siswa') }}</td>
        </tr>
    </table>

    <!-- Ketentuan Kehadiran -->
    <p style="margin: 0 0 8px 0; font-size: 10.5pt; text-align: justify; line-height: 1.45;">
        Untuk hadir dengan ketentuan sebagai berikut :
    </p>
    <table style="width: 100%; margin-left: 15px; margin-bottom: 14px; border-collapse: collapse;">
        <tr>
            <td style="width: 120px; vertical-align: top; font-size: 10.5pt;">Hari, tanggal</td>
            <td style="width: 15px; vertical-align: top; font-size: 10.5pt;">:</td>
            <td style="font-size: 10.5pt;"><strong>{{ !empty($meeting_day_date) ? $meeting_day_date : ($date ?? '-') }}</strong></td>
        </tr>
        <tr>
            <td style="vertical-align: top; font-size: 10.5pt;">Waktu</td>
            <td style="vertical-align: top; font-size: 10.5pt;">:</td>
            <td style="font-size: 10.5pt;">{{ !empty($meeting_time) ? $meeting_time : 'Pukul 09.00 WIB s/d selesai' }}</td>
        </tr>
        <tr>
            <td style="vertical-align: top; font-size: 10.5pt;">Tempat</td>
            <td style="vertical-align: top; font-size: 10.5pt;">:</td>
            <td style="font-size: 10.5pt;">{{ !empty($meeting_venue) ? $meeting_venue : 'Kantor ' . ($school->name ?? 'Sekolah') }}</td>
        </tr>
        <tr>
            <td style="vertical-align: top; font-size: 10.5pt;">Keterangan</td>
            <td style="vertical-align: top; font-size: 10.5pt;">:</td>
            <td style="font-size: 10.5pt; text-align: justify;">
                Menemui {{ $signatory_title ?? 'Waka Kesiswaan dan Guru BK' }}
                @if(!empty($reason))
                    <br><span style="color: #374151;">({{ $reason }})</span>
                @endif
            </td>
        </tr>
        @if(!empty($contact_person))
        <tr>
            <td style="vertical-align: top; font-size: 10.5pt;">Konfirmasi</td>
            <td style="vertical-align: top; font-size: 10.5pt;">:</td>
            <td style="font-size: 10.5pt;">{{ $contact_person }}</td>
        </tr>
        @endif
    </table>

    <!-- Penutup -->
    <p style="margin: 0 0 8px 0; font-size: 10.5pt; text-align: justify; line-height: 1.45;">
        Demikian surat ini disampaikan, atas perhatian dan kehadirannya kami ucapkan terima kasih.
    </p>
    <p style="margin: 0 0 16px 0; font-size: 10.5pt;">Wassalamu’alaikum Wr. Wb.</p>

    <!-- Tanda Tangan -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; page-break-inside: avoid;">
        <tr>
            <td style="width: 55%;"></td>
            <td style="width: 45%; text-align: center; vertical-align: top; font-size: 10.5pt; line-height: 1.35;">
                @php
                    $titleLower = strtolower($signatory_title ?? '');
                    $isKepsek = str_contains($titleLower, 'kepala sekolah') && !str_contains($titleLower, 'wakil') && !str_contains($titleLower, 'waka');
                @endphp
                @if(!$isKepsek)
                    a.n. Kepala Sekolah<br>
                @endif
                {{ $signatory_title ?? 'Waka Kesiswaan' }},

                @if(($school->show_signature_image ?? false) && !empty($school->signature_path))
                    <div style="margin: 5px 0;">
                        <img src="{{ public_path($school->signature_path) }}" alt="Tanda Tangan" style="max-height: 50px; max-width: 140px;">
                    </div>
                @else
                    <br><br><br><br>
                @endif

                <strong><u>{{ $signatory_name ?? ($school->principal_name ?? '.......................') }}</u></strong>
                @if(!empty($school->principal_nip) && $isKepsek)
                    <div style="font-size: 9pt; color: #4B5563; margin-top: 2px;">NIP. {{ $school->principal_nip }}</div>
                @endif
            </td>
        </tr>
    </table>

</body>
</html>
