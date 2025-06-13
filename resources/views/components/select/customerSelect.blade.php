<style>
    /* Custom styles for customer select */
    .select-customer_id,
    .select-customer_id-list {
        --ts-option-padding: 0.75rem 1rem;
        --ts-option-bg-hover: #f8fafc;
        --ts-option-bg-active: #eff6ff;
        --ts-border-color: #e2e8f0;
        --ts-focus-color: #3b82f6;
        --ts-text-color: #1e293b;
        --ts-text-muted: #64748b;
    }

    .select-customer_id .ts-wrapper,
    .select-customer_id-list .ts-wrapper {
        border-radius: 0.5rem;
        border: 1px solid var(--ts-border-color);
        transition: all 0.2s ease;
    }

    .select-customer_id .ts-wrapper.focus,
    .select-customer_id-list .ts-wrapper.focus {
        border-color: var(--ts-focus-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .select-customer_id .ts-dropdown,
    .select-customer_id-list .ts-dropdown {
        border-radius: 0.5rem;
        border: 1px solid var(--ts-border-color);
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        margin-top: 0.5rem;
    }

    .select-customer_id .ts-dropdown .option,
    .select-customer_id-list .ts-dropdown .option {
        padding: var(--ts-option-padding);
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }

    .select-customer_id .ts-dropdown .option:last-child,
    .select-customer_id-list .ts-dropdown .option:last-child {
        border-bottom: none;
    }

    .select-customer_id .ts-dropdown .option:hover,
    .select-customer_id-list .ts-dropdown .option:hover {
        background-color: var(--ts-option-bg-hover);
    }

    .select-customer_id .ts-dropdown .option.active,
    .select-customer_id-list .ts-dropdown .option.active {
        background-color: var(--ts-option-bg-active);
    }

    .customer-option {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .customer-name {
        font-weight: 500;
        color: var(--ts-text-color);
    }

    .customer-mobile {
        font-size: 0.875rem;
        color: var(--ts-text-muted);
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .customer-mobile i {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .select-customer_id .ts-control,
    .select-customer_id-list .ts-control {
        padding: 0.5rem 0.75rem;
        min-height: 42px;
    }

    .select-customer_id .ts-control>input,
    .select-customer_id-list .ts-control>input {
        font-size: 0.875rem;
    }

    .select-customer_id .ts-control.single .ts-control:after,
    .select-customer_id-list .ts-control.single .ts-control:after {
        border-color: var(--ts-text-muted) transparent transparent transparent;
    }

    .select-customer_id .ts-control.single.dropdown-active .ts-control:after,
    .select-customer_id-list .ts-control.single.dropdown-active .ts-control:after {
        border-color: transparent transparent var(--ts-text-muted) transparent;
    }

    /* Multi-select specific styles */
    .select-customer_id .ts-control.multi,
    .select-customer_id-list .ts-control.multi {
        padding: 0.25rem 0.5rem;
    }

    .select-customer_id .ts-control.multi .item,
    .select-customer_id-list .ts-control.multi .item {
        background-color: var(--ts-option-bg-active);
        border-radius: 0.25rem;
        padding: 0.25rem 0.5rem;
        margin: 0.125rem;
    }

    .select-customer_id .ts-control.multi .item.active,
    .select-customer_id-list .ts-control.multi .item.active {
        background-color: var(--ts-focus-color);
        color: white;
    }
</style>

<script type="text/javascript">
    const customerSelectConfig = {
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
        }
    };

    $('.select-customer_id').each(function() {
        new TomSelect(this, {
            ...customerSelectConfig,
            create: function(input, callback) {
                Livewire.dispatch("Customer-Page-Create-Component", {
                    'name': input
                });
            },
        });
    });
    // Initialize for list select
    $('.select-customer_id-list').each(function() {
        new TomSelect(this, {
            ...customerSelectConfig,
            plugins: ['clear_button'],
        });
    });
</script>
