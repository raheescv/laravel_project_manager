<?php

use App\Actions\Student\Guardian\SendInviteAction;
use App\Mail\AppointmentMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Services\Student\ParentMailData;
use Illuminate\Support\Facades\Mail;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

/**
 * A set-password link reaches the parent by email: the invite for a first login,
 * the reset for a parent who already has a password. A school that never opened
 * Settings still gets the email out; a school's own wording is never replaced.
 */
beforeEach(function (): void {
    Mail::fake();

    $this->world = PosWorld::create();
    StudentWorld::enableSchool($this->world);
    $this->student = StudentWorld::enrol($this->world);
    $this->guardian = $this->student->guardians->first();
    $this->guardian->forceFill(['email' => 'parent@example.com'])->save();
});

it('emails a password reset to a parent who already has a password', function (): void {
    $this->guardian->forceFill(['password' => 'secret-pass'])->save();

    $response = (new SendInviteAction())->execute($this->guardian->id, $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message'])
        ->and($response['data']['type'])->toBe(ParentMailData::PASSWORD_RESET)
        ->and($response['data']['delivered'])->toBeTrue()
        ->and($response['message'])->toContain('parent@example.com');

    $link = $response['data']['link'];
    Mail::assertSent(AppointmentMail::class, fn (AppointmentMail $mail) => $mail->hasTo('parent@example.com')
        && str_contains($mail->subjectLine, 'Reset your')
        && str_contains($mail->bodyHtml, e($link))
        && str_contains($mail->bodyHtml, 'Choose a new password'));

    expect(EmailLog::where('type', ParentMailData::PASSWORD_RESET)->where('related_id', $this->guardian->id)->exists())->toBeTrue();
});

it('explains in the footer that the email went to a parent, not a property enquiry', function (): void {
    (new SendInviteAction())->execute($this->guardian->id);

    Mail::assertSent(AppointmentMail::class, function (AppointmentMail $mail): bool {
        $html = $mail->render();

        return str_contains($html, 'the school has you on record as a parent')
            && ! str_contains($html, 'enquired about a property');
    });
});

it('emails the invite to a parent who has not set a password yet', function (): void {
    $response = (new SendInviteAction())->execute($this->guardian->id, $this->world->user->id);

    expect($response['data']['type'])->toBe(ParentMailData::INVITE);
    Mail::assertSent(AppointmentMail::class, fn (AppointmentMail $mail) => str_contains($mail->subjectLine, 'parent portal login')
        && str_contains($mail->bodyHtml, 'Set your password'));
});

it('creates the starter templates on first send so they can be edited in Settings', function (): void {
    expect(EmailTemplate::where('module', ParentMailData::MODULE)->count())->toBe(0);

    (new SendInviteAction())->execute($this->guardian->id, $this->world->user->id);

    expect(EmailTemplate::where('module', ParentMailData::MODULE)->where('is_active', true)->pluck('type')->sort()->values()->all())
        ->toBe([ParentMailData::INVITE, ParentMailData::PASSWORD_RESET]);
});

it('sends the school\'s own wording and never recreates a template it switched off', function (): void {
    $this->guardian->forceFill(['password' => 'secret-pass'])->save();
    $template = EmailTemplate::create([
        'module' => ParentMailData::MODULE,
        'type' => ParentMailData::PASSWORD_RESET,
        'name' => 'Our reset',
        'subject' => 'Canteen login reset for {{ parent_name }}',
        'body' => '<p>{{ set_password_button }}</p>',
        'language' => 'en',
        'is_active' => true,
    ]);

    (new SendInviteAction())->execute($this->guardian->id);
    Mail::assertSent(AppointmentMail::class, fn (AppointmentMail $mail) => $mail->subjectLine === 'Canteen login reset for Ahmed Saleh');

    $template->update(['is_active' => false]);
    Mail::fake();
    $response = (new SendInviteAction())->execute($this->guardian->id);

    Mail::assertNothingSent();
    expect($response['success'])->toBeTrue()
        ->and($response['data']['delivered'])->toBeFalse()
        ->and($response['message'])->toContain('switched off')
        ->and(EmailTemplate::where('type', ParentMailData::PASSWORD_RESET)->count())->toBe(1);
});

it('records a failed email and still hands back the link', function (): void {
    Mail::shouldReceive('to->send')->andThrow(new RuntimeException('Connection refused'));

    $response = (new SendInviteAction())->execute($this->guardian->id);

    expect($response['success'])->toBeTrue()
        ->and($response['data']['delivered'])->toBeFalse()
        ->and($response['data']['link'])->toContain('/#/set-password/')
        ->and($response['message'])->toContain('could not be sent');

    $log = EmailLog::where('related_id', $this->guardian->id)->first();
    expect($log->status)->toBe('failed')->and($log->error)->toBe('Connection refused');
});

it('emails the reset when a parent uses Forgot password', function (): void {
    $this->guardian->forceFill(['password' => 'secret-pass'])->save();

    // Sent from a terminating callback; the test request terminates the kernel
    // like a real one, so the mail is out by the time postJson() returns.
    $this->postJson($this->world->url('/api/v1/parent/forgot-password'), ['mobile' => '5512 3456'])
        ->assertOk()
        ->assertJsonPath('success', true);

    Mail::assertSent(AppointmentMail::class, fn (AppointmentMail $mail) => $mail->hasTo('parent@example.com')
        && str_contains($mail->subjectLine, 'Reset your'));
});

it('emails the reset when a parent gives their email instead of the number', function (): void {
    $this->guardian->forceFill(['password' => 'secret-pass'])->save();

    $this->postJson($this->world->url('/api/v1/parent/forgot-password'), ['login' => 'parent@example.com'])
        ->assertOk()
        ->assertJsonPath('success', true);

    Mail::assertSent(AppointmentMail::class, fn (AppointmentMail $mail) => $mail->hasTo('parent@example.com')
        && str_contains($mail->subjectLine, 'Reset your'));
});

it('answers Forgot password the same way for a number or email that is not registered', function (): void {
    foreach ([['mobile' => '99999999'], ['login' => '99999999'], ['login' => 'nobody@example.com']] as $payload) {
        $this->postJson($this->world->url('/api/v1/parent/forgot-password'), $payload)
            ->assertOk()
            ->assertJsonPath('message', 'If this mobile number or email is registered with the school, a link to set a new password is on its way by email or WhatsApp.');
    }

    Mail::assertNothingSent();
});
