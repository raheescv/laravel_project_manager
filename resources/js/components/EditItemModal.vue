<template>
    <div v-if="show" class="posx-modal-backdrop" role="dialog" aria-modal="true" @click.self="$emit('close')">
        <div class="posx-modal payment-modal" style="max-width: 30rem" @click.stop>

            <div class="posx-modal-head">
                <h4 class="posx-modal-title">
                    <i class="fa fa-pencil"></i>
                    <span>
                        Edit Item
                        <span v-if="item" class="posx-modal-sub">{{ item.name }}</span>
                    </span>
                </h4>
                <button type="button" class="posx-modal-close" @click="$emit('close')" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div v-if="item" class="posx-modal-body space-y-3">
                <!-- Product + unit -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="posx-label mb-1"><i class="fa fa-cube"></i> Product</label>
                        <div class="posx-field flex items-center truncate" :title="item.name">{{ item.name }}</div>
                    </div>
                    <div>
                        <label class="posx-label mb-1"><i class="fa fa-exchange"></i> Unit</label>
                        <div class="relative">
                            <select v-model="localItem.unit_id" @change="handleUnitChange" class="posx-field">
                                <option v-for="u in availableUnits" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                            <i class="fa fa-angle-down posx-caret"></i>
                        </div>
                    </div>
                </div>

                <!-- Staff -->
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="posx-label mb-1"><i class="fa fa-user"></i> Employee</label>
                        <SearchableSelect v-model="localItem.employee_id" :options="employees"
                            placeholder="Select Employee" filter-placeholder="Search employees..." :visibleItems="6"
                            input-class="posx-field posx-field-select" />
                    </div>
                    <div>
                        <label class="posx-label mb-1"><i class="fa fa-user-plus"></i> Assistant</label>
                        <SearchableSelect v-model="localItem.assistant_id" :options="employees"
                            placeholder="Select Assistant" filter-placeholder="Search assistants..." :visibleItems="6"
                            input-class="posx-field posx-field-select" />
                    </div>
                </div>

                <!-- Pricing -->
                <div class="posx-section">
                    <h4 class="posx-section-title">Pricing</h4>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="posx-label mb-1">Qty</label>
                            <input v-model.number="localItem.quantity" type="number" min="0.001" class="posx-field"
                                @input="updateItemField('quantity', $event.target.value)" />
                        </div>
                        <div>
                            <label class="posx-label mb-1">Price</label>
                            <input v-model.number="localItem.unit_price" type="number" min="0" step="0.01"
                                :disabled="!canEditItemPrice" class="posx-field"
                                @input="updateItemField('unit_price', $event.target.value)" />
                        </div>
                        <div>
                            <label class="posx-label mb-1">Discount</label>
                            <input v-model.number="localItem.discount" type="number" min="0" step="1" class="posx-field"
                                @input="updateItemField('discount', $event.target.value)" />
                        </div>
                        <div>
                            <label class="posx-label mb-1">Tax %</label>
                            <input v-model.number="localItem.tax" type="number" min="0" step="0.01" class="posx-field"
                                @input="updateItemField('tax', $event.target.value)" />
                        </div>
                    </div>
                </div>

                <!-- Totals -->
                <div class="posx-section">
                    <h4 class="posx-section-title">Totals</h4>
                    <div class="posx-kv"><span>Gross</span><span class="posx-amount-muted font-bold">{{ formatNumber(localItem.gross_amount || 0) }}</span></div>
                    <div class="posx-kv"><span>Net</span><span class="posx-amount-muted font-bold">{{ formatNumber(localItem.net_amount || 0) }}</span></div>
                    <div class="posx-kv"><span>Tax</span><span class="posx-amount-muted font-bold">{{ formatNumber(localItem.tax_amount || 0) }}</span></div>
                    <div class="posx-kv"><span class="posx-ink font-extrabold">Total</span><span class="posx-amount">{{ formatNumber(localItem.total || 0) }}</span></div>
                </div>
            </div>

            <div class="posx-modal-foot">
                <button type="button" class="posx-btn posx-btn-ghost" @click="$emit('close')">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="posx-btn posx-btn-primary" @click="save">
                    <i class="fa fa-save"></i> Save
                </button>
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

            // Use base_unit from product if available, otherwise fallback to current unit_id
            const baseUnit = this.item.base_unit || {
                id: this.item.unit_id,
                name: this.item.unit_name || 'Base Unit',
                conversion_factor: 1
            };

            // Add base unit from product
            units.push({
                id: baseUnit.id,
                name: baseUnit.name || 'Base Unit',
                conversion_factor: baseUnit.conversion_factor || 1
            });

            // Add sub units
            if (this.item.units && Array.isArray(this.item.units)) {
                this.item.units.forEach(u => {
                    // Don't add base unit again if it's already in the units array
                    if (u.id !== baseUnit.id) {
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
                const newConversionFactor = selectedUnit.conversion_factor || 1;

                // Use base_unit_price from the product (base unit price)
                // If base_unit_price is not available, fallback to calculating from current price
                const baseUnitPrice = this.localItem.base_unit_price ||
                    (this.localItem.unit_price / (this.localItem.conversion_factor || 1));

                // Update unit information
                this.localItem.unit_name = selectedUnit.name;
                this.localItem.conversion_factor = newConversionFactor;

                // Recalculate unit_price based on base unit price and new conversion factor
                this.localItem.unit_price = Math.round(baseUnitPrice * newConversionFactor * 100) / 100;

                // Ensure base_unit_price is set if not already present
                if (!this.localItem.base_unit_price) {
                    this.localItem.base_unit_price = baseUnitPrice;
                }

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
/* Positioning and colour now come from .posx-modal-backdrop / .posx-modal
   in resources/css/pos-premium.css — the old fixed/translate centering would
   fight the flex-centred backdrop. */
</style>
