<template>
    <div v-if="show" class="posx-modal-backdrop" aria-labelledby="modal-title" role="dialog" aria-modal="true"
        @click.self="$emit('close')">
        <div class="posx-modal" style="max-width: 64rem" @click.stop>
                <div class="posx-modal-head">
                    <h4 class="posx-modal-title">
                        <i class="fa fa-cube"></i>
                        <span>
                            Combo Offers
                            <span class="posx-modal-sub">Manage combo offers for your cart</span>
                        </span>
                    </h4>
                    <button type="button" class="posx-modal-close" @click="$emit('close')" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="posx-modal-body">
                    <!-- Combo Offer Selection -->
                    <div class="mb-6">
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
                            <div class="lg:col-span-3">
                                <label class="block text-sm font-semibold posx-ink-2 mb-2 flex items-center">
                                    <i class="fa fa-tags posx-ok-ink mr-2"></i>
                                    Select Combo Offer
                                </label>
                                <SearchableSelect v-model="selectedComboOfferId" :options="comboOfferOptions"
                                    placeholder="Choose a combo offer..." filter-placeholder="Search combo offers..."
                                    :visibleItems="6" @change="onComboOfferSelected"
                                    input-class="w-full rounded-lg posx-hairline shadow-sm focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200 posx-surface backdrop-blur-sm hover:shadow-md text-sm py-2 px-3" />
                            </div>
                            <div class="lg:col-span-1 flex items-end">
                                <button type="button" @click="addComboOffer"
                                    class="posx-btn posx-btn-primary w-full">
                                    <i class="fa fa-plus mr-1.5 text-sm"></i>
                                    Add
                                    <span v-if="selectedComboOffers.length > 0"
                                        class="ml-1.5 posx-surface posx-ok-ink px-1.5 py-0.5 rounded-full text-xs font-bold">
                                        {{ selectedComboOffers.length }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Service Selection -->
                    <div v-if="selectedComboOfferId && selectedComboOffer" class="mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h6 class="font-bold posx-ink flex items-center text-base">
                                <i class="fa fa-list-ul mr-2 posx-ok-ink"></i>
                                Available Services
                            </h6>
                            <span
                                class="badge posx-surface-2 posx-ok-ink px-3 py-1 rounded-full text-xs font-semibold border posx-hairline">
                                {{ selectedServices.length }} Selected
                            </span>
                        </div>

                        <div v-if="Object.keys(comboOfferItems).length === 0"
                            class="posx-surface-2 border posx-hairline rounded-lg p-4 text-center">
                            <div class="flex items-center justify-center mb-2">
                                <i class="fa fa-exclamation-triangle posx-acc-ink text-lg mr-2"></i>
                                <span class="posx-acc-ink font-medium text-sm">No cart items available</span>
                            </div>
                            <p class="posx-acc-ink text-xs">Please add items to cart first.</p>
                        </div>
                        <div v-else-if="Object.keys(filteredComboOfferItems).length > 0"
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="(item, key) in filteredComboOfferItems" :key="key" class="w-full">
                                <label class="w-full mb-0 cursor-pointer" :for="`service-${key}`">
                                    <div class="card service-card h-full transition-all duration-300 rounded-lg border-2 hover:shadow-md"
                                        :class="selectedServices.includes(key) ? 'border-emerald-500 posx-surface-2 shadow-emerald-100' : 'posx-hairline posx-surface hover:posx-hairline'">
                                        <div class="card-body p-3">
                                            <div class="flex items-center">
                                                <div class="flex-grow-1">
                                                    <input type="checkbox" :value="key" v-model="selectedServices"
                                                        :id="`service-${key}`"
                                                        class="form-check-input mr-2 posx-ok-ink focus:ring-emerald-500">
                                                    <span class="text-xs font-medium posx-ink-2">
                                                        {{ item.employee_name }} - {{ item.name }}
                                                    </span>
                                                </div>
                                                <div class="text-end ml-2">
                                                    <div class="posx-ok-ink font-bold text-xs">
                                                        {{ formatCurrency(item.unit_price) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div v-else class="posx-surface-2 border posx-hairline rounded-lg p-4 text-center">
                            <div class="flex items-center justify-center mb-2">
                                <i class="fa fa-info-circle posx-pri-ink text-lg mr-2"></i>
                                <span class="posx-pri-ink font-medium text-sm">No services available</span>
                            </div>
                            <p class="posx-pri-ink text-xs">
                                All cart items are already in combo offers.
                            </p>
                        </div>
                    </div>

                    <!-- Selected Combo Offers Summary -->
                    <div v-if="selectedComboOffers.length > 0" class="selected-combo-offer-summary">
                        <div class="card border-0 shadow-md rounded-lg overflow-hidden">
                            <div class="card-body p-3 sm:p-4 posx-surface-2">
                                <div class="summary-header flex items-center justify-between mb-3">
                                    <div class="flex items-center">
                                        <div class="summary-icon mr-2 p-1.5 posx-surface-2 rounded-md">
                                            <i class="fa fa-shopping-cart posx-ok-ink text-sm"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold mb-0 posx-ink text-base">Combo Summary</h6>
                                            <small class="posx-ink-2 text-xs">Review selected offers</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="selected-combo-offer">
                                    <div class="combo-offer-grid">
                                        <div v-for="(comboOffer, index) in selectedComboOffers" :key="index"
                                            class="combo-offer-summary-item">
                                            <div
                                                class="card combo-offer-summary-card h-full rounded-md border-0 shadow-sm">
                                                <div
                                                    class="card-header py-2 px-3 posx-surface-2 border-b posx-hairline">
                                                    <div class="flex justify-between items-center">
                                                        <div class="flex items-center gap-1.5">
                                                            <div class="combo-offer-indicator"></div>
                                                            <h6
                                                                class="combo-offer-name mb-0 posx-ok-ink font-semibold text-xs">
                                                                {{ comboOffer.combo_offer_name }}</h6>
                                                        </div>
                                                        <button type="button" @click="removeComboOffer(index)"
                                                            class="btn-close btn-close-sm posx-muted hover:posx-danger-ink transition-colors p-1 rounded-md hover:posx-surface-2">
                                                            <i class="fa fa-times text-xs"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="card-body p-2 sm:p-3 flex flex-col">
                                                    <div class="combo-offer-quick-stats rounded-md mb-2 p-2">
                                                        <div class="flex justify-around gap-3">
                                                            <div class="stat-item text-center">
                                                                <div class="stat-info">
                                                                    <div
                                                                        class="stat-value font-bold text-base posx-ok-ink">
                                                                        {{ comboOffer.items.length }}</div>
                                                                    <div
                                                                        class="stat-label posx-ink-2 text-xs font-medium">
                                                                        Services</div>
                                                                </div>
                                                            </div>
                                                            <div class="stat-item text-center">
                                                                <div class="stat-info">
                                                                    <div
                                                                        class="stat-value font-bold text-base posx-ok-ink">
                                                                        {{ calculateDiscountPercentage(comboOffer) }}%
                                                                    </div>
                                                                    <div
                                                                        class="stat-label posx-ink-2 text-xs font-medium">
                                                                        Savings</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="combo-offer-services flex-grow-1">
                                                        <div class="table-responsive h-full">
                                                            <table class="table table-sm service-price-table mb-0">
                                                                <tbody>
                                                                    <tr v-for="item in comboOffer.items" :key="item.key"
                                                                        class="border-b posx-hairline">
                                                                        <td class="py-1.5 w-60">
                                                                            <span
                                                                                class="service-name text-xs posx-ink-2">{{
                                                                                item.employee_name }} - {{ item.name
                                                                                }}</span>
                                                                        </td>
                                                                        <td class="text-end py-1.5 w-40">
                                                                            <div
                                                                                class="flex items-center justify-end gap-1">
                                                                                <span
                                                                                    class="posx-muted line-through text-xs">
                                                                                    {{ formatCurrency(item.unit_price)
                                                                                    }}
                                                                                </span>
                                                                                <span
                                                                                    class="badge posx-surface-2 posx-danger-ink rounded-full text-xs px-1 py-0.5"
                                                                                    :title="`You Save ${formatCurrency(item.unit_price - item.combo_offer_price)}`">
                                                                                    -{{ formatCurrency(item.unit_price -
                                                                                    item.combo_offer_price) }}
                                                                                </span>
                                                                                <span
                                                                                    class="posx-ok-ink font-bold text-xs">
                                                                                    {{
                                                                                    formatCurrency(item.combo_offer_price)
                                                                                    }}
                                                                                </span>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="combo-offer-footer mt-2">
                                                        <div
                                                            class="total-row flex justify-between items-center py-2 px-2.5 posx-surface-2 rounded-md border posx-hairline">
                                                            <span class="font-semibold posx-ink-2 text-xs">Combo
                                                                Total</span>
                                                            <span class="font-bold text-base posx-ok-ink">{{
                                                                formatCurrency(comboOffer.amount) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="posx-modal-foot">
                    <button type="button" class="posx-btn posx-btn-ghost" @click="$emit('close')">
                        <i class="fa fa-times"></i> Close
                    </button>
                    <button type="button" class="posx-btn posx-btn-primary" @click="saveComboOffers">
                        <i class="fa fa-check"></i> Apply Offers
                    </button>
                </div>
        </div>
    </div>
</template>

<script>
import SearchableSelect from '@/components/SearchableSelectFixed.vue'
import { ref, computed, watch, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import {
    calculateComboOfferPrices as calculateComboOfferPricesUtil,
    calculateDiscountPercentage as calculateDiscountPercentageUtil
} from '@/utils/comboOfferCalculations'

export default {
    name: 'ComboOfferModal',
    components: {
        SearchableSelect
    },
    props: {
        show: {
            type: Boolean,
            default: false
        },
        cartItems: {
            type: Object,
            default: () => ({})
        },
        initialComboOffers: {
            type: Array,
            default: () => []
        }
    },
    emits: ['close', 'save'],

    setup(props, { emit }) {
        const toast = useToast()

        // Reactive data
        const selectedComboOfferId = ref(null)
        const selectedComboOffer = ref(null)
        const selectedServices = ref([])
        const selectedComboOffers = ref([])
        const comboOfferItems = ref({})
        const comboOffers = ref([])
        const loading = ref(false)

        // Convert cart items to combo offer items format
        const convertCartItemsToComboItems = () => {
            const items = {}

            if (!props.cartItems || Object.keys(props.cartItems).length === 0) {
                return items
            }
            Object.entries(props.cartItems).forEach(([key, item]) => {
                if (item && item.name) { // Ensure item has required fields
                    items[key] = {
                        key: key,
                        employee_id: item.employee_id,
                        employee_name: item.employee_name,
                        inventory_id: item.inventory_id,
                        product_id: item.product_id,
                        name: item.name,
                        unit_price: parseFloat(item.unit_price),
                        quantity: parseInt(item.quantity),
                        discount: parseFloat(item.discount || 0),
                        tax: parseFloat(item.tax || 0),
                        gross_amount: parseFloat(item.gross_amount),
                        net_amount: parseFloat(item.net_amount),
                        tax_amount: parseFloat(item.tax_amount),
                        total: parseFloat(item.total)
                    }
                }
            })
            return items
        }

        // Load combo offers from API
        const loadComboOffers = async () => {
            loading.value = true
            try {
                const response = await axios.get('/combo_offer/list')
                if (response.data?.items) {
                    comboOffers.value = response.data.items
                }
            } catch (error) {
                toast.error('Failed to load combo offers')
            } finally {
                loading.value = false
            }
        }

        // Combo offer options for select
        const comboOfferOptions = computed(() => {
            return comboOffers.value.map(offer => ({
                value: offer.id,
                label: `${offer.name} - ${formatCurrency(offer.amount)} (${offer.count} services)`,
                name: offer.name,
                amount: offer.amount,
                count: offer.count,
                description: offer.description
            }))
        })

        // Filtered combo offer items (excluding already selected services)
        const filteredComboOfferItems = computed(() => {
            // Get all items that are already in any combo offer (including initial ones)
            const existingComboOfferServices = selectedComboOffers.value
                .flatMap(combo => combo.items)
                .map(item => item.key)
            const filtered = Object.entries(comboOfferItems.value)
                .filter(([key, item]) => {
                    // Only show items that are not already in any combo offer
                    const isExcluded = existingComboOfferServices.includes(key)
                    return !isExcluded && item
                })
                .reduce((acc, [key, item]) => {
                    acc[key] = item
                    return acc
                }, {})

            return filtered
        })

        // Handle combo offer selection
        const onComboOfferSelected = (comboOfferId) => {
            if (!comboOfferId) {
                selectedComboOffer.value = null
                selectedServices.value = []
                return
            }

            selectedComboOffer.value = comboOffers.value.find(offer => offer.id === comboOfferId)
            selectedServices.value = []
        }

        // Add combo offer
        const addComboOffer = () => {
            if (!selectedComboOfferId.value) {
                toast.error('Please select a combo offer first')
                return false
            }

            if (!selectedComboOffer.value) {
                toast.error('Combo offer not found. Please select again.')
                return false
            }

            if (selectedServices.value.length !== selectedComboOffer.value.count) {
                toast.error(`Please select exactly ${selectedComboOffer.value.count} service(s) for this combo offer. Currently selected: ${selectedServices.value.length}`)
                return false
            }

            // Check if services are already in another combo offer
            const existingComboOfferServices = selectedComboOffers.value
                .flatMap(combo => combo.items)
                .map(item => item.key)

            const hasConflict = selectedServices.value.some(serviceKey =>
                existingComboOfferServices.includes(serviceKey)
            )

            if (hasConflict) {
                toast.error('Some services are already in another combo offer')
                return false
            }

            // Calculate combo offer prices (this also updates comboOfferItems internally, matching PHP behavior)
            const comboOfferPrices = calculateComboOfferPrices(selectedServices.value, selectedComboOfferId.value)

            if (!comboOfferPrices || comboOfferPrices.length === 0) {
                toast.error('Failed to calculate combo offer prices')
                return false
            }

            // Create combo offer item (matching PHP structure)
            const item = {
                combo_offer_id: selectedComboOfferId.value,
                combo_offer_name: selectedComboOffer.value.name,
                amount: selectedComboOffer.value.amount,
                items: comboOfferPrices
            }

            // Add to selected combo offers (matching PHP: $this->selectedComboOffers[] = $item)
            selectedComboOffers.value.push(item)


            // Reset selection (matching PHP: $this->selectedComboOfferId = null; $this->selectedServices = [])
            selectedComboOfferId.value = null
            selectedServices.value = []

            toast.success('Combo Offer added successfully')
            return true
        }

        // Calculate combo offer prices using utility function
        const calculateComboOfferPrices = (selectedServices, comboOfferId) => {
            try {
                // Use utility function that matches PHP logic exactly
                const calculatedItems = calculateComboOfferPricesUtil(
                    selectedServices,
                    comboOfferId,
                    comboOfferItems.value,
                    selectedComboOffer.value
                )

                // Update comboOfferItems in place (matching PHP behavior)
                calculatedItems.forEach(item => {
                    comboOfferItems.value[item.key] = item
                })

                return calculatedItems
            } catch (error) {
                toast.error(error.message || 'Failed to calculate combo offer prices')
                return []
            }
        }

        // Remove combo offer
        const removeComboOffer = (index) => {
            if (!selectedComboOffers.value[index]) {
                toast.error('Invalid combo offer')
                return
            }

            // Reset prices for items in the removed combo offer
            selectedComboOffers.value[index].items.forEach(item => {
                if (comboOfferItems.value[item.key]) {
                    comboOfferItems.value[item.key].combo_offer_price = 0
                    comboOfferItems.value[item.key].discount = 0
                    comboOfferItems.value[item.key].combo_offer_id = null
                }
            })

            selectedComboOffers.value.splice(index, 1)
            toast.success('Combo Offer removed successfully')
        }

        // Calculate discount percentage using utility function
        const calculateDiscountPercentage = (comboOffer) => {
            return calculateDiscountPercentageUtil(comboOffer)
        }

        // Save combo offers
        const saveComboOffers = () => {
            // If no combo offers are added but user has selected one, try to add it automatically
            if (selectedComboOffers.value.length === 0) {
                // Ensure selectedComboOffer is set if we have an ID
                if (selectedComboOfferId.value && !selectedComboOffer.value) {
                    selectedComboOffer.value = comboOffers.value.find(offer => offer.id === selectedComboOfferId.value)
                }

                // Check if user has selected a combo offer and services
                if (selectedComboOfferId.value && selectedComboOffer.value && selectedServices.value.length > 0) {
                    // Check if the selection matches the required count
                    if (selectedServices.value.length === selectedComboOffer.value.count) {
                        // Try to add the combo offer automatically
                        const added = addComboOffer()
                        // If addComboOffer returns false or still no combo offers, show error
                        if (!added || selectedComboOffers.value.length === 0) {
                            toast.error('Failed to add combo offer. Please check your selection and try clicking "Add" manually.')
                            return
                        }
                    } else {
                        toast.error(`Please select exactly ${selectedComboOffer.value.count} service(s) for this combo offer (currently selected: ${selectedServices.value.length}), then click "Add"`)
                        return
                    }
                } else {
                    if (!selectedComboOfferId.value) {
                        toast.error('Please select a combo offer first')
                    } else if (!selectedComboOffer.value) {
                        toast.error('Combo offer not found. Please select again.')
                    } else if (selectedServices.value.length === 0) {
                        toast.error('Please select services for the combo offer, then click "Add"')
                    } else {
                        toast.error('Please select a combo offer and services, then click "Add" before applying')
                    }
                    return
                }
            }

            // Build a map of items that are in combo offers with their pricing
            const itemsWithComboOffers = {}

            // Collect all items from all selected combo offers
            selectedComboOffers.value.forEach(comboOffer => {
                comboOffer.items.forEach(item => {
                    // Use the item from the combo offer (which has the calculated pricing)
                    if (item.key) {
                        itemsWithComboOffers[item.key] = {
                            ...comboOfferItems.value[item.key], // Get base item data
                            ...item, // Override with combo offer pricing
                            combo_offer_price: item.combo_offer_price || 0,
                            discount: item.discount || 0,
                            combo_offer_id: item.combo_offer_id || null
                        }
                    }
                })
            })

            emit('save', {
                comboOfferItems: itemsWithComboOffers, // Only send items that are in combo offers
                selectedComboOffers: selectedComboOffers.value
            })
            emit('close')
        }

        // Format currency
        const formatCurrency = (amount) => {
            return new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                minimumFractionDigits: 2
            }).format(amount)
        }

        // Initialize when modal opens
        watch(() => props.show, (newVal) => {
            if (newVal) {
                comboOfferItems.value = convertCartItemsToComboItems()
                loadComboOffers()

                // Initialize with existing combo offers if any
                if (props.initialComboOffers && props.initialComboOffers.length > 0) {
                    selectedComboOffers.value = props.initialComboOffers

                    // Update combo offer items with pricing from existing combo offers
                    props.initialComboOffers.forEach(comboOffer => {
                        comboOffer.items.forEach(item => {
                            const itemKey = item.key || `${item.employee_id}-${item.inventory_id}`
                            if (comboOfferItems.value[itemKey]) {
                                comboOfferItems.value[itemKey] = {
                                    ...comboOfferItems.value[itemKey],
                                    combo_offer_price: item.combo_offer_price || 0,
                                    discount: item.discount || 0,
                                    combo_offer_id: item.combo_offer_id || null
                                }
                            }
                        })
                    })
                }
            }
        })

        // Watch for cart items changes
        watch(() => props.cartItems, (newItems) => {
            if (props.show && newItems) {
                comboOfferItems.value = convertCartItemsToComboItems()
            }
        }, { deep: true })

        // Watch for initial combo offers changes
        watch(() => props.initialComboOffers, (newComboOffers) => {
            if (props.show && newComboOffers && newComboOffers.length > 0) {
                selectedComboOffers.value = newComboOffers

                // Update combo offer items with pricing from existing combo offers
                newComboOffers.forEach(comboOffer => {
                    comboOffer.items.forEach(item => {
                        const itemKey = item.key || `${item.employee_id}-${item.inventory_id}`
                        if (comboOfferItems.value[itemKey]) {
                            comboOfferItems.value[itemKey] = {
                                ...comboOfferItems.value[itemKey],
                                combo_offer_price: item.combo_offer_price || 0,
                                discount: item.discount || 0,
                                combo_offer_id: item.combo_offer_id || null
                            }
                        }
                    })
                })
            }
        }, { deep: true })

        onMounted(() => {
            if (props.show) {
                comboOfferItems.value = convertCartItemsToComboItems()
                loadComboOffers()

                // Initialize with existing combo offers if any
                if (props.initialComboOffers && props.initialComboOffers.length > 0) {
                    selectedComboOffers.value = props.initialComboOffers

                    // Update combo offer items with pricing from existing combo offers
                    props.initialComboOffers.forEach(comboOffer => {
                        comboOffer.items.forEach(item => {
                            const itemKey = item.key || `${item.employee_id}-${item.inventory_id}`
                            if (comboOfferItems.value[itemKey]) {
                                comboOfferItems.value[itemKey] = {
                                    ...comboOfferItems.value[itemKey],
                                    combo_offer_price: item.combo_offer_price || 0,
                                    discount: item.discount || 0,
                                    combo_offer_id: item.combo_offer_id || null
                                }
                            }
                        })
                    })
                }
            }
        })

        return {
            selectedComboOfferId,
            selectedComboOffer,
            selectedServices,
            selectedComboOffers,
            comboOfferItems,
            comboOffers,
            loading,
            comboOfferOptions,
            filteredComboOfferItems,
            onComboOfferSelected,
            addComboOffer,
            removeComboOffer,
            calculateDiscountPercentage,
            saveComboOffers,
            formatCurrency
        }
    }
}
</script>

<style scoped>
.combo-offer-quick-stats {
    background: linear-gradient(to right, rgba(16, 185, 129, 0.05), rgba(20, 184, 166, 0.05));
    border: 1px solid rgba(16, 185, 129, 0.1);
}

.stat-icon-wrapper {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.stat-info {
    text-align: center;
}

.stat-value {
    font-size: 1.125rem;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.75rem;
    color: #6b7280;
}

.combo-offer-summary-card {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 8px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(16, 185, 129, 0.1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.combo-offer-summary-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.combo-offer-indicator {
    width: 2px;
    height: 12px;
    background: #10b981;
    border-radius: 1px;
}

.combo-offer-name {
    font-size: 0.75rem;
    font-weight: 600;
}

.btn-close-sm {
    font-size: 0.75rem;
    padding: 0.25rem;
}

.service-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border-radius: 6px;
}

.service-card:hover {
    border-color: #10b981 !important;
    background-color: #f0fdf4;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.12);
}

.service-card .form-check-input {
    pointer-events: none;
}

.combo-offer-grid {
    display: grid;
    gap: 0.75rem;
    padding: 0.125rem;
}

.combo-offer-grid:has(.combo-offer-summary-item:only-child) {
    grid-template-columns: 1fr;
}

.combo-offer-grid:not(:has(.combo-offer-summary-item:only-child)) {
    grid-template-columns: repeat(2, 1fr);
}

@media (max-width: 767.98px) {
    .combo-offer-grid {
        grid-template-columns: 1fr !important;
    }
}

.combo-offer-summary-item {
    min-width: 0;
}

.combo-offer-summary-card {
    height: 100%;
    display: flex;
    flex-direction: column;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.combo-offer-summary-card .card-body {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 0.75rem;
}

.combo-offer-services {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
}

.service-price-table {
    margin-bottom: 0;
    font-size: 0.6875rem;
}

.service-price-table td {
    border: none;
    vertical-align: middle;
    padding: 0.375rem 0.5rem;
}

.service-name {
    font-size: 0.6875rem;
    color: #374151;
}

.w-60 {
    width: 60%;
}

.w-40 {
    width: 40%;
}

.total-row {
    border-top: 1px solid #e5e7eb;
    background: rgba(16, 185, 129, 0.02);
}

@media (max-width: 767.98px) {
    .combo-offer-quick-stats {
        flex-wrap: wrap;
    }

    .service-name {
        font-size: 0.625rem;
    }

    .stat-value {
        font-size: 0.875rem;
    }

    .combo-offer-summary-card .card-body {
        padding: 0.5rem;
    }

    .service-price-table td {
        padding: 0.25rem 0.375rem;
    }
}

@media (max-width: 480px) {
    .combo-offer-grid {
        gap: 0.5rem;
    }

    .stat-value {
        font-size: 0.75rem;
    }

    .stat-label {
        font-size: 0.625rem;
    }

    .combo-offer-summary-card .card-body {
        padding: 0.375rem;
    }
}
</style>
