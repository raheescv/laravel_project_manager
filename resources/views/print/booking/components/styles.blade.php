{{-- "Official Register" print style for the off-plan sale agreement: a ruled
     grid in black ink only, built for bond paper. No background fills — grey
     shading prints blotchy on bond stock. Page margins (the letterhead and
     footer bands) are set per mode in the template's @page rule.

     Arabic is set in Noto Naskh Arabic, a serif Naskh drawn to sit beside
     Times. Browsershot blocks every external request, so the bundled font
     files are embedded as data URIs rather than loaded by URL. --}}
@php
    $naskhFont = fn (string $weight): string => 'data:font/ttf;base64,' . base64_encode(file_get_contents(public_path("assets/fonts/print/NotoNaskhArabic-{$weight}.ttf")));
@endphp
<style type="text/css">
    @font-face {
        font-family: "Print Naskh";
        font-weight: 400;
        src: url("{{ $naskhFont('Regular') }}") format("truetype");
    }

    @font-face {
        font-family: "Print Naskh";
        font-weight: 700;
        src: url("{{ $naskhFont('Bold') }}") format("truetype");
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    @page {
        size: A4;
    }

    body {
        font-family: "Times New Roman", Times, "Liberation Serif", serif;
        font-size: 8pt;
        line-height: 1.42;
        color: #111;
        background: #fff;
        width: 210mm;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    [lang="ar"] {
        font-family: "Print Naskh", "Noto Naskh Arabic", "Traditional Arabic", "Times New Roman", serif !important;
        direction: rtl;
        text-align: right;
        font-size: 8.8pt;
        line-height: 1.45;
    }

    [lang="ar"] [lang="ar"] {
        font-size: inherit;
    }

    p {
        margin: 0 0 1.4mm;
    }

    p:last-child {
        margin-bottom: 0;
    }

    b,
    strong {
        font-weight: bold;
    }

    .container {
        width: 100%;
        padding: 0 10mm;
    }

    .page-break {
        page-break-before: always;
    }

    .avoid-break {
        page-break-inside: avoid;
    }

    .underline {
        text-decoration: underline;
    }

    .text-center {
        text-align: center !important;
    }

    .text-right {
        text-align: right;
    }

    .bold {
        font-weight: bold;
    }

    .num {
        font-variant-numeric: tabular-nums;
    }

    /* ── Tables: every ruled grid in the document ─────────────────── */
    table {
        border-collapse: collapse;
    }

    /* Rows never split across a page boundary — except the .doc-wrap
       wrapper's single body row, which holds the whole document. */
    table:not(.doc-wrap) tr {
        page-break-inside: avoid;
    }

    thead {
        display: table-header-group;
    }

    table.reg {
        width: 100%;
        table-layout: fixed;
        margin-bottom: 3mm;
    }

    table.reg > tbody > tr > td,
    table.reg > thead > tr > th,
    table.reg > tr > td {
        border: 0.6pt solid #222;
        padding: 2mm 2.5mm;
        vertical-align: top;
    }

    /* A row that continues the article above it: a faint dotted rule rather
       than none, so a box split at a page break still closes on both pages. */
    table.reg tr:has(+ tr.cont) > td {
        border-bottom: 0.4pt dotted #999;
    }

    table.reg tr.cont > td {
        border-top: 0.4pt dotted #999;
    }

    table.reg col.no {
        width: 10mm;
    }

    table.reg td.no {
        text-align: center;
        font-weight: bold;
        padding-left: 0;
        padding-right: 0;
    }

    table.reg td.no span {
        display: inline-block;
        width: 6.5mm;
        height: 6.5mm;
        line-height: 6.2mm;
        border: 0.6pt solid #222;
        border-radius: 50%;
        font-size: 8.5pt;
    }

    table.reg h3 {
        display: inline-block;
        font-size: 8.4pt;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding-bottom: 0.8mm;
        margin-bottom: 1.6mm;
        border-bottom: 2.4pt double #222;
    }

    table.reg td[lang="ar"] h3 {
        text-transform: none;
        letter-spacing: 0;
        font-size: 9.8pt;
    }

    .here {
        font-weight: bold;
        margin-top: 1.4mm;
    }

    /* Label / value rows inside a party or specification cell. */
    table.reg-kv {
        width: 100%;
        margin: 1mm 0;
    }

    table.reg-kv td {
        border: 0;
        border-bottom: 0.4pt solid #bbb;
        padding: 0.7mm 0;
        vertical-align: top;
    }

    table.reg-kv tr:first-child td {
        border-top: 0.4pt solid #999;
    }

    table.reg-kv td.k {
        width: 32%;
        padding-inline-end: 2mm;
        white-space: nowrap;
    }

    table.reg-kv td.v {
        font-weight: bold;
    }

    /* Party and project groups: each its own framed box, kept on one page. */
    table.reg.group {
        page-break-inside: avoid;
    }

    /* Boxes that follow one another stack with no gap; the -0.6pt margin lays
       the shared edges on top of each other so they print as one rule. */
    table.reg:has(+ table.reg) {
        margin-bottom: -0.6pt;
    }

    /* Group header band: English title left, Arabic right, across the whole
       box. A soft grey band (light enough to print clean on bond stock), a
       short ink bar marking each title, and a single rule joining it to the rows. */
    table.reg.group tr.group-head > td {
        background: #ececec;
        border-bottom: 0.8pt solid #222;
        padding: 1.6mm 3mm 1.4mm;
    }

    tr.group-head div {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
    }

    tr.group-head span:first-child {
        font-size: 8.6pt;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding-left: 2.2mm;
        border-left: 3pt solid #111;
        line-height: 1.15;
    }

    tr.group-head span[lang="ar"] {
        font-size: 10.1pt;
        padding-right: 2.2mm;
        border-right: 3pt solid #111;
        line-height: 1.3;
    }

    /* One detail per row inside a group. No cell rules between rows — a short
       light hairline, inset from the cell edges, keeps each English line level
       with its Arabic one without drawing a full grid. */
    table.reg tr:has(+ tr.line) > td {
        border-bottom: 0;
    }

    table.reg tr.line > td {
        border-top: 0;
        border-bottom: 0;
        padding-top: 0.8mm;
        padding-bottom: 0.8mm;
        vertical-align: middle;
        background: linear-gradient(#cfcfcf, #cfcfcf) no-repeat center bottom / calc(100% - 5mm) 0.5pt;
    }

    table.reg tr.line:last-child > td {
        background: none;
        border-bottom: 0.6pt solid #222;
        padding-bottom: 2mm;
    }

    table.reg tr.line .here {
        margin: 0;
    }

    .lbl {
        font-weight: normal;
    }

    /* Hyphenated IDs (10-3804-00) stay left-to-right and on one line, even
       inside Arabic text, instead of breaking at a hyphen and reordering. */
    .ltr-id {
        direction: ltr;
        unicode-bidi: isolate;
        white-space: nowrap;
    }

    /* Figures table (instalments): double rule under the header and above the total. */
    table.reg-data {
        width: 100%;
        margin-bottom: 3mm;
    }

    table.reg-data th,
    table.reg-data td {
        border: 0.6pt solid #222;
        padding: 1.3mm 2mm;
        font-size: 8pt;
        vertical-align: middle;
    }

    table.reg-data thead th {
        border-bottom: 2.4pt double #222;
        text-align: center;
    }

    table.reg-data th span {
        display: block;
        font-weight: normal;
    }

    table.reg-data tr.total td {
        font-weight: bold;
        border-top: 2.4pt double #222;
    }

    table.reg-data td.r {
        text-align: right;
    }

    table.reg-data td.c {
        text-align: center;
    }

    table.reg-data td h4 {
        font-size: 8pt;
        text-align: center;
    }

    /* ── Header block: contract meta + QR ────────────────────────── */
    .reg-meta {
        display: flex;
        align-items: stretch;
        gap: 3mm;
        margin-bottom: 3mm;
    }

    .reg-meta table {
        flex: 1;
    }

    .reg-meta td {
        border: 0.6pt solid #222;
        padding: 1.1mm 2mm;
        font-size: 8pt;
    }

    .reg-meta td.k {
        font-weight: bold;
        width: 20%;
    }

    .reg-meta .qr {
        flex: none;
        border: 0.6pt solid #222;
        padding: 1mm;
        text-align: center;
    }

    .reg-meta .qr img {
        width: 17mm;
        height: 17mm;
        display: block;
    }

    .reg-meta .qr small {
        font-size: 6pt;
        display: block;
    }

    /* ── Framed titles ────────────────────────────────────────────── */
    .reg-title {
        border: 3pt double #111;
        text-align: center;
        padding: 2.5mm 3mm;
        margin-bottom: 3mm;
        page-break-inside: avoid;
        page-break-after: avoid;
    }

    .reg-title .en {
        font-size: 12.5pt;
        font-weight: bold;
        letter-spacing: 0.04em;
    }

    .reg-title .ar {
        font-size: 12.5pt;
        font-weight: bold;
        text-align: center;
    }

    .reg-title .sub {
        font-size: 8.5pt;
        font-weight: bold;
        text-decoration: underline;
        margin-top: 1mm;
    }

    .reg-title .sub [lang="ar"] {
        display: inline;
        font-size: 9.5pt;
    }

    .reg-sched-title {
        border: 0.6pt solid #222;
        text-align: center;
        padding: 2mm;
        margin-bottom: 3mm;
        page-break-after: avoid;
    }

    .reg-sched-title .main {
        font-size: 11.5pt;
        font-weight: bold;
    }

    .reg-sched-title .sub {
        font-weight: bold;
        text-decoration: underline;
    }

    .reg-sched-title [lang="ar"] {
        display: inline;
        font-size: inherit;
    }

    /* ── Signature boxes ──────────────────────────────────────────── */
    .sign-space {
        height: 20mm;
    }

    ul.reg-list {
        margin: 1mm 0 0;
        padding-inline-start: 5mm;
    }

    ul.reg-list li {
        margin-bottom: 0.8mm;
    }
</style>
