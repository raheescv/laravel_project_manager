<?php

use App\Livewire\Log\Jobs;
use Carbon\CarbonImmutable;

function makeJobRow(array $overrides = []): stdClass
{
    $now = CarbonImmutable::now();
    $row = new stdClass();
    $row->id = $overrides['id'] ?? 42;
    $row->queue = $overrides['queue'] ?? 'default';
    $row->attempts = $overrides['attempts'] ?? 1;
    $row->reserved_at = $overrides['reserved_at'] ?? null;
    $row->available_at = $overrides['available_at'] ?? $now->subMinute()->timestamp;
    $row->created_at = $overrides['created_at'] ?? $now->subMinutes(2)->timestamp;
    $row->payload = $overrides['payload'] ?? null;

    return $row;
}

it('builds structured details with a pretty-printed payload for a decoded job', function (): void {
    $payload = [
        'displayName' => 'App\\Jobs\\SendInvoice',
        'uuid' => 'a1b2c3d4',
        'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
        'data' => [
            'commandName' => 'App\\Jobs\\SendInvoice',
            'command' => 'serialized-payload',
        ],
        'maxTries' => 5,
        'maxExceptions' => 2,
        'timeout' => 90,
        'backoff' => [10, 30, 60],
        'retryUntil' => '2030-01-01 00:00:00',
        'tags' => ['invoice', 'customer:17'],
    ];

    $row = makeJobRow([
        'id' => 7,
        'queue' => 'billing',
        'attempts' => 2,
        'payload' => json_encode($payload),
    ]);

    $details = (new Jobs())->payloadDetails($row);

    expect($details['id'])->toBe(7)
        ->and($details['jobName'])->toBe('SendInvoice')
        ->and($details['jobClass'])->toBe('App\\Jobs\\SendInvoice')
        ->and($details['jobClassShort'])->toBe('SendInvoice')
        ->and($details['displayName'])->toBe('App\\Jobs\\SendInvoice')
        ->and($details['uuid'])->toBe('a1b2c3d4')
        ->and($details['queue'])->toBe('billing')
        ->and($details['attempts'])->toBe(2)
        ->and($details['status'])->toBe('Retrying')
        ->and($details['statusBadge'])->toBe('bg-info text-dark')
        ->and($details['maxTries'])->toBe('5')
        ->and($details['maxExceptions'])->toBe('2')
        ->and($details['timeout'])->toBe('90')
        ->and($details['backoff'])->toBe('[10,30,60]')
        ->and($details['retryUntil'])->toBe('2030-01-01 00:00:00')
        ->and($details['tags'])->toBe(['invoice', 'customer:17'])
        ->and($details['pretty'])->toContain("\n")
        ->and($details['pretty'])->toContain('"displayName": "App\\\\Jobs\\\\SendInvoice"');
});

it('falls back to defaults when the payload is missing or invalid', function (): void {
    $details = (new Jobs())->payloadDetails(makeJobRow(['payload' => 'not-json']));

    expect($details['jobName'])->toBe('Unknown Job')
        ->and($details['jobClass'])->toBeNull()
        ->and($details['displayName'])->toBeNull()
        ->and($details['uuid'])->toBeNull()
        ->and($details['maxTries'])->toBeNull()
        ->and($details['timeout'])->toBeNull()
        ->and($details['tags'])->toBe([])
        ->and($details['pretty'])->toBe('not-json')
        ->and($details['status'])->toBe('Queued');
});

it('reports a processing status when the job has been reserved', function (): void {
    $details = (new Jobs())->payloadDetails(makeJobRow([
        'reserved_at' => now()->timestamp,
        'payload' => json_encode(['displayName' => 'App\\Jobs\\ProcessImport']),
    ]));

    expect($details['status'])->toBe('Processing')
        ->and($details['statusBadge'])->toBe('bg-primary')
        ->and($details['reservedAt'])->not->toBeNull();
});

it('returns a null summary when the serialized command cannot be parsed', function (): void {
    $payload = [
        'displayName' => 'App\\Jobs\\SendInvoice',
        'data' => [
            'commandName' => 'App\\Jobs\\SendInvoice',
            'command' => 'not a serialized string',
        ],
    ];

    $details = (new Jobs())->payloadDetails(makeJobRow([
        'payload' => json_encode($payload),
    ]));

    expect($details['summary'])->toBeNull();
});

function sValue(mixed $value): string
{
    if (is_string($value) && (str_starts_with($value, 'O:') || str_starts_with($value, 'a:') || str_starts_with($value, 'N;'))) {
        return $value;
    }

    if (is_array($value)) {
        return sArr($value);
    }

    return serialize($value);
}

