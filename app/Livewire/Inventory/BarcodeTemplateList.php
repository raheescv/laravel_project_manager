<?php

namespace App\Livewire\Inventory;

use App\Support\BarcodeTemplateConfiguration;
use Illuminate\Support\Str;
use Livewire\Component;

class BarcodeTemplateList extends Component
{
    public array $templates = [];

    public string $defaultPrintTemplateKey = BarcodeTemplateConfiguration::DEFAULT_TEMPLATE_KEY;

    public string $newTemplateName = '';

    public string $newTemplateType = BarcodeTemplateConfiguration::DEFAULT_TYPE;

    public array $availableTypes = [];

    public function mount(): void
    {
        $this->availableTypes = BarcodeTemplateConfiguration::types();
        $this->newTemplateType = BarcodeTemplateConfiguration::defaultType();
        $this->loadConfiguration();
    }

    protected function loadConfiguration(): void
    {
        $configuration = BarcodeTemplateConfiguration::getConfiguration();
        $this->templates = $configuration['templates'];
        $this->defaultPrintTemplateKey = $configuration['default_template'];
    }

    protected function saveConfiguration(bool $showMessage = true): void
    {
        if (! isset($this->templates[$this->defaultPrintTemplateKey])) {
            $this->defaultPrintTemplateKey = array_key_first($this->templates);
        }

        BarcodeTemplateConfiguration::saveConfiguration([
            'default_template' => $this->defaultPrintTemplateKey,
            'templates' => $this->templates,
        ]);

        if ($showMessage) {
            $this->dispatch('success', ['message' => 'Template settings saved successfully']);
        }
    }

    public function updatedDefaultPrintTemplateKey(): void
    {
        $this->saveConfiguration(false);
    }

    public function createTemplate(): void
    {
        abort_unless(auth()->user()?->can('configuration.barcode'), 403);
        $name = trim($this->newTemplateName);
        if ($name === '') {
            $this->dispatch('error', ['message' => 'Template name is required']);

            return;
        }

        $baseKey = Str::slug($name, '_');
        if ($baseKey === '') {
            $baseKey = 'template';
        }

        $key = $baseKey;
        $suffix = 2;
        while (isset($this->templates[$key])) {
            $key = $baseKey.'_'.$suffix;
            $suffix++;
        }

        $type = BarcodeTemplateConfiguration::typeExists($this->newTemplateType)
            ? $this->newTemplateType
            : BarcodeTemplateConfiguration::defaultType();

        // Always seed from the chosen type's own defaults. Cloning the current
        // default template would hand a jewellery tag's settings to a standard
        // sticker (and the other way round) the moment both types are in use.
        $sourceSettings = BarcodeTemplateConfiguration::defaultSettings($type);

        $this->templates[$key] = [
            'name' => $name,
            'type' => $type,
            'settings' => BarcodeTemplateConfiguration::normalizeSettings($sourceSettings, $type),
        ];

        $this->newTemplateName = '';
        $this->saveConfiguration(false);
        $this->dispatch('success', ['message' => 'Template created successfully']);
    }

    public function deleteTemplate(string $templateKey): void
    {
        abort_unless(auth()->user()?->can('configuration.barcode'), 403);
        if (! isset($this->templates[$templateKey])) {
            return;
        }

        if (count($this->templates) === 1) {
            $this->dispatch('error', ['message' => 'At least one template is required']);

            return;
        }

        unset($this->templates[$templateKey]);
        $this->saveConfiguration(false);
        $this->dispatch('success', ['message' => 'Template deleted successfully']);
    }

    public function render()
    {
        return view('livewire.inventory.barcode-template-list');
    }
}
