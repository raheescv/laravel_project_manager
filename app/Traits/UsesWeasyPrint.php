<?php

namespace App\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * PDFs through WeasyPrint (tools/weasyprint-pdf.py): no browser to launch, and
 * Pango/HarfBuzz shape Arabic and order RTL text the way Chrome does. Page size
 * and margins come from the document's own `@page` rule.
 */
trait UsesWeasyPrint
{
    private function weasyPrintEnabled(): bool
    {
        return filled(config('weasyprint.python_binary'));
    }

    /**
     * An element marked data-pdf-foot is pinned to the foot of the page it ends on
     * (see tools/weasyprint-pdf.py); footShift is how far it was moved down, in px.
     *
     * @return array{pdf: string, pages: int, footShift: float}
     */
    private function weasyPrintPdf(string $html): array
    {
        $output = sys_get_temp_dir().'/weasyprint-'.Str::uuid().'.pdf';

        // Fontconfig keeps its font cache under $XDG_CACHE_HOME (default ~/.cache).
        // The web user's home is often not writable (/var/www), and then every
        // render rescans the installed fonts — seconds with a large Noto set.
        $cache = storage_path('framework/cache/weasyprint');
        File::ensureDirectoryExists($cache);

        $process = new Process([
            config('weasyprint.python_binary'),
            base_path('tools/weasyprint-pdf.py'),
            $output,
        ], base_path(), ['XDG_CACHE_HOME' => $cache]);

        $process->setInput($html);
        $process->setTimeout((float) config('weasyprint.timeout', 60));

        try {
            $process->run();

            if (! $process->isSuccessful() || ! is_file($output)) {
                throw new RuntimeException('WeasyPrint failed: '.trim($process->getErrorOutput()));
            }

            $layout = json_decode($process->getOutput(), true) ?: [];

            return [
                'pdf' => (string) file_get_contents($output),
                'pages' => max(1, (int) ($layout['pages'] ?? 1)),
                'footShift' => (float) ($layout['foot_shift'] ?? 0),
            ];
        } finally {
            @unlink($output);
        }
    }
}
