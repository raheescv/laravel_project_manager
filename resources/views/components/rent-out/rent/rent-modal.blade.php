<div class="modal" id="RentOutRentModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @livewire('rent-out.rent.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleRentOutRentModal', event => {
            $('#RentOutRentModal').modal('toggle');
        });
    </script>
@endpush
