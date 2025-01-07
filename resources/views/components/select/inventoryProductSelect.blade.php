<script type="text/javascript">
    $('.select-inventory-product_id-list').each(function() {
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'barcode', 'batch', 'size', 'color', 'id'],
            load: function(query, callback) {
                var url = "{{ route('inventory::product::list') }}";
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
                    return `
                        <div class="dropdown-item d-flex align-items-center">
                            <div class="item-icon">
                                <img src="${escape(item.image || '{{ asset('assets/img/profile-photos/1.png') }}')}" width="50%" alt="${escape(item.name)}" class="item-image">
                            </div>
                            <div class="item-content">
                                <div class="item-name">${escape(item.name)}</div>
                                <div class="item-details">
                                    <span><strong>MRP:</strong> ${escape(item.mrp)}</span>
                                    <span><strong>Barcode:</strong> ${escape(item.barcode)}</span>
                                    <span><strong>Quantity:</strong> ${escape(item.quantity)}</span>
                                    ${item.size ? `<span><strong>Size:</strong> ${escape(item.size)}</span>`:''}
                                    ${item.color ? `<span><strong>Size:</strong> ${escape(item.color)}</span>`:''}
                                    ${item.batch ? `<span><strong>Size:</strong> ${escape(item.batch)}</span>`:''}
                                </div>
                            </div>
                        </div>
                    `;
                },
                item: function(item, escape) {
                    return `<div>${ escape(item.name) }</div>`;
                },
            },
        });
    });
</script>
