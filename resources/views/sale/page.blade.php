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
            </div>
        </div>
    @endif
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('sale.page', ['table_id' => $id])
        </div>
    </div>
    <x-account.customer-modal />
    @if (cache('sale_type') == 'pos')
        <x-sale.custom-payment-modal />
        <x-sale.edit-item-modal />
        <x-sale.view-items-modal />
        <x-sale.draft-table-modal />
    @endif
    @push('styles')
    @endpush
    @push('scripts')
        @include('components.select.customerSelect')
        @include('components.select.employeeSelect')
        @include('components.select.inventoryProductSelect')
        @include('components.select.paymentMethodSelect')

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.addEventListener('print-invoice', function(event) {
                    Swal.fire({
                        title: "<strong>Submitted successfully",
                        icon: "success",
                        html: `Do you want to Print Receipt for the this Order`,
                        showCloseButton: true,
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonText: ` Print Receipt `,
                        cancelButtonText: ` Next Order `,
                        cancelButtonAriaLabel: "Thumbs down"
                    }).then((result) => {
                        console.log(result);
                        if (result.isConfirmed) {
                            window.open(event.detail[0].link);
                        }
                        if (result.isDismissed) {
                            @if ($id)
                                window.location.href = "{{ route('sale::create') }}";
                            @endif
                        }
                    });
                });
            });
        </script>
        <script>
            $(document).ready(function() {
                $('#root').attr('class', 'root mn--push');
            })
        </script>
    @endpush
</x-app-layout>
