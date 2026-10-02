<script type="text/javascript">
    function propertyTypeParams(input, query) {
        var params = 'query=' + encodeURIComponent(query);
        var scopes = { 'group-select': 'property_group_id', 'building-select': 'property_building_id' };
        Object.keys(scopes).forEach(function(attr) {
            var selector = $(input).data(attr);
            var el = selector ? document.querySelector(selector) : null;
            var value = el ? (el.tomselect ? el.tomselect.getValue() : el.value) : '';
            if (value) params += '&' + scopes[attr] + '=' + encodeURIComponent(value);
        });
        return params;
    }
    $('.select-property_type_id-list').each(function() {
        if (this.tomselect) {
            return;
        }
        new TomSelect(this, {
            plugins: ['remove_button'],
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('property::type::list') }}";
                fetch(url + '?' + propertyTypeParams(this.input, query))
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
    $('.select-property_type_id').each(function() {
        if (this.tomselect) {
            return;
        }
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('property::type::list') }}";
                fetch(url + '?' + propertyTypeParams(this.input, query)).then(response => response.json()).then(json => {
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
        });
    });
</script>
