<?php

namespace App\Actions\Settings\EmailTemplate;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateRenderer;
use App\Support\RichText;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['body'] = RichText::sanitize($data['body'] ?? '');

            validationHelper(EmailTemplate::rules(), $data);

            $unknown = app(EmailTemplateRenderer::class)->unknownVariables(
                ($data['subject'] ?? '').' '.($data['body'] ?? ''),
                $data['module'],
                $data['type']
            );
            if ($unknown) {
                throw new \Exception('Unknown variable: {{ '.implode(' }}, {{ ', $unknown).' }}. Use only the variables listed for this template type.', 1);
            }

            // Only one template of a module+type may be active at a time.
            if (! empty($data['is_active'])) {
                EmailTemplate::activeType($data['module'], $data['type'])->update(['is_active' => false]);
            }

            $model = EmailTemplate::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully created template';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
