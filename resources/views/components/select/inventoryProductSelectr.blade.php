<style>
    .ts-dropdown-content {
        padding: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border-radius: 12px;
        max-height: 400px;
    }

    .dropdown-item {
        padding: 14px;
        border-radius: 10px;
        transition: all 0.2s ease;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        border: 1px solid transparent;
    }

    .dropdown-item:hover {
        background-color: #f1f5f9;
        transform: translateY(-1px);
        border-color: #e2e8f0;
    }

    .item-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        overflow: hidden;
        margin-right: 18px;
        background-color: #f8fafc;
        flex-shrink: 0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        border: 2px solid #e2e8f0;
    }

    .item-image {
        object-fit: cover;
        width: 100%;
        height: 100%;
        transition: transform 0.3s ease;
    }

    .dropdown-item:hover .item-image {
        transform: scale(1.05);
    }

    .item-content {
        flex: 1;
    }

    .item-name {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 6px;
        font-size: 1rem;
        letter-spacing: -0.01em;
    }

    .item-details {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        font-size: 0.85rem;
    }

    .item-details span {
        background-color: #f8fafc;
        padding: 5px 10px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }

    .item-details span:hover {
        background-color: #fff;
        border-color: #cbd5e1;
    }

    .item-details strong {
        color: #334155;
        font-weight: 600;
    }

    /* Styles for the selected item */
    .selected-item {
        font-weight: 600;
        color: #1e293b;
        padding: 6px 12px;
        background-color: #f8fafc;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
    }

    /* TomSelect wrapper styles */
    .ts-wrapper {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }

    .ts-wrapper.focus {
        border-color: #64748b;
        box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.1);
    }

    .ts-control {
        border-radius: 8px !important;
        padding: 8px 12px !important;
    }
</style>

<script type="text/javascript">
    function initInventoryProductSelect(saleId = null) {
        $('.select-inventory-product_id-list').each(function() {
            new TomSelect(this, {
                persist: false,
                valueField: 'id',
                nameField: 'name',
                searchField: ['name', 'barcode', 'code', 'batch', 'size', 'color', 'id'],
                load: function(query, callback) {
                    let url;
                    if (saleId) {
                        // Load single product by sale_id
                        url = "{{ route('inventory::product::by-sale') }}" + '/' + encodeURIComponent(saleId);
                    } else {
                        // Load all products
                        url = "{{ route('inventory::product::list') }}?query=" + encodeURIComponent(query);
                    }

                    fetch(url, { headers: { 'Cache-Control': 'no-cache' } })
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
                                    <img src="${escape(item.image || '{{ cache('logo') }}')}" width="100%" height="100%" alt="${escape(item.name)}" class="item-image">
                                </div>
                                <div class="item-content">
                                    <div class="item-name">${escape(item.name)}</div>`;
                        if (item.type == 'product') {
                            option += `<div class="item-details">
                                <span><strong>MRP:</strong> ${escape(item.mrp)}</span>
                                <span><strong>Barcode:</strong> ${escape(item.barcode)}</span>
                                ${item.size ? `<span><strong>Size:</strong> ${escape(item.size)}</span>` : ''}
                                ${item.code ? `<span><strong>Code:</strong> ${escape(item.code)}</span>` : ''}
                                ${item.color ? `<span><strong>Color:</strong> ${escape(item.color)}</span>` : ''}
                                ${item.batch ? `<span><strong>Batch:</strong> ${escape(item.batch)}</span>` : ''}
                            </div>`;
                        } else {
                            option += `<div class="item-details">
                                <span><strong>Price:</strong> ${escape(item.mrp)}</span>
                            </div>`;
                        }
                        option += `</div></div>`;
                        return option;
                    },
                    item: function(item, escape) {
                        return `<div class="selected-item">${escape(item.name || item.text || '')}</div>`;
                    },
                },
            });
        });
    }

    // Call this without saleId for all products
    initInventoryProductSelect();

    // Example: Call this with a sale ID to preselect a single product
    // initInventoryProductSelect(11338);
</script>
