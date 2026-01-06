<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click.self="closeModal">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden" @click.stop>
            <!-- Header -->
            <div
                class="flex items-center justify-between p-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-blue-100 rounded-full">
                        <i class="fa fa-user-plus text-blue-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Customer Details</h3>
                        <p class="text-sm text-gray-600">Add or edit customer information</p>
                    </div>
                </div>
                <button @click="closeModal"
                    class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-all duration-200">
                    <i class="fa fa-times text-lg"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="p-4 overflow-y-auto max-h-[calc(90vh-8rem)]">
                <!-- Errors -->
                <div v-if="errors.length > 0" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <div class="flex items-center mb-2">
                        <i class="fa fa-exclamation-triangle text-red-500 mr-2"></i>
                        <span class="text-sm font-medium text-red-800">Please correct the errors:</span>
                    </div>
                    <ul class="text-sm text-red-700 list-disc list-inside">
                        <li v-for="error in errors" :key="error">{{ error }}</li>
                    </ul>
                </div>

                <form @submit.prevent="saveCustomer" class="space-y-4">
                    <!-- Main Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-1">
                                <div class="flex items-center justify-center w-6 h-6 bg-purple-100 rounded-full mr-2">
                                    <i class="fa fa-user text-purple-600 text-xs"></i>
                                </div>
                                Full Name <span class="text-red-500 ml-1">*</span>
                            </label>
                            <input v-model="customer.name" type="text" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm transition-all duration-200"
                                placeholder="Enter full name">
                        </div>

                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-1">
                                <div class="flex items-center justify-center w-6 h-6 bg-green-100 rounded-full mr-2">
                                    <i class="fa fa-phone text-green-600 text-xs"></i>
                                </div>
                                Mobile Number <span class="text-red-500 ml-1">*</span>
                            </label>
                            <input v-model="customer.mobile" type="tel" required @input="checkExistingCustomers"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm transition-all duration-200"
                                placeholder="Enter mobile number">
                        </div>

                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-1">
                                <div class="flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full mr-2">
                                    <i class="fa fa-envelope text-blue-600 text-xs"></i>
                                </div>
                                Email
                            </label>
                            <input v-model="customer.email" type="email"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all duration-200"
                                placeholder="Enter email">
                        </div>

                        <div v-if="hasCountries">
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-1">
                                <div class="flex items-center justify-center w-6 h-6 bg-red-100 rounded-full mr-2">
                                    <i class="fa fa-flag text-red-600 text-xs"></i>
                                </div>
                                Nationality
                            </label>
                            <SearchSelect v-model="customer.nationality" :options="countries"
                                placeholder="Search and select nationality..."
                                :input-class="'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm transition-all duration-200'" />
                        </div>
                        <div v-if="hasCustomerTypes">
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-1">
                                <div class="flex items-center justify-center w-6 h-6 bg-indigo-100 rounded-full mr-2">
                                    <i class="fa fa-tags text-indigo-600 text-xs"></i>
                                </div>
                                Customer Type
                            </label>
                            <select v-model="customer.customer_type_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all duration-200">
                                <option value="">Select type</option>
                                <option v-for="(type, id) in customerTypes" :key="id" :value="id">{{ type }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-1">
                                <div class="flex items-center justify-center w-6 h-6 bg-emerald-100 rounded-full mr-2">
                                    <i class="fa fa-whatsapp text-emerald-600 text-xs"></i>
                                </div>
                                WhatsApp
                            </label>
                            <input v-model="customer.whatsapp_mobile" type="tel"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition-all duration-200"
                                placeholder="WhatsApp number">
                        </div>
                    </div>

                    <!-- Additional Details -->
                    <div class="space-y-4">
                        <div class="border-t border-gray-200 pt-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="flex items-center text-sm font-medium text-gray-700 mb-1">
                                        <div
                                            class="flex items-center justify-center w-6 h-6 bg-pink-100 rounded-full mr-2">
                                            <i class="fa fa-birthday-cake text-pink-600 text-xs"></i>
                                        </div>
                                        Date of Birth
                                    </label>
                                    <input v-model="customer.dob" type="date"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent text-sm transition-all duration-200">
                                </div>

                                <div>
                                    <label class="flex items-center text-sm font-medium text-gray-700 mb-1">
                                        <div
                                            class="flex items-center justify-center w-6 h-6 bg-yellow-100 rounded-full mr-2">
                                            <i class="fa fa-id-badge text-yellow-600 text-xs"></i>
                                        </div>
                                        ID Number
                                    </label>
                                    <input v-model="customer.id_no" type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm transition-all duration-200"
                                        placeholder="ID/Passport">
                                </div>

                                <div>
                                    <label class="flex items-center text-sm font-medium text-gray-700 mb-1">
                                        <div
                                            class="flex items-center justify-center w-6 h-6 bg-orange-100 rounded-full mr-2">
                                            <i class="fa fa-building text-orange-600 text-xs"></i>
                                        </div>
                                        Company
                                    </label>
                                    <input v-model="customer.company" type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent text-sm transition-all duration-200"
                                        placeholder="Company name">
                                </div>

                            </div>
                        </div>

                        <!-- Credit Information -->
                        <div class="border-t border-gray-200 pt-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="flex items-center text-sm font-medium text-gray-700 mb-1">
                                        <div
                                            class="flex items-center justify-center w-6 h-6 bg-teal-100 rounded-full mr-2">
                                            <i class="fa fa-calendar text-teal-600 text-xs"></i>
                                        </div>
                                        Credit Period (Days)
                                    </label>
                                    <input v-model.number="customer.credit_period_days" type="number" min="0" step="1"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent text-sm transition-all duration-200"
                                        placeholder="e.g., 30, 60, 90">
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fa fa-info-circle mr-1"></i>
                                        Number of days allowed for credit payment
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Customers -->
                    <div v-if="existingCustomers.length > 0"
                        class="p-4 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-lg shadow-sm">
                        <div class="flex items-center mb-3">
                            <div class="flex items-center justify-center w-6 h-6 bg-amber-100 rounded-full mr-2">
                                <i class="fa fa-exclamation-triangle text-amber-600 text-xs"></i>
                            </div>
                            <span class="text-sm font-semibold text-amber-800">Similar customers found</span>
                        </div>
                        <div class="space-y-2">
                            <div v-for="existing in existingCustomers" :key="existing.id"
                                @click="selectExistingCustomer(existing)"
                                class="flex justify-between items-center p-3 bg-white rounded-lg border border-amber-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 hover:border-blue-200 cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md">
                                <div class="flex items-center space-x-3">
                                    <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full">
                                        <i class="fa fa-user text-blue-600 text-xs"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium text-sm text-gray-800">{{ existing.name }}</div>
                                        <div class="text-xs text-gray-600">
                                            <i class="fa fa-phone text-green-500 mr-1"></i>{{ existing.mobile }}
                                            <span class="mx-2">•</span>
                                            <i class="fa fa-envelope text-blue-500 mr-1"></i>
                                            {{ existing.email || 'No email' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-center w-6 h-6 bg-gray-100 rounded-full">
                                    <i class="fa fa-chevron-right text-gray-400 text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-end space-x-3 p-4 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50">
                <button type="button" @click="closeModal"
                    class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md">
                    <i class="fa fa-times mr-2 text-gray-500"></i>
                    Cancel
                </button>
                <button type="button" @click="saveAndAddNew" :disabled="loading"
                    class="flex items-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-500 to-emerald-600 border border-transparent rounded-lg hover:from-green-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 shadow-sm hover:shadow-md">
                    <i class="fa fa-plus mr-2"></i>
                    Save & Add New
                </button>
                <button type="button" @click="saveCustomer" :disabled="loading"
                    class="flex items-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-indigo-600 border border-transparent rounded-lg hover:from-blue-600 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 shadow-sm hover:shadow-md">
                    <i v-if="loading" class="fa fa-spinner fa-spin mr-2"></i>
                    <i v-else class="fa fa-check mr-2"></i>
                    {{ loading ? 'Saving...' : 'Save Customer' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import SearchSelect from './SearchSelect.vue'

export default {
    name: 'CustomerModal',
    components: {
        SearchSelect
    },
    props: {
        show: {
            type: Boolean,
            default: false
        },
        initialCustomer: {
            type: Object,
            default: () => ({})
        },
        customerTypes: {
            type: Object,
            default: () => ({})
        },
        countries: {
            type: Object,
            default: () => ({})
        }
    },
    emits: ['close', 'customerSaved', 'customerSelected'],
    setup(props, { emit }) {
        const toast = useToast()

        const loading = ref(false)
        const errors = ref([])
        const existingCustomers = ref([])

        // Computed properties for conditional rendering
        const hasCustomerTypes = computed(() => {
            return props.customerTypes && Object.keys(props.customerTypes).length > 0
        })

        const hasCountries = computed(() => {
            return props.countries && Object.keys(props.countries).length > 0
        })

        const customer = ref({
            id: null,
            name: '',
            mobile: '',
            whatsapp_mobile: '',
            email: '',
            company: '',
            dob: null,
            id_no: '',
            nationality: null,
            customer_type_id: '',
            credit_period_days: null,
            type: 'customer',
            status: 'active'
        })

        // Watch for initial customer changes
        watch(() => props.initialCustomer, (newCustomer) => {
            if (newCustomer && Object.keys(newCustomer).length > 0) {
                Object.assign(customer.value, newCustomer)
            } else {
                resetCustomer()
            }
        }, { immediate: true, deep: true })

        const resetCustomer = () => {
            customer.value = {
                id: null,
                name: '',
                mobile: '',
                whatsapp_mobile: '',
                email: '',
                company: '',
                dob: null,
                id_no: '',
                nationality: null,
                customer_type_id: '',
                credit_period_days: null,
                type: 'customer',
                status: 'active'
            }
            existingCustomers.value = []
            errors.value = []
        }

        const closeModal = () => {
            resetCustomer()
            emit('close')
        }

        const validateCustomer = () => {
            errors.value = []

            // Required field validation
            if (!customer.value.name || customer.value.name.trim().length === 0) {
                errors.value.push('Customer name is required')
            }

            if (!customer.value.mobile || customer.value.mobile.trim().length === 0) {
                errors.value.push('Mobile number is required')
            }

            // Length validation
            if (customer.value.name && customer.value.name.length > 255) {
                errors.value.push('Customer name must not exceed 255 characters')
            }

            if (customer.value.mobile && customer.value.mobile.length > 20) {
                errors.value.push('Mobile number must not exceed 20 characters')
            }

            if (customer.value.email && customer.value.email.length > 255) {
                errors.value.push('Email must not exceed 255 characters')
            }

            // Format validation
            if (customer.value.email && customer.value.email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customer.value.email)) {
                errors.value.push('Please enter a valid email address')
            }

            return errors.value.length === 0
        }

        const checkExistingCustomers = async () => {
            if (!customer.value.mobile || customer.value.mobile.length < 3) {
                existingCustomers.value = []
                return
            }

            try {
                const response = await axios.get('/customers/check-mobile', {
                    params: { mobile: customer.value.mobile }
                })
                existingCustomers.value = response.data.customers || []
            } catch (error) {
                console.error('Error checking existing customers:', error)
                existingCustomers.value = []
            }
        }

        const selectExistingCustomer = (existing) => {
            emit('customerSelected', existing)
            closeModal()
        }

        const saveCustomer = async () => {
            if (!validateCustomer()) {
                toast.error('Please correct the validation errors')
                return
            }

            loading.value = true
            errors.value = []

            try {
                const customerData = {
                    ...customer.value,
                    name: customer.value.name.trim(),
                    mobile: customer.value.mobile.trim(),
                    email: customer.value.email ? customer.value.email.trim() : null,
                    company: customer.value.company ? customer.value.company.trim() : null
                }

                const endpoint = customerData.id ? `/customers/${customerData.id}` : '/customers/'
                const method = customerData.id ? 'put' : 'post'
                const response = await axios[method](endpoint, customerData)

                if (response.data.success) {
                    toast.success(response.data.message || 'Customer saved successfully')
                    emit('customerSaved', response.data.customer)
                    closeModal()
                } else {
                    toast.error(response.data.message || 'Failed to save customer')
                }
            } catch (error) {
                console.error('Error saving customer:', error)
                if (error.response?.data?.errors) {
                    errors.value = Object.values(error.response.data.errors).flat()
                } else {
                    toast.error(error.response?.data?.message || 'Failed to save customer. Please try again.')
                }
            } finally {
                loading.value = false
            }
        }

        const saveAndAddNew = async () => {
            await saveCustomer()
            if (errors.value.length === 0) {
                resetCustomer()
            }
        }

        return {
            customer,
            loading,
            errors,
            existingCustomers,
            hasCustomerTypes,
            hasCountries,
            closeModal,
            saveCustomer,
            saveAndAddNew,
            checkExistingCustomers,
            selectExistingCustomer
        }
    }
}
</script>
