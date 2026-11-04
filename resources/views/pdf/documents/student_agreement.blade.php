<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject ?? 'Surat Perjanjian Siswa' }}</title>
    @include('pdf.partials.styles')
</head>
<body>

    @include('pdf.partials.kop', ['school' => $school])

    <div class="document-header-area">
        <h2 class="document-title">{{ strtoupper($subject ?? 'SURAT PERJANJIAN DAN PERNYATAAN DISIPLIN SISWA') }}</h2>
        <div class="document-number">Nomor: {{ $letter_number ?? '..... / TATIB / ' . date('Y') }}</div>
    </div>

    <p style="margin: 8px 0; font-size: 10.5pt;">Yang bertanda tangan di bawah ini:</p>

    <table class="info-table" style="margin-bottom: 10px;">
        <tr>
            <td class="label-col">Nama Lengkap</td>
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
            <td class="label-col">Orang Tua / Wali</td>
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

    <div style="font-size: 10.5pt; line-height: 1.45; text-align: justify; margin: 8px 0;">
        <p style="margin: 4px 0;">
            Menyatakan dengan sesungguhnya bahwa sehubungan dengan catatan ketidaktertiban:
        </p>
        <div style="background-color: #F9FAFB; border-left: 3px solid #6366F1; padding: 6px 12px; margin: 5px 0 10px 0; font-style: italic;">
            {{ !empty($problem_summary) ? $problem_summary : ('Pelanggaran tata tertib sekolah dengan akumulasi poin sebesar ' . ($net_points ?? 0) . ' poin.') }}
        </div>

        <p style="margin: 6px 0 3px 0;">Dengan ini saya menyatakan berjanji dan berkomitmen untuk:</p>
        <div style="white-space: pre-line; margin: 3px 0 8px 12px; line-height: 1.4;">
            {{ !empty($student_commitments) ? $student_commitments : "1. Menyadari kesalahan dan berjanji tidak akan mengulangi pelanggaran tersebut.\n2. Menaati seluruh peraturan dan tata tertib sekolah dengan sungguh-sungguh.\n3. Berperilaku sopan dan menjaga nama baik diri sendiri, keluarga, serta sekolah." }}
        </div>

        @if(!empty($guidance_plan))
            <p style="margin: 6px 0 2px 0;"><strong>Bentuk Pembinaan & Pemantauan:</strong></p>
            <p style="margin: 2px 0 8px 12px;">{{ $guidance_plan }}</p>
        @endif

        <p style="margin: 6px 0 2px 0;"><strong>Masa Evaluasi:</strong></p>
        <p style="margin: 2px 0 8px 12px;">
            Perjanjian ini berlaku selama <strong>{{ !empty($evaluation_period) ? $evaluation_period : '30 (tiga puluh) hari kalender' }}</strong> sejak tanggal ditandatangani.
        </p>

        @if(!empty($sanction_consequence))
            <p style="margin: 6px 0 2px 0;"><strong>Konsekuensi Sanksi:</strong></p>
            <p style="margin: 2px 0 8px 12px;">{{ $sanction_consequence }}</p>
        @endif

        <p style="margin: 10px 0 15px 0;">
            Demikian surat perjanjian dan pernyataan ini saya buat dengan sadar, penuh keikhlasan, serta tanpa ada paksaan dari pihak manapun untuk dapat dipergunakan sebagaimana mestinya.
        </p>
    </div>

    <!-- Multi-party Signatory Section -->
    @php
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $formattedDate = $date ?? (date('j') . ' ' . ($months[(int)date('n')] ?? date('F')) . ' ' . date('Y'));
        $city = $school->letter_city ?? ($school->city ?? 'Ponorogo');
    @endphp

    <div style="text-align: right; font-size: 10pt; margin-bottom: 6px;">
        {{ $city }}, {{ $formattedDate }}
    </div>

    <table style="width: 100%; border-collapse: collapse; font-size: 9.5pt; text-align: center; margin-bottom: 12px;" class="no-break">
        <tr>
            <td style="width: 50%; vertical-align: top; padding: 4px;">
                <div>Menyetujui,</div>
                <div style="font-weight: 500;">Orang Tua / Wali Siswa</div>
                <div style="height: 48px;"></div>
                <div style="font-weight: bold; text-decoration: underline;">
                    {{ $student->parent_name ?: '( ........................................ )' }}
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; padding: 4px;">
                <div>Yang Membuat Pernyataan,</div>
                <div style="font-weight: 500;">Siswa Bersangkutan</div>
                <div style="height: 48px; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 8pt; color: #9CA3AF; border: 1px dashed #D1D5DB; padding: 2px 6px;">Meterai</span>
                </div>
                <div style="font-weight: bold; text-decoration: underline;">
                    {{ $student->name }}
                </div>
                <div style="font-size: 8.5pt; color: #4B5563;">NIS. {{ $student->nis }}</div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; font-size: 9.5pt; text-align: center;" class="no-break">
        <tr>
            <td style="width: 50%; vertical-align: top; padding: 4px;">
                <div>Saksi / Pembina,</div>
                <div style="font-weight: 500;">Guru BK / Wali Kelas</div>
                <div style="height: 48px;"></div>
                <div style="font-weight: bold; text-decoration: underline;">
                    {{ $student->classRoom?->homeroomTeacher?->name ?? ($school->counseling_officer_name ?: '........................................') }}
                </div>
                @if($student->classRoom?->homeroomTeacher?->nip)
                    <div style="font-size: 8.5pt; color: #4B5563;">NIP. {{ $student->classRoom->homeroomTeacher->nip }}</div>
                @endif
            </td>
            <td style="width: 50%; vertical-align: top; padding: 4px; position: relative;">
                <div>Mengetahui,</div>
                <div style="font-weight: 500;">{{ $school->signer_title ?: 'Kepala Sekolah' }}</div>
                
                <div style="position: relative; min-height: 48px; margin: 2px 0;">
                    @if(($school->show_stamp ?? false) && !empty($school->stamp_src))
                        <img src="{{ $school->stamp_src }}" alt="Stempel" class="stamp-image" style="left: 15px; top: -5px; height: 50px; opacity: 0.85; position: absolute;">
                    @endif
                    @if(($school->show_signature_image ?? true) && !empty($school->signature_src))
                        <img src="{{ $school->signature_src }}" alt="TTD" class="signature-image" style="height: 44px;">
                    @else
                        <div style="height: 44px;"></div>
                    @endif
                </div>

                <div style="font-weight: bold; text-decoration: underline;">
                    {{ $school->principal_name ?: '........................................' }}
                </div>
                @if(!empty($school->principal_nip))
                    <div style="font-size: 8.5pt; color: #4B5563;">NIP. {{ $school->principal_nip }}</div>
                @endif
            </td>
        </tr>
    </table>

</body>
</html>
