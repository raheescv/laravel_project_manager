<?php

use App\Events\FileImportProgress;
use App\Exports\StudentImportIssuesExport;
use App\Exports\Templates\StudentImportTemplate;
use App\Jobs\Student\ImportStudentsJob;
use App\Livewire\Student\Import;
use App\Models\Account;
use App\Models\StudentDetail;
use App\Services\TenantService;
use App\Support\Student\StudentImportSheet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create();
    StudentWorld::enableSchool($this->world);

    foreach (config('permissions') as $group => $actions) {
        if (! str_starts_with($group, 'student')) {
            continue;
        }
        foreach ($actions as $action) {
            $this->world->user->givePermissionTo(Permission::firstOrCreate([
                'tenant_id' => $this->world->tenant->id, 'name' => "{$group}.{$action}", 'guard_name' => 'web',
            ]));
        }
    }

    $this->actingAs($this->world->user);
    // Enrolled with card 04A21B9C and parent Ahmed Saleh on 55123456.
    $this->student = StudentWorld::enrol($this->world);
    $this->admissionNo = $this->student->studentDetail->admission_no;
});

it('matches school-style headers to fields and reads parent relations from them', function (): void {
    $mappings = StudentImportSheet::guessMappings([
        'adm_no' => 'Adm No',
        'student_name' => 'Student Name',
        'class' => 'Class',
        'division' => 'Division',
        'sex' => 'Sex',
        'dob' => 'DOB',
        'rfid' => 'RFID',
        'fathers_name' => "Father's Name",
        'father_mobile_no' => 'Father Mobile No.',
        'mother_name' => 'Mother Name',
        'mothers_mobile' => "Mother's Mobile",
        'bus_route' => 'Bus Route',
    ]);

    expect($mappings)->toBe([
        'admission_no' => 'adm_no',
        'name' => 'student_name',
        'grade' => 'class',
        'section' => 'division',
        'gender' => 'sex',
        'dob' => 'dob',
        'card_uid' => 'rfid',
        'parent_name' => 'fathers_name',
        'parent_mobile' => 'father_mobile_no',
        'second_parent_name' => 'mother_name',
        'second_parent_mobile' => 'mothers_mobile',
    ])->and(StudentImportSheet::relationDefaults($mappings))->toBe(['parent' => 'father', 'second_parent' => 'mother']);

    // The template's own headings (and so the issues download) map onto themselves.
    $headings = array_combine(StudentImportTemplate::HEADINGS, StudentImportTemplate::HEADINGS);
    expect(StudentImportSheet::guessMappings($headings + ['issues' => 'issues', 'sheet_row' => 'sheet_row']))->toBe($headings);
});

it('checks every row before anything is saved', function (): void {
    $sheet = new StudentImportSheet([], 'update');
    $entries = collect($sheet->review([
        2 => ['admission_no' => 'ADM-5001', 'name' => 'Aisha Khalid', 'gender' => 'F', 'dob' => '14/03/2015', 'card_uid' => '11:22:33:44',
            'parent_name' => 'Khalid Mansour', 'parent_mobile' => '5512 3456', 'second_parent_name' => 'Noor Hassan', 'second_parent_mobile' => '66987766'],
        3 => ['admission_no' => 'ADM-5002', 'name' => '', 'parent_name' => 'Hamad', 'parent_mobile' => '70000001'],
        4 => ['admission_no' => 'adm-5001', 'name' => 'Twin', 'parent_name' => 'Hamad', 'parent_mobile' => '70000001'],
        5 => ['admission_no' => 'ADM-5003', 'name' => 'Card Clash', 'card_uid' => '04a21b9c', 'parent_name' => 'Hamad', 'parent_mobile' => '70000001'],
        6 => ['admission_no' => 'ADM-5004', 'name' => 'Card Twin', 'card_uid' => '11223344', 'parent_name' => 'Hamad', 'parent_mobile' => '70000001'],
        7 => ['admission_no' => 'ADM-5005', 'name' => 'Half Parent', 'parent_name' => 'Only A Name'],
        8 => ['admission_no' => 'ADM-5006', 'name' => 'Odd Values', 'gender' => 'x', 'parent_name' => 'Hamad', 'parent_mobile' => '70000001', 'parent_relation' => 'uncle'],
        9 => ['admission_no' => 'ADM-5007', 'name' => 'No Family', 'dob' => '31/31/2019'],
        10 => ['admission_no' => $this->admissionNo, 'name' => 'Sara Ahmed', 'card_uid' => 'FFEE0011'],
        11 => ['admission_no' => '', 'name' => '', 'grade' => ''],
    ]))->keyBy('line');

    expect($entries)->toHaveCount(9)
        ->and($entries[2]['status'])->toBe('new')
        ->and($entries[2]['issues'])->toBe([])
        ->and($entries[2]['notes'])->toBe([])
        ->and($entries[2]['data']['dob'])->toBe('2015-03-14')
        ->and($entries[2]['data']['gender'])->toBe('female')
        ->and($entries[2]['data']['guardians'])->toHaveCount(2)
        ->and($entries[3]['issues'])->toBe(['Student name is required.'])
        ->and($entries[4]['issues'])->toBe(['Admission No adm-5001 is already used on row 2.'])
        ->and($entries[5]['issues'])->toBe(["Card 04A21B9C is already linked to {$this->admissionNo} (Sara Ahmed)."])
        ->and($entries[6]['issues'])->toBe(['Card 11223344 is already used on row 2.'])
        ->and($entries[7]['issues'])->toBe(['First parent mobile is required.'])
        ->and($entries[8]['issues'])->toBe(['Gender "x" should be male or female.', 'First parent relation "uncle" should be father, mother or guardian.'])
        ->and($entries[9]['status'])->toBe('new')
        ->and($entries[9]['notes'])->toBe(['Date of birth "31/31/2019" was not understood and is left blank.', 'No parent — nobody can sign in to the parent portal for this student.'])
        ->and($entries[10]['status'])->toBe('update')
        ->and($entries[10]['notes'])->toBe(["Card left as it is — change a student's card from their Card tab."])
        // Row 2's first parent is the enrolled family's father (55123456); the mother is new.
        ->and($sheet->parentLogins())->toBe(['new' => 1, 'existing' => 1]);

    expect((new StudentImportSheet([], 'skip'))->review([10 => ['admission_no' => $this->admissionNo, 'name' => '']])[0]['status'])->toBe('skip');
});

