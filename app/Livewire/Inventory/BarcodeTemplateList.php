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

    public function mount(): void
    {
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

        $sourceSettings = $this->templates[$this->defaultPrintTemplateKey]['settings']
            ?? BarcodeTemplateConfiguration::defaultSettings();

        $this->templates[$key] = [
            'name' => $name,
            'settings' => BarcodeTemplateConfiguration::normalizeSettings($sourceSettings),
        ];

        $this->newTemplateName = '';
        $this->saveConfiguration(false);
        $this->dispatch('success', ['message' => 'Template created successfully']);
    }

    public function deleteTemplate(string $templateKey): void
    {
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
