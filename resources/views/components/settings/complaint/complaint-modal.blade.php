<div class="modal fade" id="ComplaintModal" tabindex="-1" aria-labelledby="complaintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            @livewire('settings.complaint.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleComplaintModal', event => {
            $('#ComplaintModal').modal('toggle');
        });
    </script>
@endpush
