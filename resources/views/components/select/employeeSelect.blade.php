<style>
    .ts-dropdown-content {
        max-height: 400px !important;
    }
</style>
<script type="text/javascript">
    $('.select-employee_id-list').each(function() {
        new TomSelect(this, {
            persist: false,
            valueField: 'id',
            nameField: 'name',
            searchField: ['name', 'mobile', 'email', 'id'],
            dropdownParent: 'body',
            load: function(query, callback) {
                var url = "{{ route('users::list') }}";
                url += '?query=' + encodeURIComponent(query);
                url += '&type=employee';
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
</script>
