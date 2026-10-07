<?php

namespace App\Actions\RentOut\Checklist;

use App\Models\RentOut;
use App\Services\CompanyLogoResolver;
use App\Traits\UsesBrowsershot;

/**
 * Render the Unit Handover & Snagging checklist PDF — the document behind the web
 * tab's "Download PDF" and the technician app's PDF button. A read-only Generate*
 * action: returns the PDF bytes and lets a rendering failure throw.
 */
class GeneratePdfAction
{
    use UsesBrowsershot;

    public function execute(int $rentOutId): string
    {
        $rentOut = RentOut::with([
            'account',
            'group',
            'building',
            'property',
            'type',
            'checklistLines.item',
            'checklistSignatures',
            'fixtureAreas.entries',
            'facilityCoordinator',
            'leasingCoordinator',
        ])->findOrFail($rentOutId);

        // Rendered by Chrome rather than dompdf: the handover declaration is rich text
        // edited in Settings and may hold Arabic clauses, which dompdf prints
        // unshaped and back-to-front. Browsershot embeds data URIs, so the logo
        // travels with the HTML.
        $companyLogo = CompanyLogoResolver::dataUri();

        $render = function (int $pages) use ($rentOut, $companyLogo) {
            $html = view('print.rentout.checklist', compact('rentOut', 'companyLogo', 'pages'))->render();

            return $this->makeBrowsershot($html)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->pdf();
        };

        // The acknowledgment has to sit at the foot of the LAST page, which the page
        // body can only be stretched to once the page count is known — so lay it out
        // once to count, then lay it out again at that height. If the taller body
        // spills onto one more page (rounding), the first pass is the honest answer.
        $pdf = $render(1);
        $pages = $this->pageCount($pdf);

        if ($pages > 1) {
            $stretched = $render($pages);
            if ($this->pageCount($stretched) === $pages) {
                $pdf = $stretched;
            }
        }

        return $pdf;
    }

    /** Pages in a rendered PDF, read from the page tree; 1 when it can't be told. */
    private function pageCount(string $pdf): int
    {
        return preg_match('/\/Count (\d+)/', $pdf, $matches) ? max(1, (int) $matches[1]) : 1;
    }
}
