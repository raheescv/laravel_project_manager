<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-2 sm:p-4 text-center">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="$emit('close')">
            </div>

            <div class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-4 sm:align-middle sm:max-w-4xl w-full max-h-[85vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-600 px-4 sm:px-6 py-3 sm:py-4 text-white flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="bg-white/20 p-2 rounded-lg mr-3">
                            <i class="fa fa-cube text-white text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-lg sm:text-xl font-bold text-white mb-0.5">
                                Combo Offers
                            </h4>
                            <p class="text-emerald-100 text-xs">Manage combo offers for your cart</p>
                        </div>
                    </div>
                    <button type="button" @click="$emit('close')"
                        class="text-white hover:text-emerald-100 focus:outline-none transition-colors p-1.5 rounded-lg hover:bg-white/10">
                        <i class="fa fa-times text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-4 sm:px-6 py-4 sm:py-6 bg-gradient-to-br from-gray-50 to-blue-50/30">
                    <!-- Combo Offer Selection -->
                    <div class="mb-6">
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
                            <div class="lg:col-span-3">
                                <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                                    <i class="fa fa-tags text-emerald-500 mr-2"></i>
                                    Select Combo Offer
                                </label>
                                <SearchableSelect
                                    v-model="selectedComboOfferId"
                                    :options="comboOfferOptions"
                                    placeholder="Choose a combo offer..."
                                    filter-placeholder="Search combo offers..."
                                    :visibleItems="6"
                                    @change="onComboOfferSelected"
                                    input-class="w-full rounded-lg border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200 bg-white/90 backdrop-blur-sm hover:shadow-md text-sm py-2 px-3" />
                            </div>
                            <div class="lg:col-span-1 flex items-end">
                                <button type="button" @click="addComboOffer"
                                    class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 text-white py-2 px-4 rounded-lg hover:from-emerald-600 hover:to-teal-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 font-semibold text-sm flex items-center justify-center">
                                    <i class="fa fa-plus mr-1.5 text-sm"></i>
                                    Add
                                    <span v-if="selectedComboOffers.length > 0" class="ml-1.5 bg-white text-emerald-600 px-1.5 py-0.5 rounded-full text-xs font-bold">
                                        {{ selectedComboOffers.length }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Service Selection -->
                    <div v-if="selectedComboOfferId && selectedComboOffer" class="mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h6 class="font-bold text-gray-800 flex items-center text-base">
                                <i class="fa fa-list-check mr-2 text-emerald-500"></i>
                                Available Services
                            </h6>
                            <span class="badge bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-semibold border border-emerald-200">
                                {{ selectedServices.length }} Selected
                            </span>
                        </div>

                        <div v-if="Object.keys(comboOfferItems).length === 0"
                            class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-center">
                            <div class="flex items-center justify-center mb-2">
                                <i class="fa fa-exclamation-triangle text-amber-500 text-lg mr-2"></i>
                                <span class="text-amber-700 font-medium text-sm">No cart items available</span>
                            </div>
                            <p class="text-amber-600 text-xs">Please add items to cart first.</p>
                        </div>
                        <div v-else-if="Object.keys(filteredComboOfferItems).length > 0"
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="(item, key) in filteredComboOfferItems" :key="key" class="w-full">
                                <label class="w-full mb-0 cursor-pointer" :for="`service-${key}`">
                                    <div class="card service-card h-full transition-all duration-300 rounded-lg border-2 hover:shadow-md"
                                        :class="selectedServices.includes(key) ? 'border-emerald-500 bg-emerald-50 shadow-emerald-100' : 'border-gray-200 bg-white hover:border-emerald-300'">
                                        <div class="card-body p-3">
                                            <div class="flex items-center">
                                                <div class="flex-grow-1">
                                                    <input type="checkbox"
                                                        :value="key"
                                                        v-model="selectedServices"
                                                        :id="`service-${key}`"
                                                        class="form-check-input mr-2 text-emerald-500 focus:ring-emerald-500">
                                                    <span class="text-xs font-medium text-gray-700">
                                                        {{ item.employee_name }} - {{ item.name }}
                                                    </span>
                                                </div>
                                                <div class="text-end ml-2">
                                                    <div class="text-emerald-600 font-bold text-xs">
                                                        {{ formatCurrency(item.unit_price) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div v-else
                            class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                            <div class="flex items-center justify-center mb-2">
                                <i class="fa fa-info-circle text-blue-500 text-lg mr-2"></i>
                                <span class="text-blue-700 font-medium text-sm">No services available</span>
                            </div>
                            <p class="text-blue-600 text-xs">
                                All cart items are already in combo offers.
                            </p>
                        </div>
                    </div>

                    <!-- Selected Combo Offers Summary -->
                    <div v-if="selectedComboOffers.length > 0" class="selected-combo-offer-summary">
                        <div class="card border-0 shadow-md rounded-lg overflow-hidden">
                            <div class="card-body p-3 sm:p-4 bg-gradient-to-br from-white to-gray-50/50">
                                <div class="summary-header flex items-center justify-between mb-3">
                                    <div class="flex items-center">
                                        <div class="summary-icon mr-2 p-1.5 bg-emerald-100 rounded-md">
                                            <i class="fa fa-shopping-cart text-emerald-600 text-sm"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold mb-0 text-gray-800 text-base">Combo Summary</h6>
                                            <small class="text-gray-600 text-xs">Review selected offers</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="selected-combo-offer">
                                    <div class="combo-offer-grid">
                                        <div v-for="(comboOffer, index) in selectedComboOffers" :key="index" class="combo-offer-summary-item">
                                            <div class="card combo-offer-summary-card h-full rounded-md border-0 shadow-sm">
                                                <div class="card-header py-2 px-3 bg-gradient-to-r from-emerald-50 to-teal-50 border-b border-emerald-100">
                                                    <div class="flex justify-between items-center">
                                                        <div class="flex items-center gap-1.5">
                                                            <div class="combo-offer-indicator"></div>
                                                            <h6 class="combo-offer-name mb-0 text-emerald-700 font-semibold text-xs">{{ comboOffer.combo_offer_name }}</h6>
                                                        </div>
                                                        <button type="button" @click="removeComboOffer(index)"
                                                            class="btn-close btn-close-sm text-gray-400 hover:text-red-500 transition-colors p-1 rounded-md hover:bg-red-50">
                                                            <i class="fa fa-times text-xs"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="card-body p-2 sm:p-3 flex flex-col">
                                                    <div class="combo-offer-quick-stats rounded-md mb-2 p-2">
                                                        <div class="flex justify-around gap-3">
                                                            <div class="stat-item text-center">
                                                                <div class="stat-info">
                                                                    <div class="stat-value font-bold text-base text-emerald-600">{{ comboOffer.items.length }}</div>
                                                                    <div class="stat-label text-gray-600 text-xs font-medium">Services</div>
                                                                </div>
                                                            </div>
                                                            <div class="stat-item text-center">
                                                                <div class="stat-info">
                                                                    <div class="stat-value font-bold text-base text-emerald-600">
                                                                        {{ calculateDiscountPercentage(comboOffer) }}%
                                                                    </div>
                                                                    <div class="stat-label text-gray-600 text-xs font-medium">Savings</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="combo-offer-services flex-grow-1">
                                                        <div class="table-responsive h-full">
                                                            <table class="table table-sm service-price-table mb-0">
                                                                <tbody>
                                                                    <tr v-for="item in comboOffer.items" :key="item.key" class="border-b border-gray-100">
                                                                        <td class="py-1.5 w-60">
                                                                            <span class="service-name text-xs text-gray-700">{{ item.employee_name }} - {{ item.name }}</span>
                                                                        </td>
                                                                        <td class="text-end py-1.5 w-40">
                                                                            <div class="flex items-center justify-end gap-1">
                                                                                <span class="text-gray-400 line-through text-xs">
                                                                                    {{ formatCurrency(item.unit_price) }}
                                                                                </span>
                                                                                <span class="badge bg-red-100 text-red-600 rounded-full text-xs px-1 py-0.5"
                                                                                    :title="`You Save ${formatCurrency(item.unit_price - item.combo_offer_price)}`">
                                                                                    -{{ formatCurrency(item.unit_price - item.combo_offer_price) }}
                                                                                </span>
                                                                                <span class="text-emerald-600 font-bold text-xs">
                                                                                    {{ formatCurrency(item.combo_offer_price) }}
                                                                                </span>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="combo-offer-footer mt-2">
                                                        <div class="total-row flex justify-between items-center py-2 px-2.5 bg-emerald-50 rounded-md border border-emerald-100">
                                                            <span class="font-semibold text-gray-700 text-xs">Combo Total</span>
                                                            <span class="font-bold text-base text-emerald-600">{{ formatCurrency(comboOffer.amount) }}</span>
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
                <div class="bg-gradient-to-r from-gray-50 to-blue-50/30 px-4 sm:px-6 py-4 border-t border-gray-200">
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="$emit('close')"
                            class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 border border-gray-300 shadow-sm text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all duration-200">
                            <i class="fa fa-times mr-1.5"></i>
                            Close
                        </button>
                        <button type="button" @click="saveComboOffers"
                            class="inline-flex items-center justify-center px-6 sm:px-8 py-2.5 border border-transparent text-sm font-semibold rounded-lg text-white shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 focus:ring-emerald-500">
                            <i class="fa fa-check mr-1.5"></i>
                            Apply Offers
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
