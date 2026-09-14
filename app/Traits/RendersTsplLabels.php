<?php

namespace App\Traits;

use App\Support\TsplLabel;
use Illuminate\Http\Response;

/**
 * Lets a barcode print route answer ?format=tspl: the same label HTML, sent to a
 * TSC printer as TSPL bitmaps instead of a PDF. Use alongside UsesBrowsershot.
 */
trait RendersTsplLabels
{
    protected function wantsTspl(): bool
    {
        return request()->query('format') === 'tspl';
    }

    protected function tsplResponse(string $html, array $settings): Response
    {
        $width = (float) $settings['width'];
        $height = (float) $settings['height'];

        // The label views clip overflow to fit one PDF page; the screenshot needs the whole
        // stack. The views mark the element holding the labels `label-stack`, and each label
        // gets a whole-pixel row in it, so the second label onwards renders like the first.
        $row = TsplLabel::rowPixels($height);
        $html = str_replace('</head>', '<style>html, body { overflow: visible !important; } .label-stack { display: grid !important; grid-auto-rows: '.$row.'px; align-items: start; justify-items: start; }</style></head>', $html);

        $png = $this->makeBrowsershot($html)
            ->windowSize((int) ceil(TsplLabel::cssPixels($width)), $row)
            ->deviceScaleFactor(TsplLabel::RENDER_SCALE)
            ->fullPage()
            ->screenshot();

        // Position, gap and flip belong to the label stock, so they are template
        // settings; colour polarity belongs to the printer, so the PC sends it.
        $print = $settings['print'] ?? [];
        $tspl = TsplLabel::fromScreenshot($png, $width, $height, [
            'offset_x' => (float) ($print['offset_x'] ?? 0),
            'offset_y' => (float) ($print['offset_y'] ?? 0),
            'gap' => $print['gap'] ?? null,
            'flip' => (bool) ($print['flip'] ?? false),
            'invert' => request()->boolean('invert'),
        ]);

        return response($tspl)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Cache-Control', 'no-store');
    }
}
