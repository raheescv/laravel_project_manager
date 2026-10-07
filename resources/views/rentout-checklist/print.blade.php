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

        @media (max-width: 768px) {
            body {
                padding: .5rem;
            }

            .ck-sheet {
                padding: 12px;
                border-radius: 8px;
            }

            .ck-sheet .ck-doc {
                zoom: 1;
                overflow-x: auto;
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
