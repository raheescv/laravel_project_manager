<div>
    <div class="modal-header bg-light">
        <h1 class="modal-title fs-5">
            <i class="fa fa-file-text me-2 text-primary"></i>
            General Voucher
        </h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger p-2 mb-3">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <strong>Please correct the following errors:</strong>
                    <ul class="mb-0 ps-3 mt-1">
                        @foreach ($this->getErrorBag()->toArray() as $field => $errors)
                            <li> {{ $errors[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0">
                        <i class="fa fa-list-alt me-1 text-primary"></i>
                        Journal Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date" class="form-label small fw-medium">
                                    <i class="fa fa-calendar me-1 text-muted"></i>
                                    Journal Date <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    {{ html()->input('date')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'journals.date') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="reference_number" class="form-label small fw-medium">
                                    <i class="fa fa-tag me-1 text-muted"></i>
                                    Journal No.
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-tag"></i>
                                    </span>
                                    {{ html()->text('reference_number')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'journals.reference_number')->placeholder('Adjustments 2024') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="description" class="form-label small fw-medium">
                                    <i class="fa fa-comment me-1 text-muted"></i>
                                    Description
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-comment"></i>
                                    </span>
                                    {{ html()->textarea('description')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'journals.description')->rows(1) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive" style="overflow: visible;">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th width="30%">Account</th>
                                    <th class="text-end" style="width: 150px;">Debits</th>
                                    <th class="text-end" style="width: 150px;">Credits</th>
                                    <th>Description</th>
                                    <th>Name</th>
                                    <th style="width: 50px;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entries as $index => $entry)
                                    <tr wire:key="entry-{{ $entry['key'] }}">
                                        <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                        <td>
                                            <div wire:ignore>
                                                {{ html()->select('account_id', [])->value($entry['account_id'] ?? '')->class('select-account_id')->id('account_id_' . $entry['key'])->attribute('data-entry-key', $entry['key'])->attribute('data-entry-index', $index)->attribute('data-account-id', $entry['account_id'] ?? '')->attribute('data-account-name', $entry['account_name'] ?? '')->placeholder('Select Account') }}
                                            </div>
                                        </td>
                                        <td>
                                            {{ html()->number('debit')->value($entry['debit'] ?? 0)->class('form-control form-control-sm text-end')->attribute('step', '0.01')->attribute('min', 0)->attribute('wire:model.live', 'entries.' . $index . '.debit') }}
                                        </td>
                                        <td>
                                            {{ html()->number('credit')->value($entry['credit'] ?? 0)->class('form-control form-control-sm text-end')->attribute('step', '0.01')->attribute('min', 0)->attribute('wire:model.live', 'entries.' . $index . '.credit') }}
                                        </td>
                                        <td>
                                            {{ html()->text('description')->value($entry['description'] ?? '')->class('form-control form-control-sm')->attribute('wire:model.live', 'entries.' . $index . '.description') }}
                                        </td>
                                        <td>
                                            {{ html()->text('name')->value($entry['name'] ?? '')->class('form-control form-control-sm')->attribute('wire:model', 'entries.' . $index . '.person_name') }}
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" wire:click="removeEntry('{{ $entry['key'] }}')" wire:confirm="Are you sure you want to remove this entry?" class="btn btn-sm btn-link text-danger p-0" title="Remove Entry">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td colspan="2" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold">{{ currency(array_sum(array_column($entries, 'debit'))) }}</td>
                                    <td class="text-end fw-bold">{{ currency(array_sum(array_column($entries, 'credit'))) }}</td>
                                    <td colspan="3">
                                        <button type="button" wire:click="addEntry" class="btn btn-sm btn-success rounded-circle" title="Add New Entry">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer bg-light">
            <div class="d-flex justify-content-between w-100">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i>
                    Cancel
                </button>
                <div>
                    <button type="button" wire:click="save(1)" class="btn btn-success">
                        <i class="fa fa-save me-1"></i>
                        Save & Add New
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check me-1"></i>
                        Save
                    </button>
                </div>
            </div>
        </div>
    </form>
    @push('styles')
        <style>
            /* Ensure TomSelect dropdown appears above everything */
            .ts-dropdown {
                z-index: 9999 !important;
            }
            /* Ensure the table container allows dropdown to overflow */
            .table-responsive {
                overflow: visible !important;
            }
            /* Ensure table cells don't clip the dropdown */
            .table td {
                position: relative;
                overflow: visible !important;
            }
            /* Ensure card body allows overflow for dropdown */
            .card-body .table-responsive {
                overflow: visible !important;
            }
        </style>
    @endpush
    @push('scripts')
        <script>
            $(document).ready(function() {
                // Initialize account selects for entries
                function initializeAccountSelects() {
                    $('.select-account_id[data-entry-key]').each(function() {
                        const $select = $(this);
                        const entryKey = $select.data('entry-key');
                        const selectId = $select.attr('id');

                        // Check if TomSelect is already initialized
                        if ($select[0].tomselect) {
                            return;
                        }

                        const tomSelect = new TomSelect(this, {
                            persist: false,
                            plugins: ['remove_button'],
                            valueField: 'id',
                            nameField: 'name',
                            searchField: ['name', 'id'],
                            load: function(query, callback) {
                                var url = "{{ route('account::list') }}";
                                url += '?query=' + encodeURIComponent(query);
                                fetch(url)
                                    .then(response => {
                                        if (!response.ok) throw new Error('Network response was not ok');
                                        return response.json();
                                    })
                                    .then(json => {
                                        callback(json.items);
                                    })
                                    .catch(err => {
                                        console.error('Error loading data:', err);
                                        callback();
                                    });
                            },
                            onFocus: function() {
                                this.clearOptions();
                                this.load('');
                            },
                            onChange: function(value) {
                                // Get the entry index from data attribute
                                const entryIndex = $select.data('entry-index');

                                if (entryIndex !== undefined && entryIndex !== null) {
                                    @this.set('entries.' + entryIndex + '.account_id', value || null);
                                    if (value) {
                                        // Get account name from the option
                                        const option = this.options[value];
                                        if (option) {
                                            @this.set('entries.' + entryIndex + '.account_name', option.name || null);
                                        }
                                    }
                                }
                            },
                            render: {
                                option: function(item, escape) {
                                    return `<div>${escape(item.name || item.text || '')}</div>`;
                                },
                                item: function(item, escape) {
                                    return `<div>${escape(item.name || item.text || '')}</div>`;
                                },
                            },
                            onDropdownOpen: function() {
                                // Ensure dropdown has high z-index when opened
                                const dropdown = this.dropdown;
                                if (dropdown) {
                                    $(dropdown).css({
                                        'z-index': '9999',
                                        'position': 'absolute'
                                    });
                                }
                            },
                        });

                        // Ensure dropdown wrapper has high z-index after initialization
                        setTimeout(() => {
                            if (tomSelect.dropdown) {
                                $(tomSelect.dropdown).css('z-index', '9999');
                            }
                        }, 100);

                        // Set initial value if exists (after a short delay to ensure TomSelect is ready)
                        // Get value from data attribute or select value (data attribute is more reliable)
                        let initialValue = $select.data('account-id') || $select.val();
                        // Convert to string if it's a number (TomSelect expects string IDs)
                        if (initialValue) {
                            initialValue = String(initialValue);
                        }
                        const accountName = $select.data('account-name');
                        console.log('Initial Value:', initialValue);
                        console.log('Account Name:', accountName);
                        if (initialValue && initialValue !== '0' && initialValue !== '') {
                            setTimeout(() => {
                                if (accountName) {
                                    // We have the account name, add it as an option and set the value
                                    tomSelect.addOption({id: initialValue, name: accountName});
                                    tomSelect.setValue(initialValue);
                                } else {
                                    // Load accounts first, then try to set the value
                                    tomSelect.load('', function() {
                                        // Check if the account is in the loaded options (compare as strings)
                                        const account = Object.values(tomSelect.options).find(opt => String(opt.id) === String(initialValue));
                                        if (account) {
                                            tomSelect.setValue(initialValue);
                                        } else {
                                            // Account not in loaded list, fetch it specifically
                                            fetch("{{ route('account::list') }}?query=" + encodeURIComponent(initialValue))
                                                .then(response => response.json())
                                                .then(json => {
                                                    if (json.items && json.items.length > 0) {
                                                        const foundAccount = json.items.find(item => String(item.id) === String(initialValue));
                                                        if (foundAccount) {
                                                            tomSelect.addOption(foundAccount);
                                                            tomSelect.setValue(initialValue);
                                                        }
                                                    }
                                                })
                                                .catch(() => {
                                                    // If fetch fails, just try to set the value anyway
                                                    tomSelect.setValue(initialValue);
                                                });
                                        }
                                    });
                                }
                            }, 100);
                        }
                    });
                }

                // Initialize on page load
                initializeAccountSelects();

                // Re-initialize when Livewire updates
                Livewire.hook('morph.updated', () => {
                    setTimeout(() => {
                        initializeAccountSelects();
                    }, 100);
                });

                // Listen for reinitialize event
                window.addEventListener('reinitialize-selects', () => {
                    setTimeout(() => {
                        initializeAccountSelects();
                    }, 100);
                });

                window.addEventListener('SelectDropDownValues', event => {
                    const journals = event.detail[0];
                    // Handle pre-selected values if needed for editing
                    if (journals && journals.entries) {
                        setTimeout(() => {
                            initializeAccountSelects();
                        }, 200);
                    }
                });
            });
        </script>
    @endpush
</div>
