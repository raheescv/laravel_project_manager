<?php

namespace App\Livewire\Accounts;

use App\Models\Configuration;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class ChequeConfiguration extends Component
{
    use WithFileUploads;

    public $cheque;

    public $activeElement = null;

    public $showGrid = true;

    public $backgroundImage;

    protected $listeners = [
        'elementMoved' => 'updateElementPosition',
        'elementResized' => 'updateElementSize',
        'elementVisibilityChanged' => 'updateElementVisibility',
        'reloadPreview' => '$refresh',
    ];

    public function mount()
    {
        $saved = Configuration::where('key', 'cheque_configurations')->value('value');
        $defaults = $this->getDefaultSettings();
        if ($saved) {
            $decoded = json_decode($saved, true);
            $this->cheque = is_array($decoded) ? array_replace_recursive($defaults, $decoded) : $defaults;
        } else {
            $this->cheque = $defaults;
        }
        $this->cheque['background_image'] = "https://s6.freechequewriter.com/image/cheque/TheCommercialBank_CorporateBanking_QA.jpg";
    }

    protected function getDefaultSettings()
    {
        return [
            'width' => 210,
            'height' => 100,
            'use_template' => true,
            'background_image' => "https://s6.freechequewriter.com/image/cheque/TheCommercialBank_CorporateBanking_QA.jpg",
            'date' => [
                'font_size' => 11,
                'align' => 'right',
                'visible' => true,
            ],
            'payee' => [
                'font_size' => 13,
                'align' => 'left',
                'visible' => true,
            ],
            'amount_in_words' => [
                'font_size' => 11,
                'align' => 'left',
                'visible' => true,
            ],
            'amount_in_numbers' => [
                'font_size' => 14,
                'align' => 'right',
                'visible' => true,
            ],
            'signature' => [
                'font_size' => 10,
                'align' => 'right',
                'visible' => true,
            ],
            'cheque_number' => [
                'font_size' => 9,
                'align' => 'left',
                'visible' => true,
            ],
            'account_number' => [
                'font_size' => 8,
                'align' => 'center',
                'visible' => true,
            ],
            'bank_name' => [
                'font_size' => 14,
                'align' => 'center',
                'visible' => true,
            ],
            'elements' => [
                'bank_name' => [
                    'top' => 15,
                    'left' => 60,
                    'width' => 90,
                    'height' => 15,
                ],
                'cheque_number' => [
                    'top' => 15,
                    'left' => 5,
                    'width' => 50,
                    'height' => 12,
                ],
                'date' => [
                    'top' => 15,
                    'left' => 155,
                    'width' => 50,
                    'height' => 12,
                ],
                'payee' => [
                    'top' => 40,
                    'left' => 5,
                    'width' => 200,
                    'height' => 20,
                ],
                'amount_in_words' => [
                    'top' => 68,
                    'left' => 5,
                    'width' => 135,
                    'height' => 20,
                ],
                'amount_in_numbers' => [
                    'top' => 68,
                    'left' => 145,
                    'width' => 60,
                    'height' => 20,
                ],
                'signature' => [
                    'top' => 92,
                    'left' => 140,
                    'width' => 65,
                    'height' => 18,
                ],
                'account_number' => [
                    'top' => 95,
                    'left' => 60,
                    'width' => 90,
                    'height' => 10,
                ],
            ],
        ];
    }

    public function updateElementPosition($elementId, $position)
    {
        if (! isset($this->cheque['elements'][$elementId])) {
            $this->cheque['elements'][$elementId] = [];
        }
        $left = isset($position['left']) ? (int) str_replace('px', '', $position['left']) : 0;
        $top = isset($position['top']) ? (int) str_replace('px', '', $position['top']) : 0;

        $position['left'] = $left;
        $position['top'] = $top;

        $this->cheque['elements'][$elementId] = array_merge($this->cheque['elements'][$elementId], array_intersect_key($position, array_flip(['top', 'left'])));
        $this->save(false);
    }

    public function updateElementSize($elementId, $size)
    {
        if (! isset($this->cheque['elements'][$elementId])) {
            $this->cheque['elements'][$elementId] = [];
        }

        $this->cheque['elements'][$elementId] = array_merge(
            $this->cheque['elements'][$elementId],
            array_intersect_key($size, array_flip(['width', 'height']))
        );

        $this->save(false);
    }

    public function updateElementVisibility($elementId, $isVisible)
    {
        $element = str_replace('-', '_', $elementId);
        if (isset($this->cheque[$element])) {
            $this->cheque[$element]['visible'] = $isVisible;
            $this->save(false);
        }
    }

    public function updateElementStyle($elementId, $style)
    {
        $element = str_replace('-', '_', $elementId);
        if (isset($this->cheque[$element])) {
            $allowedStyles = ['font_size', 'align'];
            foreach ($style as $key => $value) {
                if (in_array($key, $allowedStyles)) {
                    $this->cheque[$element][$key] = $value;
                }
            }
            $this->save(false);
        }
    }

    public function updatedBackgroundImage()
    {
        $this->validate([
            'backgroundImage' => 'image|max:5120', // 5MB max
        ]);

        // Store the uploaded image
        $path = $this->backgroundImage->store('cheques/backgrounds', 'public');
        $this->cheque['background_image'] = 'https://freechequewriter.com/download/cheque-writing-printing-software-for-qatar-banks';
        $this->save(false);
    }

    public function removeBackgroundImage()
    {
        if (!empty($this->cheque['background_image'])) {
            // Delete old image if exists
            if (Storage::disk('public')->exists($this->cheque['background_image'])) {
                Storage::disk('public')->delete($this->cheque['background_image']);
            }
            $this->cheque['background_image'] = 'https://freechequewriter.com/download/cheque-writing-printing-software-for-qatar-banks';
            $this->save(false);
        }
    }

    public function save($showMessage = true)
    {
        Configuration::updateOrCreate(['key' => 'cheque_configurations'], ['value' => json_encode($this->cheque)]);
        if ($showMessage) {
            $this->dispatch('success', ['message' => 'Settings saved successfully']);
        }

        $this->dispatch('reloadIframe');
    }

    public function resetToDefaults()
    {
        $cheque = config('cheque_default_configuration');
        $this->cheque = $cheque;
        $this->save(false);
        $this->dispatch('success', ['message' => 'Settings reset to default successfully']);
    }

    public function render()
    {
        return view('livewire.accounts.cheque-configuration');
    }
}
