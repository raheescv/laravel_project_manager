<script type="text/javascript">
    $('.select-property_id-list').each(function() {
        var $el = $(this);
        var buildingSelector = $el.data('building-select') || null;
        var groupSelector = $el.data('group-select') || null;
        var typeSelector = $el.data('type-select') || null;
        new TomSelect(this, {
            plugins: ['clear_button', 'remove_button'],
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('property::property::list') }}";
                var params = 'query=' + encodeURIComponent(query);
                if (buildingSelector) {
                    var buildingEl = document.querySelector(buildingSelector);
                    var buildingId = buildingEl && buildingEl.tomselect ? buildingEl.tomselect.getValue() : (buildingEl ? buildingEl.value : '');
                    if (buildingId) params += '&building_id=' + encodeURIComponent(buildingId);
                }
                if (groupSelector) {
                    var groupEl = document.querySelector(groupSelector);
                    var groupId = groupEl && groupEl.tomselect ? groupEl.tomselect.getValue() : (groupEl ? groupEl.value : '');
                    if (groupId) params += '&property_group_id=' + encodeURIComponent(groupId);
                }
                if (typeSelector) {
                    var typeEl = document.querySelector(typeSelector);
                    var typeId = typeEl && typeEl.tomselect ? typeEl.tomselect.getValue() : (typeEl ? typeEl.value : '');
                    if (typeId) params += '&property_type_id=' + encodeURIComponent(typeId);
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
    $('.select-property_id').each(function() {
        var $el = $(this);
        var buildingSelector = $el.data('building-select') || null;
        var groupSelector = $el.data('group-select') || null;
        var typeSelector = $el.data('type-select') || null;
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'id'],
            load: function(query, callback) {
                var url = "{{ route('property::property::list') }}";
                var params = 'query=' + encodeURIComponent(query);
                if (buildingSelector) {
                    var buildingEl = document.querySelector(buildingSelector);
                    var buildingId = buildingEl && buildingEl.tomselect ? buildingEl.tomselect.getValue() : (buildingEl ? buildingEl.value : '');
                    if (buildingId) params += '&building_id=' + encodeURIComponent(buildingId);
                }
                if (groupSelector) {
                    var groupEl = document.querySelector(groupSelector);
                    var groupId = groupEl && groupEl.tomselect ? groupEl.tomselect.getValue() : (groupEl ? groupEl.value : '');
                    if (groupId) params += '&property_group_id=' + encodeURIComponent(groupId);
                }
                if (typeSelector) {
                    var typeEl = document.querySelector(typeSelector);
                    var typeId = typeEl && typeEl.tomselect ? typeEl.tomselect.getValue() : (typeEl ? typeEl.value : '');
                    if (typeId) params += '&property_type_id=' + encodeURIComponent(typeId);
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
