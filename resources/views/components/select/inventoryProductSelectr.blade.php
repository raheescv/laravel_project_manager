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
        const ts = new TomSelect(this, {
            persist: false,
            valueField: 'id',
            labelField: 'name',
            searchField: ['name', 'barcode', 'code', 'batch', 'size', 'color', 'id'],
            load: function(query, callback) {
                let url;
                if (saleId) {
                    // Load products by sale ID
                    url = "{{ route('inventory::product::pro.productBySale', '') }}/" + encodeURIComponent(saleId);
                } 

                fetch(url, { headers: { 'Cache-Control': 'no-cache' } })
                    .then(res => res.json())
                    .then(json => {
                        callback(json.items);

                        // Auto-select first product if saleId provided
                        if (saleId && json.items.length > 0) {
                            ts.setValue(json.items[0].id);
                        }
                    })
                    .catch(err => callback());
            },
            onFocus: function() { this.load(''); },
            render: { /* keep your render code */ }
        });
    });
}

    // Call this without saleId for all products
    initInventoryProductSelect();

    // Example: Call this with a sale ID to preselect a single product
    // initInventoryProductSelect(11338);
</script>
