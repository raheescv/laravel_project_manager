<div class="modal" id="AccountCategoryModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.account-category.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleAccountCategoryModal', event => {
            $('#AccountCategoryModal').modal('toggle');
        });
    </script>
@endpush

