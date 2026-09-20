<?php

// TEMPORARY visual-check dump (session 783147f1). Delete after screenshots.

use App\Actions\Sale\CreateAction as SaleCreateAction;
use App\Livewire\Student\Purchases;
use App\Livewire\Student\TopupModal;
use App\Livewire\Student\Statement;
use App\Livewire\Student\Topups;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

it('dumps the student view tabs for screenshots', function (): void {
    $world = PosWorld::create(stock: 100, price: 50);
    StudentWorld::enableSchool($world);

    foreach (config('permissions') as $group => $actions) {
        if (! str_starts_with($group, 'student')) {
            continue;
        }
        foreach ($actions as $action) {
            $world->user->givePermissionTo(Permission::firstOrCreate([
                'tenant_id' => $world->tenant->id, 'name' => "{$group}.{$action}", 'guard_name' => 'web',
            ]));
        }
    }

    $this->actingAs($world->user);
    $student = StudentWorld::enrol($world);
    StudentWorld::topUp($world, $student, 30);
    StudentWorld::topUp($world, $student, 120);
    (new SaleCreateAction())->execute(StudentWorld::salePayload($world, $student->id, 20, card: 20), $world->user->id);
    (new SaleCreateAction())->execute(StudentWorld::salePayload($world, $student->id, 85.5, card: 85.5), $world->user->id);

    $shell = view('student.view', ['id' => $student->id])->render();
    preg_match('/<head[^>]*>(.*?)<\/head>/s', $shell, $matches);
    $head = str_replace(config('app.url'), 'https://project_manager.test', $matches[1]);

    $topups = Livewire::test(Topups::class, ['account_id' => $student->id])->html();
    $modal = Livewire::test(TopupModal::class, ['account_id' => $student->id])->html();
    $statement = Livewire::test(Statement::class, ['account_id' => $student->id])->html();
    $purchases = Livewire::test(Purchases::class, ['account_id' => $student->id])->html();

    // Alpine is not running in a static dump: pin the classes it would bind.
    $modal = str_replace(['class="mh"', 'class="opt in"'], ['class="mh in"', 'class="opt in on"'], $modal);

    $page = <<<HTML
    <!DOCTYPE html>
    <html lang="en" data-bs-theme="light">
    <head>{$head}
    <style>
        body { background: var(--bs-body-bg); padding: 26px; }
        .shot { max-width: 1180px; margin: 0 auto 30px; }
        .shot h6 { font-size: 11px; letter-spacing: .12em; text-transform: uppercase; opacity: .55; margin-bottom: 10px; }
        /* Show the modal inline instead of over a backdrop. */
        .tpm.modal { display: block !important; position: static !important; opacity: 1 !important; }
        .tpm .modal-dialog { margin: 0 auto; transform: none !important; }
    </style>
    </head>
    <body>
        <div class="svx">
            <div class="shot"><h6>Record top-up modal</h6>{$modal}</div>
            <div class="shot"><h6>Top-ups tab</h6><div class="sheet" style="padding:22px">{$topups}</div></div>
            <div class="shot"><h6>Statement tab</h6><div class="sheet" style="padding:22px">{$statement}</div></div>
            <div class="shot"><h6>Purchases tab</h6><div class="sheet" style="padding:22px">{$purchases}</div></div>
        </div>
    </body>
    </html>
    HTML;

    file_put_contents(getenv('ZZ_SHOT_OUT'), $page);
    expect(true)->toBeTrue();
});
