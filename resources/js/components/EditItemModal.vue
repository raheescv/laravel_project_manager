<template>
    <div v-if="show" class="modal-overlay">
        <div class="payment-modal bg-white rounded-xl p-6 shadow-2xl w-full max-w-lg border border-slate-200">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-2 rounded-lg mr-3">
                        <i class="fa fa-edit text-white text-sm"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Edit Item</h3>
                </div>
                <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fa fa-times text-lg"></i>
                </button>
            </div>

            <div v-if="item" class="space-y-4">
                <!-- Product Info -->
                <div class="bg-gradient-to-r from-slate-50 to-blue-50 rounded-lg p-4 border border-slate-200">
                    <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center">
                        <i class="fa fa-box text-blue-500 mr-2"></i>
                        Product
                    </label>
                    <div class="text-sm font-medium text-slate-800 bg-white px-3 py-2 rounded-lg border">
                        {{ item.name }}
                    </div>
                </div>

                                <!-- Employee Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center">
                            <i class="fa fa-user text-emerald-500 mr-2"></i>
                            Employee
                        </label>
                                                <SearchableSelect
                            v-model="localItem.employee_id"
                            :options="employees"
                            placeholder="Select Employee"
                            filter-placeholder="Search employees..."
                            :visibleItems="8"
                            input-class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200 bg-white" />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center">
                            <i class="fa fa-user-plus text-purple-500 mr-2"></i>
                            Assistant
                        </label>
                                                <SearchableSelect
                            v-model="localItem.assistant_id"
                            :options="employees"
                            placeholder="Select Assistant"
                            filter-placeholder="Search assistants..."
                            :visibleItems="8"
                            input-class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:border-purple-500 focus:ring-purple-500/20 transition-all duration-200 bg-white" />
                    </div>
                </div>

                <!-- Pricing Section -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
                    <h4 class="text-sm font-bold text-slate-800 mb-3 flex items-center">
                        <i class="fa fa-calculator text-green-500 mr-2"></i>
                        Pricing Details
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Quantity</label>
                            <input v-model.number="localItem.quantity" type="number" min="1"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-blue-500/20 transition-all duration-200"
                                @input="updateItemField('quantity', $event.target.value)" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Unit Price</label>
                            <input v-model.number="localItem.unit_price" type="number" min="0" step="0.01"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-blue-500/20 transition-all duration-200"
                                @input="updateItemField('unit_price', $event.target.value)" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Discount</label>
                            <input v-model.number="localItem.discount" type="number" min="0" step="1"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:border-red-500 focus:ring-red-500/20 transition-all duration-200"
                                @input="updateItemField('discount', $event.target.value)" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Tax (%)</label>
                            <input v-model.number="localItem.tax" type="number" min="0" step="0.01"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:border-purple-500 focus:ring-purple-500/20 transition-all duration-200"
                                @input="updateItemField('tax', $event.target.value)" />
                        </div>
                    </div>
                </div>

                <!-- Calculated Totals -->
                <div class="bg-gradient-to-r from-slate-50 to-blue-50 rounded-lg p-4 border border-slate-200">
                    <h4 class="text-sm font-bold text-slate-800 mb-3 flex items-center">
                        <i class="fa fa-receipt text-blue-500 mr-2"></i>
                        Calculated Totals
                    </h4>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">Gross Amount:</span>
                            <span class="font-bold text-slate-800">₹{{ Number(localItem.gross_amount || 0).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">Net Amount:</span>
                            <span class="font-bold text-slate-800">₹{{ Number(localItem.net_amount || 0).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">Tax Amount:</span>
                            <span class="font-bold text-purple-600">₹{{ Number(localItem.tax_amount || 0).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">Total:</span>
                            <span class="font-bold text-emerald-600 text-lg">₹{{ Number(localItem.total || 0).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <button type="button" @click="$emit('close')"
                        class="px-6 py-2.5 bg-slate-200 text-slate-700 rounded-lg font-semibold hover:bg-slate-300 transition-all duration-200 flex items-center">
                        <i class="fa fa-times mr-2"></i>
                        Cancel
                    </button>
                    <button type="button" @click="save"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center">
                        <i class="fa fa-save mr-2"></i>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>



<script>
import SearchableSelect from '@/components/SearchableSelectFixed.vue'

export default {
    name: 'EditItemModal',
    components: {
        SearchableSelect
    },
    props: {
        show: Boolean,
        item: Object,
        employees: {
            type: [Array, Object],
            default: () => []
        }
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
                if (val) {
                    this.localItem = { ...val }
                    this.calculateTotals()
                }
            },
            deep: true,
            immediate: true
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
            this.calculateTotals();
        },
        calculateTotals() {
            // Calculate amounts
            this.localItem.gross_amount = this.localItem.unit_price * this.localItem.quantity;
            this.localItem.net_amount = this.localItem.gross_amount - this.localItem.discount;
            this.localItem.tax_amount = this.localItem.net_amount * (this.localItem.tax / 100);
            this.localItem.total = this.localItem.net_amount + this.localItem.tax_amount;
        },
        save() {
            // Validate numeric fields
            ['quantity', 'unit_price', 'discount', 'tax'].forEach(field => {
                this.localItem[field] = Number(this.localItem[field]) || 0;
            });

            // Ensure employee is selected
            if (!this.localItem.employee_id) {
                alert('Please select an employee');
                return;
            }

            // Calculate final totals
            this.calculateTotals();

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
