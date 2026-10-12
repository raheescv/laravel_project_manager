<?php

namespace App\Http\Requests\Dashboard;

use App\Services\Dashboard\WidgetRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveDashboardLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('dashboard.customize');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $visible = array_keys(app(WidgetRegistry::class)->visibleTo($this->user()));

        return [
            'layout' => ['present', 'array', 'max:80'],
            'layout.*' => ['array'],
            'layout.*.type' => ['nullable', Rule::in(['divider'])],
            'layout.*.key' => ['required_without:layout.*.type', 'nullable', 'string', Rule::in($visible), 'distinct'],
            'layout.*.span' => ['required_with:layout.*.key', 'nullable', 'integer', Rule::in(WidgetRegistry::SPANS)],
            'layout.*.collapsed' => ['nullable', 'boolean'],
            'layout.*.row' => ['nullable', 'boolean'],
            'layout.*.tall' => ['nullable', 'boolean'],
            'layout.*.id' => ['required_if:layout.*.type,divider', 'nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', 'distinct'],
            'layout.*.title' => ['required_if:layout.*.type,divider', 'nullable', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $registry = app(WidgetRegistry::class);
            foreach ((array) $this->input('layout', []) as $index => $item) {
                $definition = is_array($item) && is_string($item['key'] ?? null) ? $registry->find($item['key']) : null;
                if ($definition && (int) ($item['span'] ?? 0) < $definition->minSpan) {
                    $validator->errors()->add("layout.{$index}.span", "{$definition->title} can't be narrower than {$definition->minSpan} of 12 columns.");
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'layout.*.key.in' => "That widget isn't available to you.",
            'layout.*.key.distinct' => 'A widget can only appear once.',
            'layout.*.span.in' => 'Widgets are 4, 6, 8 or 12 columns wide.',
            'layout.*.title.max' => 'Keep section titles under 60 characters.',
        ];
    }

    /**
     * The validated layout in its stored shape.
     *
     * @return list<array<string, mixed>>
     */
    public function layout(): array
    {
        return collect($this->validated('layout'))->map(fn (array $item): array => ($item['type'] ?? null) === 'divider'
            ? ['type' => 'divider', 'id' => $item['id'], 'title' => trim($item['title'])]
            : ['key' => $item['key'], 'span' => (int) $item['span'], 'collapsed' => (bool) ($item['collapsed'] ?? false)] + (empty($item['row']) ? [] : ['row' => true]) + (empty($item['tall']) ? [] : ['tall' => true])
        )->values()->all();
    }
}
