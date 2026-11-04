<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <meta charset="utf-8">
    <title>{{ $subject ?? ($document_title ?? 'Surat Dokumen Siswa') }}</title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        @page Section1 {
            size: 595.3pt 841.9pt; /* A4 */
            margin: 2.0cm 2.0cm 2.0cm 2.0cm;
            mso-header-margin: 36.0pt;
            mso-footer-margin: 36.0pt;
            mso-paper-source: 0;
        }
        div.Section1 {
            page: Section1;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.35;
            color: #000;
        }
        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .double-divider {
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }
        .minimal-divider {
            border-bottom: 1px solid #000;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }
        .meta-table td {
            vertical-align: top;
            font-size: 11pt;
        }
        .info-table {
            width: 100%;
            margin: 8px 0 10px 18px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 2.5px 0;
            vertical-align: top;
            font-size: 11pt;
        }
        .info-label {
            width: 130px;
        }
        .metric-table {
            width: 100%;
            margin: 18px 0;
            border-collapse: collapse;
        }
        .metric-cell {
            width: 33.33%;
            border: 1px solid #777;
            padding: 8px;
            text-align: center;
        }
        .signature-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .signature-table td {
            vertical-align: top;
            font-size: 11pt;
        }
    </style>
</head>
<body>

    @php
        $schoolObj = is_array($school) ? (object) $school : $school;

        $getBase64Image = function($path) {
            if (empty($path)) return null;
            $fullPath = public_path(ltrim($path, '/'));
            if (!file_exists($fullPath)) {
                $storagePath = storage_path('app/public/' . ltrim(str_replace('/storage/', '', $path), '/'));
                if (file_exists($storagePath)) {
                    $fullPath = $storagePath;
                }
            }
            if (file_exists($fullPath) && !is_dir($fullPath)) {
                $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
                $mime = in_array(strtolower($ext), ['jpg', 'jpeg']) ? 'image/jpeg' : 'image/png';
                $data = file_get_contents($fullPath);
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }
            return null;
        };

        $logoBase64 = $getBase64Image($schoolObj->logo_path ?? null);
        $signatureBase64 = ($schoolObj->show_signature_image ?? false) ? $getBase64Image($schoolObj->signature_path ?? null) : null;
        $stampBase64 = ($schoolObj->show_stamp ?? false) ? $getBase64Image($schoolObj->stamp_path ?? null) : null;

        $addressParts = array_filter([
            $schoolObj->address ?? null,
            $schoolObj->city ?? null,
            $schoolObj->province ?? null,
            $schoolObj->postal_code ?? null,
        ]);
        $fullAddress = implode(', ', $addressParts);

        $metaParts = [];
        if (($schoolObj->show_npsn ?? true) && !empty($schoolObj->npsn)) {
            $metaParts[] = 'NPSN ' . $schoolObj->npsn;
        }
        if (($schoolObj->show_phone ?? true) && !empty($schoolObj->phone)) {
            $metaParts[] = 'Telp. ' . $schoolObj->phone;
        }
        if (($schoolObj->show_email ?? true) && !empty($schoolObj->email)) {
            $metaParts[] = $schoolObj->email;
        }
        if (($schoolObj->show_website ?? false) && !empty($schoolObj->website)) {
            $metaParts[] = $schoolObj->website;
        }
        $metaLine = implode(' · ', $metaParts);

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $formattedDate = date('j') . ' ' . ($months[(int)date('n')] ?? date('F')) . ' ' . date('Y');
        $city = $schoolObj->letter_city ?? ($schoolObj->city ?? 'Ponorogo');

        $isCall = in_array($type ?? '', ['call_1', 'call_2', 'call_3', 'parent_call', 'call_general']) || ($canonical_type ?? '') === 'parent_call';

        $callHal = 'Panggilan Orang Tua / Wali Murid';
        if (($type ?? '') === 'call_1') $callHal = 'Panggilan Orang Tua / Wali Murid I';
        if (($type ?? '') === 'call_2') $callHal = 'Panggilan Orang Tua / Wali Murid II';
        if (($type ?? '') === 'call_3') $callHal = 'Panggilan Orang Tua / Wali Murid III';

        $signatoryTitle = $signatory_title ?? 'Kepala Sekolah';
        $signatoryName = $signatory_name ?? ($schoolObj->principal_name ?? 'Kepala Sekolah');
        $signatoryNip = $schoolObj->principal_nip ?? '-';
        $isKepsek = true;

        if (($type ?? '') === 'call_1') {
            $signatoryTitle = !empty($schoolObj->counseling_officer_name) ? 'Guru BK / Koordinator BK' : ($schoolObj->signer_title ?: 'Guru BK');
            $signatoryName = $schoolObj->counseling_officer_name ?: ($schoolObj->principal_name ?? 'Koordinator BK');
            $isKepsek = false;
        } elseif (($type ?? '') === 'call_2') {
            $signatoryTitle = !empty($schoolObj->vice_principal_name) ? 'Wakil Kepala Sekolah Bidang Kesiswaan' : ($schoolObj->signer_title ?: 'Waka Kesiswaan');
            $signatoryName = $schoolObj->vice_principal_name ?: ($schoolObj->principal_name ?? 'Waka Kesiswaan');
            $isKepsek = false;
        } elseif (($type ?? '') === 'call_3') {
            $signatoryTitle = 'Kepala Sekolah';
            $signatoryName = $schoolObj->principal_name ?? 'Kepala Sekolah';
            $isKepsek = true;
        }
    @endphp

    <div class="Section1">
        <!-- KOP SURAT RESMI -->
        <table class="header-table">
            <tr>
                @if($logoBase64)
                    <td style="width: 75px; vertical-align: middle; text-align: center;">
                        <img src="{{ $logoBase64 }}" width="65" height="65" style="display: block;" />
                    </td>
                    <td style="vertical-align: middle; text-align: center; padding-left: 10px; padding-right: 40px;">
                @else
                    <td style="vertical-align: middle; text-align: center;">
                @endif
                    <div style="font-size: 14pt; font-weight: bold; text-transform: uppercase;">
                        {{ $schoolObj->name ?? 'NAMA SEKOLAH' }}
                    </div>
                    @if(!empty($fullAddress))
                        <div style="font-size: 10pt; font-style: italic; color: #333; margin-top: 2px;">
                            {{ $fullAddress }}
                        </div>
                    @endif
                    @if(!empty($metaLine))
                        <div style="font-size: 9.5pt; color: #444; margin-top: 2px;">
                            {{ $metaLine }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>
        @if(($schoolObj->template_style ?? 'formal_indonesia') === 'formal_minimal')
            <div class="minimal-divider"></div>
        @else
            <div class="double-divider"></div>
        @endif

        @if($isCall)
            <!-- METADATA SURAT PANGGILAN -->
            <table class="meta-table">
                <tr>
                    <td style="width: 60%;">
                        <table style="border-collapse: collapse;">
                            <tr>
                                <td style="width: 60px;">Nomor</td>
                                <td style="width: 15px;">:</td>
                                <td>{{ $letter_number ?? (($schoolObj->classification_code ?? '421.3') . ' / _____ / ' . ($schoolObj->school_code ?? 'DP') . ' / ' . date('Y')) }}</td>
                            </tr>
                            <tr>
                                <td>Lamp.</td>
                                <td>:</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>Hal</td>
                                <td>:</td>
                                <td><strong><u>{{ $callHal }}</u></strong></td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 40%; text-align: right;">
                        {{ $city }}, {{ $date ?? $formattedDate }}
                    </td>
                </tr>
            </table>

            <!-- PENERIMA -->
            <div style="margin-bottom: 12px; font-size: 11pt; line-height: 1.4;">
                Kepada :<br>
                Yth. Bapak/Ibu Wali Murid dari <strong>{{ $student->name }}</strong><br>
                Di <br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $student->address ?? ($student->parent_address ?? 'Tempat') }}
            </div>

            <p style="margin: 0 0 6px 0; font-size: 11pt;">Assalamu’alaikum Wr. Wb.</p>
            <p style="margin: 0 0 8px 0; font-size: 11pt; text-align: justify;">
                Mengharap dengan hormat atas kehadiran Bapak/Ibu wali murid dari :
            </p>

            <!-- DATA SISWA -->
            <table class="info-table">
                <tr>
                    <td class="info-label">Nama Siswa</td>
                    <td style="width: 15px;">:</td>
                    <td><strong>{{ $student->name }}</strong></td>
                </tr>
                <tr>
                    <td class="info-label">Kelas</td>
                    <td>:</td>
                    <td>{{ $student->classRoom->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">NIS / NISN</td>
                    <td>:</td>
                    <td>{{ $student->nis ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Alamat</td>
                    <td>:</td>
                    <td>{{ $student->address ?? ($student->parent_address ?? 'Tempat tinggal siswa') }}</td>
                </tr>
            </table>

            <p style="margin: 0 0 8px 0; font-size: 11pt; text-align: justify;">
                Untuk hadir dengan ketentuan sebagai berikut :
            </p>

            <!-- KETENTUAN KEHADIRAN -->
            <table class="info-table">
                <tr>
                    <td class="info-label">Hari, tanggal</td>
                    <td style="width: 15px;">:</td>
                    <td><strong>{{ !empty($meeting_day_date) ? $meeting_day_date : ($date ?? $formattedDate) }}</strong></td>
                </tr>
                <tr>
                    <td class="info-label">Waktu</td>
                    <td>:</td>
                    <td>{{ !empty($meeting_time) ? $meeting_time : '08.00 WIB s/d Selesai' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Tempat</td>
                    <td>:</td>
                    <td>{{ !empty($meeting_venue) ? $meeting_venue : 'Ruang BK / Ruang Tata Tertib' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Keterangan</td>
                    <td>:</td>
                    <td>
                        Menemui {{ $signatoryTitle }}
                        @if(!empty($meeting_purpose) || !empty($reason) || !empty($notes))
                            <br><span style="color: #444; font-style: italic;">({{ $meeting_purpose ?? ($reason ?? $notes) }})</span>
                        @endif
                    </td>
                </tr>
            </table>

            <p style="margin: 0 0 6px 0; font-size: 11pt; text-align: justify;">
                Demikian surat ini disampaikan, atas perhatian dan kehadirannya kami ucapkan terima kasih.
            </p>
            <p style="margin: 0 0 16px 0; font-size: 11pt;">Wassalamu’alaikum Wr. Wb.</p>

            <!-- TANDA TANGAN BERJENJANG -->
            <table class="signature-table">
                <tr>
                    <td style="width: 55%;"></td>
                    <td style="width: 45%; text-align: center;">
                        @if(!$isKepsek)
                            a.n. Kepala Sekolah<br>
                        @endif
                        {{ $signatoryTitle }},
                        <div style="height: 60px; text-align: center; vertical-align: middle; padding: 4px 0;">
                            @if($stampBase64 && $signatureBase64)
                                <img src="{{ $stampBase64 }}" width="60" height="60" style="vertical-align: middle; margin-right: -25px;" />
                                <img src="{{ $signatureBase64 }}" width="110" height="50" style="vertical-align: middle;" />
                            @elseif($signatureBase64)
                                <img src="{{ $signatureBase64 }}" width="110" height="50" style="vertical-align: middle;" />
                            @elseif($stampBase64)
                                <img src="{{ $stampBase64 }}" width="60" height="60" style="vertical-align: middle;" />
                            @else
                                <br><br><br>
                            @endif
                        </div>
                        <strong><u>{{ $signatoryName }}</u></strong>
                        @if(!empty($signatoryNip) && $signatoryNip !== '-')
                            <div style="font-size: 9.5pt;">NIP. {{ $signatoryNip }}</div>
                        @endif
                    </td>
                </tr>
            </table>

        @else
            <!-- FORMAT PEMBERITAHUAN / PERJANJIAN / REKAP -->
            <div style="text-align: center; margin: 15px 0 10px 0;">
                <div style="font-size: 12pt; font-weight: bold; text-decoration: underline; text-transform: uppercase;">
                    {{ $document_title ?? 'SURAT PEMBERITAHUAN ORANG TUA/WALI' }}
                </div>
                <div style="font-size: 11pt; margin-top: 3px;">
                    Nomor: {{ $letter_number ?? ('421.3 / _____ / DP / ' . date('Y')) }}
                </div>
            </div>

            <div style="margin-bottom: 12px; font-size: 11pt; line-height: 1.4;">
                Kepada :<br>
                Yth. Bapak/Ibu Orang Tua/Wali Peserta Didik<br>
                Di Tempat
            </div>

            <p style="font-size: 11pt; margin: 0 0 4px 0;">Dengan hormat,</p>
            <p style="font-size: 11pt; text-align: justify; margin: 0 0 10px 0;">
                Sehubungan dengan proses pembinaan dan pemantauan kedisiplinan peserta didik, sekolah menyampaikan informasi mengenai:
            </p>

            <table class="info-table">
                <tr>
                    <td class="info-label">Nama</td>
                    <td style="width: 15px;">:</td>
                    <td><strong>{{ $student->name }}</strong></td>
                </tr>
                <tr>
                    <td class="info-label">NIS</td>
                    <td>:</td>
                    <td>{{ $student->nis }}</td>
                </tr>
                <tr>
                    <td class="info-label">Kelas</td>
                    <td>:</td>
                    <td>{{ $student->classRoom->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Orang Tua/Wali</td>
                    <td>:</td>
                    <td>{{ $student->parent_name ?? 'Nama Orang Tua/Wali' }}</td>
                </tr>
                @if(!empty($notes))
                <tr>
                    <td class="info-label">Catatan</td>
                    <td>:</td>
                    <td style="font-style: italic;">{{ $notes }}</td>
                </tr>
                @endif
            </table>

            <!-- 3 KOTAK POIN (PELANGGARAN, PRESTASI, POIN BERSIH) -->
            <table class="metric-table">
                <tr>
                    <td class="metric-cell">
                        <div style="font-size: 9.5pt; color: #555;">Pelanggaran</div>
                        <div style="font-size: 12pt; font-weight: bold; margin-top: 3px;">{{ $violation_points ?? 0 }} poin</div>
                    </td>
                    <td class="metric-cell">
                        <div style="font-size: 9.5pt; color: #555;">Prestasi</div>
                        <div style="font-size: 12pt; font-weight: bold; margin-top: 3px;">{{ $achievement_points ?? 0 }} poin</div>
                    </td>
                    <td class="metric-cell">
                        <div style="font-size: 9.5pt; color: #555;">Poin Bersih</div>
                        <div style="font-size: 12pt; font-weight: bold; margin-top: 3px;">{{ $net_points ?? 0 }} poin</div>
                    </td>
                </tr>
            </table>

            <p style="font-size: 11pt; margin-top: 14px; text-align: justify;">
                Demikian surat ini disampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami mengucapkan terima kasih.
            </p>

            <table class="signature-table">
                <tr>
                    <td style="width: 55%;"></td>
                    <td style="width: 45%; text-align: center;">
                        {{ $city }}, {{ $date ?? $formattedDate }}<br>
                        {{ $schoolObj->signer_title ?? 'Kepala Sekolah' }}
                        <div style="height: 60px; text-align: center; vertical-align: middle; padding: 4px 0;">
                            @if($stampBase64 && $signatureBase64)
                                <img src="{{ $stampBase64 }}" width="60" height="60" style="vertical-align: middle; margin-right: -25px;" />
                                <img src="{{ $signatureBase64 }}" width="110" height="50" style="vertical-align: middle;" />
                            @elseif($signatureBase64)
                                <img src="{{ $signatureBase64 }}" width="110" height="50" style="vertical-align: middle;" />
                            @elseif($stampBase64)
                                <img src="{{ $stampBase64 }}" width="60" height="60" style="vertical-align: middle;" />
                            @else
                                <br><br><br>
                            @endif
                        </div>
                        <strong><u>{{ $schoolObj->principal_name ?? 'Kepala Sekolah' }}</u></strong>
                        @if(!empty($schoolObj->principal_nip))
                            <div style="font-size: 9.5pt;">NIP. {{ $schoolObj->principal_nip }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        @endif
    </div>

</body>
</html>
