<div class="modal" id="AccountModal" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('account.page')
        </div>
    </div>
</div>
@push('scripts')
    <x-select.accountCategorySelect />
    <script>
        window.addEventListener('ToggleAccountModal', event => {
            $('#AccountModal').modal('toggle');
        });
    </script>
@endpush
