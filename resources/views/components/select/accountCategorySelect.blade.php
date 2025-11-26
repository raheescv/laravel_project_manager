<script type="text/javascript">
    $('.select-account_category_id-list').each(function() {
        new TomSelect(this, {
            plugins: ['clear_button', 'remove_button'],
            persist: true,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('settings::account_category::list') }}";
                url += '?query=' + encodeURIComponent(query);
                fetch(url).then(response => {
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
    $('.select-account_category_id').each(function() {
        new TomSelect(this, {
            persist: true,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('settings::account_category::list') }}";
                url += '?query=' + encodeURIComponent(query);
                if (typeof $('#main_account_category_id').val() != 'undefined') {
                    url += '&parent_id=' + $('#main_account_category_id').val();
                }
                fetch(url).then(response => response.json()).then(json => {
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
                callback({
                    id: 'add ' + input,
                    name: input
                });
            },
        });
    });
    $('.select-account_category_id-parent').each(function() {
        new TomSelect(this, {
            persist: true,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('settings::account_category::list') }}";
                url += '?query=' + encodeURIComponent(query);
                url += '&is_parent=1';
                fetch(url).then(response => response.json()).then(json => {
                    callback(json.items);
                }).catch(() => {
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
            create: function(input, callback) {
                callback({
                    id: 'add ' + input,
                    name: input
                });
            },
        });
    });
</script>

