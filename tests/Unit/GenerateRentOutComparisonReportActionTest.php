<?php

use App\Actions\RentOut\Comparison\GenerateRentOutComparisonReportAction;
use Illuminate\Filesystem\Filesystem;

it('escapes values and writes reference links without external dependencies', function () {
    $filesystem = new Filesystem();
    $temporaryRoot = sys_get_temp_dir().'/rentout-comparison-test-'.bin2hex(random_bytes(4));
    $output = $temporaryRoot.'/report';
    $comparison = comparisonReportFixture();

    $paths = (new GenerateRentOutComparisonReportAction($filesystem))->execute($comparison, $output);

    $record = file_get_contents($paths['index']);
    $detail = file_get_contents($output.'/records/1887.html');
    $records = file_get_contents($paths['records']);
    $differences = file_get_contents($paths['differences']);
    $javascript = file_get_contents($paths['javascript']);

    expect($record)
        ->toContain('php artisan property:compare-rentout-migration')
        ->not->toContain('cdn.');
    expect($detail)
        ->toContain('target="_blank" rel="noopener noreferrer"')
        ->toContain('id="verifyButton"')
        ->toContain('Mark as verified')
        ->toContain('<script src="../app.js" defer></script>')
        ->toContain('https://accounts.test/Property/lease/view/1887')
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->not->toContain('<script>alert(1)</script>');
    expect($records)
        ->toContain('id="verificationFilter"')
        ->toContain('verified-badge')
        ->toContain('list-verify-button')
        ->toContain('<script src="../app.js" defer></script>');
    expect($differences)
        ->toContain('<option value="unverified" selected>Not verified</option>')
        ->toContain('.record[hidden]{display:none!important}')
        ->toContain('<script src="app.js" defer></script>')
        ->toContain('return=..%2Fdifferences.html');
    expect($javascript)
        ->toContain("incoming.get('verify')")
        ->toContain('let verified=getVerified()')
        ->toContain('if(verified[id])delete verified[id]')
        ->toContain('localStorage.setItem(verificationKey');

    $filesystem->deleteDirectory($temporaryRoot);
});

function comparisonReportFixture(): array
{
    return [
        'meta' => [
            'generated_at' => '2026-07-26T12:00:00+05:30',
            'old_connection' => 'mysql2',
            'new_connection' => 'mysql',
            'type' => null,
            'selected_ids' => [1887],
            'version' => 1,
        ],
        'summary' => [
            'total' => 1,
            'matching' => 0,
            'differing' => 1,
            'missing' => 0,
            'extra' => 0,
            'match_percentage' => 0.0,
            'by_category' => ['Lease/sale agreement' => 1],
        ],
        'records' => [
            1887 => [
                'id' => 1887,
                'exists_old' => true,
                'exists_new' => true,
                'agreement_type' => 'lease',
                'status' => 'completed',
                'is_booking' => false,
                'category' => 'Lease/sale agreement',
                'old_url' => 'https://accounts.test/Property/lease/view/1887',
                'new_url' => 'https://project_manager.test/property/sale/view/1887',
                'header' => [
                    'Remark' => [
                        'old' => '<script>alert(1)</script>',
                        'new' => 'safe',
                        'matches' => false,
                    ],
                ],
                'tabs' => [
                    'Documents' => [
                        'available' => true,
                        'old_count' => 0,
                        'new_count' => 0,
                        'difference_count' => 0,
                        'rows' => [],
                    ],
                ],
                'ledger' => [
                    'old' => ['rows' => 1, 'debit' => 10.0, 'credit' => 0.0],
                    'new' => ['rows' => 0, 'debit' => 0.0, 'credit' => 0.0],
                    'debit_difference' => -10.0,
                    'credit_difference' => 0.0,
                    'matches' => false,
                ],
                'difference_count' => 2,
                'matches' => false,
            ],
        ],
    ];
}
