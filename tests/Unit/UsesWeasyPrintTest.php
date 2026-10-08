<?php

use App\Traits\UsesWeasyPrint;

uses(Tests\TestCase::class);

/**
 * WeasyPrint renders through tools/weasyprint-pdf.py. The rendering tests need a
 * python with WeasyPrint installed, so they run only where
 * WEASYPRINT_PYTHON_BINARY is set — the same switch that turns it on in the app.
 */
function weasyPrinter(): object
{
    return new class()
    {
        use UsesWeasyPrint;

        public function enabled(): bool
        {
            return $this->weasyPrintEnabled();
        }

        /** @return array{pdf: string, pages: int, footGap: float|null} */
        public function render(string $html): array
        {
            return $this->weasyPrintPdf($html);
        }
    };
}

function skipWithoutWeasyPrint(): void
{
    if (blank(config('weasyprint.python_binary'))) {
        test()->markTestSkipped('WEASYPRINT_PYTHON_BINARY is not set.');
    }
}

it('stays off until a python binary is configured', function (): void {
    config(['weasyprint.python_binary' => null]);
    expect(weasyPrinter()->enabled())->toBeFalse();

    config(['weasyprint.python_binary' => '/usr/bin/python3']);
    expect(weasyPrinter()->enabled())->toBeTrue();
});

it('renders english and arabic text to a pdf and counts its pages', function (): void {
    skipWithoutWeasyPrint();

    $result = weasyPrinter()->render(<<<'HTML'
        <style>@page { size: A4; margin: 10mm; } .page { height: 270mm; }</style>
        <div class="page">Handover checklist</div>
        <p dir="rtl">أقر بأنني استلمت الوحدة بحالة جيدة</p>
        HTML);

    expect($result['pdf'])->toStartWith('%PDF-')
        ->and($result['pages'])->toBe(2)
        ->and($result['footGap'])->toBeNull();
});

it('measures the gap under the foot block so it can be pinned to the page foot', function (): void {
    skipWithoutWeasyPrint();

    $html = fn (float $gap) => '<style>@page { size: A4; margin: 10mm; }</style>'
        .'<p>Inventory</p><div data-pdf-foot style="height: 100px; padding-top: '.$gap.'px; box-sizing: content-box;">Sign here</div>';

    $first = weasyPrinter()->render($html(0));
    $pinned = weasyPrinter()->render($html(floor($first['footGap']) - 1));

    expect($first['footGap'])->toBeGreaterThan(800)
        ->and($pinned['pages'])->toBe(1)
        ->and($pinned['footGap'])->toBeLessThan(2);
});

it('never fetches anything outside the html', function (): void {
    skipWithoutWeasyPrint();

    $server = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($server, false);
    stream_set_blocking($server, false);

    $result = weasyPrinter()->render(
        '<p>Logo</p><img src="http://'.$address.'/logo.png"><link rel="stylesheet" href="http://'.$address.'/x.css">'
    );

    expect($result['pages'])->toBe(1)
        ->and(@stream_socket_accept($server, 0))->toBeFalse();

    fclose($server);
});
