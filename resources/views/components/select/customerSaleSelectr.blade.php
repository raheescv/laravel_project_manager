<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    const saleId = "{{ $sale_id }}"; // passed from Blade

    $('.select-customer_sales-list').each(function() {
        const select = new TomSelect(this, {
            persist: false,
            valueField: 'id',
            labelField: 'invoice_no',
            searchField: ['invoice_no', 'reference_no', 'id'],
            load: function(query, callback) {
                if (!saleId || saleId == 0) {
                    callback([]);
                    return;
                }

                const url = "{{ route('sale::single.invoice-list', ['sale_id' => $sale_id]) }}";

                fetch(url, { headers: { 'Cache-Control': 'no-cache' } })
                    .then(response => response.json())
                    .then(json => {
                        callback(json.items);
                        if (json.items.length > 0) {
                            select.setValue(json.items[0].id); // preselect invoice
                        }
                    })
                    .catch(err => {
                        console.error('Error loading data:', err);
                        callback([]);
                    });
            },
            onFocus: function() {
                this.clearOptions();
                this.load('');
            },
            render: {
                option: function(item, escape) {
                    return `<div>${escape(item.invoice_no)}${item.reference_no ? ` @${escape(item.reference_no)}` : ''}</div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.invoice_no)}</div>`;
                },
            },
        });
    });
});
</script>


