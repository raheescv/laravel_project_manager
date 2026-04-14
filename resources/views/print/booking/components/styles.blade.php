<style type="text/css">
    /* Arabic Font Support */
    @import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Scheherazade+New:wght@400;700&family=Noto+Sans+Arabic:wght@400;700&display=swap');

    /* Professional Contract Styling */
    * {
        margin: 0;
        padding: 0;
        text-indent: 0;
        box-sizing: border-box;
    }

    /* Arabic Text Styling */
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

    body {
        font-family: "Times New Roman", serif;
        line-height: 1.4;
        color: #2c3e50;
        background: #ffffff;
        font-size: 11pt;
    }

    /* Enhanced Arabic support */
    *:lang(ar) {
        font-family: "Traditional Arabic", "Simplified Arabic", "Arabic Typesetting", "Tahoma", "Arial Unicode MS", "Segoe UI", "Amiri", "Scheherazade New", "Noto Sans Arabic", Arial, sans-serif !important;
        direction: rtl;
        text-align: right;
    }

    /* Professional Typography */
    .text-primary { color: #2c3e50; font-family: "Times New Roman", serif; font-size: 11pt; line-height: 1.4; }
    .text-secondary { color: #2c3e50; font-family: "Times New Roman", serif; font-weight: bold; font-size: 11pt; line-height: 1.4; }
    .text-bold { color: #2c3e50; font-family: "Times New Roman", serif; font-weight: bold; font-size: 11pt; line-height: 1.4; }
    .text-underline { color: #2c3e50; font-family: "Times New Roman", serif; font-weight: bold; text-decoration: underline; font-size: 11pt; line-height: 1.4; }
    .text-normal { color: #2c3e50; font-family: "Times New Roman", serif; font-size: 11pt; line-height: 1.4; }
    .text-small { color: #2c3e50; font-family: "Times New Roman", serif; font-size: 9pt; line-height: 1.3; }
    .text-large { color: #2c3e50; font-family: "Times New Roman", serif; font-weight: bold; font-size: 13pt; line-height: 1.4; }

    /* Professional Layout Classes */
    .ml-10 { margin-left: 10px; }
    .ml-24 { margin-left: 24px; }
    .pt-1 { padding-top: 1px; }
    .pt-2 { padding-top: 2px; }
    .pt-3 { padding-top: 3px; }
    .pt-4 { padding-top: 4px; }
    .pt-5 { padding-top: 5px; }
    .pt-8 { padding-top: 8px; }
    .pl-5 { padding-left: 5px; }
    .pl-6 { padding-left: 6px; }
    .pl-8 { padding-left: 8px; }
    .pl-13 { padding-left: 13px; }
    .pl-20 { padding-left: 20px; }
    .pl-24 { padding-left: 24px; }
    .pr-4 { padding-right: 4px; }
    .pr-24 { padding-right: 24px; }

    /* Professional Table Styling */
    .border-all { border: 1px solid #34495e; border-collapse: collapse; }

    /* Document Styling */
    .section-title {
        background: #ecf0f1;
        color: #2c3e50;
        padding: 5px 2px;
        margin: 5px 0 5px 0;
        border-left: 4px solid #3498db;
        font-weight: bold;
        font-size: 12pt;
    }

    .contract-box {
        border: 2px solid #bdc3c7;
        border-radius: 5px;
        padding: 15px;
        margin: 10px 0;
        background: #f8f9fa;
    }

    .stamp-area {
        border: 1px dashed #7f8c8d;
        padding: 20px;
        text-align: center;
        margin: 15px 0;
        background: #f8f9fa;
    }

    /* Print Styling */
    @media print {
        body { font-size: 10pt; line-height: 1.3; }
        .section-title { background: #ecf0f1 !important; color: #2c3e50 !important; }
    }

    /* A4 page size specifications */
    @page { size: A4; margin: 0; }

    body {
        margin: 0;
        padding: 0;
        font-family: "Times New Roman", serif;
        font-size: 9pt;
        line-height: 1.3;
        color: #2c3e50;
        width: 210mm;
        min-height: 297mm;
    }

    .container {
        width: 100%;
        padding: 10mm;
        box-sizing: border-box;
        margin-bottom: 150px;
    }

    .row {
        display: table;
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
    }

    .cell {
        display: table-cell;
        padding: 4px;
        vertical-align: top;
        width: 50%;
    }

    .cell-en {
        border-right: 1px solid #000;
        padding-right: 8px;
    }

    .cell-ar {
        text-align: right;
        direction: rtl;
        font-family: "Traditional Arabic", "Simplified Arabic", "Arabic Typesetting", "Tahoma", "Arial Unicode MS", "Segoe UI", "Amiri", "Scheherazade New", "Noto Sans Arabic", Arial, sans-serif;
        padding-left: 8px;
        font-size: 9pt;
        unicode-bidi: embed;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    .header-table td {
        border: 1px solid #000;
        padding: 4px;
        font-size: 8pt;
    }

    .section {
        margin-bottom: 8px;
        border: 1px solid #000;
        padding: 6px;
    }

    .section-title {
        font-weight: bold;
        margin-bottom: 6px;
        font-size: 10pt;
    }

    .title-text { font-size: 9pt; font-weight: bold; margin-bottom: 4px; }
    .subtitle-text { font-size: 9pt; margin-bottom: 4px; }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin: 6px 0;
    }

    .data-table th,
    .data-table td {
        border: 1px solid #000;
        padding: 4px;
        text-align: center;
        font-size: 8pt;
    }

    .data-table th { font-weight: bold; background-color: #f8f9fa; }

    .stamp-box {
        display: inline-block;
        border: 2px dashed #000;
        padding: 12px;
        font-size: 9pt;
    }

    /* Utility classes */
    .bold { font-weight: bold; }
    .underline { text-decoration: underline; }
    .text-center { text-align: center; }
    .small-text { font-size: 8pt; }
    .normal-text { font-size: 9pt; }
    .large-text { font-size: 10pt; }

    p { margin: 2px 0; line-height: 1.3; }

    @media print {
        body { width: 210mm; height: 297mm; margin: 0; padding: 0; }
        .container { width: 100%; margin: 0mm; }
        .page-break { page-break-before: always; }
    }

    .page-footer {
        position: fixed;
        bottom: 0;
        width: 100%;
        padding-bottom: 20px;
        background: white;
    }

    .signature-row {
        display: flex;
        justify-content: space-between;
        margin: 0 50px;
    }

    .signature-label { font-size: 12px; margin-bottom: 10px; }

    /* Arabic text improvements */
    .section-title[lang="ar"], p[lang="ar"], div[lang="ar"], span[lang="ar"] {
        font-family: "Traditional Arabic", "Simplified Arabic", "Arabic Typesetting", "Tahoma", "Arial Unicode MS", "Segoe UI", "Amiri", "Scheherazade New", "Noto Sans Arabic", Arial, sans-serif !important;
        font-weight: normal;
        line-height: 1.5;
    }

    [lang="ar"] b, [lang="ar"] strong {
        font-family: "Traditional Arabic", "Simplified Arabic", "Arabic Typesetting", "Tahoma", "Arial Unicode MS", "Segoe UI", "Amiri", "Scheherazade New", "Noto Sans Arabic", Arial, sans-serif !important;
        font-weight: bold;
    }
</style>
