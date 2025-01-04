<script type="text/javascript">
    $('.select-product_id-list').each(function() {
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('product::list') }}";
                fetch(url + '?query=' + encodeURIComponent(query))
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
                    return `<div> ${ escape(item.name) } </div>`;
                },
                item: function(item, escape) {
                    return `<div>${ escape(item.name) }</div>`;
                },
            },
        });
    });
</script>
