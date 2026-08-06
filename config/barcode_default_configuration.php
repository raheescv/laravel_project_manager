<?php

/*
|--------------------------------------------------------------------------
| Barcode label types
|--------------------------------------------------------------------------
|
| Every barcode template is created against one of the types below and keeps
| that type for life. The `settings` block of a type is the shape the designer
| renders, the shape stored in the `barcode_configurations` configuration row,
| and the shape the print views expect.
|
| Adding a new type = one entry here + one view folder under
| resources/views/inventory/label-types/<type> + one control panel in the Vue
| designer. Nothing else should need to change.
|
| Never introduce a numerically indexed list inside `settings`: saved settings
| are merged over these defaults with array_replace_recursive(), which merges
| lists index-wise, so a saved list can never shrink. Use keyed maps.
|
*/

return [
    'default_type' => 'standard',

    /*
    | The typefaces a template may print in. Deliberately a short curated list -
    | a free text font box only ever produces labels that print in whatever the
    | print machine happens to fall back to.
    |
    | `stack` is the CSS font-family value. A family with an `embed` block is
    | shipped with the app: its files are base64 embedded into the print HTML so
    | the PDF renders identically on every machine. A family without one relies
    | on the fonts installed where the label is rendered, so it is only ever
    | offered as a stack that degrades sensibly.
    */
    'fonts' => [
        'default' => 'plex_sans',
        'weights' => [
            400 => 'Regular',
            500 => 'Medium',
            600 => 'Semi Bold',
            700 => 'Bold',
        ],
        'families' => [
            'plex_sans' => [
                'label' => 'IBM Plex Sans (recommended)',
                'description' => 'Shipped with the app. Clean, tight, reads well at label sizes and covers Arabic.',
                'stack' => "'IBM Plex Sans Arabic', 'Helvetica Neue', Helvetica, Arial, sans-serif",
                'embed' => [
                    'family' => 'IBM Plex Sans Arabic',
                    'files' => [
                        400 => 'assets/fonts/labels/IBMPlexSansArabic-Regular.ttf',
                        700 => 'assets/fonts/labels/IBMPlexSansArabic-Bold.ttf',
                    ],
                ],
            ],
            'grotesque' => [
                'label' => 'Helvetica / Arial',
                'description' => 'The neutral system grotesque. Uses whatever the print machine has installed.',
                'stack' => "'Helvetica Neue', Helvetica, Arial, 'Liberation Sans', sans-serif",
            ],
            'condensed' => [
                'label' => 'Condensed',
                'description' => 'Narrower letters - fits longer names on a jewellery wing. Needs a condensed face installed.',
                'stack' => "'Arial Narrow', 'Helvetica Neue Condensed', 'Liberation Sans Narrow', 'Roboto Condensed', Arial, sans-serif",
            ],
            'mono' => [
                'label' => 'Monospace',
                'description' => 'Even width digits. Best for barcode numbers and codes.',
                'stack' => "Menlo, Consolas, 'DejaVu Sans Mono', 'Liberation Mono', monospace",
            ],
        ],
    ],

    'types' => [

        // Free positioned rectangular sticker - the original template shape.
        'standard' => [
            'label' => 'Standard Sticker',
            'description' => 'Rectangular label with freely positioned elements.',
            'settings' => [
                'type' => 'standard',
                'width' => 50,
                'height' => 30,
                // Template wide typeface. Elements leave `font_family` empty to
                // inherit it and only name a family when they need their own.
                'font' => [
                    'family' => 'plex_sans',
                    'weight' => 400,
                ],
                'product_name' => [
                    'font_size' => 9,
                    'font_family' => '',
                    'font_weight' => 600,
                    'align' => 'left',
                    'visible' => true,
                    'char_limit' => 40,
                ],
                'product_name_arabic' => [
                    'font_size' => 8,
                    'font_family' => '',
                    'font_weight' => 500,
                    'align' => 'right',
                    'visible' => true,
                    'char_limit' => 32,
                ],
                'barcode' => [
                    'font_size' => 12,
                    'font_family' => 'mono',
                    'font_weight' => 400,
                    'align' => 'center',
                    'visible' => true,
                    'show_value' => true,
                    'scale' => 4,
                    'type' => 'C128',
                ],
                'size' => [
                    'font_size' => 8,
                    'font_family' => '',
                    'font_weight' => 700,
                    'align' => 'center',
                    'visible' => false,
                    'char_limit' => 50,
                ],
                'company_name' => [
                    'font_size' => 8,
                    'font_family' => '',
                    'font_weight' => 700,
                    'align' => 'center',
                    'visible' => false,
                    'char_limit' => 50,
                ],
                'logo' => [
                    'visible' => false,
                ],
                'price' => [
                    'font_size' => 16,
                    'font_family' => '',
                    'font_weight' => 700,
                    'align' => 'left',
                    'visible' => true,
                ],
                'price_arabic' => [
                    'font_size' => 14,
                    'font_family' => '',
                    'font_weight' => 700,
                    'align' => 'right',
                    'visible' => true,
                ],
                'elements' => [
                    'product_name' => [
                        'top' => 1,
                        'left' => 2,
                        'width' => 180,
                        'height' => 15,
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
                    'company_name' => [
                        'top' => 78,
                        'left' => 2,
                        'width' => 180,
                        'height' => 12,
                    ],
                    'logo' => [
                        'top' => 78,
                        'left' => 2,
                        'width' => 28,
                        'height' => 18,
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
                ],
            ],
        ],

        // Continuous roll jewellery tag: two wings joined by a narrow neck. The
        // tag wraps around the item so the wings stick back to back and each
        // wing becomes one readable face - barcode on one, details on the other.
        'jewellery_tag' => [
            'label' => 'Jewellery Tag (Butterfly)',
            'description' => 'Two wing tag joined by a neck. One wing is the barcode, the other carries name, qty, price and size.',
            'settings' => [
                'type' => 'jewellery_tag',
                // width is derived from the wings + neck on save, never edited directly.
                'width' => 75,
                'height' => 13,
                'wing_width' => 25,
                'neck_width' => 25,
                'neck_height' => 5,
                'inner_padding' => 1.5,
                'barcode_wing' => 'left',
                // Off = what the designer shows is what prints. A tag turned
                // about its vertical axis reads upright on both faces; only turn
                // this on if the shop's folded tags read upside down.
                'rotate_text_wing' => false,
                'show_neck_guides' => false,
                'font' => [
                    'family' => 'plex_sans',
                    'weight' => 400,
                ],
                'barcode' => [
                    'visible' => true,
                    'type' => 'C128',
                    'scale' => 2,
                    'orientation' => 'horizontal',
                    'show_value' => true,
                    'font_size' => 6,
                    'font_family' => 'mono',
                    'height' => 9,
                ],
                'fields' => [
                    'product_name' => [
                        'visible' => true,
                        'order' => 1,
                        'font_size' => 7,
                        'font_family' => '',
                        'align' => 'left',
                        'char_limit' => 22,
                        'bold' => true,
                    ],
                    'product_name_arabic' => [
                        'visible' => false,
                        'order' => 2,
                        'font_size' => 6,
                        'font_family' => '',
                        'align' => 'right',
                        'char_limit' => 22,
                        'bold' => false,
                    ],
                    'qty' => [
                        'visible' => true,
                        'order' => 3,
                        'font_size' => 6,
                        'font_family' => '',
                        'align' => 'left',
                        'bold' => false,
                        'prefix' => 'Qty',
                        'source' => 'unit',
                        'custom_text' => '1',
                    ],
                    'price' => [
                        'visible' => true,
                        'order' => 4,
                        'font_size' => 8,
                        'font_family' => '',
                        'align' => 'left',
                        'bold' => true,
                        'prefix' => 'QR',
                    ],
                    'size' => [
                        'visible' => true,
                        'order' => 5,
                        'font_size' => 6,
                        'font_family' => '',
                        'align' => 'left',
                        'char_limit' => 18,
                        'bold' => false,
                        'prefix' => 'Size',
                    ],
                ],
            ],
        ],
    ],
];
