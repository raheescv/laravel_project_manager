<script type="text/javascript">
    $('.select-vendor_id-list').each(function() {
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'mobile', 'email', 'id'],
            load: function(query, callback) {
                var url = "{{ route('account::list') }}";
                url += '?query=' + encodeURIComponent(query);
                url += '&model=vendor';
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
                    return `<div> ${escape(item.name || item.text || '')}${item.mobile ? `@${escape(item.mobile)}` : ''} </div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name || item.text || '')}</div>`;
                },
            },
        });
    });
    $('.select-vendor_id').each(function() {
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'mobile', 'email', 'id'],
            load: function(query, callback) {
                var url = "{{ route('account::list') }}";
                url += '?query=' + encodeURIComponent(query);
                url += '&model=vendor';
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
                    return `<div> ${escape(item.name || item.text || '')}${item.mobile ? `@${escape(item.mobile)}` : ''} </div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name || item.text || '')}</div>`;
                },
            },
            create: function(input, callback) {
                Livewire.dispatch("Vendor-Page-Create-Component", {
                    'name': input
                });
            },
        });
    });
</script>
