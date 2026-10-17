{{-- The hand-over checklist on screen, where the parties sign. It renders the SAME
     document as the PDF (print.rentout.partials.checklist-document) — the web
     "Download PDF" and the technician app's PDF are that document too — with the
     unsigned signature boxes turned into signature pads. --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unit Handover & Snagging</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/font-awesome/font-awesome.min.css') }}">
    <script src="{{ asset('assets/js/signature_pdf.js') }}"></script>
    @include('print.rentout.partials.checklist-styles')
    <style>
        body {
            background-color: #f0f2f5;
            padding: 2rem 1rem;
        }

        /* An A4 sheet on the desk: the document is laid out at the PDF's 10px scale,
           so the sheet is sized like the page and the type scaled up for reading. */
        .ck-sheet {
            background: #ffffff;
            max-width: 1080px;
            margin: 0 auto;
            padding: 28px 32px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
        }

        .ck-sheet .ck-doc {
            zoom: 1.25;
        }

        .ck-toolbar {
            max-width: 1080px;
            margin: 0 auto 12px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        /* The pad component carries its own name header; in the document the ruled
           line under the box already names the signatory. */
        .sign-cell .sigpad-block > .section-title {
            display: none;
        }

        .sign-cell .sigpad-block {
            text-align: left;
            font-size: 12px;
        }

        .ck-doc img.zoomable {
            cursor: zoom-in;
        }

        /* Tablet and phone: the A4 grid would only scroll sideways, so the document
           reflows instead — each inventory row and fixture entry becomes a card with
           its photo, name and labelled values. Screen-only and in this file alone: the
           PDF renders the same partial at A4 width and must keep the grid. zoom is
           dropped here so the signature pads map touches 1:1. */
        @media screen and (max-width: 991.98px) {
            body {
                padding: .75rem;
            }

            .ck-sheet {
                padding: 14px;
                border-radius: 10px;
            }

            .ck-sheet .ck-doc {
                zoom: 1;
                font-size: 13px;
            }

            .ck-doc .title-band .t {
                font-size: 16px;
            }

            .ck-doc .title-band .s {
                font-size: 11px;
            }

            .ck-doc .sec,
            .ck-doc .sec-split td {
                font-size: 12px;
                padding: 7px 10px;
                margin: 18px 0 8px;
            }

            .ck-doc .meta td,
            .ck-doc .decl,
            .ck-doc .terms td {
                font-size: 13px;
            }

            .ck-doc .meta td {
                padding: 7px 10px;
            }

            .ck-doc .accept .ph {
                font-size: 13px;
            }

            .ck-doc .sign-line,
            .ck-doc .sign-name {
                font-size: 12px;
            }

            /* Inventory: one card per item */
            .ck-doc .items,
            .ck-doc .items > tbody,
            .ck-doc .items > tbody > tr.fx > td,
            .ck-doc .items > tbody > tr.cat > td {
                display: block;
            }

            .ck-doc .items > thead {
                display: none;
            }

            .ck-doc .items > tbody > tr {
                display: grid;
                grid-template-columns: 68px minmax(0, 1fr);
                gap: 5px 12px;
                margin-bottom: 8px;
                padding: 10px 12px;
                border: 1px solid #e4e0d2;
                border-radius: 10px;
                background: #fff;
            }

            .ck-doc .items > tbody > tr > td {
                grid-column: 2;
                display: flex;
                gap: 10px;
                align-items: baseline;
                padding: 0;
                border: 0;
                background: transparent;
                font-size: 13px;
                text-align: start;
            }

            .ck-doc .items > tbody > tr:nth-child(even) > td {
                background: transparent;
            }

            .ck-doc .items > tbody > tr > td[colspan] {
                grid-column: 1 / -1;
            }

            .ck-doc .items > tbody > tr > td[data-label]::before {
                content: attr(data-label);
                flex: none;
                width: 104px;
                font-size: 10.5px;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: #8a8060;
            }

            .ck-doc .items > tbody > tr > td[data-label]:empty,
            .ck-doc .items > tbody > tr > td.it-sn {
                display: none;
            }

            .ck-doc .items > tbody > tr > td.it-name {
                grid-row: 1;
                font-size: 14px;
                font-weight: bold;
            }

            .ck-doc .items > tbody > tr > td.it-img {
                grid-column: 1;
                grid-row: 1 / span 3;
                display: block;
            }

            .ck-doc .items > tbody > tr > td.it-img img {
                width: 68px !important;
                height: 68px !important;
                border-radius: 8px;
                border: 1px solid #e4e0d2;
            }

            .ck-doc .items .mo-img {
                display: inline-block;
                width: 44px;
                height: 44px;
                margin: 0 0 0 8px;
                vertical-align: middle;
            }

            .ck-doc .items .ok,
            .ck-doc .items .no {
                font-size: 15px;
            }

            .ck-doc .items > tbody > tr.cat,
            .ck-doc .items > tbody > tr.fx {
                display: block;
                padding: 0;
                border: 0;
                background: none;
            }

            .ck-doc .items > tbody > tr.cat > td {
                margin-top: 14px;
                padding: 7px 10px;
                border-radius: 6px;
                background: #f3efe2;
                font-size: 12px;
            }

            .ck-doc .items > tbody > tr.total {
                display: flex;
                justify-content: space-between;
                background: #efe9d6;
            }

            .ck-doc .items > tbody > tr.total > td {
                font-size: 14px;
            }

            /* Fixture comments: one card per entry */
            .ck-doc .fx-table,
            .ck-doc .fx-table tbody {
                display: block;
            }

            .ck-doc .fx-table {
                overflow: hidden;
                margin-bottom: 10px;
                border: 1px solid #e4e0d2;
                border-radius: 10px;
            }

            .ck-doc .fx-table tr {
                display: block;
                padding: 8px 12px;
                border-top: 1px solid #eee9da;
            }

            .ck-doc .fx-table td {
                display: block;
                padding: 0;
                border: 0;
                background: transparent;
                font-size: 13px;
            }

            .ck-doc .fx-table tr.fx-head td,
            .ck-doc .fx-table tr.fx-sign td {
                font-size: 11px;
            }

            .ck-doc .fx-table tr.fx-cols,
            .ck-doc .fx-table td.fx-n {
                display: none;
            }

            .ck-doc .fx-table tr.fx-entry {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px 12px;
                background: #fdfcf8;
            }

            .ck-doc .fx-table td.fx-cmt {
                grid-column: 1 / -1;
            }

            .ck-doc .fx-table td[data-label]::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 3px;
                font-size: 10.5px;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: #8a8060;
                text-align: start;
            }

            .ck-doc .fx-table tr.fx-entry > td {
                text-align: start;
            }

            .ck-doc .fx-img {
                width: 100%;
                max-width: 280px;
                margin: 0;
                height: auto;
                aspect-ratio: 4 / 3;
                border-radius: 8px;
            }

            .ck-doc .fx-table tr.fx-sign {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
            }

            .ck-doc .fx-sig-img {
                max-height: 48px;
            }

            .ck-doc .fx-sig-line {
                font-size: 11px;
            }

            .ck-doc .sign-img {
                height: 64px;
            }
        }

        /* Phone: property details, signatures and the bilingual terms stack. */
        @media screen and (max-width: 767.98px) {
            .ck-doc .title-band .tb-logo {
                width: 64px;
            }

            .ck-doc .logo-box img {
                max-width: 56px;
            }

            .ck-doc .meta,
            .ck-doc .meta tbody {
                display: block;
                border: 1px solid #d8d4c4;
                border-bottom: 0;
            }

            .ck-doc .meta tr {
                display: grid;
                grid-template-columns: minmax(110px, 40%) minmax(0, 1fr);
            }

            .ck-doc .meta td {
                width: auto;
                border: 0;
                border-bottom: 1px solid #d8d4c4;
            }

            .ck-doc .meta td:empty {
                display: none;
            }

            .ck-doc .sign-table,
            .ck-doc .sign-table tbody,
            .ck-doc .sign-table tr,
            .ck-doc .terms,
            .ck-doc .terms tbody,
            .ck-doc .terms tr,
            .ck-doc .sec-split,
            .ck-doc .sec-split tbody,
            .ck-doc .sec-split tr {
                display: block;
            }

            .ck-doc .sign-cell {
                display: block;
                width: 100%;
                margin-bottom: 16px;
            }

            .ck-doc .terms td,
            .ck-doc .sec-split td {
                display: block;
                width: 100% !important;
            }
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }

            .ck-sheet {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
                padding: 0;
            }

            .ck-sheet .ck-doc {
                zoom: 1;
            }

            .no-print,
            .fx-pad,
            .sigpad-block,
            [class^="sigpad-container-"],
            [class^="sigpad-actions-"] {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    @php
        $imageSrc = function (?string $relative) {
            if (! $relative) {
                return null;
            }
            $relative = ltrim(preg_replace('#^public/#', '', $relative), '/');

            return is_file(public_path('storage/' . $relative)) ? asset('storage/' . $relative) : null;
        };
    @endphp

    <div class="ck-toolbar no-print">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
        <a class="btn btn-primary btn-sm" href="{{ route('print::rentout::checklist', $rentOut->id) }}" target="_blank"><i class="fa fa-download me-1"></i> Download PDF</a>
    </div>

    <div class="ck-sheet">
        @include('print.rentout.partials.checklist-document', [
            'rentOut' => $rentOut,
            'imageSrc' => $imageSrc,
            'companyLogo' => \App\Services\CompanyLogoResolver::dataUri(),
            'interactive' => true,
        ])
    </div>

    {{-- Self-contained image lightbox (no Bootstrap JS dependency on this standalone page) --}}
    <div id="imgZoomOverlay" class="no-print"
        style="display:none; position:fixed; inset:0; z-index:1080; background:rgba(0,0,0,.85);
               align-items:center; justify-content:center; cursor:zoom-out;">
        <span id="imgZoomClose" style="position:absolute; top:18px; right:26px; color:#fff; font-size:34px; line-height:1; cursor:pointer;"
            aria-label="Close">&times;</span>
        <img id="imgZoomTarget" src="" alt="Preview"
            style="max-width:92vw; max-height:88vh; object-fit:contain; border-radius:8px; box-shadow:0 12px 40px rgba(0,0,0,.5);">
    </div>
    <script>
        (function () {
            var overlay = document.getElementById('imgZoomOverlay');
            var target = document.getElementById('imgZoomTarget');
            if (!overlay || !target) return;

            document.addEventListener('click', function (e) {
                var img = e.target.closest && e.target.closest('img.zoomable');
                if (img) {
                    target.src = img.getAttribute('data-img') || img.src;
                    overlay.style.display = 'flex';
                    return;
                }
                if (e.target === overlay || e.target.id === 'imgZoomClose') {
                    overlay.style.display = 'none';
                    target.src = '';
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && overlay.style.display === 'flex') {
                    overlay.style.display = 'none';
                    target.src = '';
                }
            });
        })();
    </script>
</body>

</html>
