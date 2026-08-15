<?php

namespace App\Actions\Settings\EmailTemplate;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateRenderer;
use App\Support\RichText;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = EmailTemplate::findOrFail($id);

            // Only sanitise a body that was actually supplied. Sanitising an
            // absent key would turn a partial update — toggling is_active, say —
            // into a silent wipe of the tenant's wording.
            if (array_key_exists('body', $data)) {
                $data['body'] = RichText::sanitize($data['body']);
            }

            // Validate and variable-check the RESULT of the update, not just the
            // fields that happened to be sent.
            $merged = array_merge($model->toArray(), $data);

            validationHelper(EmailTemplate::rules($id), $merged);

            $unknown = app(EmailTemplateRenderer::class)->unknownVariables(
                ($merged['subject'] ?? '').' '.($merged['body'] ?? ''),
                $merged['module'],
                $merged['type']
            );
            if ($unknown) {
                throw new \Exception('Unknown variable: {{ '.implode(' }}, {{ ', $unknown).' }}. Use only the variables listed for this template type.', 1);
            }

            // Stand the others down first, otherwise et_active_key_unique rejects
            // the save with a database error instead of doing what was meant.
            if (! empty($data['is_active'])) {
                EmailTemplate::activeType($merged['module'], $merged['type'])
                    ->where('id', '!=', $model->id)
                    ->update(['is_active' => false]);
            }

            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully updated template';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
