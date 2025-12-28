<div class="modal" id="PackageModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('package.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('TogglePackageModal', event => {
            $('#PackageModal').modal('toggle');
        });
    </script>
@endpush

