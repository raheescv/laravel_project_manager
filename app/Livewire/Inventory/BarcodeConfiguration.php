<?php

namespace App\Livewire\Inventory;

use App\Models\Configuration;
use Livewire\Component;

class BarcodeConfiguration extends Component
{
    public $barcode;

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

    public function mount()
    {
        $saved = Configuration::where('key', 'barcode_configurations')->value('value');
        $defaults = $this->getDefaultSettings();
        if ($saved) {
            $decoded = json_decode($saved, true);
            $this->barcode = is_array($decoded) ? array_replace_recursive($defaults, $decoded) : $defaults;
        } else {
            $this->barcode = $defaults;
        }
    }

    protected function getDefaultSettings()
    {
        return [
            'width' => 50,
            'height' => 30,
            'product_name' => [
                'font_size' => 9,
                'align' => 'left',
                'visible' => true,
                'char_limit' => 40,
            ],
            'size' => [
                'font_size' => 10,
                'align' => 'left',
                'visible' => true,
            ],
            'product_name_arabic' => [
                'font_size' => 8,
                'align' => 'right',
                'visible' => true,
                'char_limit' => 32,
            ],
            'barcode' => [
                'font_size' => 12,
                'align' => 'center',
                'visible' => true,
                'show_value' => true,
                'scale' => 3,
                'type' => 'C128',
            ],
            'price' => [
                'font_size' => 16,
                'align' => 'left',
                'visible' => true,
            ],
            'price_arabic' => [
                'font_size' => 14,
                'align' => 'right',
                'visible' => true,
            ],
            'company_name' => [
                'font_size' => 8,
                'align' => 'center',
                'visible' => true,
                'char_limit' => 50,
            ],
            'elements' => [
                'product_name' => [
                    'top' => 1,
                    'left' => 2,
                    'width' => 180,
                    'height' => 15,
                ],
                'size' => [
                    'top' => 16,
                    'left' => 2,
                    'width' => 60,
                    'height' => 14,
                ],
                'product_name_arabic' => [
                    'top' => 16,
                    'left' => 2,
                    'width' => 180,
                    'height' => 15,
                ],
                'barcode' => [
                    'top' => 32,
                    'left' => 2,
                    'width' => 180,
                    'height' => 42,
                ],
                'price' => [
                    'top' => 78,
                    'left' => 2,
                    'width' => 85,
                    'height' => 18,
                ],
                'price_arabic' => [
                    'top' => 78,
                    'left' => 95,
                    'width' => 85,
                    'height' => 18,
                ],
                'company_name' => [
                    'top' => 98,
                    'left' => 2,
                    'width' => 180,
                    'height' => 12,
                ],
            ],
        ];
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

    public function save($showMessage = true)
    {
        $selectedType = $this->barcode['barcode']['type'] ?? 'C128';
        if (! array_key_exists($selectedType, $this->barcodeTypes)) {
            $this->barcode['barcode']['type'] = 'C128';
        }

        Configuration::updateOrCreate(['key' => 'barcode_configurations'], ['value' => json_encode($this->barcode)]);
        if ($showMessage) {
            $this->dispatch('success', ['message' => 'Settings saved successfully']);
        }

        $this->dispatch('reloadIframe');
    }

    public function resetToDefaults()
    {
        $barcode = config('barcode_default_configuration');
        $this->barcode = $barcode;
        $this->save(false);
        $this->dispatch('success', ['message' => 'Settings reset to default successfully']);
    }

    public function render()
    {
        return view('livewire.inventory.barcode-configuration');
    }
}
