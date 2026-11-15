<?php

use App\Actions\Student\PreOrder\SummaryAction;
use App\Console\Commands\Student\PreOrderCloseCommand;
use App\Livewire\Settings\StudentConfiguration;
use App\Mail\AppointmentMail;
use App\Models\Configuration;
use App\Models\EmailLog;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

/**
 * Orders for a day close at 18:00 the evening before, and the kitchen is emailed
 * that day's orders the minute they close.
 */
beforeEach(function (): void {
    // Sunday 20 September 2026, late afternoon: Monday's orders are still open.
    $this->travelTo(Carbon::parse('2026-09-20 17:00:00'));

    $this->world = PosWorld::create(stock: 100, price: 5);
    StudentWorld::enableSchool($this->world);
    $this->student = StudentWorld::enrol($this->world);
    $this->token = StudentWorld::parentToken($this->student->guardians->first());

    StudentWorld::enablePreOrders($this->world, [(int) $this->world->product->main_category_id], [
        'pre_order_cutoff' => '18:00',
        'pre_order_cutoff_day_before' => true,
        'pre_order_summary_emails' => ['kitchen@school.qa'],
    ]);
    StudentWorld::topUp($this->world, $this->student, 500);
});

function orderDay($test, string $date, int $quantity = 2)
{
    app('auth')->forgetGuards();

    return $test->withToken($test->token)->putJson(
        $test->world->url("/api/v1/parent/students/{$test->student->id}/pre-orders/days/{$date}"),
        ['items' => [['product_id' => $test->world->product->id, 'quantity' => $quantity]]],
    );
}

it('closes a day\'s orders at the cut-off time the evening before', function (): void {
    orderDay($this, '2026-09-21')->assertOk();

    $this->travelTo(Carbon::parse('2026-09-20 18:00:00'));
    orderDay($this, '2026-09-21')
        ->assertStatus(422)
        ->assertJsonPath('message', 'Orders for tomorrow closed at 6:00 PM the day before.');
    orderDay($this, '2026-09-22')->assertOk();
});

it('tells the portal the rule and each day\'s deadline', function (): void {
    app('auth')->forgetGuards();
    $schedule = $this->withToken($this->token)->getJson($this->world->url("/api/v1/parent/students/{$this->student->id}/pre-orders"))->assertOk();

    $monday = collect($schedule->json('data.days'))->firstWhere('date', '2026-09-21');
    expect($schedule->json('data.cutoff_label'))->toBe('6:00 PM the day before')
        ->and($schedule->json('data.days.0.locked'))->toBeTrue()
        ->and($monday['locked'])->toBeFalse()
        ->and(Carbon::parse($monday['deadline'])->format('Y-m-d H:i'))->toBe('2026-09-20 18:00');
});

it('lists what to prepare for a day, per item and per student', function (): void {
    orderDay($this, '2026-09-21', 3)->assertOk();

    $summary = (new SummaryAction())->execute(Carbon::parse('2026-09-21'));

    expect($summary['students'])->toBe(1)
        ->and($summary['orders'][0]['paid'])->toBeTrue()
        ->and($summary['orders'][0]['student'])->toBe($this->student->name)
        ->and($summary['items'][0])->toMatchArray(['name' => $this->world->product->name, 'quantity' => 3])
        ->and($summary['total'])->toBe(round(3 * (float) $this->world->product->mrp, 2));
});

it('emails the kitchen once, the minute a day\'s orders close', function (): void {
    Mail::fake();
    orderDay($this, '2026-09-21')->assertOk();

    $this->artisan('student:pre-order-close')->assertSuccessful();
    Mail::assertNothingSent();

    $this->travelTo(Carbon::parse('2026-09-20 18:00:30'));
    $this->artisan('student:pre-order-close')->assertSuccessful();
    $this->artisan('student:pre-order-close')->assertSuccessful();

    Mail::assertSentCount(1);
    Mail::assertSent(AppointmentMail::class, fn (AppointmentMail $mail) => $mail->hasTo('kitchen@school.qa')
        && str_contains($mail->subjectLine, 'Mon 21 Sep')
        && str_contains($mail->bodyHtml, $this->student->name));
    expect(EmailLog::withoutGlobalScopes()->where('type', 'pre_order_summary')->count())->toBe(1)
        ->and(Configuration::withoutGlobalScopes()->where('key', PreOrderCloseCommand::SENT_KEY)->value('value'))->toBe('2026-09-21');
});

it('sends nothing when no one is set to receive the summary', function (): void {
    Mail::fake();
    StudentWorld::enablePreOrders($this->world, [(int) $this->world->product->main_category_id], [
        'pre_order_cutoff' => '18:00', 'pre_order_cutoff_day_before' => true,
    ]);

    $this->travelTo(Carbon::parse('2026-09-20 18:05:00'));
    $this->artisan('student:pre-order-close')->assertSuccessful();

    Mail::assertNothingSent();
});

it('saves the cut-off day and the summary addresses from settings', function (): void {
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'student settings.edit', 'guard_name' => 'web']));

    Livewire::actingAs($this->world->user)->test(StudentConfiguration::class)
        ->assertSet('pre_order_cutoff_day_before', '1')
        ->set('pre_order_summary_emails', "kitchen@school.qa\nnot-an-email")
        ->call('save')
        ->assertHasErrors('pre_order_summary_emails')
        ->set('pre_order_summary_emails', "Kitchen@School.qa\n manager@school.qa ")
        ->set('pre_order_cutoff_day_before', '0')
        ->call('save')
        ->assertHasNoErrors();

    $settings = StudentSettings::current();
    expect($settings->preOrderCutoffDayBefore)->toBeFalse()
        ->and($settings->preOrderSummaryEmails)->toBe(['kitchen@school.qa', 'manager@school.qa']);
});
