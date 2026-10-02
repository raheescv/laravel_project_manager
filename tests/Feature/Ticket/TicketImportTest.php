<?php

use App\Models\Ticket;
use App\Support\Ticket\TicketImportSheet;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create();

    foreach (config('permissions.ticket') as $action) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => "ticket.{$action}", 'guard_name' => 'web']));
    }

    $this->actingAs($this->world->user);
    $this->url = fn (string $path): string => $this->world->url('/ticket/api/import/'.$path);
    $this->upload = fn (string $csv) => $this->post(($this->url)('upload'), ['file' => UploadedFile::fake()->createWithContent('tickets.csv', $csv)], ['Accept' => 'application/json']);
});

it('matches everyday header names to ticket fields', function (): void {
    expect(TicketImportSheet::guessMappings([0 => 'Subject', 1 => 'Details', 2 => 'State', 3 => 'Module', 4 => 'Reported On', 5 => 'Owner']))
        ->toBe(['title' => 0, 'description' => 1, 'status' => 2, 'group' => 3, 'created_at' => 4]);

    expect(TicketImportSheet::guessMappings([0 => 'Foo', 1 => 'Bar']))->each->toBeNull();
});

it('reads the sheet headers with samples and a guessed mapping', function (): void {
    $sheet = ($this->upload)("Subject,Module,Notes\nPrinter idle,Sales,Cash sale\nRounding,Accounts,\n\n")
        ->assertOk()
        ->json('data');

    expect($sheet['row_count'])->toBe(2)
        ->and($sheet['headers'][0])->toBe(['index' => 0, 'label' => 'Subject', 'samples' => ['Printer idle', 'Rounding']])
        ->and($sheet['mappings'])->toMatchArray(['title' => 0, 'group' => 1, 'description' => 2, 'status' => null]);
});

