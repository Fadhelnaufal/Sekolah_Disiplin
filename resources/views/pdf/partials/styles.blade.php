<style>
    @page {
        size: A4 portrait;
        margin: 20mm 15mm 20mm 15mm;
    }

    body {
        font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
        font-size: 11pt;
        line-height: 1.45;
        color: #111827;
        margin: 0;
        padding: 0;
    }

    .page-break {
        page-break-after: always;
    }

    .no-break {
        page-break-inside: avoid;
    }

    /* Kop Surat */
    .kop-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 4px;
    }

    .kop-logo-td {
        width: 70px;
        vertical-align: middle;
        text-align: center;
    }

    .kop-logo-img {
        max-width: 65px;
        max-height: 65px;
    }

    .kop-text-td {
        text-align: center;
        vertical-align: middle;
    }

    .kop-school-name {
        font-size: 15pt;
        font-weight: bold;
        text-transform: uppercase;
        margin: 0;
        color: #111827;
        letter-spacing: 0.5px;
    }

    .kop-school-address {
        font-size: 9.5pt;
        margin: 2px 0 0 0;
        color: #374151;
        font-style: italic;
    }

    .kop-school-meta {
        font-size: 9pt;
        margin: 2px 0 0 0;
        color: #4B5563;
    }

    /* Dividers */
    .kop-divider-formal {
        margin-top: 8px;
        margin-bottom: 18px;
        border-top: 2.5px solid #000;
        border-bottom: 1px solid #000;
        height: 2px;
    }

    .kop-divider-minimal {
        margin-top: 8px;
        margin-bottom: 18px;
        border-bottom: 1px solid #6B7280;
    }

    /* Document Titles */
    .document-header-area {
        text-align: center;
        margin-bottom: 20px;
    }

    .document-title {
        font-size: 13pt;
        font-weight: bold;
        text-transform: uppercase;
        text-decoration: underline;
        margin: 0;
    }

    .document-number {
        font-size: 10pt;
        margin-top: 4px;
        color: #374151;
    }

    /* Standard Tables */
    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }

    table.data-table th,
    table.data-table td {
        border: 1px solid #000;
        padding: 5px 8px;
        font-size: 9.5pt;
        vertical-align: middle;
    }

    table.data-table th {
        background-color: #F3F4F6;
        font-weight: bold;
        text-align: center;
    }

    table.data-table tr {
        page-break-inside: avoid;
    }

    /* 3 Point Summary Boxes */
    .point-boxes-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
        text-align: center;
    }

    .point-box-td {
        border: 1px solid #9CA3AF;
        padding: 8px 10px;
        width: 33.33%;
        vertical-align: middle;
    }

    .point-box-label {
        font-size: 9pt;
        color: #4B5563;
        margin-bottom: 3px;
    }

    .point-box-val {
        font-size: 12pt;
        font-weight: bold;
        color: #111827;
    }

    /* Info Table */
    table.info-table {
        width: 100%;
        margin: 10px 0;
        border-collapse: collapse;
    }

    table.info-table td {
        padding: 2px 0;
        vertical-align: top;
        font-size: 10.5pt;
    }

    table.info-table td.label-col {
        width: 140px;
        color: #374151;
    }

    table.info-table td.colon-col {
        width: 15px;
        text-align: center;
    }

    /* Signatory Section */
    .signatory-table {
        width: 100%;
        margin-top: 30px;
        border-collapse: collapse;
        page-break-inside: avoid;
    }

    .signatory-table td {
        vertical-align: top;
    }

    .signatory-box {
        text-align: center;
        width: 240px;
        font-size: 10.5pt;
        line-height: 1.35;
    }

    .signatory-name {
        font-weight: bold;
        text-decoration: underline;
        color: #111827;
        margin-top: 5px;
    }

    .signatory-nip {
        font-size: 9pt;
        color: #4B5563;
        margin-top: 2px;
    }

    .signature-image {
        max-height: 55px;
        max-width: 160px;
        margin: 3px auto;
        display: block;
    }

    .stamp-image {
        max-height: 55px;
        max-width: 70px;
        position: absolute;
        opacity: 0.8;
    }
</style>
