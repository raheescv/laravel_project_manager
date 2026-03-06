<?php

it('uses black and white styles for the day session pdf print view', function (): void {
    $template = file_get_contents(__DIR__.'/../../../resources/views/sale/day-session-print-pdf.blade.php');

    expect($template)->toContain('color: #000;');
    expect($template)->toContain('border: 1px solid #000;');
    expect($template)->toContain('border-left: 4px solid #000;');
    expect($template)->not->toContain('#1d4ed8');
    expect($template)->not->toContain('#2563eb');
    expect($template)->not->toContain('#ea580c');
    expect($template)->not->toContain('#059669');
    expect($template)->not->toContain('#0ea5e9');
});
