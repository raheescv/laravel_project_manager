<div class="modal" id="IncomeModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @livewire('account.income.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleIncomeModal', event => {
            $('#IncomeModal').modal('toggle');
        });
    </script>
@endpush
