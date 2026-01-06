<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cheque Basic Size Configuration
    |--------------------------------------------------------------------------
    |
    | Standard cheque dimensions in millimeters (mm)
    | Common sizes:
    | - Standard International: 203mm x 89mm (8" x 3.5")
    | - US Standard: 152mm x 70mm (6" x 2.75")
    | - A4 Compatible: 210mm x 100mm
    |
    */
    'width' => 210,  // Cheque width in mm (default: 210mm / 8.27 inches)
    'height' => 100, // Cheque height in mm (default: 100mm / 3.94 inches)
    'unit' => 'mm',  // Measurement unit (mm, cm, inches)
    'use_template' => true,
    'background_image' => null, // Path to background image for alignment reference
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
            'top' => 10,
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
