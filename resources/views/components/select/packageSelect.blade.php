<script type="text/javascript">
    $('.select-package_id-list').each(function() {
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'amount', 'description', 'service_count'],
            load: function(query, callback) {
                var url = "{{ route('service::package::list') }}";
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
                this.load('');
            },
            render: {
                option: function(item, escape) {
                    return `
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom hover:bg-gray-50">
                            <div class="flex-grow-1">
                                <div class="font-weight-bold text-lg text-primary">${escape(item.name || '')}</div>
                                ${item.description ? `<div class="text-muted text-sm mt-1">${escape(item.description)}</div>` : ''}
                            </div>
                            <div class="ml-4 text-right">
                                <div class="text-success font-weight-bold text-lg">${escape(item.amount ? '₹' + item.amount : '')}</div>
                                ${item.service_count ? `<div class="badge bg-primary text-white rounded-pill px-3 py-1 mt-1">${escape(item.service_count)} services</div>` : ''}
                            </div>
                        </div>`;
                },
                item: function(item, escape) {
                    return `
                        <div class="d-flex align-items-center gap-2 py-1 px-2">
                            <span class="font-medium text-indigo-600">${escape(item.name || '')}</span>
                            ${item.amount ? `
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                    ₹${escape(item.amount)}
                                </span>` : ''}
                        </div>`;
                },
            },
        });
    });
</script>
