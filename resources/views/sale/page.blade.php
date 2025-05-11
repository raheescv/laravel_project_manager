<x-app-layout>
    @if (cache('sale_type') != 'pos')
        <div class="content__header content__boxed overlapping">
            <div class="content__wrap">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('sale::index') }}">Sale</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Page</li>
                    </ol>
                </nav>
                @if (!$id)
                    <h1 class="page-title mb-0 mt-2">Create Sale</h1>
                    <p class="lead">Create a new sale entry</p>
                @else
                    <h1 class="page-title mb-0 mt-2">Edit Sale</h1>
                    <p class="lead">Update existing sale entry</p>
                @endif
            </div>
        </div>
    @endif
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('sale.page', ['table_id' => $id])
        </div>
    </div>
    <x-account.customer-modal />
    <x-account.customer-view-modal />
    @if (cache('sale_type') == 'pos')
        <x-sale.custom-payment-modal />
        <x-sale.edit-item-modal />
        <x-sale.view-items-modal />
        <x-sale.draft-table-modal />
    @endif
    @push('styles')
        <style>
            /* Subtle input styling */
            .form-control-sm.text-end {
                background: transparent;
                border: 1px solid transparent;
                transition: all 0.2s ease;
                padding: 0.25rem 0.5rem;
                box-shadow: none;
            }

            .form-control-sm.text-end:hover {
                border-color: var(--bs-border-color);
                background-color: var(--bs-body-bg);
            }

            .form-control-sm.text-end:focus {
                background-color: var(--bs-body-bg);
                border-color: var(--bs-primary);
                box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.15);
            }

            /* Make number inputs more compact */
            input[type="number"].form-control-sm {
                -moz-appearance: textfield;
            }

            input[type="number"].form-control-sm::-webkit-outer-spin-button,
            input[type="number"].form-control-sm::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            /* Table cell alignment for inputs */
            .table>tbody>tr>td {
                vertical-align: middle;
                padding: 0.5rem;
            }

            /* Highlight row on hover */
            .table>tbody>tr:hover .form-control-sm.text-end {
                border-color: var(--bs-border-color);
            }
        </style>
    @endpush
    @push('scripts')
        <x-select.customerSelect />
        <x-select.employeeSelect />
        <x-select.inventoryProductSelect />
        <x-select.paymentMethodSelect />
        <x-select.comboOfferSelect />
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.addEventListener('print-invoice', function(event) {
                    if (event.detail[0].print) {
                        window.open(event.detail[0].link);
                    } else {
                        @if ($id)
                            window.location.href = "{{ route('sale::create') }}";
                        @endif
                    }
                });
            });
        </script>
        <script>
            $('#root').attr('class', 'root mn--push');
        </script>
    @endpush
</x-app-layout>
