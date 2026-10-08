@php
    // Warranty / handover clauses written on this booking's Checklist tab — an
    // annex printed on its own page after the signatures. Empty ([]) on a rental,
    // and on any booking that has none. Resolved up here because the page-body
    // height in <style> below depends on it.
    $terms = \App\Support\RentOutHandoverTerms::forPrint($rentOut);
    // The stretched body has to cover every page EXCEPT the clause page, otherwise
    // it runs a page long and GeneratePdfAction throws the stretched pass away.
    // Clauses longer than one page do exactly that, and the honest unstretched
    // first pass is printed instead: correct, just without the acknowledgment
    // glued to the foot of its page.
    $bodyPages = max(1, (int) ($pages ?? 1) - (empty($terms) ? 0 : 1));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unit Handover & Snagging</title>
    @include('print.rentout.partials.checklist-styles')
    <style>
        /* WeasyPrint takes the paper from here; Browsershot passes the same A4 and
           10mm margins as options. */
        @page { size: A4; margin: 10mm; }
        body { margin: 0; }
        /* The acknowledgment signs off the document, so it sits at the FOOT of the last
           page rather than trailing the inventory table. Chrome has no "footer on the
           last page" rule, so the page body is stretched to a whole number of pages
           (counted by GeneratePdfAction from a first pass) and the block is pushed down
           by the auto margin.
           277mm = A4's 297mm less the 10mm top and bottom margins the PDF prints
           with — vh can't be used here, it measures the whole page, margins included,
           and would spill onto an extra page. 2mm is shaved off so rounding can't do
           the same. */
        @if (isset($footGap))
        /* WeasyPrint measures instead: a flex column stretched over several pages
           loses content there, so the block is pushed down by the gap its first
           layout left under it (GeneratePdfAction passes $footGap). The group is
           kept whole so the gap is measured on the page it will be pinned to.
           Padding rather than margin: a margin collapses into the one above it and
           is dropped when the block opens a page. */
        .ck-doc .wrap { display: block; }
        .ck-doc .accept-group { padding-top: {{ $footGap }}px; break-inside: avoid; }
        @else
        .ck-doc .wrap { min-height: calc({{ $bodyPages }} * 277mm - 2mm); }
        @endif
    </style>
</head>
<body>
@php
    // Browsershot renders the page from a temp file, so images have to travel with
    // it — every stored path is inlined as a data URI.
    $dataUri = function (?string $relative) {
        if (! $relative) {
            return null;
        }
        $path = public_path('storage/' . ltrim(preg_replace('#^public/#', '', $relative), '/'));
        if (! is_file($path)) {
            return null;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'png';

        return 'data:image/' . ($ext === 'svg' ? 'svg+xml' : $ext) . ';base64,' . base64_encode(file_get_contents($path));
    };
@endphp
@include('print.rentout.partials.checklist-document', ['imageSrc' => $dataUri, 'interactive' => false, 'terms' => $terms])
</body>
</html>
