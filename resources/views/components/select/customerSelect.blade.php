<style>
    /* Custom styles for customer select */
    .select-customer_id .ts-wrapper {
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }

    .select-customer_id .ts-wrapper.focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .select-customer_id .ts-dropdown {
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        margin-top: 0.5rem;
    }

    .select-customer_id .ts-dropdown .option {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }

    .select-customer_id .ts-dropdown .option:last-child {
        border-bottom: none;
    }

    .select-customer_id .ts-dropdown .option:hover {
        background-color: #f8fafc;
    }

    .select-customer_id .ts-dropdown .option.active {
        background-color: #eff6ff;
    }

    .select-customer_id .customer-option {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .select-customer_id .customer-name {
        font-weight: 500;
        color: #1e293b;
    }

    .select-customer_id .customer-mobile {
        font-size: 0.875rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .select-customer_id .customer-mobile i {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .select-customer_id .ts-control {
        padding: 0.5rem 0.75rem;
        min-height: 42px;
    }

    .select-customer_id .ts-control>input {
        font-size: 0.875rem;
    }

    .select-customer_id .ts-control.single .ts-control:after {
        border-color: #64748b transparent transparent transparent;
    }

    .select-customer_id .ts-control.single.dropdown-active .ts-control:after {
        border-color: transparent transparent #64748b transparent;
    }
</style>

<script type="text/javascript">
    $('.select-customer_id-list').each(function() {
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'mobile', 'email', 'id'],
            load: function(query, callback) {
                var url = "{{ route('account::list') }}";
                url += '?query=' + encodeURIComponent(query);
                url += '&model=customer';
                fetch(url)
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
                    return `<div> ${escape(item.name || item.text || '')}${item.mobile ? `@${escape(item.mobile)}` : ''} </div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name || item.text || '')}</div>`;
                },
            },
        });
    });
    $('.select-customer_id').each(function() {
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'mobile', 'email', 'id'],
            load: function(query, callback) {
                var url = "{{ route('account::list') }}";
                url += '?query=' + encodeURIComponent(query);
                url += '&model=customer';
                fetch(url, {
                        headers: {
                            'Cache-Control': 'no-cache',
                        }
                    })
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
                        <div class="customer-option">
                            <div class="customer-name">${escape(item.name || item.text || '')}</div>
                            ${item.mobile ? `
                                <div class="customer-mobile">
                                    <i class="fa fa-phone"></i>
                                    ${escape(item.mobile)}
                                </div>
                            ` : ''}
                        </div>
                    `;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name || item.text || '')}</div>`;
                },
            },
            create: function(input, callback) {
                Livewire.dispatch("Customer-Page-Create-Component", {
                    'name': input
                });
            },
        });
    });
</script>
