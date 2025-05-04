<div class="modal" id="CountryModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.country.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleCountryModal', event => {
            $('#CountryModal').modal('toggle');
        });
    </script>
@endpush
