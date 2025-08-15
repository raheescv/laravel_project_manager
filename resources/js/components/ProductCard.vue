<template>
    <div class="product-card group relative overflow-hidden" :class="{ 'low-stock': isLowStock }" @click="handleClick"
        @mouseenter="$event.currentTarget.style.transform = 'translateY(-2px) scale(1.02)'"
        @mouseleave="$event.currentTarget.style.transform = 'translateY(0) scale(1)'">

        <!-- Type Badge -->
        <div class="type-badge" :class="{ 'product': isProduct, 'service': !isProduct }">
            {{ isProduct ? 'P' : 'S' }}
        </div>

        <!-- Low Stock Indicator -->
        <div v-if="isProduct && isLowStock" class="low-stock-badge">
            <i class="fa fa-exclamation-circle"></i>
        </div>

        <div class="product-image-container">
            <img v-if="product.image" :src="product.image" :alt="product.name"
                class="product-image group-hover:scale-110" @error="handleImageError">
            <div v-else class="fallback-icon">
                <i class="fa fa-cube"></i>
            </div>
        </div>

        <div class="product-info-wrapper">
            <h3 class="product-name" :title="product.name">{{ product.name }}</h3>

            <!-- Product Details Row -->
            <div class="product-details-row">
                <div v-if="product.code" class="product-code">
                    <span class="detail-label">SKU:</span>
                    <span class="detail-value">{{ product.code }}</span>
                </div>
                <div v-if="product.size" class="product-size">
                    <span class="detail-label">Size:</span>
                    <span class="detail-value">{{ product.size }}</span>
                </div>
                <div v-if="isProduct && product.barcode" class="product-barcode">
                    <span class="detail-label">Barcode:</span>
                    <span class="detail-value">{{ product.barcode }}</span>
                </div>
            </div>

            <div class="product-price-row">
                <div v-if="isProduct" class="product-qty" :class="{ 'low': isLowStock }">
                    {{ product.stock }}
                </div>
                <div v-else>
                </div>
                <div class="product-price">{{ formatPrice(product.mrp) }}</div>
            </div>
        </div>

        <!-- Touch feedback -->
        <div class="ripple-effect">
            <div class="ripple-inner"></div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProductCard',

    props: {
        product: {
            type: Object,
            required: true
        },
        lowStockThreshold: {
            type: Number,
            default: 10
        }
    },

    computed: {
        isProduct() {
            return this.product.type === 'product';
        },
        isLowStock() {
            return this.isProduct && this.product.stock < this.lowStockThreshold;
        }
    },

    methods: {
        handleClick() {
            console.log('ProductCard clicked with product:', this.product);

            // Enhanced product validation
            if (!this.product) {
                console.error('ProductCard: Product is undefined or null');
                return; // Prevent click event from propagating
            } else if (!this.product.id) {
                console.error('ProductCard: Product missing id:', JSON.stringify(this.product));
                return; // Prevent click event from propagating
            }

            // Ensure product has essential properties
            if (!this.product.name) {
                console.warn('ProductCard: Product missing name:', JSON.stringify(this.product));
                // Allow it to continue but log the warning
            }

            // Emit with validated product
            this.$emit('click', this.product);
        },
        formatPrice(price) {
            if (price) {
                return parseFloat(price).toFixed(2);
            } else {
                return '0.00';
            }
        },
        handleImageError(event) {
            event.target.style.display = 'none';
            const parent = event.target.parentNode;

            // Create and append fallback icon if it doesn't exist
            if (!parent.querySelector('[data-fallback]')) {
                const fallback = document.createElement('div');
                fallback.setAttribute('data-fallback', '');
                fallback.className = 'w-full h-full bg-gradient-to-br from-blue-100 via-indigo-100 to-purple-100 flex items-center justify-center';
                fallback.innerHTML = '<i class="fa fa-cube text-3xl text-blue-500 group-hover:text-purple-600 transition-colors duration-300"></i>';
                parent.appendChild(fallback);
            }
        }
    }
}
</script>

<style scoped>
.product-card {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    transition: all 0.25s ease;
    position: relative;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    min-height: 140px;
    height: auto;
    /* Reduced height */
    border: 1px solid rgba(229, 231, 235, 0.5);
}

.product-card:active {
    transform: scale(0.98);
}

