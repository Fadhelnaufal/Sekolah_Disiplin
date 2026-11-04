@php
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $formattedDate = $date_string ?? (date('j') . ' ' . ($months[(int)date('n')] ?? date('F')) . ' ' . date('Y'));
    $city = $school->letter_city ?? ($school->city ?? 'Ponorogo');
    $title = $signatory_title ?? ($school->signer_title ?? 'Kepala Sekolah');
    $name = $signatory_name ?? ($school->principal_name ?? '.......................');
    $nip = $signatory_nip ?? ($school->principal_nip ?? null);

    $showSignature = ($school->show_signature_image ?? true) && !empty($school->signature_src);
    $showStamp = ($school->show_stamp ?? false) && !empty($school->stamp_src);
@endphp

<table class="signatory-table no-break">
    <tr>
        <td style="width: 55%;"></td>
        <td style="width: 45%;">
            <div class="signatory-box" style="float: right;">
                <div>{{ $city }}, {{ $formattedDate }}</div>
                <div style="font-weight: 500; margin-top: 2px;">{{ $title }}</div>

                <div style="position: relative; min-height: 55px; margin: 5px 0;">
                    @if($showStamp)
                        <img src="{{ $school->stamp_src }}" alt="Stempel" class="stamp-image" style="left: -15px; top: 0;">
                    @endif
                    @if($showSignature)
                        <img src="{{ $school->signature_src }}" alt="Tanda Tangan" class="signature-image">
                    @else
                        <div style="height: 50px;"></div>
                    @endif
                </div>

                <div class="signatory-name">{{ $name }}</div>
                @if(!empty($nip) && ($title === 'Kepala Sekolah' || empty($signatory_title)))
                    <div class="signatory-nip">NIP. {{ $nip }}</div>
                @endif
            </div>
        </td>
    </tr>
</table>
