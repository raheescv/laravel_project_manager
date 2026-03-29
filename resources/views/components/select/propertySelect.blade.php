<style>
    .prop-card {
        padding: 14px;
        border-radius: 10px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .prop-card:hover {
        background-color: #f1f5f9;
        transform: translateY(-1px);
        border-color: #e2e8f0;
    }

    .prop-status-indicator {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }

    .prop-status-indicator.vacant { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #166534; }
    .prop-status-indicator.occupied { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #991b1b; }
    .prop-status-indicator.booked { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
    .prop-status-indicator.sold { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #3730a3; }
    .prop-status-indicator.default { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #475569; }

    .prop-body {
        flex: 1;
        min-width: 0;
    }

    .prop-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 6px;
    }

    .prop-name {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.95rem;
        line-height: 1.2;
    }

    .prop-badge {
        font-size: 0.65rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        flex-shrink: 0;
        white-space: nowrap;
    }

    .prop-badge.vacant { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .prop-badge.occupied { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .prop-badge.booked { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .prop-badge.sold { background-color: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }
    .prop-badge.default { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    .prop-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .prop-tag {
        font-size: 0.72rem;
        padding: 2px 8px;
        border-radius: 5px;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        white-space: nowrap;
        transition: all 0.15s ease;
    }

    .prop-card:hover .prop-tag {
        background-color: #fff;
        border-color: #cbd5e1;
    }

    .prop-tag i {
        margin-right: 3px;
        font-size: 0.6rem;
        opacity: 0.6;
    }

    .prop-tag strong {
        color: #334155;
        font-weight: 600;
    }

    .prop-rent-tag {
        background-color: #eff6ff;
        border-color: #bfdbfe;
        color: #1e40af;
        font-weight: 600;
    }
</style>
<script type="text/javascript">
    function propertyOptionRender(item, escape) {
        var s = (item.status || 'default').toLowerCase();
        if (!['vacant','occupied','booked','sold'].includes(s)) s = 'default';

        var icons = { vacant: 'fa-check-circle', occupied: 'fa-user', booked: 'fa-bookmark', sold: 'fa-handshake-o', default: 'fa-home' };

        var html = `<div class="prop-card">
            <div class="prop-status-indicator ${s}"><i class="fa ${icons[s]}"></i></div>
            <div class="prop-body">
                <div class="prop-header">
                    <span class="prop-name">${escape(item.name || item.text || '')}</span>
                    <span class="prop-badge ${s}">${escape(item.status || '')}</span>
                </div>
                <div class="prop-tags">`;

        if (item.building) html += `<span class="prop-tag"><i class="fa fa-building-o"></i> ${escape(item.building)}</span>`;
        if (item.group) html += `<span class="prop-tag"><i class="fa fa-folder-open"></i> ${escape(item.group)}</span>`;
        if (item.type) html += `<span class="prop-tag"><i class="fa fa-tag"></i> ${escape(item.type)}</span>`;
        if (item.floor) html += `<span class="prop-tag"><i class="fa fa-th-list"></i> <strong>Floor:</strong> ${escape(item.floor)}</span>`;
        if (item.rooms) html += `<span class="prop-tag"><i class="fa fa-bed"></i> <strong>Rooms:</strong> ${escape(item.rooms)}</span>`;
        if (item.size) html += `<span class="prop-tag"><i class="fa fa-arrows-alt"></i> ${escape(item.size)} sqm</span>`;
        if (item.rent) html += `<span class="prop-tag prop-rent-tag"><i class="fa fa-money"></i> QAR ${escape(item.rent)}</span>`;

        html += `</div>
            </div>
        </div>`;
        return html;
    }

    function propertyItemRender(item, escape) {
        return `<div>${escape(item.name || item.text || '')}</div>`;
    }

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
                option: propertyOptionRender,
                item: propertyItemRender,
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
                option: propertyOptionRender,
                item: propertyItemRender,
            },
        });
    });
</script>
