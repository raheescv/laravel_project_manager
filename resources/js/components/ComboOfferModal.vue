<template>
    <div v-if="show" class="posx-modal-backdrop" aria-labelledby="combo-offer-modal-title" role="dialog"
        aria-modal="true" @click.self="$emit('close')">
        <div class="posx-modal cbx" style="max-width: 68rem" @click.stop>
            <div class="posx-modal-head">
                <h4 id="combo-offer-modal-title" class="posx-modal-title">
                    <i class="fa fa-cube"></i>
                    <span>
                        Combo Offers
                        <span class="posx-modal-sub">Bundle cart services into a fixed-price offer</span>
                    </span>
                </h4>
                <span v-if="selectedComboOffers.length" class="cbx-head-pill">
                    {{ selectedComboOffers.length }} applied · save {{ formatCurrency(summaryTotals.saving) }}
                </span>
                <button type="button" class="posx-modal-close" @click="$emit('close')" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="posx-modal-body">
                <div v-if="cartServices.length === 0" class="cbx-empty">
                    <i class="fa fa-shopping-cart"></i>
                    No services in the cart yet — add items first, then build a combo.
                </div>

                <div v-else class="cbx-grid">
                    <!-- Offer rail -->
                    <div class="cbx-rail">
                        <label class="cbx-search">
                            <i class="fa fa-search"></i>
                            <input v-model="offerSearch" type="text" placeholder="Search offers…">
                        </label>
                        <div v-if="loading && comboOffers.length === 0" class="cbx-rail-note">
                            <i class="fa fa-spinner fa-spin"></i> Loading offers…
                        </div>
                        <div v-else-if="filteredComboOffers.length === 0" class="cbx-rail-note">
                            {{ offerSearch ? 'No offer matches your search' : 'No combo offers configured' }}
                        </div>
                        <div class="cbx-offers">
                            <button v-for="offer in filteredComboOffers" :key="offer.id" type="button" class="cbx-offer"
                                :class="{ 'is-on': offer.id === selectedComboOfferId }"
                                @click="selectComboOffer(offer.id)">
                                <span class="cbx-offer-count">{{ offer.count }}</span>
                                <span class="cbx-offer-text">
                                    <b>{{ offer.name }}</b>
                                    <span>
                                        {{ offer.count }} services
                                        <em v-if="appliedCountByOfferId[offer.id]"> · {{ appliedCountByOfferId[offer.id] }}× added</em>
                                    </span>
                                </span>
                                <span class="cbx-offer-price">{{ formatCurrency(offer.amount) }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Builder -->
                    <div class="cbx-build">
                        <div v-if="!selectedComboOffer" class="cbx-empty cbx-empty-fill">
                            <i class="fa fa-hand-o-left"></i>
                            Choose an offer on the left to start building a combo
                        </div>

                        <template v-else>
                            <div class="cbx-build-head">
                                <div>
                                    <h5>{{ selectedComboOffer.name }}</h5>
                                    <p>
                                        Pick any {{ selectedComboOffer.count }} services · customer pays
                                        {{ formatCurrency(selectedComboOffer.amount) }} for all
                                    </p>
                                </div>
                                <div class="cbx-progress">
                                    <b>{{ selectedServices.length }} of {{ selectedComboOffer.count }} picked</b>
                                    <div class="cbx-slots" :class="{ 'is-done': isSelectionComplete }">
                                        <i v-for="slot in Number(selectedComboOffer.count)" :key="slot"
                                            :class="{ 'is-filled': slot <= selectedServices.length }"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="cbx-services">
                                <button v-for="service in cartServices" :key="service.key" type="button"
                                    class="cbx-service" :class="{
                                        'is-on': selectedServices.includes(service.key),
                                        'is-locked': lockedComboNameByKey[service.key],
                                        'is-dim': isServiceDimmed(service.key)
                                    }" :disabled="!!lockedComboNameByKey[service.key] || isServiceDimmed(service.key)"
                                    :title="lockedComboNameByKey[service.key] ? `Already in ${lockedComboNameByKey[service.key]}` : ''"
                                    @click="toggleService(service.key)">
                                    <span class="cbx-check"><i class="fa fa-check"></i></span>
                                    <span class="cbx-service-name">
                                        <span v-if="service.employee_name" class="cbx-staff">{{ service.employee_name }} ·</span>
                                        {{ service.name }}
                                    </span>
                                    <span v-if="lockedComboNameByKey[service.key]" class="cbx-lock">
                                        <i class="fa fa-lock"></i> {{ lockedComboNameByKey[service.key] }}
                                    </span>
                                    <span v-else class="cbx-money">{{ formatCurrency(service.unit_price) }}</span>
                                </button>
                            </div>

                            <div class="cbx-preview">
                                <div class="cbx-preview-figures">
                                    <template v-if="selectedServices.length">
                                        <div class="cbx-kv">
                                            <span>Regular</span>
                                            <b>{{ formatCurrency(pickedRegularTotal) }}</b>
                                        </div>
                                        <div class="cbx-kv">
                                            <span>Combo price</span>
                                            <b>{{ formatCurrency(selectedComboOffer.amount) }}</b>
                                        </div>
                                        <div class="cbx-kv">
                                            <span>Saving</span>
                                            <b class="is-ok">
                                                <template v-if="isSelectionComplete">
                                                    {{ formatCurrency(pickedRegularTotal - selectedComboOffer.amount) }}
                                                    · {{ savingPercentage(pickedRegularTotal, selectedComboOffer.amount) }}%
                                                </template>
                                                <template v-else>—</template>
                                            </b>
                                        </div>
                                    </template>
                                    <span v-else class="cbx-staff">
                                        Tick services above — the saving appears here before you add.
                                    </span>
                                </div>
                                <button v-if="selectedServices.length" type="button" class="posx-btn posx-btn-ghost"
                                    @click="selectedServices = []">
                                    Clear
                                </button>
                                <button type="button" class="posx-btn posx-btn-primary" :disabled="!isSelectionComplete"
                                    @click="addComboOffer">
                                    <template v-if="isSelectionComplete">
                                        <i class="fa fa-plus"></i> Add {{ selectedComboOffer.name }}
                                    </template>
                                    <template v-else>Pick {{ servicesStillNeeded }} more</template>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Applied combos -->
                <div v-if="selectedComboOffers.length" class="cbx-applied">
                    <p class="cbx-eyebrow">
                        Applied to this sale
                        <span>{{ selectedComboOffers.length }} combo{{ selectedComboOffers.length > 1 ? 's' : '' }}</span>
                    </p>
                    <div class="cbx-receipts">
                        <div v-for="(comboOffer, index) in selectedComboOffers" :key="index" class="cbx-receipt">
                            <div class="cbx-receipt-head">
                                <i class="fa fa-cube"></i>
                                <b>{{ comboOffer.combo_offer_name }}</b>
                                <span class="cbx-save">save {{ savingPercentage(comboRegularTotal(comboOffer), comboOffer.amount) }}%</span>
                                <button type="button" class="cbx-remove" @click="removeComboOffer(index)">
                                    <i class="fa fa-trash-o"></i> Remove
                                </button>
                            </div>
                            <div v-for="item in comboOffer.items" :key="item.key" class="cbx-receipt-line">
                                <span class="cbx-service-name">
                                    <span v-if="item.employee_name" class="cbx-staff">{{ item.employee_name }} ·</span>
                                    {{ item.name }}
                                </span>
                                <span class="cbx-strike">{{ formatCurrency(item.unit_price) }}</span>
                                <span class="cbx-money">{{ formatCurrency(item.combo_offer_price) }}</span>
                            </div>
                            <div class="cbx-receipt-foot">
                                <span class="cbx-strike">{{ formatCurrency(comboRegularTotal(comboOffer)) }}</span>
                                <span class="cbx-money cbx-money-lg">{{ formatCurrency(comboOffer.amount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="posx-modal-foot cbx-foot">
                <div class="cbx-foot-summary">
                    <div class="cbx-kv">
                        <span>Combos</span>
                        <b>{{ selectedComboOffers.length }}</b>
                    </div>
                    <div class="cbx-kv">
                        <span>Services</span>
                        <b>{{ summaryTotals.serviceCount }}</b>
                    </div>
                    <div class="cbx-kv">
                        <span>Combo price</span>
                        <b>
                            <s v-if="summaryTotals.regular">{{ formatCurrency(summaryTotals.regular) }}</s>
                            {{ formatCurrency(summaryTotals.payable) }}
                        </b>
                    </div>
                    <div class="cbx-kv">
                        <span>Customer saves</span>
                        <b class="is-ok">{{ formatCurrency(summaryTotals.saving) }}</b>
                    </div>
                </div>
                <button type="button" class="posx-btn posx-btn-ghost" @click="$emit('close')">
                    Cancel
                </button>
                <button type="button" class="posx-btn posx-btn-primary" @click="saveComboOffers">
                    <i class="fa fa-check"></i>
                    Apply {{ selectedComboOffers.length ? `${selectedComboOffers.length} offer${selectedComboOffers.length > 1 ? 's' : ''}` : 'offers' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { calculateComboOfferPrices as calculateComboOfferPricesUtil } from '@/utils/comboOfferCalculations'

export default {
    name: 'ComboOfferModal',
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
    emits: ['close', 'save', 'update'],

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

        const offerSearch = ref('')

        // Offers shown in the rail, narrowed by the search box
        const filteredComboOffers = computed(() => {
            const term = offerSearch.value.trim().toLowerCase()
            if (!term) {
                return comboOffers.value
            }
            return comboOffers.value.filter(offer => String(offer.name).toLowerCase().includes(term))
        })

        // How many times each offer has already been added to this sale
        const appliedCountByOfferId = computed(() => {
            return selectedComboOffers.value.reduce((counts, combo) => {
                counts[combo.combo_offer_id] = (counts[combo.combo_offer_id] || 0) + 1
                return counts
            }, {})
        })

        // Cart line key -> name of the combo that already holds it
        const lockedComboNameByKey = computed(() => {
            const locked = {}
            selectedComboOffers.value.forEach(combo => {
                combo.items.forEach(item => {
                    locked[item.key] = combo.combo_offer_name
                })
            })
            return locked
        })

        // Every cart service, including ones already inside a combo (shown locked)
        const cartServices = computed(() => Object.values(comboOfferItems.value).filter(Boolean))

        const isSelectionComplete = computed(() => {
            return !!selectedComboOffer.value && selectedServices.value.length === Number(selectedComboOffer.value.count)
        })

        const servicesStillNeeded = computed(() => {
            return selectedComboOffer.value ? Math.max(Number(selectedComboOffer.value.count) - selectedServices.value.length, 0) : 0
        })

        const pickedRegularTotal = computed(() => {
            return selectedServices.value.reduce((sum, key) => sum + (parseFloat(comboOfferItems.value[key]?.unit_price) || 0), 0)
        })

        const comboRegularTotal = (comboOffer) => {
            return comboOffer.items.reduce((sum, item) => sum + (parseFloat(item.unit_price) || 0), 0)
        }

        const summaryTotals = computed(() => {
            const regular = selectedComboOffers.value.reduce((sum, combo) => sum + comboRegularTotal(combo), 0)
            const payable = selectedComboOffers.value.reduce((sum, combo) => sum + (parseFloat(combo.amount) || 0), 0)
            return {
                regular,
                payable,
                saving: Math.max(regular - payable, 0),
                serviceCount: selectedComboOffers.value.reduce((sum, combo) => sum + combo.items.length, 0)
            }
        })

        const savingPercentage = (regular, payable) => {
            if (!regular) {
                return 0
            }
            return Math.round((1 - (parseFloat(payable) || 0) / regular) * 1000) / 10
        }

        const isServiceDimmed = (key) => {
            return isSelectionComplete.value && !selectedServices.value.includes(key) && !lockedComboNameByKey.value[key]
        }

        const toggleService = (key) => {
            if (lockedComboNameByKey.value[key]) {
                return
            }
            if (selectedServices.value.includes(key)) {
                selectedServices.value = selectedServices.value.filter(selectedKey => selectedKey !== key)
                return
            }
            if (isSelectionComplete.value) {
                return
            }
            selectedServices.value = [...selectedServices.value, key]
        }

        const selectComboOffer = (comboOfferId) => {
            if (selectedComboOfferId.value === comboOfferId) {
                return
            }
            selectedComboOfferId.value = comboOfferId
            onComboOfferSelected(comboOfferId)
        }

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


            // Keep the offer selected so the same combo can be built again straight away
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

            // Push the change to the cart right away, otherwise the removed offer's
            // pricing stays applied until (and unless) the user hits Apply
            emit('update', buildComboOfferPayload())

            toast.success('Combo Offer removed successfully')
        }

        // Build the payload the cart needs to mirror the current combo offer state
        const buildComboOfferPayload = () => {
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

            return {
                comboOfferItems: itemsWithComboOffers, // Only send items that are in combo offers
                selectedComboOffers: selectedComboOffers.value
            }
        }

        // Save combo offers
        const saveComboOffers = () => {
            // If no combo offers are added but user has selected one, try to add it automatically.
            // With nothing selected an empty list is a valid state - the user removed them all.
            if (selectedComboOffers.value.length === 0 && selectedComboOfferId.value) {
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
                    if (!selectedComboOffer.value) {
                        toast.error('Combo offer not found. Please select again.')
                    } else {
                        toast.error('Please select services for the combo offer, then click "Add"')
                    }
                    return
                }
            }

            emit('save', buildComboOfferPayload())
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
            offerSearch,
            filteredComboOffers,
            appliedCountByOfferId,
            lockedComboNameByKey,
            cartServices,
            isSelectionComplete,
            servicesStillNeeded,
            pickedRegularTotal,
            comboRegularTotal,
            summaryTotals,
            savingPercentage,
            isServiceDimmed,
            toggleService,
            selectComboOffer,
            addComboOffer,
            removeComboOffer,
            saveComboOffers,
            formatCurrency
        }
    }
}
</script>

<style scoped>
/* Combo Offers — "Builder": offer rail · service picker with live preview ·
   applied receipts. Colours come from the POS preset tokens (.posx root). */
.cbx-head-pill {
    margin-left: auto;
    padding: 4px 9px;
    border-radius: 99px;
    background: color-mix(in srgb, var(--pos-on-pri) 14%, transparent);
    color: var(--pos-on-pri);
    font-size: var(--pos-fs-meta);
    font-weight: 800;
    white-space: nowrap;
}

.cbx .posx-modal-body {
    font-size: var(--pos-fs-body);
}

.cbx-grid {
    display: grid;
    grid-template-columns: 270px minmax(0, 1fr);
    gap: 16px;
    min-height: 360px;
}

/* ------------------------------------------------------------ offer rail */
.cbx-rail {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 0;
    padding-right: 16px;
    border-right: 1px solid var(--pos-line);
}

.cbx-search {
    display: flex;
    align-items: center;
    gap: 8px;
    height: var(--pos-h-field);
    margin: 0;
    padding: 0 11px;
    border: 1px solid var(--pos-field-line);
    border-radius: var(--pos-radius-sm);
    background: var(--pos-field);
    color: var(--pos-muted);
}

.cbx-search:focus-within {
    border-color: var(--pos-pri-line);
    box-shadow: 0 0 0 3px var(--pos-pri-ring);
}

.cbx-search input {
    flex: 1;
    min-width: 0;
    padding: 0;
    border: 0;
    outline: 0;
    box-shadow: none;
    background: transparent;
    color: var(--pos-ink);
    font-size: var(--pos-fs-body);
    font-weight: 600;
}

.cbx-rail-note {
    padding: 10px 2px;
    color: var(--pos-ink-2);
    font-size: var(--pos-fs-meta);
    font-weight: 600;
}

.cbx-offers {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 52vh;
    overflow-y: auto;
}

.cbx-offer {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 9px 11px;
    border: 1px solid var(--pos-line);
    border-radius: var(--pos-radius);
    background: var(--pos-panel);
    color: var(--pos-ink);
    text-align: left;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease;
}

.cbx-offer:hover {
    border-color: var(--pos-pri-line);
}

.cbx-offer.is-on {
    border-color: var(--pos-pri);
    background: var(--pos-pri-soft);
    box-shadow: inset 3px 0 0 var(--pos-pri);
}

.cbx-offer-count {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 30px;
    height: 30px;
    border: 1px solid var(--pos-line);
    border-radius: 8px;
    background: var(--pos-panel-2);
    font-weight: 800;
}

.cbx-offer.is-on .cbx-offer-count {
    border-color: var(--pos-pri);
    background: var(--pos-pri);
    color: var(--pos-on-pri);
}

.cbx-offer-text {
    flex: 1;
    min-width: 0;
}

.cbx-offer-text b {
    display: block;
    overflow: hidden;
    font-size: .78rem;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.cbx-offer-text span {
    color: var(--pos-muted);
    font-size: var(--pos-fs-meta);
    font-weight: 700;
}

.cbx-offer-text em {
    color: var(--pos-ok);
    font-style: normal;
}

.cbx-offer-price {
    color: var(--pos-acc);
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

/* --------------------------------------------------------------- builder */
.cbx-build {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 0;
}

.cbx-build-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 12px;
}

.cbx-build-head h5 {
    margin: 0;
    color: var(--pos-ink);
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -.01em;
}

.cbx-build-head p {
    margin: 2px 0 0;
    color: var(--pos-ink-2);
    font-size: var(--pos-fs-meta);
    font-weight: 600;
}

.cbx-progress {
    min-width: 180px;
    margin-left: auto;
    text-align: right;
    font-size: var(--pos-fs-meta);
}

.cbx-slots {
    display: flex;
    gap: 4px;
    margin-top: 5px;
}

.cbx-slots i {
    flex: 1;
    height: 5px;
    border-radius: 99px;
    background: var(--pos-line-strong);
    transition: background .2s ease;
}

.cbx-slots i.is-filled {
    background: var(--pos-pri);
}

.cbx-slots.is-done i.is-filled {
    background: var(--pos-ok);
}

.cbx-services {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px;
    max-height: 40vh;
    overflow-y: auto;
}

.cbx-service {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 8px 11px;
    border: 1px solid var(--pos-line);
    border-radius: 9px;
    background: var(--pos-panel);
    color: var(--pos-ink);
    text-align: left;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease;
}

.cbx-service:hover:not(:disabled) {
    border-color: var(--pos-pri-line);
}

.cbx-service.is-on {
    border-color: var(--pos-pri);
    background: var(--pos-pri-soft);
}

.cbx-service.is-locked {
    background: var(--pos-panel-2);
    opacity: .6;
    cursor: not-allowed;
}

.cbx-service.is-dim {
    opacity: .45;
    cursor: not-allowed;
}

.cbx-check {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    border: 1.5px solid var(--pos-line-strong);
    border-radius: 5px;
    background: var(--pos-panel);
    color: transparent;
    font-size: var(--pos-ico-micro);
}

.cbx-service.is-on .cbx-check {
    border-color: var(--pos-pri);
    background: var(--pos-pri);
    color: var(--pos-on-pri);
}

.cbx-service-name {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    font-weight: 700;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.cbx-staff {
    color: var(--pos-muted);
    font-weight: 600;
}

.cbx-lock {
    padding: 2px 7px;
    border: 1px solid var(--pos-line);
    border-radius: 99px;
    background: var(--pos-panel);
    color: var(--pos-ink-2);
    font-size: var(--pos-fs-micro);
    font-weight: 800;
    white-space: nowrap;
}

.cbx-money {
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.cbx-money-lg {
    font-size: .875rem;
}

.cbx-strike {
    color: var(--pos-muted);
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    text-decoration: line-through;
    white-space: nowrap;
}

.cbx-preview {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px 14px;
    margin-top: auto;
    padding: 11px 12px;
    border: 1px solid var(--pos-line);
    border-radius: 11px;
    background: var(--pos-panel-2);
}

.cbx-preview-figures {
    display: flex;
    flex: 1;
    flex-wrap: wrap;
    gap: 18px;
    min-width: 0;
}

.cbx-kv {
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.cbx-kv > span {
    color: var(--pos-muted);
    font-size: var(--pos-fs-micro);
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.cbx-kv > b {
    color: var(--pos-ink);
    font-size: .875rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.cbx-kv > b.is-ok {
    color: var(--pos-ok);
}

.cbx-kv > b > s {
    margin-right: 4px;
    color: var(--pos-muted);
    font-size: var(--pos-fs-body);
    font-weight: 600;
}

.cbx-empty {
    padding: 22px;
    border: 1px dashed var(--pos-line-strong);
    border-radius: 12px;
    color: var(--pos-ink-2);
    font-weight: 600;
    text-align: center;
}

.cbx-empty > i {
    display: block;
    margin-bottom: 6px;
    color: var(--pos-muted);
    font-size: 1.25rem;
}

.cbx-empty-fill {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* --------------------------------------------------------------- applied */
.cbx-applied {
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid var(--pos-line);
}

.cbx-eyebrow {
    display: flex;
    align-items: center;
    margin: 0 0 8px;
    color: var(--pos-muted);
    font-size: var(--pos-fs-micro);
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.cbx-eyebrow span {
    margin-left: auto;
    color: var(--pos-ink-2);
    font-size: var(--pos-fs-meta);
    letter-spacing: 0;
    text-transform: none;
}

.cbx-receipts {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 10px;
}

.cbx-receipt {
    overflow: hidden;
    border: 1px solid var(--pos-line);
    border-radius: 11px;
    background: var(--pos-panel);
}

.cbx-receipt-head {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 8px 8px 11px;
    border-bottom: 1px solid var(--pos-line);
    background: var(--pos-panel-2);
}

.cbx-receipt-head > i {
    color: var(--pos-acc);
}

.cbx-receipt-head > b {
    overflow: hidden;
    font-size: .78rem;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.cbx-save {
    padding: 3px 7px;
    border-radius: 99px;
    background: var(--pos-ok-soft);
    color: var(--pos-ok);
    font-size: var(--pos-fs-micro);
    font-weight: 800;
    white-space: nowrap;
}

.cbx-remove {
    margin-left: auto;
    padding: 4px 7px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: var(--pos-muted);
    font-size: var(--pos-fs-meta);
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
}

.cbx-remove:hover {
    background: var(--pos-danger-soft);
    color: var(--pos-danger);
}

.cbx-receipt-line {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 11px;
    border-bottom: 1px dashed var(--pos-line);
}

.cbx-receipt-line .cbx-service-name {
    font-weight: 600;
}

.cbx-receipt-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 11px;
    background: var(--pos-panel-2);
}

/* ---------------------------------------------------------------- footer */
.cbx-foot {
    flex-wrap: wrap;
}

.cbx-foot-summary {
    display: flex;
    flex: 1;
    flex-wrap: wrap;
    align-items: center;
    gap: 18px;
    min-width: 0;
}

@media (max-width: 860px) {
    .cbx-grid {
        grid-template-columns: 1fr;
        min-height: 0;
    }

    .cbx-rail {
        padding: 0 0 14px;
        border-right: 0;
        border-bottom: 1px solid var(--pos-line);
    }

    .cbx-offers {
        flex-direction: row;
        max-height: none;
        overflow-x: auto;
    }

    .cbx-offer {
        flex: 0 0 220px;
    }

    .cbx-services {
        grid-template-columns: 1fr;
    }

    .cbx-progress {
        margin-left: 0;
        text-align: left;
        width: 100%;
    }

    .cbx-foot-summary {
        flex-basis: 100%;
        gap: 14px;
    }
}
</style>
