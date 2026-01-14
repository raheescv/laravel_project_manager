<template>
    <div v-if="show" class="modal-overlay">
        <div class="payment-modal bg-white rounded-lg p-4 shadow-xl w-full max-w-md border border-slate-200">
            <!-- Compact Header -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-1.5 rounded mr-2">
                        <i class="fa fa-edit text-white text-xs"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Edit Item</h3>
                </div>
                <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div v-if="item" class="space-y-2">
                <!-- Compact Product Info -->
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-slate-60 rounded p-2 border border-slate-200">
                        <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center">
                            <i class="fa fa-box text-blue-500 mr-1 text-xs"></i>
                            Product
                        </label>
                        <div class="text-xs font-medium text-slate-800 bg-white px-2 py-1 rounded border truncate" :title="item.name">
                            {{ item.name }}
                        </div>
                    </div>
                    <div class="bg-slate-40 rounded p-2 border border-slate-200">
                        <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center">
                            <i class="fa fa-balance-scale text-orange-500 mr-1 text-xs"></i>
                            Unit
                        </label>
                        <select v-model="localItem.unit_id" @change="handleUnitChange"
                            class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:border-orange-500 focus:ring-orange-500/20 transition-all duration-200 bg-white">
                            <option v-for="u in availableUnits" :key="u.id" :value="u.id">
                                {{ u.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Compact Employee Selection -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center">
                            <i class="fa fa-user text-emerald-500 mr-1 text-xs"></i>
                            Employee
                        </label>
                        <SearchableSelect v-model="localItem.employee_id" :options="employees"
                            placeholder="Select Employee" filter-placeholder="Search employees..." :visibleItems="6"
                            input-class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200 bg-white" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center">
                            <i class="fa fa-user-plus text-purple-500 mr-1 text-xs"></i>
                            Assistant
                        </label>
                        <SearchableSelect v-model="localItem.assistant_id" :options="employees"
                            placeholder="Select Assistant" filter-placeholder="Search assistants..." :visibleItems="6"
                            input-class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:border-purple-500 focus:ring-purple-500/20 transition-all duration-200 bg-white" />
                    </div>
                </div>

                <!-- Compact Pricing Section -->
                <div class="bg-green-50 rounded p-3 border border-green-200">
                    <h4 class="text-xs font-bold text-slate-800 mb-2 flex items-center">
                        <i class="fa fa-calculator text-green-500 mr-1 text-xs"></i>
                        Pricing
                    </h4>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Qty</label>
                            <input v-model.number="localItem.quantity" type="number" min="0.001"
                                class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:border-blue-500 focus:ring-blue-500/20 transition-all duration-200"
                                @input="updateItemField('quantity', $event.target.value)" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Price</label>
                            <input v-model.number="localItem.unit_price" type="number" min="0" step="0.01"
                                :disabled="!canEditItemPrice" :class="[
                                    'w-full border rounded px-2 py-1 text-xs transition-all duration-200',
                                    canEditItemPrice
                                        ? 'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20'
                                        : 'border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed'
                                ]" @input="updateItemField('unit_price', $event.target.value)" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Discount</label>
                            <input v-model.number="localItem.discount" type="number" min="0" step="1"
                                class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:border-red-500 focus:ring-red-500/20 transition-all duration-200"
                                @input="updateItemField('discount', $event.target.value)" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Tax %</label>
                            <input v-model.number="localItem.tax" type="number" min="0" step="0.01"
                                class="w-full border border-slate-300 rounded px-2 py-1 text-xs focus:border-purple-500 focus:ring-purple-500/20 transition-all duration-200"
                                @input="updateItemField('tax', $event.target.value)" />
                        </div>
                    </div>
                </div>

                <!-- Compact Totals -->
                <div class="bg-slate-50 rounded p-3 border border-slate-200">
                    <h4 class="text-xs font-bold text-slate-800 mb-2 flex items-center">
                        <i class="fa fa-receipt text-blue-500 mr-1 text-xs"></i>
                        Totals
                    </h4>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">Gross:</span>
                            <span class="font-bold text-slate-800">{{ formatNumber(localItem.gross_amount || 0) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">Net:</span>
                            <span class="font-bold text-slate-800">{{ formatNumber(localItem.net_amount || 0) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">Tax:</span>
                            <span class="font-bold text-purple-600">{{ formatNumber(localItem.tax_amount || 0) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600">Total:</span>
                            <span class="font-bold text-emerald-600">{{ formatNumber(localItem.total || 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Compact Action Buttons -->
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                    <button type="button" @click="$emit('close')"
                        class="px-4 py-1.5 bg-slate-200 text-slate-700 rounded font-semibold hover:bg-slate-300 transition-all duration-200 flex items-center text-xs">
                        <i class="fa fa-times mr-1"></i>
                        Cancel
                    </button>
                    <button type="button" @click="save"
                        class="px-4 py-1.5 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 shadow hover:shadow-md transform hover:-translate-y-0.5 flex items-center text-xs">
                        <i class="fa fa-save mr-1"></i>
                        Save
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
        },
        canEditItemPrice: {
            type: Boolean,
            default: false
        }
    },
    emits: ['save', 'close'],
    data() {
        return {
            localItem: {
                ...this.item
            },
            originalConversionFactor: this.item?.conversion_factor || 1
        }
    },

    computed: {
        availableUnits() {
            const units = [];
            // Add base unit
            if (this.item.unit_id) {
                units.push({
                    id: this.item.unit_id,
                    name: this.item.unit_name || 'Base Unit',
                    conversion_factor: 1
                });
            }
            // Add sub units
            if (this.item.units && Array.isArray(this.item.units)) {
                this.item.units.forEach(u => {
                    if (u.id !== this.item.unit_id) {
                        units.push(u);
                    }
                });
            }
            return units;
        }
    },
    watch: {
        item: {
            handler(val) {
                if (val) {
                    this.localItem = {
                        ...val
                    }
                    this.originalConversionFactor = val.conversion_factor || 1
                    this.calculateTotals()
                }
            },
            deep: true,
            immediate: true
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
        updateItemField(field, value) {
            // Convert to number for numeric fields
            const numericFields = ['unit_price', 'quantity', 'discount', 'tax'];
            let processedValue = numericFields.includes(field) ? parseFloat(value) || 0 : value;

            // Ensure quantity is at least 1
            if (field === 'quantity' && processedValue < 1) {
                // processedValue = 1;
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
        handleUnitChange() {
            const selectedUnit = this.availableUnits.find(u => u.id === this.localItem.unit_id);
            if (selectedUnit) {
                const oldConversionFactor = this.localItem.conversion_factor || 1;
                const newConversionFactor = selectedUnit.conversion_factor || 1;

                // Calculate base MRP from current unit_price and old conversion factor
                const baseMRP = this.localItem.unit_price / (oldConversionFactor || 1);

                // Update unit information
                this.localItem.unit_name = selectedUnit.name;
                this.localItem.conversion_factor = newConversionFactor;

                // Recalculate unit_price based on new conversion factor
                this.localItem.unit_price = Math.round(baseMRP * newConversionFactor * 100) / 100;

                // Update the original conversion factor for future calculations
                this.originalConversionFactor = newConversionFactor;

                // Recalculate totals with new price
                this.calculateTotals();
            }
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

            this.$emit('save', {
                ...this.localItem
            });
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
