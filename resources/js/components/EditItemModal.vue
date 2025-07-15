<template>
    <div v-if="show" class="modal-overlay">
        <div class="payment-modal bg-white rounded-lg p-6 shadow-lg w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Edit Item</h3>
            <div v-if="item">
                <div class="mb-2">
                    <label class="block text-xs font-semibold mb-1">Product</label>
                    <div class="text-sm">{{ item.name }}</div>
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-semibold mb-1">Quantity</label>
                    <input v-model.number="localItem.quantity" type="number" min="1"
                        class="w-full border rounded px-2 py-1" />
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-semibold mb-1">Unit Price</label>
                    <input v-model.number="localItem.unit_price" type="number" min="0" step="0.01"
                        class="w-full border rounded px-2 py-1" />
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-semibold mb-1">Discount</label>
                    <input v-model.number="localItem.discount" type="number" min="0" step="0.01"
                        class="w-full border rounded px-2 py-1" />
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-semibold mb-1">Tax (%)</label>
                    <input v-model.number="localItem.tax" type="number" min="0" step="0.01"
                        class="w-full border rounded px-2 py-1" />
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="$emit('close')" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                    <button type="button" @click="save" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </div>
        </div>
    </div>
</template>



<script>
export default {
    name: 'EditItemModal',
    props: {
        show: Boolean,
        item: Object
    },
    emits: ['save', 'close'],
    data() {
        return {
            localItem: { ...this.item }
        }
    },
    watch: {
        item: {
            handler(val) {
                Object.assign(this.localItem, val || {})
            },
            deep: true
        }
    },
    methods: {
        updateItemField(field, value) {
            // Convert to number for numeric fields
            const numericFields = ['unit_price', 'quantity', 'discount', 'tax'];
            let processedValue = numericFields.includes(field) ? parseFloat(value) || 0 : value;

            // Ensure quantity is at least 1
            if (field === 'quantity' && processedValue < 1) {
                processedValue = 1;
            }

            // Ensure non-negative values for price, discount, and tax
            if (['unit_price', 'discount', 'tax'].includes(field) && processedValue < 0) {
                processedValue = 0;
            }

            this.localItem[field] = processedValue;
        },
        save() {
            // Validate numeric fields
            ['quantity', 'unit_price', 'discount', 'tax'].forEach(field => {
                this.localItem[field] = Number(this.localItem[field]) || 0;
            });
            // Calculate amounts
            this.localItem.gross_amount = this.localItem.unit_price * this.localItem.quantity;
            this.localItem.net_amount = this.localItem.gross_amount - this.localItem.discount;
            this.localItem.tax_amount = this.localItem.net_amount * (this.localItem.tax / 100);
            this.localItem.total = this.localItem.net_amount + this.localItem.tax_amount;
            this.$emit('save', { ...this.localItem });
            this.$emit('close');
        }
    }
}
</script>


<style scoped>
.payment-modal {
    position: fixed;
    z-index: 50;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.modal-overlay {
    position: fixed;
    z-index: 40;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.5);
}
</style>
