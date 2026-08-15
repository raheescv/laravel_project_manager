<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Carries an already-rendered, already-sanitised tenant template.
 *
 * Deliberately dumb: all merging happens in TemplateRenderer so the same
 * output can be previewed in Settings without constructing a Mailable.
 */
class AppointmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
        public ?string $replyToAddress = null,
        public ?string $companyName = null,
        public ?int $logId = null,
        public ?string $preheader = null,
    ) {}

    /**
     * Ties this message back to the row created before sending, so
     * LogSentEmail updates it rather than writing a second entry.
     */
    public function headers(): Headers
    {
        return new Headers(text: $this->logId
            ? [\App\Listeners\LogSentEmail::LOG_HEADER => (string) $this->logId]
            : []);
    }

    public function envelope(): Envelope
    {
        $envelope = new Envelope(subject: $this->subjectLine);

        if (filled($this->replyToAddress)) {
            $envelope = $envelope->replyTo($this->replyToAddress);
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.appointment.template',
            with: [
                'bodyHtml' => $this->bodyHtml,
                'companyName' => $this->companyName ?: config('app.name'),
                // Branding is resolved here rather than in the view so the
                // wrapper stays a dumb template with no container lookups.
                'companyLogo' => tenant_cache('logo', '') ?: '',
                'companyPhone' => tenant_cache('mobile', '') ?: '',
                'companyEmail' => tenant_cache('email', '') ?: '',
                'accent' => \App\Services\EmailTemplateRenderer::accent(),
                'preheader' => $this->preheader,
            ],
        );
    }
}
