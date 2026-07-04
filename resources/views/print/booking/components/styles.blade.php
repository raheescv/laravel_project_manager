<style type="text/css">
    /* Arabic Font Support */
    @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Scheherazade+New:wght@400;700&family=Noto+Sans+Arabic:wght@400;700&display=swap');

    * {
        margin: 0;
        padding: 0;
        text-indent: 0;
        box-sizing: border-box;
    }

    /* A4 sheet. Horizontal margins are 0 (the .container supplies them) so the
       fixed page footer can span the full sheet width. The top/bottom bands
       reserved for the letterhead and the repeating signature strip are set
       per-mode in the template via @page margin-top / margin-bottom. */
    @page {
        size: A4;
        margin: 0;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: "Times New Roman", serif;
        font-size: 9pt;
        line-height: 1.35;
        color: #1b1b1b;
        background: #ffffff;
        width: 210mm;
    }

    /* ── Arabic text ───────────────────────────────────────────── */
    .arabic-text,
    [lang="ar"],
    .cell-ar p,
    .cell-ar div,
    .cell-ar span {
        font-family: "Traditional Arabic", "Simplified Arabic", "Arabic Typesetting", "Tahoma", "Arial Unicode MS", "Segoe UI", "Amiri", "Scheherazade New", "Noto Sans Arabic", Arial, sans-serif !important;
        direction: rtl;
        text-align: right;
        unicode-bidi: embed;
        font-feature-settings: "liga" 1, "kern" 1;
        -webkit-font-feature-settings: "liga" 1, "kern" 1;
    }

    *:lang(ar) {
        font-family: "Traditional Arabic", "Simplified Arabic", "Arabic Typesetting", "Tahoma", "Arial Unicode MS", "Segoe UI", "Amiri", "Scheherazade New", "Noto Sans Arabic", Arial, sans-serif !important;
        direction: rtl;
        text-align: right;
    }

    [lang="ar"] b,
    [lang="ar"] strong {
        font-weight: bold;
    }

    /* ── Layout ────────────────────────────────────────────────── */
    .container {
        width: 100%;
        padding: 0 10mm;
    }

    .row {
        display: table;
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
    }

    .cell {
        display: table-cell;
        padding: 3px 4px;
        vertical-align: top;
        width: 50%;
    }

    .cell-en {
        border-right: 1px solid #555;
        padding-right: 8px;
    }

    .cell-ar {
        text-align: right;
        direction: rtl;
        padding-left: 8px;
        unicode-bidi: embed;
    }

    .section {
        margin-bottom: 5px;
        border: 1px solid #333;
        padding: 4px 6px;
        page-break-inside: auto;
    }

    /* Never split a single bilingual row (article, party block…) across pages;
       the section itself may flow so pages stay full. */
    .section .row {
        page-break-inside: avoid;
    }

    .section-title {
        font-weight: bold;
        font-size: 9.5pt;
        letter-spacing: 0.3px;
        background: #f1f3f5;
        border-left: 3px solid #1b1b1b;
        padding: 2px 6px;
        margin-bottom: 3px;
    }

    .cell-ar .section-title {
        border-left: none;
        border-right: 3px solid #1b1b1b;
    }

    /* ── Document title band ───────────────────────────────────── */
    .doc-title {
        border: 1px solid #333;
        border-bottom: 2px solid #1b1b1b;
        background: #f1f3f5;
        text-align: center;
        padding: 5px 6px;
        margin-bottom: 6px;
    }

    .doc-title-en {
        font-size: 12pt;
        font-weight: bold;
        letter-spacing: 0.5px;
    }

    .doc-title-ar {
        font-size: 11pt;
        font-weight: bold;
        text-align: center !important;
    }

    .doc-subtitle {
        font-size: 9pt;
        margin-top: 2px;
    }

    /* ── Header meta table & QR ────────────────────────────────── */
    .header-table {
        width: 100%;
        border-collapse: collapse;
    }

    .header-table td {
        border: 1px solid #333;
        padding: 3px 5px;
        font-size: 8pt;
    }

    .qr-box {
        flex-shrink: 0;
        border: 1px solid #333;
        padding: 3px;
        text-align: center;
        background: #fff;
    }

    .qr-box img {
        width: 56px;
        height: 56px;
        display: block;
        margin: 0 auto;
    }

    .qr-box small {
        font-size: 7pt;
        display: block;
        margin-top: 1px;
    }

    /* ── Tables ────────────────────────────────────────────────── */
    table {
        border-collapse: collapse;
    }

    tr {
        page-break-inside: avoid;
    }

    thead {
        display: table-header-group;
    }

    .data-table {
        width: 100%;
        border: 2px solid #1b1b1b;
    }

    .data-table th,
    .data-table td {
        border: 1px solid #333;
        padding: 3px 5px;
        font-size: 8.5pt;
    }

    .data-table th {
        font-weight: bold;
        background-color: #f1f3f5;
        text-align: center;
    }

    .data-table .total-row td {
        font-weight: bold;
        background-color: #f1f3f5;
    }

    .avoid-break {
        page-break-inside: avoid;
    }

    /* ── Schedule / appendix headings ──────────────────────────── */
    .sched-head {
        border: 1px solid #333;
        background: #f1f3f5;
        text-align: center;
        padding: 4px 6px;
        margin-bottom: 6px;
        page-break-inside: avoid;
    }

    .sched-head .main {
        font-size: 12pt;
        font-weight: bold;
        letter-spacing: 0.5px;
    }

    .sched-head .sub {
        font-size: 9.5pt;
        font-weight: bold;
        text-decoration: underline;
        margin-top: 1px;
    }

    hr {
        border: none;
        border-top: 1px solid #999;
        margin: 4px 0;
    }

    /* ── Utilities ─────────────────────────────────────────────── */
    .bold { font-weight: bold; }
    .underline { text-decoration: underline; }
    .text-center { text-align: center; }
    .small-text { font-size: 8pt; }
    .normal-text { font-size: 9pt; }
    .large-text { font-size: 10pt; }

    p {
        margin: 1.5px 0;
        line-height: 1.35;
    }

    .page-break {
        page-break-before: always;
    }

    /* ── Repeating page footer (signature strip) ───────────────── */
    /* Sits inside the bottom band reserved by @page margin-bottom and repeats
       on every printed page. Its padding-bottom (set per-mode in the template)
       keeps the signatures clear of the pre-printed bond-paper footer. */
    .page-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        background: transparent;
    }

    .signature-row {
        display: table;
        width: 100%;
        table-layout: fixed;
        padding: 0 20mm;
    }

    .signature-row > div {
        display: table-cell;
        text-align: center;
    }

    .signature-line {
        border-top: 1px solid #1b1b1b;
        width: 65%;
        margin: 0 auto;
    }

    .signature-label {
        font-size: 8pt;
        padding-top: 2px;
    }
</style>
