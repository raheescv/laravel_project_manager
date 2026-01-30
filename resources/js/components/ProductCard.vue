<template>
    <div class="card h-100 shadow-sm transition-hover border-light position-relative overflow-hidden" 
         :class="{ 'border-danger border-2': isLowStock }" 
         @click="handleClick">

        <!-- Type Badge -->
        <span class="badge position-absolute top-0 start-0 m-2 z-1" 
              :class="isProduct ? 'bg-success' : 'bg-purple'">
            {{ isProduct ? 'P' : 'S' }}
        </span>

        <!-- Low Stock Indicator -->
        <span v-if="isProduct && isLowStock" 
              class="badge bg-danger rounded-circle position-absolute top-0 end-0 m-2 z-1 d-flex align-items-center justify-content-center p-0" 
              style="width: 20px; height: 20px;">
            <i class="fa fa-exclamation-circle" style="font-size: 0.65rem;"></i>
        </span>

        <div class="card-img-container ratio ratio-16x9 bg-light overflow-hidden border-bottom border-light">
            <img v-if="product.image" :src="product.image" :alt="product.name"
                 class="card-img-top object-fit-cover hover-scale" @error="handleImageError">
            <div v-else class="d-flex align-items-center justify-content-center bg-gradient-light">
                <i class="fa fa-cube fa-2x text-primary opacity-25"></i>
            </div>
        </div>

        <div class="card-body p-2 d-flex flex-column">
            <h6 class="card-title product-name mb-1 fw-bold text-dark" :title="product.name">
                {{ product.name }}
            </h6>

            <!-- Product Details Row -->
            <div class="product-details d-flex flex-wrap gap-2 mb-2 pb-2 border-bottom border-light-subtle extra-small text-muted">
                <div v-if="product.code" class="d-flex align-items-center">
                    <span class="fw-medium me-1">SKU:</span>
                    <span class="text-secondary">{{ product.code }}</span>
                </div>
                <div v-if="product.size" class="d-flex align-items-center">
                    <span class="fw-medium me-1">Size:</span>
                    <span class="text-secondary">{{ product.size }}</span>
                </div>
                <div v-if="isProduct && product.barcode" class="d-flex align-items-center">
                    <span class="fw-medium me-1">BC:</span>
                    <span class="text-secondary text-truncate" style="max-width: 50px;">{{ product.barcode }}</span>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-auto">
                <div v-if="isProduct" class="badge rounded-1 fw-semibold px-2 py-1" 
                     :class="isLowStock ? 'bg-danger-subtle text-danger' : 'bg-light text-secondary'">
                    Stock: {{ product.stock }}
                </div>
                <div v-else></div>
                
                <div class="text-success fw-bold">
                    {{ formatPrice(product.mrp) }}
                    <span v-if="product.unit_name" class="unit-text text-muted fw-normal">/{{ product.unit_name }}</span>
                </div>
            </div>
        </div>

        <!-- Touch feedback ripple container -->
        <div class="ripple-container"></div>
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
            if (!this.product) return;

            const productId = this.product.id || this.product.product_id || this.product.inventory_id;
            if (!productId) return;

            const cleanProduct = {
                ...this.product,
                id: productId
            };

            this.$emit('click', cleanProduct);
        },
        formatPrice(price) {
            return price ? parseFloat(price).toFixed(2) : '0.00';
        },
        handleImageError(event) {
            event.target.style.display = 'none';
            const parent = event.target.parentNode;

            if (!parent.querySelector('[data-fallback]')) {
                const fallback = document.createElement('div');
                fallback.setAttribute('data-fallback', '');
                fallback.className = 'w-100 h-100 bg-gradient-light d-flex align-items-center justify-content-center';
                fallback.innerHTML = '<i class="fa fa-cube fa-2x text-secondary opacity-50"></i>';
                parent.appendChild(fallback);
            }
        }
    }
}
</script>

<style scoped>
.transition-hover {
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    cursor: pointer;
    min-height: 160px;
    background-color: var(--bs-card-bg, #fff);
}

.transition-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1) !important;
    z-index: 5;
}

.transition-hover:active {
    transform: scale(0.97);
}

.hover-scale {
    transition: transform 0.5s ease;
}

.transition-hover:hover .hover-scale {
    transform: scale(1.1);
}

.object-fit-cover {
    object-fit: cover;
}

.bg-purple {
    background-color: #6f42c1;
    color: white;
}

.extra-small {
    font-size: 0.7rem;
}

.unit-text {
    font-size: 0.65rem;
}

.bg-gradient-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.ripple-container {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow: hidden;
    pointer-events: none;
}

/* Multi-line clamp for product name */
.product-name {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: 2.4em;
    line-height: 1.2em;
    font-size: 0.85rem;
}

/* Mobile optimizations */
@media (max-width: 576px) {
    .transition-hover {
        min-height: 140px;
    }
    .product-name {
        font-size: 0.75rem;
    }
    .extra-small {
        font-size: 0.65rem;
    }
}
</style>

