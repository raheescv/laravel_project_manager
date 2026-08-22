<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Support\EmailStyler;
use App\Support\RichText;

/**
 * Resolves the tenant's active template for a module event and merges data into it.
 *
 * The wording is entirely the tenant's — nothing here supplies copy. If no
 * template is active for the event the send is REFUSED with a clear message
 * rather than silently falling back to something invented in PHP.
 *
 * Variable VALUES are supplied by the calling module (see
 * App\Services\PropertyAppointment\AppointmentMailData), so this service stays free of
 * any module's domain knowledge and can serve the next module unchanged.
 */
class EmailTemplateRenderer
{
    /** The one active template for a module event, or fail loudly. */
    public function activeTemplate(string $module, string $type): EmailTemplate
    {
        $template = EmailTemplate::activeType($module, $type)->first();

        if (! $template) {
            $label = EmailTemplate::typeLabelFor($module, $type);

            throw new \Exception("No active \"{$label}\" email template. Add one under Settings → Email Templates before sending.", 1);
        }

        return $template;
    }

    /**
     * @param  array<string, string>  $variables
     * @return array{subject: string, body: string, reply_to: ?string, template: EmailTemplate}
     */
    public function render(string $module, string $type, array $variables): array
    {
        $template = $this->activeTemplate($module, $type);

        return [
            'subject' => $this->replace($template->subject, $variables, false),
            'body' => $this->body($template->body, $variables),
            'reply_to' => $template->reply_to,
            'template' => $template,
        ];
    }

    /** Preview a specific template without it having to be the active one. */
    public function renderTemplate(EmailTemplate $template, array $variables): array
    {
        return [
            'subject' => $this->replace($template->subject, $variables, false),
            'body' => $this->body($template->body, $variables),
            'reply_to' => $template->reply_to,
            'template' => $template,
        ];
    }

    /**
     * Variables whose value is HTML we generated ourselves, not tenant or
     * customer input. These are injected raw; everything else is escaped.
     * Public so the Settings live preview applies the same escaping rule.
     */
    public const RAW_HTML = ['appointment_button'];

    /**
     * Sanitise, merge, then inline the Editorial typography.
     *
     * Order matters. The tenant's body is sanitised BEFORE substitution, never
     * after: sanitising afterwards would strip the attributes off the CTA
     * button this class injects, and escaping it would print the markup as
     * visible text. Styling is applied at render time and never stored, so the
     * saved wording stays plain and the design can change without rewriting it.
     */
    private function body(?string $template, array $variables): string
    {
        $safe = RichText::sanitize($template);
        $merged = $this->replace($safe, $variables, true);

        return EmailStyler::editorial($merged, self::accent());
    }

    /** The tenant's theme colour, falling back to the app default. */
    public static function accent(): string
    {
        return (string) (tenant_cache('theme_color', null) ?: '#1D4ED8');
    }

    /**
     * Rejects content that references a variable the type cannot resolve, so a
     * typo surfaces at save time instead of mailing "{{ custmer_name }}" out.
     *
     * @return array<int, string>
     */
    public function unknownVariables(?string $content, string $module, string $type): array
    {
        preg_match_all('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', (string) $content, $matches);

        $known = EmailTemplate::variablesFor($module, $type);

        return array_values(array_unique(array_diff($matches[1] ?? [], $known)));
    }

    private function replace(?string $content, array $variables, bool $isHtml): string
    {
        $content = (string) $content;

        foreach ($variables as $key => $value) {
            $raw = in_array($key, self::RAW_HTML, true);
            $replacement = ($isHtml && ! $raw) ? e((string) $value) : (string) $value;

            // preg_replace treats $ and \ in the replacement as backreferences,
            // and a customer name or generated button can legitimately contain
            // either — pass it through a callback so it is used verbatim.
            $content = preg_replace_callback(
                '/\{\{\s*'.preg_quote($key, '/').'\s*\}\}/i',
                fn () => $replacement,
                $content
            ) ?? $content;
        }

        return $content;
    }
}
