<script type="text/javascript">
    $('.select-product_id-list').each(function() {
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'barcode', 'mrp', 'cost', 'size', 'color', 'id'],
            load: function(query, callback) {
                var url = "{{ route('product::list') }}";
                fetch(url + '?query=' + encodeURIComponent(query) + '&type=product')
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
                    var option = `
                        <div class="dropdown-item d-flex align-items-center">
                            <div class="item-icon">
                                <img src="${escape(item.thumbnail || '{{ cache('logo') }}')}" width="50%" alt="${escape(item.name)}" class="item-image">
                            </div>
                            <div class="item-content">
                                <div class="item-name">${escape(item.name)}</div>`;
                    if (item.type == 'product') {
                        option += ` <div class="item-details">
                                        <span><strong>MRP:</strong> ${escape(item.mrp)}</span>
                                        <span><strong>Cost:</strong> ${escape(item.cost)}</span>
                                        <span><strong>Barcode:</strong> ${escape(item.barcode)}</span>
                                        ${item.size ? `<span><strong>Size:</strong> ${escape(item.size)}</span>`:''}
                                        ${item.color ? `<span><strong>Size:</strong> ${escape(item.color)}</span>`:''}
                                    </div>`;
                    } else {
                        option += ` <div class="item-details">
                                        <span><strong>Price:</strong> ${escape(item.mrp)}</span>
                                    </div>`;
                    }
                    option += `
                                </div>
                            </div>
                        </div>
                    `;
                    return option;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name || item.text || '')}</div>`;
                },
            },
        });
    });
</script>
