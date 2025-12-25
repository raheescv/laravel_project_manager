<template>
    <div class="products-grid-container">
        <div class="products-grid">
            <product-card v-for="product in validProducts" :key="product.id" :product="product"
                :lowStockThreshold="lowStockThreshold" @click="handleCardClick" />
        </div>

        <!-- Empty state when no products -->
        <div v-if="validProducts.length === 0" class="no-products">
            <div class="no-products-content">
                <span class="icon">📦</span>
                <h3>No Products Found</h3>
                <p>Try adjusting your search or filters</p>
            </div>
        </div>
    </div>
</template>

<script>
import ProductCard from './ProductCard.vue';

export default {
    name: 'ProductsGrid',
    components: {
        ProductCard
    },
    props: {
        products: {
            type: Array,
            default: () => []
        },
        lowStockThreshold: {
            type: Number,
            default: 10
        }
    },
    computed: {
        validProducts() {
            // Filter out any products without valid IDs at the component level as an extra safeguard
            const filtered = this.products.filter(product => {
                if (!product || !product.id) {
                    console.warn('ProductsGrid: Filtered out product with missing ID:', product);
                    return false;
                }
                return true;
            });

            if (filtered.length < this.products.length) {
                console.log(`ProductsGrid: Filtered ${this.products.length - filtered.length} invalid products`);
            }

            return filtered;
        }
    },
    methods: {
        handleCardClick(product) {
            console.log('ProductsGrid received click with product:', product);

            // Validate product before passing it up
            if (!product) {
                console.error('ProductsGrid received undefined or null product');
                return;
            } else if (!product.id) {
                console.error('ProductsGrid received product missing id:', product);
                return;
            }

            this.$emit('product-selected', product);
        }
    }
}
</script>

<style scoped>
.products-grid-container {
    width: 100%;
    max-width: 100%;
    padding: 4px;
    box-sizing: border-box;
    overflow: hidden;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 8px;
    width: 100%;
    box-sizing: border-box;
}

.products-grid>* {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

/* Responsive grid layouts for different screen sizes */
@media (min-width: 1536px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(125px, 1fr));
        gap: 10px;
    }
}

@media (min-width: 1280px) and (max-width: 1535px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
    }
}

@media (min-width: 1024px) and (max-width: 1279px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(115px, 1fr));
        gap: 8px;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(105px, 1fr));
        gap: 8px;
    }
}

@media (min-width: 480px) and (max-width: 767px) {
    .products-grid-container {
        padding: 3px;
    }

    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(95px, 1fr));
        gap: 6px;
    }
}

@media (max-width: 479px) {
    .products-grid-container {
        padding: 2px;
    }

    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        gap: 6px;
    }
}

.no-products {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 250px;
    width: 100%;
    background-color: rgba(255, 255, 255, 0.7);
    border-radius: 12px;
    margin-top: 20px;
}

.no-products-content {
    text-align: center;
    color: #64748b;
    padding: 20px;
}

.no-products-content .icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
    display: block;
    color: #94a3b8;
}

.no-products-content h3 {
    margin: 8px 0;
    font-weight: 600;
    font-size: 1.2rem;
    color: #475569;
}

.no-products-content p {
    font-size: 0.9rem;
}
</style>
