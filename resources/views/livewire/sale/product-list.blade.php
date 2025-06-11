<div class="pos-product-grid-container">
    @push('styles')
        <style>
            .pos-product-grid-container {
                background: #f1f5f9;
                min-height: 100vh;
                padding: 1rem;
                -webkit-tap-highlight-color: transparent;
                touch-action: manipulation;
            }

            .product-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 0.75rem;
                max-width: 1920px;
                margin: 0 auto;
                padding: 0.5rem;
            }

            @media (max-width: 1600px) {
                .product-grid {
                    gap: 0.75rem;
                }
            }

            @media (max-width: 1200px) {
                .product-grid {
                    gap: 0.5rem;
                }

                .product-name {
                    font-size: 1rem;
                }

                .product-price {
                    font-size: 1.25rem;
                }

                .product-badge {
                    font-size: 0.875rem;
                    padding: 0.375rem 0.75rem;
                }
            }

            @media (max-width: 768px) {
                .product-grid {
                    gap: 0.5rem;
                }

                .product-name {
                    font-size: 0.875rem;
                }

                .product-price {
                    font-size: 1.125rem;
                    padding: 0.375rem;
                }

                .product-badge {
                    font-size: 0.75rem;
                    padding: 0.25rem 0.5rem;
                    min-width: 100px;
                }

                .quick-add {
                    width: 2rem;
                    height: 2rem;
                    font-size: 1.25rem;
                }
            }

            .product-card {
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                transition: all 0.2s ease;
                cursor: pointer;
                overflow: hidden;
                position: relative;
                height: 100%;
                display: flex;
                flex-direction: column;
                border: 2px solid transparent;
                touch-action: manipulation;
                -webkit-tap-highlight-color: transparent;
                min-height: 250px;
            }

            .product-card:active {
                transform: scale(0.98);
                border-color: #0ea5e9;
                background: #f0f9ff;
            }

            .product-image {
                position: relative;
                padding-top: 75%;
                background: #f8fafc;
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
            }

            .product-content {
                padding: 1rem;
                flex-grow: 1;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                background: #fff;
            }

            .product-name {
                font-size: 1.125rem;
                font-weight: 600;
                color: #1e293b;
                line-height: 1.3;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                min-height: 2.6em;
            }

            .product-details {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                margin-top: auto;
            }

            .product-price {
                font-size: 1.5rem;
                font-weight: 700;
                color: #0f766e;
                text-align: center;
                padding: 0.5rem;
                background: #f0fdf4;
                border-radius: 0.5rem;
                border: 2px solid #dcfce7;
            }

            .product-badge {
                position: absolute;
                top: 0.75rem;
                right: 0.75rem;
                padding: 0.25rem 0.5rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 600;
                background: rgba(255, 255, 255, 0.95);
                color: #0f766e;
                z-index: 5;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                min-width: auto;
                text-align: center;
            }

            .product-badge.out-of-stock {
                background: #ef4444;
                color: white;
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
                min-width: auto;
                width: auto;
                border: none;
            }

            .product-badge .stock-indicator {
                display: none;
            }

            .product-badge:not(.out-of-stock) {
                min-width: 120px;
            }

            /* Stock level indicator */
            .stock-indicator {
                height: 0.5rem;
                background: #e2e8f0;
                border-radius: 9999px;
                overflow: hidden;
                margin-top: 0.5rem;
            }

            .stock-level {
                height: 100%;
                background: #10b981;
                transition: width 0.3s ease;
            }

            .stock-level.low {
                background: #f59e0b;
            }

            .stock-level.critical {
                background: #ef4444;
            }

            /* Touch feedback */
            .product-card:active .product-price {
                background: #dcfce7;
            }

            /* Loading state */
            .product-card.loading {
                opacity: 0.7;
                pointer-events: none;
            }

            /* Selected state */
            .product-card.selected {
                border-color: #0ea5e9;
                background: #f0f9ff;
            }

            /* Quick add button for POS */
            .quick-add {
                position: absolute;
                bottom: 1rem;
                right: 1rem;
                width: 2.5rem;
                height: 2.5rem;
                background: #0ea5e9;
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                font-weight: bold;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                border: 2px solid white;
                opacity: 0;
                transform: scale(0.8);
                transition: all 0.2s ease;
            }

            .product-card:hover .quick-add,
            .product-card:active .quick-add {
                opacity: 1;
                transform: scale(1);
            }

            .quick-add:active {
                transform: scale(0.95);
                background: #0284c7;
            }
        </style>
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
