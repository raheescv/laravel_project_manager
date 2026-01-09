<template>
    <div class="product-card group relative overflow-hidden" :class="{ 'low-stock': isLowStock }" @click="handleClick">

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
            // Enhanced product validation
            if (!this.product) {
                console.error('ProductCard: Product is undefined or null');
                return; // Prevent click event from propagating
            }
            
            // Check for id in multiple possible locations
            const productId = this.product.id || this.product.product_id || this.product.inventory_id;
            if (!productId) {
                console.error('ProductCard: Product missing id:', JSON.stringify(this.product));
                console.error('ProductCard: Available keys:', Object.keys(this.product || {}));
                return; // Prevent click event from propagating
            }

            // Create a clean product object with guaranteed id
            const cleanProduct = {
                ...this.product,
                id: productId // Ensure id is set
            };

            // Ensure product has essential properties
            if (!cleanProduct.name) {
                console.warn('ProductCard: Product missing name:', JSON.stringify(cleanProduct));
                // Allow it to continue but log the warning
            }

            // Emit with validated and cleaned product
            this.$emit('click', cleanProduct);
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
    transition: box-shadow 0.25s ease;
    position: relative;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    min-height: 140px;
    height: auto;
    width: 100%;
    max-width: 100%;
    border: 1px solid rgba(229, 231, 235, 0.5);
    box-sizing: border-box;
    contain: layout style paint;
    margin: 0;
    padding: 0;
}

.product-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    z-index: 1;
}

.product-card:active {
    transform: scale(0.98);
}

/* Image styling */
.product-image-container {
    position: relative;
    width: 100%;
    max-width: 100%;
    height: 90px;
    overflow: hidden;
    background-color: #f1f5f9;
    box-sizing: border-box;
    flex-shrink: 0;
}

.product-image {
    width: 100%;
    height: 100%;
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
    display: block;
    box-sizing: border-box;
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
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow: hidden;
    min-width: 0;
}

.product-name {
    font-size: 0.8rem;
    font-weight: 600;
    margin: 0;
    color: #374151;
    line-height: 1.3;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    hyphens: auto;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: normal;
    padding-bottom: 4px;
    border-bottom: 1px solid #f3f4f6;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    max-height: 2.6em;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    min-width: 0;
}

.product-details-row {
    display: flex;
    gap: 6px;
    margin-bottom: 4px;
    padding-bottom: 4px;
    border-bottom: 1px solid #f3f4f6;
    flex-wrap: wrap;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    min-height: 20px;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow: hidden;
    min-width: 0;
}

.product-code,
.product-size,
.product-barcode {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 1;
    max-width: 100%;
    min-width: 0;
    overflow: hidden;
    box-sizing: border-box;
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
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 60px;
    min-width: 0;
    box-sizing: border-box;
}

.product-price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    gap: 4px;
    flex-wrap: nowrap;
    min-width: 0;
}

.product-qty {
    background-color: #f3f4f6;
    color: #4b5563;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 1px 6px;
    border-radius: 4px;
    flex-shrink: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 45%;
    min-width: 0;
    box-sizing: border-box;
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
    flex-shrink: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-align: right;
    max-width: 55%;
    min-width: 0;
    box-sizing: border-box;
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
        width: 100%;
        max-width: 100%;
    }

    .product-image-container {
        height: 80px;
        width: 100%;
    }

    .product-name {
        font-size: 0.75rem;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        max-height: 2.4em;
    }

    .product-price {
        font-size: 0.85rem;
    }

    .product-details-row {
        gap: 6px;
    }

    .detail-label {
        font-size: 0.6rem;
    }

    .detail-value {
        font-size: 0.65rem;
        max-width: 50px;
    }

    .product-qty {
        font-size: 0.65rem;
        padding: 1px 4px;
        max-width: 45%;
    }
}

@media (max-width: 480px) {
    .product-card {
        min-height: 125px;
        height: auto;
        width: 100%;
        max-width: 100%;
    }

    .product-image-container {
        height: 75px;
        width: 100%;
    }

    .product-info-wrapper {
        padding: 5px 6px 6px;
        width: 100%;
        box-sizing: border-box;
    }

    .product-name {
        font-size: 0.7rem;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        max-height: 2.2em;
        padding-bottom: 3px;
        margin-bottom: 3px;
    }

    .product-price {
        font-size: 0.75rem;
    }

    .product-qty {
        font-size: 0.6rem;
        padding: 1px 3px;
        max-width: 40%;
    }

    .product-details-row {
        gap: 4px;
        margin-bottom: 3px;
        padding-bottom: 3px;
    }

    .detail-label {
        font-size: 0.55rem;
    }

    .detail-value {
        font-size: 0.6rem;
        max-width: 45px;
    }

    .product-price-row {
        gap: 2px;
    }
}
</style>
