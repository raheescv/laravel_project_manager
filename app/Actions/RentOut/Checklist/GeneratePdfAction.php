<?php

namespace App\Actions\RentOut\Checklist;

use App\Models\RentOut;
use App\Services\CompanyLogoResolver;
use App\Traits\UsesBrowsershot;
use App\Traits\UsesWeasyPrint;

/**
 * Render the Unit Handover & Snagging checklist PDF — the document behind the web
 * tab's "Download PDF" and the technician app's PDF button. A read-only Generate*
 * action: returns the PDF bytes and lets a rendering failure throw.
 */
class GeneratePdfAction
{
    use UsesBrowsershot;
    use UsesWeasyPrint;

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

        // Rendered by WeasyPrint when the server has it (no browser to launch), by
        // Chrome otherwise — never dompdf: the handover declaration is rich text
        // edited in Settings and may hold Arabic clauses, which dompdf prints
        // unshaped and back-to-front. Both renderers take data URIs only, so the
        // logo travels with the HTML.
        $companyLogo = CompanyLogoResolver::dataUri();

        if ($this->weasyPrintEnabled()) {
            return $this->renderWithWeasyPrint($rentOut, $companyLogo);
        }

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

    /**
     * The same idea, measured rather than stretched: tools/weasyprint-pdf.py lays the
     * document out, measures the gap under the acknowledgment and lays it out again
     * with the block pushed down by that much — both passes in one process.
     */
    private function renderWithWeasyPrint(RentOut $rentOut, ?string $companyLogo): string
    {
        $html = view('print.rentout.checklist', ['rentOut' => $rentOut, 'companyLogo' => $companyLogo, 'weasyPrint' => true])->render();

        return $this->weasyPrintPdf($html)['pdf'];
    }

    /** Pages in a rendered PDF, read from the page tree; 1 when it can't be told. */
    private function pageCount(string $pdf): int
    {
        return preg_match('/\/Count (\d+)/', $pdf, $matches) ? max(1, (int) $matches[1]) : 1;
    }
}