it('reports an empty sheet and refuses the wrong file type', function (): void {
    ($this->upload)("Title,Group\n")->assertOk()->assertJsonPath('data.row_count', 0);

    $this->post(($this->url)('upload'), ['file' => UploadedFile::fake()->create('notes.pdf', 10)], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.file.0', 'Upload an Excel (.xlsx, .xls) or CSV file.');
});

it('checks every row and imports only the ready ones', function (): void {
    Ticket::factory()->create(['title' => 'Already here', 'group' => 'Sales']);

    $token = ($this->upload)(implode("\n", [
        'Title,Status,Group,Created',
        'Printer idle,WIP,sales,2026-09-01',
        'Already here,Open,Sales,',
        'Printer idle,Done,Sales,',
        ',Open,Sales,',
        'Bad status,Someday,Inventory,',
        'Bad date,,Inventory,not a date',
        'Plain one,,,',
    ]))->json('data.token');

    $payload = ['token' => $token, 'mappings' => ['title' => 0, 'status' => 1, 'group' => 2, 'created_at' => 3], 'options' => ['default_status' => 'resolved', 'duplicates' => 'skip']];

    $review = $this->postJson(($this->url)('review'), $payload)->assertOk()->json('data');
    expect($review['summary'])->toBe(['total' => 7, 'ready' => 2, 'skip' => 2, 'error' => 3])
        ->and(collect($review['rows'])->pluck('state', 'line')->all())->toBe([2 => 'ready', 3 => 'skip', 4 => 'skip', 5 => 'error', 6 => 'error', 7 => 'error', 8 => 'ready'])
        ->and($review['rows'][0]['data'])->toMatchArray(['status' => 'in_progress', 'group' => 'Sales'])
        ->and($review['rows'][4]['issues'])->toBe(['Unknown status "Someday".']);

    $this->postJson(($this->url)('commit'), $payload)
        ->assertOk()
        ->assertJsonPath('data', ['created' => 2, 'skipped' => 2, 'failed' => 3]);

    $printer = Ticket::where('title', 'Printer idle')->sole();
    expect($printer)->status->toBe('in_progress')->group->toBe('Sales')
        ->and($printer->created_at->toDateString())->toBe('2026-09-01')
        ->and($printer->created_by)->toBe($this->world->user->id)
        ->and(Ticket::where('title', 'Plain one')->sole())->status->toBe('resolved')->group->toBeNull();

    $this->postJson(($this->url)('commit'), $payload)->assertUnprocessable()->assertJsonPath('message', 'This upload has expired — upload the sheet again.');
});

it('imports duplicate titles when asked to and needs a title column', function (): void {
    Ticket::factory()->create(['title' => 'Same']);
    $token = ($this->upload)("Title,Group\nSame,Ops\nSame,Ops\n")->json('data.token');

    $this->postJson(($this->url)('commit'), ['token' => $token, 'mappings' => ['title' => null, 'group' => 1], 'options' => []])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Match a column to Title before importing.');

    $this->postJson(($this->url)('commit'), ['token' => $token, 'mappings' => ['title' => 0, 'group' => 1], 'options' => ['duplicates' => 'import']])
        ->assertOk()
        ->assertJsonPath('data.created', 2);

    expect(Ticket::where('title', 'Same')->count())->toBe(3);
});

it('opens the busiest sheet of a workbook, steps over its title row and works out formulas', function (): void {
    $book = new Spreadsheet();
    $summary = $book->getActiveSheet()->setTitle('Summary');
    $summary->fromArray([['Status', 'Count'], ['Pending', "=COUNTIF('CRM Fixing Tracker'!C:C,\"Pending\")"]]);
    $tracker = $book->createSheet()->setTitle('CRM Fixing Tracker');
    $tracker->fromArray([
        ['CRM New System — Fixing Tracking'],
        [null],
        ['Issue', 'Module', 'Status', 'Owner'],
        ['Login loop', 'Auth', 'Pending', 'Ali'],
        ['Slow report', 'Reports', 'Under Process', 'Sara'],
        ['=A4&" (again)"', 'Auth', 'Completed', 'Ali'],
    ]);
    $path = storage_path('framework/testing/tracker.xlsx');
    @mkdir(dirname($path), 0777, true);
    (new Xlsx($book))->save($path);

    $file = new UploadedFile($path, 'CRM_Tracking.xlsx', null, null, true);
    $sheet = $this->post(($this->url)('upload'), ['file' => $file], ['Accept' => 'application/json'])->assertOk()->json('data');

    expect($sheet['sheet'])->toBe('CRM Fixing Tracker')
        ->and(array_column($sheet['sheets'], 'name'))->toBe(['Summary', 'CRM Fixing Tracker'])
        ->and($sheet['header_row'])->toBe(3)
        ->and($sheet['row_count'])->toBe(3)
        ->and($sheet['mappings'])->toMatchArray(['title' => 0, 'group' => 1, 'status' => 2])
        ->and($sheet['headers'][0]['samples'])->toBe(['Login loop', 'Slow report', 'Login loop (again)']);

    $summarySheet = $this->postJson(($this->url)('sheet'), ['token' => $sheet['token'], 'sheet' => 'Summary'])->assertOk()->json('data');
    expect($summarySheet['header_row'])->toBe(1)
        ->and($summarySheet['headers'][1]['samples'])->toBe(['1']);

    $back = $this->postJson(($this->url)('sheet'), ['token' => $sheet['token'], 'sheet' => 'CRM Fixing Tracker', 'header_row' => 3])->assertOk()->json('data');
    $this->postJson(($this->url)('commit'), ['token' => $back['token'], 'mappings' => $back['mappings'], 'options' => []])
        ->assertOk()
        ->assertJsonPath('data.created', 3);

    expect(Ticket::where('title', 'Slow report')->sole()->status)->toBe('in_progress');
    @unlink($path);
});

it('shortens an over-long title and keeps the full text as the description', function (): void {
    $long = str_repeat('Only the relevant salesperson names should be displayed. ', 6);
    $rows = [[trim($long), 'Leads']];

    $row = TicketImportSheet::review($rows, ['title' => 0, 'group' => 1])[0];

    expect($row['state'])->toBe('ready')
        ->and(mb_strlen($row['data']['title']))->toBeLessThanOrEqual(255)
        ->and($row['data']['title'])->toEndWith('…')
        ->and($row['data']['description'])->toBe(trim($long))
        ->and($row['issues'])->toBe(['Long title shortened; the full text is kept in the description.']);
});

it('downloads the template', function (): void {
    $this->get(($this->url)('template'))->assertOk()->assertDownload('ticket_import_template.xlsx');
});
