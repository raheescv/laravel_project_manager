<script type="text/javascript">
    $('.select-brand-list').each(function() {
        new TomSelect(this, {
            plugins: ['clear_button', 'remove_button'],
            persist: true,
            valueField: 'brand',
            nameField: 'brand',
            searchField: ['brand'],
            load: function(query, callback) {
                var url = "{{ route('settings::brand::list') }}";
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
                    return `<div>${escape(item.brand || item.text || '')}</div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.brand || item.text || '')}</div>`;
                },
            },
        });
    });
</script>
