{{--
    The print cart runs standalone like the template designer: no sidebar, no
    breadcrumb, no page chrome — just the console, filling the window. The
    console's own bar carries the "Inventory" button back out.
--}}
<x-layouts.standalone title="Barcode Print Cart">
    <x-barcode.premium />
    <x-qz-print />

    @push('styles')
    <style>
        .bcx-fullscreen {
            height: 100dvh;
            width: 100%;
            display: flex;
            flex-direction: column;
        }

        .bcx-fullscreen > .bcx {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        /* Flush against the window: the shell IS the page here. */
        .bcx-fullscreen .bcx-cart {
            flex: 1 1 auto;
            min-height: 0;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .bcx-fullscreen .bcx-cart__body {
            flex: 1 1 auto;
            min-height: 0;
            grid-template-rows: minmax(0, 1fr);
        }

        /* Drawers and the cart cap at 70vh inside the boxed page; standalone
           they share the full height instead of leaving dead space below. */
        .bcx-fullscreen .bcx-cart .bcx-drawer,
        .bcx-fullscreen .bcx-cart__stage {
            max-height: none;
        }

        /* Phone widths stack rail, drawers and cart — let the window scroll
           rather than crushing them into one screen height. */
        @media (max-width: 820px) {
            .bcx-fullscreen,
            .bcx-fullscreen > .bcx {
                height: auto;
                min-height: 100dvh;
            }

            .bcx-fullscreen .bcx-cart__body {
                grid-template-rows: none;
            }

            .bcx-fullscreen .bcx-cart .bcx-drawer {
                max-height: 60vh;
            }
        }
    </style>
    @endpush

    <div class="bcx-fullscreen">
        @livewire('inventory.barcode.cart-page')
    </div>
</x-layouts.standalone>
