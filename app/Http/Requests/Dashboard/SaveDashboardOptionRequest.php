<?php

namespace App\Http\Requests\Dashboard;

use App\Services\Dashboard\WidgetDefinition;
use App\Services\Dashboard\WidgetRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDashboardOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $definition = $this->definition();
        abort_if($definition === null, 404);

        return $definition->isVisibleTo($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'option' => ['required', 'string', Rule::in(array_keys($this->definition()?->options ?? []))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'option.in' => "That option isn't available on this widget.",
        ];
    }

    public function definition(): ?WidgetDefinition
    {
        return app(WidgetRegistry::class)->find((string) $this->route('key'));
    }
}
