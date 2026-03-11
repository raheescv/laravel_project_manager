{{-- Payment Term Checkbox Helper Scripts --}}
@push('scripts')
<script>
    function getSelectedTermIds() {
        var ids = [];
        document.querySelectorAll('.term-checkbox:checked').forEach(function(cb) {
            ids.push(parseInt(cb.value));
        });
        return ids;
    }

    function toggleSelectAllTerms() {
        var checkboxes = document.querySelectorAll('.term-checkbox');
        var allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        var headerCb = document.getElementById('selectAllTermsCheckbox');
        if (headerCb) headerCb.checked = !allChecked;
    }

    function deselectAllTerms() {
        document.querySelectorAll('.term-checkbox').forEach(cb => cb.checked = false);
        var headerCb = document.getElementById('selectAllTermsCheckbox');
        if (headerCb) headerCb.checked = false;
    }

    function deleteSelectedTerms() {
        var ids = getSelectedTermIds();
        if (!ids.length) {
            alert('Please select at least one row to delete.');
            return;
        }
        if (!confirm('Are you sure you want to delete ' + ids.length + ' selected term(s)?')) {
            return;
        }
        @this.deleteSelectedTerms(ids);
    }

    function paySelectedTerms() {
        var ids = getSelectedTermIds();
        if (!ids.length) {
            alert('Please select at least one row for payment.');
            return;
        }
        @this.openPaySelectedModal(ids);
    }
</script>
@endpush