.product-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Image styling */
.product-image-container {
    position: relative;
    width: 100%;
    height: 90px;
    overflow: hidden;
    background-color: #f1f5f9;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.fallback-icon {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f0f4ff, #e6f0ff);
}

.fallback-icon i {
    font-size: 1.5rem;
    color: #6366f1;
}

/* Badge styling */
.type-badge {
    position: absolute;
    top: 6px;
    left: 6px;
    width: 20px;
    height: 20px;
    border-radius: 5px;
    font-size: 0.6rem;
    font-weight: 700;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.type-badge.product {
    background: #22c55e;
    color: white;
}

.type-badge.service {
    background: #8b5cf6;
    color: white;
}

.low-stock-badge {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: #ef4444;
    color: white;
    font-size: 0.65rem;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}

/* Product info section */
.product-info-wrapper {
    padding: 6px 8px 8px;
    background: white;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    min-height: 0;
}

.product-name {
    font-size: 0.8rem;
    font-weight: 600;
    margin: 0;
    color: #374151;
    line-height: 1.3;
    word-wrap: break-word;
    hyphens: auto;
    overflow: visible !important;
    text-overflow: unset !important;
    white-space: normal !important;
    padding-bottom: 4px;
    border-bottom: 1px solid #f3f4f6;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: unset !important;
    -webkit-box-orient: vertical;
    max-height: none !important;
}

.product-details-row {
    display: flex;
    gap: 10px;
    margin-bottom: 4px;
    padding-bottom: 4px;
    border-bottom: 1px solid #f3f4f6;
    flex-wrap: wrap;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    min-height: 20px;
}

.product-code, .product-size, .product-barcode {
    display: flex;
    align-items: center;
    gap: 4px;
}

.detail-label {
    font-size: 0.65rem;
    color: #6b7280;
    font-weight: 500;
}

.detail-value {
    font-size: 0.7rem;
    color: #4b5563;
    font-weight: 600;
}

.product-price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.product-qty {
    background-color: #f3f4f6;
    color: #4b5563;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 1px 6px;
    border-radius: 4px;
}

.product-qty.low {
    background-color: #fee2e2;
    color: #ef4444;
}

.product-price {
    font-weight: 700;
    color: #047857;
    font-size: 0.9rem;
    padding: 1px 0;
}

/* Low stock styling */
.product-card.low-stock {
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.1);
}

/* Ripple effect */
.ripple-effect {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow: hidden;
    pointer-events: none;
}

.ripple-inner {
    position: absolute;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.7) 0%, rgba(255, 255, 255, 0) 70%);
    transform: scale(0);
    opacity: 0;
    transition: transform 0.5s, opacity 0.5s;
}

.product-card:active .ripple-inner {
    transform: scale(3);
    opacity: 0.2;
    transition: 0s;
}

/* Responsive styles */
@media (min-width: 1280px) {
    .product-card {
        min-height: 150px;
        height: auto;
    }

    .product-image-container {
        height: 100px;
    }

    .product-name {
        font-size: 0.85rem;
    }

    .detail-label {
        font-size: 0.7rem;
    }

    .detail-value {
        font-size: 0.75rem;
    }
}

@media (max-width: 768px) {
    .product-card {
        min-height: 130px;
        height: auto;
    }

    .product-image-container {
        height: 80px;
    }

    .product-name {
        font-size: 0.75rem;
    }

    .product-price {
        font-size: 0.85rem;
    }

    .product-details-row {
        gap: 8px;
    }

    .detail-label {
        font-size: 0.6rem;
    }

    .detail-value {
        font-size: 0.65rem;
    }
}

@media (max-width: 480px) {
    .product-card {
        min-height: 125px;
        height: auto;
    }

    .product-image-container {
        height: 75px;
    }

    .product-info-wrapper {
        padding: 5px 7px 7px;
    }

    .product-name {
        font-size: 0.7rem;
    }

    .product-price {
        font-size: 0.8rem;
    }

    .product-qty {
        font-size: 0.65rem;
        padding: 1px 4px;
    }

    .product-details-row {
        gap: 6px;
        margin-bottom: 3px;
        padding-bottom: 3px;
    }

    .detail-label {
        font-size: 0.55rem;
    }

    .detail-value {
        font-size: 0.6rem;
    }
}
</style>
