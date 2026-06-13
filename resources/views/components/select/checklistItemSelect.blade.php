<script type="text/javascript">
    $('.select-checklist_item-list').each(function() {
        if (this.tomselect) {
            return;
        }
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            labelField: 'name',
            searchField: ['name', 'category'],
            load: function(query, callback) {
                var url = "{{ route('settings::checklist_item::list') }}";
                url += '?query=' + encodeURIComponent(query);
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
                this.clearOptions();
                this.load('');
            },
            onChange: function(value) {
                if (!value) {
                    return;
                }
                Livewire.dispatch('ChecklistTab-AddItem', { id: value });
                this.clear();
                this.clearOptions();
                this.blur();
            },
            render: {
                option: function(item, escape) {
                    return `<div class="py-1">
                                <div class="fw-semibold">${escape(item.name)}</div>
                                ${item.category ? `<small class="text-muted">${escape(item.category)}</small>` : ''}
                            </div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name || item.text || '')}</div>`;
                },
            },
        });
    });
</script>
