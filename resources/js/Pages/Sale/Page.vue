<template>
    <div class="min-h-screen bg-gray-100">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <!-- Sale Header -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Customer</label>
                                <select v-model="saleData.account_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="updateCustomer">
                                    <option v-for="account in accounts" :key="account.id" :value="account.id">
                                        {{ account.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date</label>
                                <input type="date" v-model="saleData.date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sale Type</label>
                                <select v-model="saleData.sale_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="updateSaleType">
                                    <option value="normal">Normal</option>
                                    <option value="wholesale">Wholesale</option>
                                </select>
                            </div>
                        </div>

                        <!-- Product Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="col-span-1">
                                <label class="block text-sm font-medium text-gray-700">Categories</label>
                                <select v-model="selectedCategory"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="loadProducts">
                                    <option value="favorite">Favorites</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Search Products</label>
                                <input type="text" v-model="productSearch"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Search by name or barcode" @input="searchProducts" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-sm font-medium text-gray-700">Employee</label>
                                <select v-model="selectedEmployee"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="(name, id) in initialData.employees" :key="id" :value="id">
                                        {{ name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Product Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
                            <div v-for="product in filteredProducts" :key="product.id"
                                class="bg-white p-4 rounded-lg shadow cursor-pointer hover:shadow-md transition-shadow"
                                @click="addItem(product)">
                                <h3 class="text-sm font-medium text-gray-900">{{ product.name }}</h3>
                                <p class="text-sm text-gray-500">{{ product.mrp }}</p>
                            </div>
                        </div>

                        <!-- Cart Items -->
                        <div class="mb-6">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Cart Items</h2>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Item
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Price
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Qty
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Total
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(item, key) in saleData.items" :key="key">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ item.name }}</div>
                                                <div class="text-sm text-gray-500">{{ item.employee_name }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ item.unit_price }}</div>
                                                <div class="text-sm text-gray-500" v-if="item.discount">-{{
                                                    item.discount }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center space-x-2">
                                                    <button class="text-gray-500 hover:text-gray-700"
                                                        @click="modifyQuantity(key, 'minus')">-</button>
                                                    <input type="number" v-model.number="item.quantity"
                                                        class="w-16 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        @change="updateItemQuantity(key)" />
                                                    <button class="text-gray-500 hover:text-gray-700"
                                                        @click="modifyQuantity(key, 'plus')">+</button>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ item.total }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button class="text-red-600 hover:text-red-900"
                                                    @click="removeItem(key)">Remove</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Totals and Payment -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900 mb-4">Payment Details</h2>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                                        <select v-model="selectedPaymentMethod"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            @change="updatePaymentMethod">
                                            <option v-for="(name, id) in initialData.paymentMethods" :key="id"
                                                :value="id">{{ name }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Amount</label>
                                        <input type="number" v-model.number="paymentAmount"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            :max="saleData.balance" />
                                    </div>
                                    <button
                                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        @click="addPayment">Add Payment</button>
                                </div>
                            </div>
                            <div>
                                <h2 class="text-lg font-medium text-gray-900 mb-4">Sale Summary</h2>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Gross Amount:</span>
                                            <span class="font-medium">{{ saleData.gross_amount }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Item Discount:</span>
                                            <span class="font-medium">{{ saleData.item_discount }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Other Discount:</span>
                                            <span class="font-medium">{{ saleData.other_discount }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Tax Amount:</span>
                                            <span class="font-medium">{{ saleData.tax_amount }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Freight:</span>
                                            <span class="font-medium">{{ saleData.freight }}</span>
                                        </div>
                                        <div class="border-t border-gray-200 pt-2 mt-2">
                                            <div class="flex justify-between">
                                                <span class="text-lg font-medium">Grand Total:</span>
                                                <span class="text-lg font-medium">{{ saleData.grand_total }}</span>
                                            </div>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Paid Amount:</span>
                                            <span class="font-medium">{{ saleData.paid }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Balance:</span>
                                            <span class="font-medium">{{ saleData.balance }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex justify-end space-x-4">
                            <button
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                @click="saveSale('completed')">Complete Sale</button>
                            <button
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                                @click="saveSale('draft')">Save as Draft</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useForm } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'

export default {
    props: {
        initialData: {
            type: Object,
            required: true
        },
        saleData: {
            type: Object,
            required: true
        },
        categories: {
            type: Array,
            required: true
        },
        customerDetails: {
            type: Object,
            required: true
        }
    },

    setup(props) {
        const toast = useToast()
        const selectedCategory = ref('favorite')
        const productSearch = ref('')
        const selectedEmployee = ref(null)
        const selectedPaymentMethod = ref(props.initialData.defaultPaymentMethodId)
        const paymentAmount = ref(0)
        const products = ref([])
        const filteredProducts = computed(() => {
            if (!productSearch.value) return products.value
            const search = productSearch.value.toLowerCase()
            return products.value.filter(p =>
                p.name.toLowerCase().includes(search) ||
                p.barcode?.toLowerCase().includes(search)
            )
        })

        const form = useForm({
            ...props.saleData,
            items: props.saleData.items || [],
            payments: props.saleData.payments || []
        })

        onMounted(() => {
            loadProducts()
            if (props.initialData.employees) {
                selectedEmployee.value = Object.keys(props.initialData.employees)[0]
            }
        })

        const loadProducts = async () => {
            try {
                const response = await axios.get('/api/products', {
                    params: {
                        category_id: selectedCategory.value,
                        sale_type: form.sale_type,
                        search: productSearch.value
                    }
                })
                products.value = response.data
            } catch (error) {
                toast.error('Failed to load products')
            }
        }

        const addItem = async (product) => {
            if (!selectedEmployee.value) {
                toast.error('Please select an employee first')
                return
            }

            try {
                const response = await axios.post('/api/sale/add-item', {
                    inventory_id: product.id,
                    employee_id: selectedEmployee.value
                })

                const { item, totals } = response.data
                const key = `${item.employee_id}-${item.inventory_id}`

                if (form.items[key]) {
                    form.items[key].quantity += 1
                    updateItemQuantity(key)
                } else {
                    form.items[key] = item
                }

                updateTotals(totals)
                toast.success('Item added to cart')
            } catch (error) {
                toast.error(error.response?.data?.error || 'Failed to add item')
            }
        }

        const updateItemQuantity = async (key) => {
            try {
                const response = await axios.post('/api/sale/update-item', {
                    key,
                    item: form.items[key]
                })

                const { item, totals } = response.data
                form.items[key] = item
                updateTotals(totals)
            } catch (error) {
                toast.error(error.response?.data?.error || 'Failed to update item')
            }
        }

        const removeItem = async (key) => {
            try {
                await axios.post('/api/sale/remove-item', {
                    id: form.items[key].id,
                    key
                })

                delete form.items[key]
                calculateTotals()
                toast.success('Item removed from cart')
            } catch (error) {
                toast.error(error.response?.data?.error || 'Failed to remove item')
            }
        }

        const modifyQuantity = (key, action) => {
            const item = form.items[key]
            if (action === 'plus') {
                item.quantity += 1
            } else if (action === 'minus' && item.quantity > 1) {
                item.quantity -= 1
            }
            updateItemQuantity(key)
        }

        const updatePaymentMethod = () => {
            if (['cash', 'card'].includes(selectedPaymentMethod.value)) {
                paymentAmount.value = form.balance
                addPayment()
            }
        }

        const addPayment = () => {
            if (!paymentAmount.value) {
                toast.error('Please enter an amount')
                return
            }

            if (!selectedPaymentMethod.value) {
                toast.error('Please select a payment method')
                return
            }

            if (paymentAmount.value > form.balance) {
                toast.error('Amount cannot exceed balance')
                return
            }

            const payment = {
                amount: paymentAmount.value,
                payment_method_id: selectedPaymentMethod.value,
                name: props.initialData.paymentMethods[selectedPaymentMethod.value]
            }

            form.payments.push(payment)
            paymentAmount.value = 0
            calculateTotals()
        }

        const updateTotals = (totals) => {
            Object.assign(form, totals)
        }

        const calculateTotals = () => {
            const totals = {
                gross_amount: 0,
                total_quantity: 0,
                item_discount: 0,
                tax_amount: 0,
                total: 0
            }

            Object.values(form.items).forEach(item => {
                totals.gross_amount += item.gross_amount
                totals.total_quantity += item.quantity
                totals.item_discount += item.discount
                totals.tax_amount += item.tax_amount
                totals.total += item.total
            })

            form.gross_amount = totals.gross_amount
            form.total_quantity = totals.total_quantity
            form.item_discount = totals.item_discount
            form.tax_amount = totals.tax_amount
            form.total = totals.total
            form.grand_total = calculateGrandTotal(totals.total)
            form.paid = form.payments.reduce((sum, p) => sum + p.amount, 0)
            form.balance = form.grand_total - form.paid
        }

        const calculateGrandTotal = (total) => {
            let grandTotal = total
            grandTotal -= form.other_discount
            grandTotal += form.freight
            return Math.round(grandTotal * 100) / 100
        }

        const saveSale = async (type) => {
            if (!form.total_quantity) {
                toast.error('Please add at least one item')
                return
            }

            try {
                const response = await axios.post('/api/sale/save', {
                    ...form,
                    type,
                    print: type === 'completed'
                })

                if (response.data.success) {
                    toast.success(response.data.message)
                    if (type === 'completed') {
                        window.open(`/print/sale/invoice/${response.data.sale_id}`, '_blank')
                    }
                    window.location.href = '/sale'
                }
            } catch (error) {
                toast.error(error.response?.data?.error || 'Failed to save sale')
            }
        }

        return {
            selectedCategory,
            productSearch,
            selectedEmployee,
            selectedPaymentMethod,
            paymentAmount,
            filteredProducts,
            form,
            loadProducts,
            addItem,
            updateItemQuantity,
            removeItem,
            modifyQuantity,
            updatePaymentMethod,
            addPayment,
            saveSale
        }
    }
}
</script>

<style scoped>
/* Custom scrollbar for better UX */
.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Product grid card hover effects */
.grid>div {
    transition: all 0.2s ease-in-out;
}

.grid>div:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

/* Input focus styles */
input:focus,
select:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

/* Button hover effects */
button {
    transition: all 0.2s ease-in-out;
}

button:hover {
    transform: translateY(-1px);
}

/* Table styles */
table {
    border-collapse: separate;
    border-spacing: 0;
}

th {
    position: sticky;
    top: 0;
    background-color: #f9fafb;
    z-index: 10;
}

td,
th {
    padding: 0.75rem 1rem;
}

/* Quantity input styles */
input[type="number"] {
    -moz-appearance: textfield;
}

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Payment summary card */
.bg-gray-50 {
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .grid>div {
        margin-bottom: 1rem;
    }

    .overflow-x-auto {
        margin: 0 -1rem;
        padding: 0 1rem;
    }
}

/* Loading state styles */
.loading {
    opacity: 0.7;
    pointer-events: none;
}

/* Error state styles */
.error {
    border-color: #ef4444;
}

.error:focus {
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
}

/* Success state styles */
.success {
    border-color: #10b981;
}

.success:focus {
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}
</style>
