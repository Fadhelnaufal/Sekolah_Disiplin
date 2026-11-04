@php
    $logoSrc = $school->logo_src ?? null;
    $addressParts = array_filter([
        $school->address ?? null,
        $school->city ?? null,
        $school->province ?? null,
        $school->postal_code ?? null,
    ]);
    $fullAddress = implode(', ', $addressParts);

    $metaParts = [];
    if (($school->show_npsn ?? true) && !empty($school->npsn)) {
        $metaParts[] = 'NPSN ' . $school->npsn;
    }
    if (($school->show_phone ?? true) && !empty($school->phone)) {
        $metaParts[] = 'Telp. ' . $school->phone;
    }
    if (($school->show_email ?? true) && !empty($school->email)) {
        $metaParts[] = $school->email;
    }
    if (($school->show_website ?? false) && !empty($school->website)) {
        $metaParts[] = $school->website;
    }
    $metaLine = implode(' · ', $metaParts);
    $style = $school->template_style ?? 'formal_indonesia';
@endphp

<div class="kop-container no-break">
    <table class="kop-table">
        <tr>
            @if($logoSrc)
                <td class="kop-logo-td">
                    <img src="{{ $logoSrc }}" alt="Logo" class="kop-logo-img">
                </td>
            @endif
            <td class="kop-text-td" style="{{ $logoSrc ? 'padding-left: 10px;' : '' }}">
                <h1 class="kop-school-name">{{ $school->name ?? 'NAMA SEKOLAH' }}</h1>
                @if(!empty($fullAddress))
                    <p class="kop-school-address">{{ $fullAddress }}</p>
                @endif
                @if(!empty($metaLine))
                    <p class="kop-school-meta">{{ $metaLine }}</p>
                @endif
            </td>
        </tr>
    </table>

    @if($style === 'formal_minimal')
        <div class="kop-divider-minimal"></div>
    @else
        <div class="kop-divider-formal"></div>
    @endif
</div>
