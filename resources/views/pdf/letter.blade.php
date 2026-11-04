<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $document_title ?? ($subject ?? 'Surat Pemanggilan') }}</title>
    @include('pdf.partials.styles')
</head>
<body>

    @include('pdf.partials.kop', ['school' => $school])

    @php
        $isCallLetter = in_array($type ?? '', ['call_1', 'call_2', 'call_3', 'parent_call', 'call_general']) || ($canonical_type ?? '') === 'parent_call';
    @endphp

    @if($isCallLetter)
        <!-- Format Dinas Resmi Pemanggilan Orang Tua (Sesuai Standar Tata Naskah Dinas Sekolah) -->
        <table style="width: 100%; margin-bottom: 14px; border-collapse: collapse;">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <table style="border-collapse: collapse;">
                        <tr>
                            <td style="width: 60px; vertical-align: top; font-size: 10.5pt;">Nomor</td>
                            <td style="width: 15px; vertical-align: top; font-size: 10.5pt;">:</td>
                            <td style="vertical-align: top; font-size: 10.5pt;">{{ $letter_number ?? (($school->classification_code ?? '421.3') . ' / _____ / ' . ($school->school_code ?? 'DP') . ' / ' . date('Y')) }}</td>
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

        <div style="margin-bottom: 14px; font-size: 10.5pt; line-height: 1.45;">
            Kepada :<br>
            Yth. Bapak/Ibu Wali Murid dari <strong>{{ $student->name }}</strong><br>
            Di Tempat
        </div>

        <p style="margin: 0 0 8px 0; font-size: 10.5pt;">Assalamu’alaikum Wr. Wb.</p>
        <p style="margin: 0 0 8px 0; font-size: 10.5pt; text-align: justify; line-height: 1.45;">
            Mengharap dengan hormat atas kehadiran Bapak/Ibu wali murid dari :
        </p>

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

        <p style="margin: 0 0 8px 0; font-size: 10.5pt; text-align: justify; line-height: 1.45;">
            Demikian surat ini disampaikan, atas perhatian dan kehadirannya kami ucapkan terima kasih.
        </p>
        <p style="margin: 0 0 16px 0; font-size: 10.5pt;">Wassalamu’alaikum Wr. Wb.</p>

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

    @else
        <!-- Format Surat Pemberitahuan Perkembangan Kedisiplinan -->
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
                            <td style="vertical-align: top; font-size: 10.5pt;"><strong>{{ $subject ?? 'Pemberitahuan Perkembangan Kedisiplinan' }}</strong></td>
                        </tr>
                    </table>
                </td>
                <td style="width: 40%; vertical-align: top; text-align: right; font-size: 10.5pt;">
                    {{ $school->letter_city ?? ($school->city ?? 'Ponorogo') }}, {{ $date ?? date('d F Y') }}
                </td>
            </tr>
        </table>

        <div style="margin-bottom: 14px; font-size: 10.5pt; line-height: 1.45;">
            Kepada :<br>
            Yth. Bapak/Ibu Orang Tua / Wali dari <strong>{{ $student->name }}</strong><br>
            Di <br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $student->address ?? 'Tempat' }}
        </div>

        <p style="margin: 0 0 8px 0; font-size: 10.5pt;">Dengan hormat,</p>
        <p style="margin: 0 0 8px 0; font-size: 10.5pt; text-align: justify; line-height: 1.45;">
            Sehubungan dengan proses pembinaan dan pemantauan tata tertib peserta didik di sekolah, kami menyampaikan rincian perkembangan kedisiplinan atas peserta didik:
        </p>

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
                <td style="vertical-align: top; font-size: 10.5pt;">NIS</td>
                <td style="vertical-align: top; font-size: 10.5pt;">:</td>
                <td style="font-size: 10.5pt;">{{ $student->nis }}</td>
            </tr>
            <tr>
                <td style="vertical-align: top; font-size: 10.5pt;">Total Poin Bersih</td>
                <td style="vertical-align: top; font-size: 10.5pt;">:</td>
                <td style="font-size: 10.5pt; font-weight: bold; color: #B42318;">{{ $net_points ?? 0 }} Poin</td>
            </tr>
        </table>

        @if(!empty($notes))
            <p style="margin: 0 0 8px 0; font-size: 10.5pt; text-align: justify; line-height: 1.45;">
                <strong>Catatan Sekolah:</strong> {{ $notes }}
            </p>
        @endif

        <p style="margin: 12px 0 8px 0; font-size: 10.5pt; text-align: justify; line-height: 1.45;">
            Demikian surat pemberitahuan ini kami sampaikan agar menjadi perhatian bersama demi pembinaan karakter ananda. Atas kerja sama Bapak/Ibu, kami ucapkan terima kasih.
        </p>

        @include('pdf.partials.signatory', [
            'school' => $school,
            'signatory_title' => $signatory_title ?? null,
            'signatory_name' => $signatory_name ?? null,
            'date_string' => $date ?? null,
        ])
    @endif

</body>
</html>