it('imports the checked rows through the queued job with the chosen columns', function (): void {
    Storage::fake('local');
    Event::fake([FileImportProgress::class]);
    Notification::fake();

    Storage::disk('local')->put('student-imports/sheet.csv', implode("\n", [
        'Adm No,Student Name,Class,Division,Father Name,Father Mobile,Mother Name,Mother Mobile,RFID',
        'ADM-6001,Lina Omar,Grade 3,A,Omar Said,33112233,Huda Ali,33445566,AA:BB:CC:01',
        'ADM-6002,,Grade 3,A,Omar Said,33112233,,,',
        "{$this->admissionNo},Sara Ahmed,Grade 6,C,,,,,DD:EE:FF:00",
    ]));
    $mappings = [
        'admission_no' => 'adm_no', 'name' => 'student_name', 'grade' => 'class', 'section' => 'division',
        'parent_name' => 'father_name', 'parent_mobile' => 'father_mobile',
        'second_parent_name' => 'mother_name', 'second_parent_mobile' => 'mother_mobile', 'card_uid' => 'rfid',
    ];

    (new ImportStudentsJob($this->world->user->id, 'student-imports/sheet.csv', $this->world->tenant->id, 'update', $mappings, 'run-1'))->handle();
    app(TenantService::class)->setCurrentTenant($this->world->tenant);

    $lina = Account::student()->where('name', 'Lina Omar')->sole();
    $status = Cache::get(ImportStudentsJob::statusKey('run-1'));

    expect($lina->studentDetail->card_uid)->toBe('AABBCC01')
        ->and($lina->guardians()->orderBy('guardians.name')->get()->map(fn ($g) => [$g->name, $g->pivot->relation])->all())
        ->toBe([['Huda Ali', 'mother'], ['Omar Said', 'father']])
        ->and(StudentDetail::where('admission_no', 'ADM-6002')->exists())->toBeFalse()
        ->and(StudentDetail::where('account_id', $this->student->id)->first()->only(['grade', 'section', 'card_uid']))
        ->toBe(['grade' => 'Grade 6', 'section' => 'C', 'card_uid' => '04A21B9C'])
        ->and($status)->toMatchArray(['progress' => 100, 'created' => 1, 'updated' => 1, 'skipped' => 0, 'failed' => 1])
        ->and($status['errors'][0])->toMatchArray(['row' => 3, 'admission_no' => 'ADM-6002', 'message' => 'Student name is required.'])
        ->and(Storage::disk('local')->exists('student-imports/sheet.csv'))->toBeFalse();
});

it('walks the import screen from upload to a queued job', function (): void {
    Storage::fake('local');
    Queue::fake();

    $csv = UploadedFile::fake()->createWithContent('students.csv', implode("\n", [
        'Adm No,Student Name,Class,Father Name,Father Mobile,Remarks',
        'ADM-7001,Omar Faisal,Grade 6,Faisal Omar,70001122,',
        'ADM-7002,,Grade 6,Someone,70001123,late joiner',
    ]));

    $component = Livewire::test(Import::class)
        ->assertSee('Upload the student sheet')
        ->set('file', $csv)
        ->assertHasNoErrors()
        ->assertSet('step', 2)
        ->assertSet('rowCount', 2)
        ->assertSet('mappings.admission_no', 'adm_no')
        ->assertSet('mappings.parent_mobile', 'father_mobile')
        ->assertSee('Father · from the headers')
        ->assertSee('Remarks')
        ->set('mappings.name', '')
        ->call('checkRows')
        ->assertHasErrors('mappings')
        ->set('mappings.name', 'student_name')
        ->call('checkRows')
        ->assertSet('step', 3)
        ->assertSet('summary.new', 1)
        ->assertSet('summary.error', 1)
        ->assertSee('Omar Faisal')
        ->assertSee('Student name is required.')
        ->set('rowFilter', 'error')
        ->assertDontSee('Omar Faisal');

    Excel::fake();
    $component->call('downloadIssues');
    Excel::assertDownloaded('student_import_issues.xlsx', function (StudentImportIssuesExport $export) {
        $row = array_combine([...StudentImportTemplate::HEADINGS, 'issues', 'sheet_row'], $export->array()[0]);

        return $row['admission_no'] === 'ADM-7002' && $row['parent_mobile'] === '70001123' && $row['issues'] === 'Student name is required.' && $row['sheet_row'] === 3;
    });

    $component->call('startImport')->assertSet('step', 4)->assertSee('Importing 1 student');

    Queue::assertPushed(ImportStudentsJob::class, function (ImportStudentsJob $job) {
        [$path, $mappings] = (fn () => [$this->filePath, $this->mappings])->call($job);

        return Storage::disk('local')->exists($path) && $mappings['parent_mobile'] === 'father_mobile' && ! isset($mappings['section']);
    });
});

it('opens the import page only for users who may import', function (): void {
    $this->get($this->world->url('/student/import'))->assertOk()->assertSee('Upload the student sheet');

    $this->world->user->revokePermissionTo('student.import');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->get($this->world->url('/student/import'))->assertForbidden();
});
