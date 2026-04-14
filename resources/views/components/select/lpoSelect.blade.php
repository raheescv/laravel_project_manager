<script type="text/javascript">
    $('.select-lpo_id-list').each(function() {
        var el = this;
        var options = @json(
            \App\Models\LocalPurchaseOrder::with('vendor')
                ->latest()
                ->limit(500)
                ->get()
                ->map(fn ($lpo) => ['id' => $lpo->id, 'text' => 'LPO #' . $lpo->id . ' - ' . ($lpo->vendor?->name ?? 'N/A')])
        );

        new TomSelect(el, {
            persist: false,
            valueField: 'id',
            labelField: 'text',
            searchField: ['text', 'id'],
            options: options,
            render: {
                option: function(item, escape) {
                    return `<div>${escape(item.text || '')}</div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.text || '')}</div>`;
                },
            },
        });
    });
</script>
