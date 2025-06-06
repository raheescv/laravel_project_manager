<script type="text/javascript">
    $('.select-customer_type-id-list').each(function() {
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('settings::customer_type::list') }}";
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
                    return `<div>${escape(item.name || item.text || '')}</div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name || item.text || '')}</div>`;
                },
            },
        });
    });
    $('.select-customer_type-id').each(function() {
        new TomSelect(this, {
            plugins: ['clear_button', 'remove_button'],
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('settings::customer_type::list') }}";
                fetch(url + '?query=' + encodeURIComponent(query)).then(response => response.json()).then(json => {
                    callback(json.items);
                }).catch(() => {
                    callback();
                });
            },
            onFocus: function() {
                this.clearOptions();
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
            create: function(input, callback) {
                Livewire.dispatch("CustomerType-Page-Create-Component", {
                    'name': input
                });
            },
        });
    });
</script>
