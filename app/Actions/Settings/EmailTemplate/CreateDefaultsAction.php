<?php

namespace App\Actions\Settings\EmailTemplate;

use App\Models\EmailTemplate;

/**
 * Creates starter templates for a module from the wording in
 * config/email_templates.php.
 *
 * It only ever ADDS what is missing. An event that already has a template is
 * skipped entirely, so running this again can never overwrite wording a tenant
 * has edited — the whole point of starters is that ownership transfers on
 * creation and never comes back.
 */
class CreateDefaultsAction
{
    public function execute($module, $userId = null)
    {
        try {
            $types = EmailTemplate::typesFor($module);
            if (! $types) {
                throw new \Exception('That module has no email events to create templates for.', 1);
            }

            $created = 0;
            $skipped = 0;

            foreach ($types as $type => $meta) {
                if (blank($meta['default']['subject'] ?? null)) {
                    continue;
                }

                $exists = EmailTemplate::where('module', $module)->where('type', $type)->exists();
                if ($exists) {
                    $skipped++;

                    continue;
                }

                EmailTemplate::create([
                    'module' => $module,
                    'type' => $type,
                    'name' => $meta['label'] ?? $type,
                    'subject' => $meta['default']['subject'],
                    'body' => $meta['default']['body'] ?? '',
                    'language' => 'en',
                    // Safe to activate: we only reach here when the event has no
                    // template at all, so there is nothing to stand down.
                    'is_active' => true,
                    'created_by' => $userId,
                ]);

                $created++;
            }

            if ($created === 0) {
                throw new \Exception('Every event already has a template — nothing to add.', 1);
            }

            $return['success'] = true;
            $return['message'] = $created.' default template'.($created === 1 ? '' : 's').' created'
                .($skipped ? ", {$skipped} left untouched" : '').'. Edit the wording to suit your company.';
            $return['data'] = ['created' => $created, 'skipped' => $skipped];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
