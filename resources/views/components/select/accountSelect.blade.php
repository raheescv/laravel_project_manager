<script type="text/javascript">
    $('.select-account_id-list').each(function() {
        new TomSelect(this, {
            persist: false,
            plugins: ['remove_button'],
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('account::list') }}";
                url += '?query=' + encodeURIComponent(query);
                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(json => {
                        callback(json.items);
                    })
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
    $('.select-account_id').each(function(index, el) {
        const account_type = el.getAttribute('account_type') || null;
        new TomSelect(this, {
            persist: false,
            plugins: ['remove_button'],
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('account::list') }}";
                url += '?query=' + encodeURIComponent(query);
                if (account_type) {
                    url += '&account_type=' + account_type;
                }
                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(json => {
                        callback(json.items);
                    })
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
                    return `<div>${escape(item.name || item.text || '')}</div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name || item.text || '')}</div>`;
                },
            },
            create: function(input, callback) {
                Livewire.dispatch("Account-Page-Create-Component", {
                    'name': input,
                    'account_type': account_type
                });
            },
        });
    });
</script>
