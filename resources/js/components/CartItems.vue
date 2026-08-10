<template>
    <div class="h-auto lg:h-full flex flex-col min-h-0">
        <!-- Compact Cart Header -->
        <div class="p-2 border-b posx-divide posx-panel-2 flex-shrink-0 rounded-t-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="posx-badge mr-1.5" :style="{ minWidth: badgeWidth }">
                        {{ formatNumber(totalQuantity, 3) }}
                    </div>
                    <div>
                        <h6 class="font-bold posx-ink text-xs flex items-center gap-1.5 mb-0">
                            <i class="fa fa-shopping-cart posx-muted"></i>
                            Cart Items
                        </h6>
                        <small class="text-xs posx-muted font-semibold">
                            {{ totalQuantity === 1 ? '1 item' : `${formatNumber(totalQuantity, 3)} items` }}
                        </small>
                    </div>
                </div>
                <div class="flex gap-1">
                    <button v-if="canFeedback" type="button" @click="$emit('open-feedback')"
                        class="posx-icon-btn" title="Feedback">
                        <i class="fa fa-comment-o"></i>
                    </button>
                    <button type="button" @click="$emit('view-cart-items')"
                        class="posx-icon-btn" title="View Items">
                        <i class="fa fa-list"></i>
                    </button>
                    <button type="button" @click="$emit('manage-combo-offer')"
                        class="posx-icon-btn" title="Manage Combo Offer">
                        <i class="fa fa-cube"></i>
                    </button>
                    <button type="button" @click="$emit('clear-cart')"
                        class="posx-icon-btn is-danger" title="Clear Cart">
                        <i class="fa fa-trash-o"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Cart Items Scrollable Container -->
        <div class="flex-1 relative overflow-hidden min-h-0">
            <!-- Product Wrap with improved scrolling and padding -->
            <div class="posx-cart-body h-full overflow-y-auto custom-scrollbar px-1 sm:px-2 pb-2 pt-1">

                <!-- Compact Empty Cart State -->
                <div v-if="Object.keys(items).length === 0" class="text-center py-6">
                    <div class="posx-empty-ring mx-auto mb-2.5">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                    <h3 class="posx-ink font-bold text-xs sm:text-sm mb-1">Your cart is empty</h3>
                    <p class="posx-muted text-xs font-semibold max-w-[180px] mx-auto">
                        Add products to begin your order
                    </p>
                </div>

                <!-- Cart Items List -->
                <div v-else class="space-y-2 pt-1">
                    <div v-for="(item, key) in items" :key="key"
                        class="group relative posx-item p-1.5">

                        <!-- More compact two-row layout -->
                        <div class="flex flex-col">
                            <!-- First row: Product name with total price -->
                            <div class="flex items-start justify-between mb-1">
                                <!-- Product Name with overflow handling -->
                                <div class="flex-1 min-w-0 pr-1">
                                    <h6 class="font-bold posx-ink text-xs leading-tight truncate mb-0"
                                        :title="item.name">
                                        {{ item.name }}
                                    </h6>
                                    <!-- Product Barcode and Size -->
                                    <div class="flex items-center gap-2 text-xs posx-muted leading-tight mb-1">
                                        <span v-if="item.barcode" class="flex items-center">
                                            <i class="fa fa-barcode mr-1"></i>
                                            {{ item.barcode }}
                                        </span>
                                        <span v-if="item.size" class="flex items-center">
                                            <i class="fa fa-arrows mr-1"></i>
                                            {{ item.size }}
                                        </span>
                                    </div>
                                    <div class="text-xs posx-ink-2 leading-tight">
                                        <span v-if="item.combo_offer_price && item.combo_offer_price > 0" class="flex items-center gap-1">
                                            <span class="line-through posx-muted">{{ formatNumber(item.unit_price) }}</span>
                                            <span class="posx-amount-ok">{{ formatNumber(item.combo_offer_price) }}</span>
                                            <span class="posx-chip-ok">Combo</span>
                                        </span>
                                        <span v-else>{{ formatNumber(item.unit_price) }}</span>
                                        <span> × {{ formatNumber(item.quantity) }} <span class="text-[10px] font-semibold posx-muted">{{ item.unit_name }}</span></span>
                                    </div>
                                </div>

                                <!-- Total Price - more compact -->
                                <div class="flex-shrink-0">
                                    <span :class="[
                                        'text-xs',
                                        item.combo_offer_price && item.combo_offer_price > 0 ? 'posx-amount-ok' : 'posx-amount'
                                    ]">
                                        {{ formatNumber(item.total || (item.quantity * (item.combo_offer_price || item.unit_price))) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Second row: Quantity controls and action buttons -->
                            <div class="flex items-center justify-between mt-0.5">
                                <!-- Compact Quantity Controls -->
                                <div class="posx-qty">
                                    <button type="button" :disabled="item.quantity <= 1"
                                        @click="item.quantity > 1 ? decreaseQuantity(key) : null"
                                        class="h-7 w-7 sm:h-6 sm:w-6 touch-manipulation">
                                        <i class="fa fa-minus text-xs"></i>
                                    </button>

                                    <input v-model.number="item.quantity" @change="updateItemQuantity(key)"
                                        @blur="updateItemQuantity(key)" type="number" min="1"
                                        class="w-16 sm:w-14 h-7 sm:h-6 text-sm sm:text-xs p-0 touch-manipulation">

                                    <button type="button" @click="increaseQuantity(key)"
                                        class="h-7 w-7 sm:h-6 sm:w-6 touch-manipulation">
                                        <i class="fa fa-plus text-xs"></i>
                                    </button>
                                </div>

                                <!-- Compact Action Buttons -->
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="$emit('edit-cart-item', key)"
                                        class="posx-icon-btn" title="Edit Item">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <button type="button" @click="$emit('remove-cart-item', key)"
                                        class="posx-icon-btn is-danger" title="Remove Item">
                                        <i class="fa fa-trash-o"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Minimal scroll indicator for mobile -->
                <div v-if="Object.keys(items).length > 3"
                    class="lg:hidden sticky bottom-0 left-0 right-0 h-0.5 mt-2 rounded-full posx-scroll-hint">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CartItems',
    props: {
        items: {
            type: Object,
            required: true
        },
        totalQuantity: {
            type: Number,
            default: 0
        },
        canFeedback: {
            type: Boolean,
            default: false
        },
    },
    emits: [
        'view-cart-items',
        'clear-cart',
        'update-item-quantity',
        'remove-cart-item',
        'edit-cart-item',
        'increase-quantity',
        'decrease-quantity',
        'manage-combo-offer',
        'open-feedback'
    ],
    computed: {
        badgeWidth() {
            const formattedValue = this.formatNumber(this.totalQuantity, 3);
            // Calculate width based on character count: min 24px (w-6), ~8px per character
            const minWidth = 24;
            const charWidth = 8;
            const calculatedWidth = Math.max(minWidth, formattedValue.length * charWidth + 8); // +8 for padding
            return `${calculatedWidth}px`;
        }
    },
    methods: {
        formatNumber(value, decimals = 2) {
            const num = parseFloat(value) || 0;
            return num.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        },
        updateItemQuantity(key) {
            this.$emit('update-item-quantity', key);
        },
        increaseQuantity(key) {
            this.$emit('increase-quantity', key);
        },
        decreaseQuantity(key) {
            this.$emit('decrease-quantity', key);
        }
    }
}
</script>
