<?php

namespace App\Traits;

use App\Services\CompanyLogoResolver;
use App\Support\EscPosReceipt;
use Illuminate\Http\Response;

/**
 * Lets a receipt print route answer ?format=escpos: the same receipt HTML, sent
 * to a thermal receipt printer as an ESC/POS raster. Use alongside UsesBrowsershot.
 */
trait RendersEscPosReceipts
{
    protected function wantsEscPos(): bool
    {
        return request()->query('format') === 'escpos';
    }

    protected function escPosResponse(string $html, float $paperMm = 80): Response
    {
        $request = request();

        // Chrome renders offline here, so the logo travels inside the HTML, and the
        // page's own margin would otherwise shift the receipt off the paper.
        $logo = tenant_cache('logo');
        $logoData = is_string($logo) && $logo !== '' ? CompanyLogoResolver::dataUri() : null;
        if ($logoData) {
            $html = str_replace([e($logo), $logo], $logoData, $html);
        }
        $html = str_replace('</head>', '<style>html, body { margin: 0 !important; }</style></head>', $html);

        $png = $this->makeBrowsershot($html)
            ->windowSize((int) ceil($paperMm / 25.4 * 96), 200)
            ->deviceScaleFactor(EscPosReceipt::RENDER_SCALE)
            ->fullPage()
            ->screenshot();

        $width = $request->integer('width', EscPosReceipt::WIDTHS[0]);
        $escPos = EscPosReceipt::fromScreenshot($png, in_array($width, EscPosReceipt::WIDTHS, true) ? $width : EscPosReceipt::WIDTHS[0], [
            'cut' => $request->boolean('cut', true),
            'drawer' => $request->boolean('drawer'),
        ]);

        return response($escPos)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Cache-Control', 'no-store');
    }
}
