<template>
    <div
        class="bg-white/95 backdrop-blur-sm rounded-lg shadow-md border border-indigo-100/80 h-auto lg:h-full flex flex-col min-h-0">
        <!-- Compact Cart Header -->
        <div
            class="p-2 border-b border-indigo-100 bg-gradient-to-r from-indigo-50/80 via-violet-50/60 to-indigo-50/80 flex-shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div
                        class="flex items-center justify-center h-6 w-6 bg-gradient-to-br from-violet-500 to-indigo-600 text-white rounded-md shadow-sm mr-1.5">
                        <span class="font-bold text-xs">{{ totalQuantity }}</span>
                    </div>
                    <div>
                        <h6 class="font-medium text-indigo-900 text-xs flex items-center">
                            <i class="fa fa-shopping-cart text-violet-600 mr-1"></i>
                            Cart Items
                        </h6>
                        <small class="text-xs text-indigo-500">
                            {{ totalQuantity === 1 ? '1 item' : `${totalQuantity} items` }}
                        </small>
                    </div>
                </div>
                <div v-if="totalQuantity > 0" class="flex gap-1">
                    <button type="button" @click="$emit('view-cart-items')"
                        class="h-6 w-6 flex items-center justify-center bg-indigo-100 text-indigo-600 rounded-md hover:bg-indigo-600 hover:text-white transition-all duration-200"
                        title="View Items">
                        <i class="fa fa-list text-xs"></i>
                    </button>
                    <button type="button" @click="$emit('clear-cart')"
                        class="h-6 w-6 flex items-center justify-center bg-rose-50 text-rose-500 rounded-md hover:bg-rose-500 hover:text-white transition-all duration-200"
                        title="Clear Cart">
                        <i class="fa fa-trash text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Cart Items Scrollable Container -->
        <div class="flex-1 relative overflow-hidden min-h-0">
            <!-- Product Wrap with improved scrolling and padding -->
            <div class="h-full overflow-y-auto custom-scrollbar px-1 sm:px-2 pb-2 pt-1"
                :style="{ 'height': cartHeight, 'min-height': '140px', 'max-height': maxHeight ? maxHeight : '300px' }">

                <!-- Compact Empty Cart State -->
                <div v-if="Object.keys(items).length === 0" class="text-center py-6">
                    <div
                        class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-violet-50 mb-2 shadow-inner">
                        <i class="fa fa-shopping-cart text-violet-300 text-lg"></i>
                    </div>
                    <h3 class="text-indigo-800 font-medium text-xs sm:text-sm mb-1">Your cart is empty</h3>
                    <p class="text-indigo-500 text-xs max-w-[180px] mx-auto">
                        Add products to begin your order
                    </p>
                </div>

                <!-- Cart Items List -->
                <div v-else class="space-y-2 pt-1">
                    <div v-for="(item, key) in items" :key="key"
                        class="group relative bg-white/95 backdrop-blur-sm rounded-lg p-1.5 shadow-sm hover:shadow-md transition-all duration-200 border border-indigo-100/60 hover:border-violet-200">

                        <!-- More compact two-row layout -->
                        <div class="flex flex-col">
                            <!-- First row: Product name with total price -->
                            <div class="flex items-start justify-between mb-1">
                                <!-- Product Name with overflow handling -->
                                <div class="flex-1 min-w-0 pr-1">
                                    <h6 class="font-medium text-indigo-900 text-xs leading-tight truncate"
                                        :title="item.name">
                                        {{ item.name }}
                                    </h6>
                                    <div class="text-xs text-violet-500 leading-tight">
                                        {{ item.unit_price.toFixed(2) }} × {{ item.quantity }}
                                    </div>
                                </div>

                                <!-- Total Price - more compact -->
                                <div class="flex-shrink-0">
                                    <span
                                        class="font-medium text-teal-600 text-xs bg-teal-50/70 px-1.5 py-0.5 rounded-md border border-teal-100/50">
                                        {{ (item.quantity * item.unit_price).toFixed(2) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Second row: Quantity controls and action buttons -->
                            <div class="flex items-center justify-between mt-0.5">
                                <!-- Compact Quantity Controls -->
                                <div
                                    class="flex items-center bg-violet-50 rounded-md border border-violet-100 overflow-hidden">
                                    <button type="button" :disabled="item.quantity <= 1"
                                        @click="item.quantity > 1 ? decreaseQuantity(key) : null"
                                        class="flex items-center justify-center h-6 w-6 transition-colors"
                                        :class="item.quantity <= 1 ? 'text-violet-200 cursor-not-allowed' : 'text-violet-600 hover:text-indigo-500 active:bg-violet-100'">
                                        <i class="fa fa-minus text-xs"></i>
                                    </button>

                                    <input v-model.number="item.quantity" @change="updateItemQuantity(key)"
                                        @blur="updateItemQuantity(key)" type="number" min="1"
                                        class="w-8 h-6 text-center text-xs bg-transparent border-0 focus:outline-none focus:ring-0 p-0 text-indigo-800"
                                        style="appearance: textfield">

                                    <button type="button" @click="increaseQuantity(key)"
                                        class="flex items-center justify-center h-6 w-6 text-violet-600 hover:text-indigo-500 active:bg-violet-100 transition-colors">
                                        <i class="fa fa-plus text-xs"></i>
                                    </button>
                                </div>

                                <!-- Compact Action Buttons -->
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="$emit('edit-cart-item', key)"
                                        class="flex items-center justify-center h-6 w-6 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-md hover:bg-indigo-600 hover:text-white transition-all duration-150"
                                        title="Edit Item">
                                        <i class="fa fa-edit text-xs"></i>
                                    </button>
                                    <button type="button" @click="$emit('remove-cart-item', key)"
                                        class="flex items-center justify-center h-6 w-6 bg-rose-50 text-rose-500 border border-rose-100 rounded-md hover:bg-rose-500 hover:text-white transition-all duration-150"
                                        title="Remove Item">
                                        <i class="fa fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Minimal scroll indicator for mobile -->
                <div v-if="Object.keys(items).length > 3"
                    class="lg:hidden sticky bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-transparent via-violet-300 to-transparent mt-2 rounded-full">
                </div>
            </div>

            <!-- Minimal scroll shadow effects -->
            <div
                class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-b from-white to-transparent pointer-events-none z-10">
            </div>
            <div
                class="absolute bottom-0 left-0 right-0 h-2 bg-gradient-to-t from-white to-transparent pointer-events-none z-10">
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
        cartHeight: {
            type: String,
            default: 'calc(40vh - 100px)'
        },
        maxHeight: {
            type: String,
            default: null
        }
    },
    emits: [
        'view-cart-items',
        'clear-cart',
        'update-item-quantity',
        'remove-cart-item',
        'edit-cart-item',
        'increase-quantity',
        'decrease-quantity'
    ],
    methods: {
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
