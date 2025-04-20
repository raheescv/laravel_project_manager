<div class="modal" id="ExpenseModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @livewire('account.expense.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleExpenseModal', event => {
            $('#ExpenseModal').modal('toggle');
        });
    </script>
@endpush
