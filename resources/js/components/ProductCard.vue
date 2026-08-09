<template>
    <div class="posx-card" :class="{ 'is-out': isOutOfStock }" @click="handleClick">

        <div class="posx-card-media">
            <img v-if="product.image" :src="product.image" :alt="product.name" class="hover-scale"
                 @error="handleImageError">
            <div v-else class="posx-card-empty"><i class="fa fa-cube"></i></div>

            <!-- Product / Service -->
            <span class="posx-card-tag type" :title="isProduct ? 'Product' : 'Service'">
                {{ isProduct ? 'P' : 'S' }}
            </span>


        </div>

        <div class="posx-card-body">
            <h6 class="posx-card-name product-name" :title="product.name">
                {{ product.name }}
            </h6>

            <div class="posx-card-meta">
                <span v-if="product.code"><b>SKU</b> {{ product.code }}</span>
                <span v-if="product.size"><b>Size</b> {{ product.size }}</span>
                <span v-if="isProduct && product.barcode" class="text-truncate" style="max-width: 60px;">
                    <b>BC</b> {{ product.barcode }}
                </span>
            </div>

            <div class="posx-card-foot">
                <span v-if="isProduct" class="posx-stock" :class="stockState.cls" :title="stockState.title">
                    {{ stockState.label }}
                </span>
                <span v-else></span>

                <span class="posx-price">
                    {{ formatPrice(product.mrp) }}<em v-if="product.unit_name">/{{ product.unit_name }}</em>
                </span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProductCard',
    emits: ['product-selected'],

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
        isOutOfStock() {
            return this.isProduct && Number(this.product.stock) <= 0;
        },
        isLowStock() {
            const s = Number(this.product.stock);
            return this.isProduct && s > 0 && s <= this.lowStockThreshold;
        },
        // Three states, not two. A shop whose normal stock is 0-3 must not read
        // as an error page, so only <=0 is alarming; low stock is a soft amber
        // chip and healthy stock is plain neutral.
        stockState() {
            const s = Number(this.product.stock) || 0;
            if (s <= 0) return { cls: 'is-out', label: s < 0 ? `Out ${s}` : 'Out', title: 'Out of stock' };
            if (s <= this.lowStockThreshold) return { cls: 'is-low', label: String(s), title: 'Low stock' };
            return { cls: '', label: String(s), title: 'In stock' };
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

            this.$emit('product-selected', cleanProduct);
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
                fallback.className = 'posx-card-empty';
                fallback.innerHTML = '<i class="fa fa-cube"></i>';
                parent.appendChild(fallback);
            }
        }
    }
}
</script>

<style scoped>
/* Colours all come from the .posx token layer (resources/css/pos-premium.css).
   Only card-local geometry and motion live here. */

.posx-card:active {
    transform: scale(.97);
}

.hover-scale {
    transition: transform .5s ease;
}

.posx-card:hover .hover-scale {
    transform: scale(1.06);
}

.posx-card-meta b {
    font-weight: 700;
    opacity: .75;
}

/* Two-line clamp for the product name — needs to outrank the single-line
   default in `.posx .posx-card-name`, hence the doubled class */
.posx-card-name.product-name {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: 2.4em;
    line-height: 1.2em;
    white-space: normal;
    font-size: 11px;
}

@media (max-width: 576px) {
    .posx-card-name.product-name {
        font-size: 10.5px;
    }
}
</style>
