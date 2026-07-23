<div>
    <div class="row g-3">
        <div class="col-12 col-lg-8">
        <div class="panel h-100">
            <div class="phead">
                <span class="ic"><i class="fa fa-bar-chart"></i></span>
                <div>
                    <h4>Grouped Item Summary</h4>
                    <span class="hint">How often each product is bought</span>
                </div>
                <div class="right">
                    <span class="tag mute">{{ $rows->count() }} {{ $rows->count() == 1 ? 'product' : 'products' }}</span>
                </div>
            </div>
            <div class="pbody barlist">
                @forelse ($rows as $row)
                    <div class="barrow">
                        <div class="bl">
                            <div class="nm">
                                @if ($row->product_id)
                                    <a href="{{ route('inventory::product::view', $row->product_id) }}" target="_blank">{{ $row->product_name }}</a>
                                @else
                                    {{ $row->product_name }}
                                @endif
                            </div>
                            <div class="bar"><i style="width: {{ max(round(($row->count / $max) * 100), 3) }}%"></i></div>
                        </div>
                        <div class="qt">{{ $row->count }}</div>
                    </div>
                @empty
                    <div class="empty"><i class="fa fa-bar-chart"></i> Nothing has been sold to this customer yet.</div>
                @endforelse
            </div>
        </div>

        </div>
        <div class="col-12 col-lg-4">
        <div class="panel h-100">
            <div class="phead p-info">
                <span class="ic"><i class="fa fa-trophy"></i></span>
                <div><h4>Top Highlights</h4></div>
            </div>
            <div class="pbody">
                <dl class="dl">
                    <dt>Distinct products</dt>
                    <dd class="num">{{ $highlights['products'] }}</dd>
                </dl>
                <dl class="dl">
                    <dt>Most bought</dt>
                    <dd class="{{ $highlights['top'] ? '' : 'empty' }}">{{ $highlights['top']->product_name ?? '—' }}</dd>
                </dl>
                <dl class="dl">
                    <dt>Total invoices</dt>
                    <dd class="num">{{ $highlights['invoices'] }}</dd>
                </dl>
                <dl class="dl">
                    <dt>First purchase</dt>
                    <dd class="{{ $highlights['first_date'] ? '' : 'empty' }}">{{ $highlights['first_date'] ? systemDate($highlights['first_date']) : '—' }}</dd>
                </dl>
                <dl class="dl">
                    <dt>Last purchase</dt>
                    <dd class="{{ $highlights['last_date'] ? '' : 'empty' }}">{{ $highlights['last_date'] ? systemDate($highlights['last_date']) : '—' }}</dd>
                </dl>
                <dl class="dl">
                    <dt>Purchase frequency</dt>
                    <dd class="{{ $highlights['frequency'] ? '' : 'empty' }}">
                        {{ $highlights['frequency'] ? '~' . $highlights['frequency'] . ' / month' : '—' }}
                    </dd>
                </dl>
            </div>
        </div>
        </div>
    </div>
</div>
