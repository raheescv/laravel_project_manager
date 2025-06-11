<div class="pos-product-grid-container">
    @push('styles')
    @endpush

    <div class="product-grid">
        @foreach ($products as $item)
            <div wire:key="product-{{ $item['id'] }}" class="product-card" wire:click="selectItem({{ $item['id'] }})" wire:loading.class="loading" wire:target="selectItem">
                @if ($item['type'] == 'product')
                    <div class="product-badge {{ $item['quantity'] <= 0 ? 'out-of-stock' : '' }}">
                        @if ($item['quantity'] > 0)
                            <span>In Stock: {{ $item['quantity'] }}</span>
                            <div class="stock-indicator">
                                <div class="stock-level {{ $item['quantity'] <= 5 ? 'critical' : ($item['quantity'] <= 10 ? 'low' : '') }}"
                                    style="width: {{ min(($item['quantity'] / 20) * 100, 100) }}%">
                                </div>
                            </div>
                        @else
                            Out of stock
                        @endif
                    </div>
                @endif
                <div class="product-image">
                    <img src="{{ $item['thumbnail'] ?? cache('logo') }}" alt="{{ $item['name'] }}" loading="lazy" onerror="this.onerror=null; this.src='{{ cache('logo') }}';">
                </div>
                <div class="product-content">
                    <h5 class="product-name">{{ $item['name'] }}</h5>
                    <div class="product-details">
                        <span class="product-price">{{ currency($item['mrp']) }}</span>
                    </div>
                </div>
                @if ($item['type'] == 'product' && $item['quantity'] > 0)
                    <button type="button" class="quick-add" wire:click.stop="selectItem({{ $item['id'] }})" title="Quick Add to Cart">
                        +
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</div>
