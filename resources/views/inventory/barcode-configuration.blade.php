{{--
    The designer runs standalone: no sidebar, no breadcrumb, no page chrome —
    just the console, filling the window. The console's own bar carries the
    "Templates" button back to the template list.
--}}
<x-layouts.standalone title="Barcode Template Designer">
    <x-barcode.premium />

    @push('styles')
    <style>
        .bcx-fullscreen {
            height: 100dvh;
            width: 100%;
        }

        /* Flush against the window: the shell IS the page here. */
        .bcx-fullscreen > .bcx-designer {
            height: 100%;
            min-height: 0;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        /* Drawers cap at 72vh inside the boxed page; standalone they share the
           full height with the stage instead of leaving dead space below. */
        .bcx-fullscreen > .bcx-designer .bcx-drawer {
            max-height: none;
        }

        /* Phone widths stack rail, drawers and stage — let the window scroll
           rather than crushing four panes into one screen height. */
        @media (max-width: 700px) {
            .bcx-fullscreen,
            .bcx-fullscreen > .bcx-designer {
                height: auto;
                min-height: 100dvh;
            }

            .bcx-fullscreen > .bcx-designer .bcx-drawer {
                max-height: 60vh;
            }
        }
    </style>
    @endpush

    <div class="bcx bcx-fullscreen"
        id="barcode-template-designer"
        data-template-key="{{ $templateKey }}"
        data-list-url="{{ route('inventory::barcode::configuration') }}"
        data-data-url="{{ route('inventory::barcode::configuration.data', $templateKey) }}"
        data-save-url="{{ route('inventory::barcode::configuration.save', $templateKey) }}"
        data-reset-url="{{ route('inventory::barcode::configuration.reset', $templateKey) }}"
        data-csrf="{{ csrf_token() }}"
    ></div>

    @vite('resources/js/barcode-template-config.js')
</x-layouts.standalone>
