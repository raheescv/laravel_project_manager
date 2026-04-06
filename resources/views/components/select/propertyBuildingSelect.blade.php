<script type="text/javascript">
    $('.select-property_building_id-list').each(function() {
        var $el = $(this);
        var groupSelector = $el.data('group-select') || null;
        new TomSelect(this, {
            plugins: ['remove_button'],
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('property::building::list') }}";
                var params = 'query=' + encodeURIComponent(query);
                if (groupSelector) {
                    var groupEl = document.querySelector(groupSelector);
                    var groupId = groupEl && groupEl.tomselect ? groupEl.tomselect.getValue() : (groupEl ? groupEl.value : '');
                    if (groupId) params += '&property_group_id=' + encodeURIComponent(groupId);
                }
                fetch(url + '?' + params)
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
    $('.select-property_building_id').each(function() {
        var $el = $(this);
        var groupSelector = $el.data('group-select') || null;
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('property::building::list') }}";
                var params = 'query=' + encodeURIComponent(query);
                if (groupSelector) {
                    var groupEl = document.querySelector(groupSelector);
                    var groupId = groupEl && groupEl.tomselect ? groupEl.tomselect.getValue() : (groupEl ? groupEl.value : '');
                    if (groupId) params += '&property_group_id=' + encodeURIComponent(groupId);
                }
                fetch(url + '?' + params).then(response => response.json()).then(json => {
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
