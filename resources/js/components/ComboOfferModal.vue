<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="$emit('close')">
            </div>

            <div class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-700 px-6 py-4 text-white flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="bg-white/20 p-2 rounded-lg mr-3">
                            <i class="fa fa-cube text-white text-lg"></i>
                        </div>
                        <h4 class="text-xl font-bold text-white">
                            Combo Selection
                        </h4>
                    </div>
                    <button type="button" @click="$emit('close')" class="text-white hover:text-gray-200 focus:outline-none transition-colors">
                        <i class="fa fa-times text-lg"></i>
                    </button>
                </div>

                                <!-- Modal Body -->
                <div class="px-6 py-6">
                    <!-- Combo Offer Selection -->
                    <div class="mb-6">
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                            <div class="lg:col-span-3">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Combo</label>
                                <SearchableSelect
                                    v-model="selectedComboOfferId"
                                    :options="comboOfferOptions"
                                    placeholder="Select Combo Offer"
                                    filter-placeholder="Search combo offers..."
                                    :visibleItems="8"
                                    @change="onComboOfferSelected"
                                    input-class="w-full rounded-lg border-slate-200 shadow-sm focus:border-purple-500 focus:ring-purple-500/20 transition-all duration-200 bg-white/90 backdrop-blur-sm hover:shadow-md text-sm py-2" />
                            </div>
                            <div class="lg:col-span-1 flex items-end">
                                <button type="button" @click="addComboOffer"
                                    class="w-full bg-gradient-to-r from-purple-500 to-indigo-600 text-white py-2 px-4 rounded-lg hover:from-purple-600 hover:to-indigo-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 font-semibold text-sm flex items-center justify-center">
                                    <i class="fa fa-shopping-cart mr-2 text-sm"></i>
                                    Add Combo
                                    <span v-if="selectedComboOffers.length > 0" class="ml-2 bg-white text-purple-600 px-2 py-0.5 rounded-full text-xs font-bold">
                                        {{ selectedComboOffers.length }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                                        <!-- Service Selection -->
                    <div v-if="selectedComboOfferId && selectedComboOffer" class="mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h6 class="font-bold text-slate-800 flex items-center">
                                <i class="fa fa-cog mr-2 text-purple-500"></i>
                                Available Services
                            </h6>
                            <span class="badge bg-purple-500 text-white px-3 py-1 rounded-full text-sm">
                                {{ selectedServices.length }} Selected
                            </span>
                        </div>

                        <div v-if="Object.keys(comboOfferItems).length === 0" class="alert alert-warning flex items-center p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <i class="fa fa-exclamation-triangle text-yellow-500 mr-2 text-lg"></i>
                            <span class="text-yellow-700">No cart items available. Please add items to cart first.</span>
                        </div>
                        <div v-else-if="Object.keys(filteredComboOfferItems).length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="(item, key) in filteredComboOfferItems" :key="key" class="w-full">
                                <label class="w-full mb-0 cursor-pointer" :for="`service-${key}`">
                                    <div class="card service-card h-full transition-all duration-200"
                                        :class="selectedServices.includes(key) ? 'border-purple-500 bg-purple-50' : 'border-slate-200'">
                                        <div class="card-body p-3">
                                            <div class="flex items-center">
                                                <div class="flex-grow-1">
                                                    <input type="checkbox"
                                                        :value="key"
                                                        v-model="selectedServices"
                                                        :id="`service-${key}`"
                                                        class="form-check-input mr-2">
                                                    <span class="text-sm font-medium text-slate-700">
                                                        {{ item.employee_name }} - {{ item.name }}
                                                    </span>
                                                </div>
                                                <div class="text-end ml-2">
                                                    <small class="text-success font-semibold block">
                                                        {{ formatCurrency(item.unit_price) }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div v-else class="alert alert-info flex items-center p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <i class="fa fa-info-circle text-blue-500 mr-2 text-lg"></i>
                            <span class="text-blue-700">
                                No services available for this combo offer.
                                Cart items: {{ Object.keys(comboOfferItems).length }},
                                Filtered: {{ Object.keys(filteredComboOfferItems).length }}
                            </span>
                        </div>
                    </div>

                    <!-- Selected Combo Offers Summary -->
                    <div v-if="selectedComboOffers.length > 0" class="selected-combo-offer-summary">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="summary-header flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="summary-icon mr-3">
                                            <i class="fa fa-shopping-cart text-purple-500"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold mb-0 text-slate-800">Combo Offer Summary</h6>
                                            <small class="text-slate-600">Review your selected combo offer and services</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="selected-combo-offer">
                                    <div class="combo-offer-grid">
                                        <div v-for="(comboOffer, index) in selectedComboOffers" :key="index" class="combo-offer-summary-item">
                                            <div class="card combo-offer-summary-card h-full">
                                                <div class="card-header py-3 px-4">
                                                    <div class="flex justify-between items-center">
                                                        <div class="flex items-center gap-2">
                                                            <div class="combo-offer-indicator"></div>
                                                            <h6 class="combo-offer-name mb-0">{{ comboOffer.combo_offer_name }}</h6>
                                                        </div>
                                                        <button type="button" @click="removeComboOffer(index)"
                                                            class="btn-close btn-close-sm text-slate-400 hover:text-slate-600 transition-colors">
                                                            <i class="fa fa-times text-sm"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="card-body p-4 flex flex-col">
                                                    <div class="combo-offer-quick-stats rounded-lg mb-4 p-4">
                                                        <div class="flex justify-around gap-4">
                                                            <div class="stat-item text-center">
                                                                <div class="stat-info">
                                                                    <div class="stat-value font-bold text-lg">{{ comboOffer.items.length }}</div>
                                                                    <div class="stat-label text-slate-600 text-sm">Services</div>
                                                                </div>
                                                            </div>
                                                            <div class="stat-item text-center">
                                                                <div class="stat-info">
                                                                    <div class="stat-value font-bold text-lg text-success">
                                                                        {{ calculateDiscountPercentage(comboOffer) }}%
                                                                    </div>
                                                                    <div class="stat-label text-slate-600 text-sm">Savings</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="combo-offer-services flex-grow-1">
                                                        <div class="table-responsive h-full">
                                                            <table class="table table-sm service-price-table mb-0">
                                                                <tbody>
                                                                    <tr v-for="item in comboOffer.items" :key="item.key">
                                                                        <td class="py-2 w-60">
                                                                            <span class="service-name text-sm">{{ item.employee_name }} - {{ item.name }}</span>
                                                                        </td>
                                                                        <td class="text-end py-2 w-40">
                                                                            <div class="flex items-center justify-end gap-2">
                                                                                <span class="text-muted line-through text-xs">
                                                                                    {{ formatCurrency(item.unit_price) }}
                                                                                </span>
                                                                                <span class="badge bg-red-100 text-red-600 rounded-pill text-xs"
                                                                                    :title="`You Save ${formatCurrency(item.unit_price - item.combo_offer_price)}`">
                                                                                    -{{ formatCurrency(item.unit_price - item.combo_offer_price) }}
                                                                                </span>
                                                                                <span class="text-success font-bold text-sm">
                                                                                    {{ formatCurrency(item.combo_offer_price) }}
                                                                                </span>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="combo-offer-footer mt-4">
                                                        <div class="total-row flex justify-between items-center py-3 px-4 bg-slate-50 rounded-lg">
                                                            <span class="font-medium text-slate-700">Combo Offer Total</span>
                                                            <span class="font-bold text-lg text-slate-800">{{ formatCurrency(comboOffer.amount) }}</span>
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
                <div class="bg-gradient-to-r from-slate-50 to-gray-50 px-6 py-4 border-t border-slate-200">
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="$emit('close')"
                            class="inline-flex items-center justify-center px-6 py-3 border border-slate-300 shadow-sm text-sm font-semibold rounded-xl text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                            <i class="fa fa-times mr-2"></i>
                            Close
                        </button>
                        <button type="button" @click="saveComboOffers"
                            class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-semibold rounded-xl text-white shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 focus:ring-purple-500">
                            <i class="fa fa-check mr-2"></i>
                            Submit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import SearchableSelect from '@/components/SearchableSelectFixed.vue'
import { ref, computed, watch, onMounted } from 'vue'
import { useToast } from 'vue-toastification'

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
            console.log(props.cartItems);
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
            console.log(existingComboOfferServices);
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
                return
            }

            if (!selectedComboOffer.value || selectedServices.value.length !== selectedComboOffer.value.count) {
                toast.error(`Please select ${selectedComboOffer.value.count} services for this combo offer`)
                return
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
                return
            }

            const comboOfferPrices = calculateComboOfferPrices(selectedServices.value, selectedComboOfferId.value)

            const item = {
                combo_offer_id: selectedComboOfferId.value,
                combo_offer_name: selectedComboOffer.value.name,
                amount: selectedComboOffer.value.amount,
                items: comboOfferPrices
            }

            selectedComboOffers.value.push(item)

            // Update combo offer items with new prices
            comboOfferPrices.forEach(item => {
                comboOfferItems.value[item.key] = item
            })

            selectedComboOfferId.value = null
            selectedServices.value = []

            toast.success('Combo Offer added successfully')
        }

        // Calculate combo offer prices
        const calculateComboOfferPrices = (selectedServices, comboOfferId) => {
            const services = selectedServices.map(key => comboOfferItems.value[key]).filter(Boolean)
            const totalOriginalPrice = services.reduce((sum, item) => sum + item.unit_price, 0)
            const comboOfferAmount = selectedComboOffer.value.amount

            return services.map(item => {
                const comboOfferPrice = Math.round((item.unit_price / totalOriginalPrice) * comboOfferAmount * 100) / 100
                const discount = Math.round((item.unit_price - comboOfferPrice) * 100) / 100

                return {
                    ...item,
                    combo_offer_price: comboOfferPrice,
                    discount: discount,
                    combo_offer_id: comboOfferId
                }
            })
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

        // Calculate discount percentage
        const calculateDiscountPercentage = (comboOffer) => {
            const originalTotal = comboOffer.items.reduce((sum, item) => sum + (parseFloat(item.unit_price) || 0), 0)
            if (originalTotal === 0) return 0
            const discountPercent = Math.round((1 - (parseFloat(comboOffer.amount) || 0) / originalTotal) * 100 * 10) / 10
            return isNaN(discountPercent) ? 0 : discountPercent
        }

        // Save combo offers
        const saveComboOffers = () => {
            emit('save', {
                comboOfferItems: comboOfferItems.value,
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
    background: linear-gradient(to right, rgba(147, 51, 234, 0.05), rgba(59, 130, 246, 0.05));
    border: 1px solid rgba(147, 51, 234, 0.1);
}

.stat-icon-wrapper {
    width: 36px;
    height: 36px;
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
    font-size: 1.25rem;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.875rem;
    color: #64748b;
}

.combo-offer-summary-card {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 16px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(147, 51, 234, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: transform 0.3s ease;
}

.combo-offer-summary-card:hover {
    transform: translateY(-4px);
}

.combo-offer-indicator {
    width: 3px;
    height: 16px;
    background: #8b5cf6;
    border-radius: 2px;
}

.combo-offer-name {
    font-size: 0.9375rem;
    font-weight: 500;
}

.btn-close-sm {
    font-size: 0.75rem;
    padding: 0.25rem;
}

.service-card {
    transition: all 0.2s ease;
    cursor: pointer;
    border-radius: 8px;
}

.service-card:hover {
    border-color: #8b5cf6 !important;
    background-color: #f8fafc;
    transform: translateY(-2px);
}

.service-card .form-check-input {
    pointer-events: none;
}

.combo-offer-grid {
    display: grid;
    gap: 1rem;
    padding: 0.5rem;
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
    border: 1px solid #e2e8f0;
    border-radius: 8px;
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
    font-size: 0.875rem;
}

.service-price-table td {
    border: none;
    vertical-align: middle;
    padding: 0.4rem 0.75rem;
}

.service-name {
    font-size: 0.875rem;
    color: #374151;
}

.w-60 {
    width: 60%;
}

.w-40 {
    width: 40%;
}

.total-row {
    border-top: 1px solid #e2e8f0;
    background: rgba(147, 51, 234, 0.02);
}

@media (max-width: 767.98px) {
    .combo-offer-quick-stats {
        flex-wrap: wrap;
    }

    .service-name {
        font-size: 0.8125rem;
    }
}
</style>
