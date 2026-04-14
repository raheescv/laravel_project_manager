<style>
    .cust-card {
        padding: 14px;
        border-radius: 10px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .cust-card:hover {
        background-color: #f1f5f9;
        transform: translateY(-1px);
        border-color: #e2e8f0;
    }

    .cust-avatar {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        color: #1e40af;
    }

    .cust-body {
        flex: 1;
        min-width: 0;
    }

    .cust-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 6px;
    }

    .cust-name {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.95rem;
        line-height: 1.2;
    }

    .cust-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .cust-tag {
        font-size: 0.72rem;
        padding: 2px 8px;
        border-radius: 5px;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        white-space: nowrap;
        transition: all 0.15s ease;
    }

    .cust-card:hover .cust-tag {
        background-color: #fff;
        border-color: #cbd5e1;
    }

    .cust-tag i {
        margin-right: 3px;
        font-size: 0.6rem;
        opacity: 0.6;
    }

    .cust-tag strong {
        color: #334155;
        font-weight: 600;
    }

    .cust-tag-email {
        background-color: #eff6ff;
        border-color: #bfdbfe;
        color: #1e40af;
    }
</style>

<script type="text/javascript">
    function customerOptionRender(item, escape) {
        var html = `<div class="cust-card">
            <div class="cust-avatar"><i class="fa fa-user"></i></div>
            <div class="cust-body">
                <div class="cust-header">
                    <span class="cust-name">${escape(item.name || item.text || '')}</span>
                </div>
                <div class="cust-tags">`;

        if (item.mobile) html += `<span class="cust-tag"><i class="fa fa-phone"></i> ${escape(item.mobile)}</span>`;
        if (item.email) html += `<span class="cust-tag cust-tag-email"><i class="fa fa-envelope-o"></i> ${escape(item.email)}</span>`;

        html += `</div>
            </div>
        </div>`;
        return html;
    }

    function customerItemRender(item, escape) {
        return `<div>${escape(item.name || item.text || '')}</div>`;
    }

    const customerBaseConfig = {
        persist: false,
        valueField: 'id',
        nameField: 'name',
        searchField: ['name', 'mobile', 'email', 'id'],
        load: function(query, callback) {
            var url = "{{ route('account::list') }}?query=" + encodeURIComponent(query) + '&model=customer';
            fetch(url, { headers: { 'Cache-Control': 'no-cache' } })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(json => callback(json.items))
                .catch(err => { console.error('Error loading data:', err); callback(); });
        },
        onFocus: function() { this.load(''); },
        render: { option: customerOptionRender, item: customerItemRender },
    };

    $('.select-customer_id').each(function() {
        new TomSelect(this, {
            ...customerBaseConfig,
            create: function(input) {
                Livewire.dispatch("Customer-Page-Create-Component", { 'name': input });
            },
        });
    });

    $('.select-customer_id-list').each(function() {
        new TomSelect(this, { ...customerBaseConfig, plugins: [] });
    });
</script>
