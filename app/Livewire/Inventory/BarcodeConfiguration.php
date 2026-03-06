<?php

namespace App\Livewire\Inventory;

use App\Support\BarcodeTemplateConfiguration;
use Livewire\Component;

class BarcodeConfiguration extends Component
{
    public $barcode;

    public array $templates = [];

    public string $selectedTemplateKey = BarcodeTemplateConfiguration::DEFAULT_TEMPLATE_KEY;

    public string $defaultPrintTemplateKey = BarcodeTemplateConfiguration::DEFAULT_TEMPLATE_KEY;

    public string $templateName = 'Default';

    public $activeElement = null;

    public $showGrid = true;

    public array $barcodeTypes = [
        'C128' => 'Code 128',
        'C128A' => 'Code 128 A',
        'C128B' => 'Code 128 B',
        // 'C128C' => 'Code 128 C',
        'C39' => 'Code 39',
        'C39+' => 'Code 39+',
        'C39E' => 'Code 39 Extended',
        'C39E+' => 'Code 39 Extended +',
        'C93' => 'Code 93',
        // 'S25' => 'Standard 2 of 5',
        // 'S25+' => 'Standard 2 of 5 +',
        // 'I25' => 'Interleaved 2 of 5',
        // 'I25+' => 'Interleaved 2 of 5 +',
        // 'EAN8' => 'EAN-8',
        // 'EAN13' => 'EAN-13',
        // 'UPCA' => 'UPC-A',
        // 'UPCE' => 'UPC-E',
        // 'MSI' => 'MSI',
        // 'MSI+' => 'MSI+',
        // 'POSTNET' => 'Postnet',
        // 'PHARMA' => 'Pharmacode',
        'PHARMA2T' => 'Pharmacode Two-Track',
        'CODABAR' => 'Codabar',
    ];

    protected $listeners = [
        'elementMoved' => 'updateElementPosition',
        'elementResized' => 'updateElementSize',
        'elementVisibilityChanged' => 'updateElementVisibility',
        'reloadPreview' => '$refresh',
    ];

    public function mount(?string $templateKey = null)
    {
        if ($templateKey) {
            $this->selectedTemplateKey = $templateKey;
        }

        $this->loadConfiguration();
    }

    protected function getDefaultSettings()
    {
        return BarcodeTemplateConfiguration::defaultSettings();
    }

    protected function loadConfiguration(): void
    {
        $configuration = BarcodeTemplateConfiguration::getConfiguration();
        $this->templates = $configuration['templates'];
        $this->defaultPrintTemplateKey = $configuration['default_template'];

        if (! isset($this->templates[$this->selectedTemplateKey])) {
            $this->selectedTemplateKey = $this->defaultPrintTemplateKey;
        }

        $this->loadSelectedTemplate();
    }

    protected function loadSelectedTemplate(): void
    {
        $template = $this->templates[$this->selectedTemplateKey] ?? null;
        if (! $template) {
            $this->selectedTemplateKey = array_key_first($this->templates);
            $template = $this->templates[$this->selectedTemplateKey];
        }

        $this->templateName = $template['name'];
        $this->barcode = BarcodeTemplateConfiguration::normalizeSettings($template['settings'] ?? []);
    }

    protected function syncCurrentTemplate(): void
    {
        if (! isset($this->templates[$this->selectedTemplateKey])) {
            return;
        }

        $selectedType = $this->barcode['barcode']['type'] ?? 'C128';
        if (! array_key_exists($selectedType, $this->barcodeTypes)) {
            $this->barcode['barcode']['type'] = 'C128';
        }

        $name = trim($this->templateName) !== '' ? trim($this->templateName) : 'Untitled Template';

        $this->templates[$this->selectedTemplateKey] = [
            'name' => $name,
            'settings' => BarcodeTemplateConfiguration::normalizeSettings($this->barcode ?? []),
        ];
    }

    protected function persistConfiguration(bool $showMessage = true): void
    {
        $this->syncCurrentTemplate();

        if (! isset($this->templates[$this->defaultPrintTemplateKey])) {
            $this->defaultPrintTemplateKey = $this->selectedTemplateKey;
        }

        BarcodeTemplateConfiguration::saveConfiguration([
            'default_template' => $this->defaultPrintTemplateKey,
            'templates' => $this->templates,
        ]);

        if ($showMessage) {
            $this->dispatch('success', ['message' => 'Settings saved successfully']);
        }

        $this->dispatch('reloadIframe');
    }

    public function updateElementPosition($elementId, $position)
    {
        if (! isset($this->barcode['elements'][$elementId])) {
            $this->barcode['elements'][$elementId] = [];
        }
        $left = isset($position['left']) ? (int) str_replace('px', '', $position['left']) : 0;
        $top = isset($position['top']) ? (int) str_replace('px', '', $position['top']) : 0;

        $position['left'] = $left;
        $position['top'] = $top;

        $this->barcode['elements'][$elementId] = array_merge($this->barcode['elements'][$elementId], array_intersect_key($position, array_flip(['top', 'left'])));
        $this->save(false);
    }

    public function updateElementSize($elementId, $size)
    {
        if (! isset($this->barcode['elements'][$elementId])) {
            $this->barcode['elements'][$elementId] = [];
        }

        $this->barcode['elements'][$elementId] = array_merge(
            $this->barcode['elements'][$elementId],
            array_intersect_key($size, array_flip(['width', 'height']))
        );

        $this->save(false);
    }

    public function updateElementVisibility($elementId, $isVisible)
    {
        $element = str_replace('-', '_', $elementId);
        if (isset($this->barcode[$element])) {
            $this->barcode[$element]['visible'] = $isVisible;
            $this->save(false);
        }
    }

    public function updateElementStyle($elementId, $style)
    {
        $element = str_replace('-', '_', $elementId);
        if (isset($this->barcode[$element])) {
            $allowedStyles = ['font_size', 'align'];
            foreach ($style as $key => $value) {
                if (in_array($key, $allowedStyles)) {
                    $this->barcode[$element][$key] = $value;
                }
            }
            $this->save(false);
        }
    }

    public function updatingSelectedTemplateKey($value)
    {
        $this->syncCurrentTemplate();
    }

    public function updatedSelectedTemplateKey($value)
    {
        if (! isset($this->templates[$value])) {
            $this->selectedTemplateKey = array_key_first($this->templates);
        }

        $this->loadSelectedTemplate();
        $this->dispatch('reloadIframe');
    }

    public function save($showMessage = true)
    {
        $this->persistConfiguration($showMessage);
    }

    public function resetToDefaults()
    {
        $this->barcode = BarcodeTemplateConfiguration::defaultSettings();
        $this->save(false);
        $this->dispatch('success', ['message' => 'Settings reset to default successfully']);
    }

    public function render()
    {
        return view('livewire.inventory.barcode-configuration');
    }
}