function sArr(array $items): string
{
    $body = '';
    foreach ($items as $key => $value) {
        $body .= is_int($key) ? 'i:'.$key.';' : 's:'.strlen((string) $key).':"'.$key.'";';
        $body .= sValue($value);
    }

    return 'a:'.count($items).':{'.$body.'}';
}

function sObj(string $class, array $properties): string
{
    $body = '';
    foreach ($properties as $key => $value) {
        $body .= 's:'.strlen((string) $key).':"'.$key.'";'.sValue($value);
    }

    return 'O:'.strlen($class).':"'.$class.'":'.count($properties).':{'.$body.'}';
}

it('summarizes a simple job command with its public properties', function (): void {
    $command = sObj('App\\Jobs\\SendInvoice', [
        'invoiceId' => 42,
        'resendNotification' => true,
        'tries' => 5,
        'queue' => 'billing',
    ]);

    $payload = [
        'displayName' => 'App\\Jobs\\SendInvoice',
        'data' => [
            'commandName' => 'App\\Jobs\\SendInvoice',
            'command' => $command,
        ],
    ];

    $details = (new Jobs())->payloadDetails(makeJobRow([
        'payload' => json_encode($payload),
    ]));

    $summary = $details['summary'];

    expect($summary)->not->toBeNull()
        ->and($summary['type'])->toBe('job')
        ->and($summary['class'])->toBe('App\\Jobs\\SendInvoice')
        ->and($summary['classShort'])->toBe('SendInvoice')
        ->and($summary['listener'])->toBeNull()
        ->and($summary['properties'])->toEqualCanonicalizing([
            ['key' => 'invoiceId', 'value' => '42'],
            ['key' => 'resendNotification', 'value' => 'true'],
        ]);
});

it('summarizes a listener-wrapped audit dispatch with changes, user and context', function (): void {
    $user = sObj('App\\Models\\User', [
        'attributes' => [
            'id' => 2,
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ],
    ]);

    $event = sObj('OwenIt\\Auditing\\Events\\DispatchAudit', [
        'class' => 'App\\Models\\Configuration',
        'model_data' => [
            'attributes' => [
                'id' => 39,
                'key' => 'active_module',
                'value' => 'POS Module',
                'updated_at' => '2026-04-24 21:16:10',
            ],
            'original' => [
                'id' => 39,
                'key' => 'active_module',
                'value' => 'Property Management Module',
                'updated_at' => '2026-04-24 21:13:40',
            ],
            'auditEvent' => 'updated',
            'preloadedResolverData' => [
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'url' => 'https://example.test/update',
                'user' => $user,
            ],
        ],
    ]);

    $listener = sObj('Illuminate\\Events\\CallQueuedListener', [
        'class' => 'OwenIt\\Auditing\\Listeners\\ProcessDispatchAudit',
        'method' => 'handle',
        'data' => sArr([$event]),
    ]);

    $payload = [
        'displayName' => 'OwenIt\\Auditing\\Listeners\\ProcessDispatchAudit',
        'data' => [
            'commandName' => 'Illuminate\\Events\\CallQueuedListener',
            'command' => $listener,
        ],
    ];

    $details = (new Jobs())->payloadDetails(makeJobRow([
        'payload' => json_encode($payload),
    ]));

    $summary = $details['summary'];

    expect($summary)->not->toBeNull()
        ->and($summary['type'])->toBe('listener')
        ->and($summary['classShort'])->toBe('CallQueuedListener')
        ->and($summary['listener']['classShort'])->toBe('ProcessDispatchAudit')
        ->and($summary['listener']['method'])->toBe('handle')
        ->and($summary['event']['classShort'])->toBe('DispatchAudit')
        ->and($summary['model']['classShort'])->toBe('Configuration')
        ->and($summary['model']['id'])->toBe(39)
        ->and($summary['model']['event'])->toBe('updated')
        ->and($summary['changes'])->toBe([
            [
                'field' => 'value',
                'old' => 'Property Management Module',
                'new' => 'POS Module',
            ],
        ])
        ->and($summary['context']['ipAddress'])->toBe('127.0.0.1')
        ->and($summary['context']['url'])->toBe('https://example.test/update')
        ->and($summary['user']['id'])->toBe(2)
        ->and($summary['user']['name'])->toBe('Admin')
        ->and($summary['user']['email'])->toBe('admin@example.com');
});
