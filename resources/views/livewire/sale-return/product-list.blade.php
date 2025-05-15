<div class="row">
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
                position: relative;
                background: white;
                border: 1px solid rgba(0, 0, 0, .125);
                border-radius: 0.5rem;
                transition: all 0.2s ease-in-out;
                height: 100%;
                cursor: pointer;
            }

            .product-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .15);
                border-color: var(--bs-primary);
            }

            .product-image {
                position: relative;
                padding-top: 75%;
                border-top-left-radius: 0.5rem;
                border-top-right-radius: 0.5rem;
                background: #f8f9fa;
                overflow: hidden;
            }

            .product-image img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .product-badge {
                position: absolute;
                top: 0.75rem;
                right: 0.75rem;
                padding: 0.25rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.875rem;
                font-weight: 500;
                background: rgba(var(--bs-primary-rgb), 0.1);
                color: var(--bs-primary);
                z-index: 1;
            }

            .product-content {
                padding: 1rem;
            }

            .product-name {
                font-size: 0.875rem;
                font-weight: 600;
                color: var(--bs-gray-900);
                margin-bottom: 0.5rem;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .product-price {
                font-size: 1rem;
                font-weight: 700;
                color: var(--bs-primary);
            }

            .product-quantity {
                font-size: 0.875rem;
                color: var(--bs-gray-600);
            }
        </style>
    @endpush

    <div class="product-grid">
        @foreach ($products as $item)
            <div wire:key="product-{{ $item['id'] }}" class="product-card" wire:click="selectItem('{{ $item['id'] }}','{{ $item['sale_item_id'] }}')">
                <div class="product-image">
                    <img src="{{ $item['thumbnail'] ?? cache('logo') }}" alt="{{ $item['name'] }}" class="img-fluid">
                </div>
                <div class="product-content">
                    <h6 class="product-name">{{ $item['name'] }}</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="product-price">{{ currency($item['mrp']) }}</span>
                        @if ($item['type'] == 'product')
                            <span class="product-quantity">
                                <i class="fa fa-cube me-1"></i>
                                {{ $item['quantity'] }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
