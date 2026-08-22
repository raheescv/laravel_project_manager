<?php

namespace App\Livewire\Settings;

use App\Actions\Settings\EmailTemplate\CreateAction;
use App\Actions\Settings\EmailTemplate\CreateDefaultsAction;
use App\Actions\Settings\EmailTemplate\DeleteAction;
use App\Actions\Settings\EmailTemplate\UpdateAction;
use App\Models\EmailTemplate as TemplateModel;
use App\Services\EmailTemplateRenderer;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EmailTemplate extends Component
{
    public $templateId;

    public $module = '';

    public $type = '';

    public $name = '';

    public $subject = '';

    public $body = '';

    public $language = 'en';

    public $reply_to = '';

    public $is_active = false;

    /** Rail filter — which module's templates are listed. */
    public $filterModule = '';

    protected $listeners = [
        'EmailTemplate-Refresh-Component' => '$refresh',
    ];

    public function mount(): void
    {
        $this->module = (string) array_key_first(TemplateModel::modules());
        $this->type = (string) array_key_first(TemplateModel::typesFor($this->module));

        $first = TemplateModel::orderBy('module')->orderBy('type')->first();
        if ($first) {
            $this->select($first->id);
        }
    }

    /** Switching module resets the event type to one that module actually has. */
    public function updatedModule($value): void
    {
        $types = TemplateModel::typesFor($value);
        if (! array_key_exists($this->type, $types)) {
            $this->type = (string) array_key_first($types);
        }
    }

    public function select($id): void
    {
        $template = TemplateModel::find($id);
        if (! $template) {
            return;
        }

        $this->templateId = $template->id;
        $this->module = $template->module;
        $this->type = $template->type;
        $this->name = $template->name;
        $this->subject = $template->subject;
        $this->body = $template->body;
        $this->language = $template->language;
        $this->reply_to = $template->reply_to;
        $this->is_active = (bool) $template->is_active;

        // The body editor is wire:ignore — tell it to re-read its value.
        $this->dispatch('rich-text:refresh', model: 'body');
    }

    public function newTemplate(): void
    {
        abort_unless(auth()->user()?->can('email template.create'), 403);

        $this->reset(['templateId', 'name', 'subject', 'body', 'reply_to', 'is_active']);
        $this->module = $this->filterModule ?: (string) array_key_first(TemplateModel::modules());
        $this->type = (string) array_key_first(TemplateModel::typesFor($this->module));
        $this->language = 'en';

        $this->dispatch('rich-text:refresh', model: 'body');
    }

    /** Create starter templates for every event this module has none for. */
    public function createDefaults(): void
    {
        abort_unless(auth()->user()?->can('email template.create'), 403);

        try {
            DB::beginTransaction();
            $response = (new CreateDefaultsAction())->execute(
                $this->filterModule ?: $this->module,
                auth()->id()
            );
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    /**
     * Load the starter wording into the open editor WITHOUT saving, so the user
     * sees exactly what they are about to keep and can still back out.
     */
    public function useDefaultWording(): void
    {
        $default = TemplateModel::defaultFor($this->module, $this->type);

        if (! $default) {
            $this->dispatch('error', ['message' => 'This event has no starter wording.']);

            return;
        }

        $this->subject = $default['subject'];
        $this->body = $default['body'] ?? '';
        $this->dispatch('rich-text:refresh', model: 'body');
        $this->dispatch('success', ['message' => 'Starter wording loaded — review it, then save.']);
    }

    public function save(): void
    {
        // create and edit are distinct abilities: a user may be allowed to
        // adjust existing wording without being able to add new templates.
        abort_unless(
            auth()->user()?->can($this->templateId ? 'email template.edit' : 'email template.create'),
            403
        );

        $payload = [
            'module' => $this->module,
            'type' => $this->type,
            'name' => $this->name,
            'subject' => $this->subject,
            'body' => $this->body,
            'language' => $this->language,
            'reply_to' => $this->reply_to ?: null,
            'is_active' => $this->is_active,
        ];

        try {
            DB::beginTransaction();

            $response = $this->templateId
                ? (new UpdateAction())->execute($payload, $this->templateId)
                : (new CreateAction())->execute($payload);

            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            DB::commit();
            $this->templateId = $response['data']->id;
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function delete($id): void
    {
        abort_unless(auth()->user()?->can('email template.delete'), 403);
        try {
            DB::beginTransaction();
            $response = (new DeleteAction())->execute($id);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();

            if ($this->templateId == $id) {
                $this->newTemplate();
            }
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    /**
     * Values merged into the live preview. A module that declares a `sample`
     * class in config/email_templates.php supplies realistic data; otherwise
     * each variable previews as a readable placeholder.
     */
    private function sampleVariables(): array
    {
        $class = config("email_templates.{$this->module}.sample");
        if ($class && method_exists($class, 'sample')) {
            return app($class)->sample();
        }

        $values = [];
        foreach (TemplateModel::variablesFor($this->module, $this->type) as $variable) {
            $values[$variable] = '['.str_replace('_', ' ', ucwords($variable, '_')).']';
        }
        if (array_key_exists('company_name', $values)) {
            $values['company_name'] = tenant_cache('company_name', '') ?: config('app.name');
        }

        return $values;
    }

    public function render()
    {
        return view('livewire.settings.email-template', [
            'templates' => TemplateModel::query()
                ->when($this->filterModule, fn ($query, $value) => $query->where('module', $value))
                ->orderBy('module')->orderBy('type')->orderBy('name')
                ->get(),
            'modules' => TemplateModel::modules(),
            'types' => TemplateModel::typesFor($this->module),
            'variables' => TemplateModel::variablesFor($this->module, $this->type),
            'hasDefault' => (bool) TemplateModel::defaultFor($this->module, $this->type),
            'samples' => $this->sampleVariables(),
            'rawHtmlVariables' => EmailTemplateRenderer::RAW_HTML,
        ]);
    }
}
