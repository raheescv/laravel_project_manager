<script type="text/javascript">
    $('.select-payment_method_id-list').each(function() {
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('account::list') }}";
                url += '?query=' + encodeURIComponent(query);
                url += '&is_payment_method=1';
                fetch(url)
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
                this.load('');
            },
            render: {
                option: function(item, escape) {
                    return `<div>${escape(item.name || item.text || '')}</div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name || item.text || '')}</div>`;
                },
            },
        });
    });
</script>
