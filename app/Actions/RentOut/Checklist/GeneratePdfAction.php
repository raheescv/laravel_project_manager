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
     * The same two passes, measured rather than stretched: the first layout reports
     * the gap left under the acknowledgment, and the second pushes it down by that
     * much. A pass that gains a page (rounding) loses to the first, as above.
     */
    private function renderWithWeasyPrint(RentOut $rentOut, ?string $companyLogo): string
    {
        $render = fn (float $footGap) => $this->weasyPrintPdf(
            view('print.rentout.checklist', ['rentOut' => $rentOut, 'companyLogo' => $companyLogo, 'footGap' => $footGap])->render()
        );

        $first = $render(0);

        if (($first['footGap'] ?? 0) < 1) {
            return $first['pdf'];
        }

        $pinned = $render(floor($first['footGap']) - 1);

        return $pinned['pages'] === $first['pages'] ? $pinned['pdf'] : $first['pdf'];
    }

    /** Pages in a rendered PDF, read from the page tree; 1 when it can't be told. */
    private function pageCount(string $pdf): int
    {
        return preg_match('/\/Count (\d+)/', $pdf, $matches) ? max(1, (int) $matches[1]) : 1;
    }
}
