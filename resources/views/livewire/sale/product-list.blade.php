<div class="product-grid">
    @push('styles')
        <style>
            .product-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 0.5rem;
                padding: 0.5rem;
            }

            @media (max-width: 1400px) {
                .product-grid {
                    grid-template-columns: repeat(4, 1fr);
                }
            }

            @media (max-width: 1200px) {
                .product-grid {
                    grid-template-columns: repeat(3, 1fr);
                }
            }

            @media (max-width: 768px) {
                .product-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (max-width: 576px) {
                .product-grid {
                    grid-template-columns: repeat(1, 1fr);
                }
            }

            .product-card {
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
                transition: all 0.2s ease;
                cursor: pointer;
                overflow: hidden;
                position: relative;
                height: 100%;
                display: flex;
                flex-direction: column;
            }

            .product-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            }

            .product-image {
                position: relative;
                padding-top: 75%;
                background: #f1f5f9;
                overflow: hidden;
                flex-shrink: 0;
            }

            .product-image img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.2s ease;
            }

            .product-card:hover .product-image img {
                transform: scale(1.05);
            }

            .product-content {
                padding: 0.5rem;
                flex-grow: 1;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                background: #fff;
            }

            .product-name {
                font-size: 0.875rem;
                font-weight: 600;
                color: #1e293b;
                margin-bottom: 0.25rem;
                line-height: 1.25;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                height: 2.5em;
            }

            .product-details {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 0.25rem;
            }

            .product-price {
                font-size: 1rem;
                font-weight: 700;
                color: #0ea5e9;
            }

            .product-stock {
                padding: 0.125rem 0.5rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 500;
                background: #f0f9ff;
                color: #0369a1;
                white-space: nowrap;
            }

            .product-badge {
                position: absolute;
                top: 0.5rem;
                right: 0.5rem;
                padding: 0.125rem 0.5rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 600;
                background: rgba(16, 185, 129, 0.9);
                color: white;
                z-index: 1;
                backdrop-filter: blur(4px);
            }

            .out-of-stock {
                background: rgba(239, 68, 68, 0.9);
            }
        </style>
    @endpush
    @foreach ($products as $item)
        <div wire:key="product-{{ $item['id'] }}" class="product-card" wire:click="selectItem({{ $item['id'] }})">
            @if ($item['type'] == 'product')
                <div class="product-badge {{ $item['quantity'] <= 0 ? 'out-of-stock' : '' }}">
                    {{ $item['quantity'] > 0 ? 'Stock: ' . $item['quantity'] : 'Out of Stock' }}
                </div>
            @endif
            <div class="product-image">
                <img src="{{ $item['thumbnail'] ?? cache('logo') }}" alt="{{ $item['name'] }}" loading="lazy">
            </div>
            <div class="product-content">
                <h5 class="product-name">{{ $item['name'] }}</h5>
                <div class="product-details">
                    <span class="product-price">{{ currency($item['mrp']) }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>
