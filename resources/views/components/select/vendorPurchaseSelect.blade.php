<script type="text/javascript">
    $('.select-vendor_purchase-list').each(function() {
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            labelField: 'invoice_no',
            searchField: ['invoice_no', 'id'],
            load: function(query, callback) {
                var url = "{{ route('purchase::invoice-list') }}";
                let accountId = $('#account_id').val(); // Correct selector
                url += '?query=' + encodeURIComponent(query);
                url += '&account_id=' + encodeURIComponent(accountId);
                fetch(url, {
                        headers: {
                            'Cache-Control': 'no-cache',
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(json => callback(json.items))
                    .catch(err => {
                        console.error('Error loading data:', err);
                        callback();
                    });
            },
            onFocus: function() {
                this.clearOptions();
                this.load('');
            },
            render: {
                option: function(item, escape) {
                    return `<div> ${escape(item.invoice_no || item.text || '')} </div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.invoice_no || item.text || '')}</div>`;
                },
            },
        });
    });
</script>
