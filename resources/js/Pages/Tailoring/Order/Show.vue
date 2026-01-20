<template>
    <div class="min-h-screen bg-gray-50 pb-12">
        <!-- Top Navigation Bar -->
        <div class="bg-white border-b sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-4">
                        <Link :href="route('tailoring::order::index')" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-lg font-bold text-gray-900 leading-tight">Order #{{ order.order_no }}</h1>
                            <p class="text-xs text-gray-500 font-medium">{{ order.customer_name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="printOrder" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print
                        </button>
                        <Link :href="route('tailoring::order::edit', order.id)" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-blue-700 transition-all shadow-md hover:shadow-lg active:scale-95">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Order
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Order Stats & Customer Info -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Current Status</p>
                                    <span :class="getStatusClass(order.status)" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                                        {{ order.status }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Completion</p>
                                    <span :class="getCompletionStatusClass(order.completion_status)" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                                        {{ order.completion_status }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex justify-between py-3 border-b border-gray-50">
                                    <span class="text-sm text-gray-500 font-medium">Order Date</span>
                                    <span class="text-sm font-bold text-gray-900">{{ formatDate(order.order_date) }}</span>
                                </div>
                                <div class="flex justify-between py-3 border-b border-gray-50">
                                    <span class="text-sm text-gray-500 font-medium">Delivery Date</span>
                                    <span class="text-sm font-bold text-red-600">{{ formatDate(order.delivery_date) }}</span>
                                </div>
                                <div class="flex justify-between py-3">
                                    <span class="text-sm text-gray-500 font-medium">Salesman</span>
                                    <span class="text-sm font-bold text-gray-900">{{ order.salesman?.name || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gray-900 p-6 text-white">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-xl font-bold">
                                    {{ order.customer_name?.charAt(0) }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg leading-tight">{{ order.customer_name }}</h3>
                                    <p class="text-white/60 text-sm">{{ order.customer_mobile || 'No mobile number' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                             <div class="grid grid-cols-2 gap-4">
                                 <div class="p-3 bg-gray-50 rounded-xl">
                                     <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Branch</p>
                                     <p class="text-sm font-bold text-gray-800">{{ order.branch?.name || 'Main Branch' }}</p>
                                 </div>
                                 <div class="p-3 bg-gray-50 rounded-xl">
                                     <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Total Items</p>
                                     <p class="text-sm font-bold text-gray-800">{{ order.items?.length || 0 }} Items</p>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <!-- Payment Summary Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest mb-6 border-l-4 border-emerald-500 pl-3">Payment Summary</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Gross Amount</span>
                                    <span class="font-bold text-gray-900">{{ formatCurrency(order.gross_amount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Stitch Rate Total</span>
                                    <span class="font-bold text-gray-900">{{ formatCurrency(order.stitch_amount) }}</span>
                                </div>
                                <div v-if="order.discount" class="flex justify-between text-sm">
                                    <span class="text-gray-500">Discount</span>
                                    <span class="font-bold text-red-500">-{{ formatCurrency(order.discount) }}</span>
                                </div>
                                <div class="pt-3 border-t-2 border-dashed flex justify-between items-center">
                                    <span class="text-sm font-bold text-gray-900">Total Amount</span>
                                    <span class="text-xl font-black text-gray-900">{{ formatCurrency(order.total_amount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm pt-4">
                                    <span class="text-gray-500">Paid Amount</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(order.paid_amount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Balance Due</span>
                                    <span :class="order.balance_amount > 0 ? 'text-red-600 font-black' : 'text-emerald-600 font-bold'">
                                        {{ formatCurrency(order.balance_amount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Items & Details -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="flex items-center justify-between mb-2">
                            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Order Items</h2>
                            <div class="h-px bg-gray-200 grow mx-4"></div>
                    </div>

                    <div v-for="(item, index) in order.items" :key="item.id" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md">
                        <!-- Item Header -->
                        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full bg-gray-900 text-white flex items-center justify-center text-xs font-bold shadow-sm">
                                    #{{ item.item_no }}
                                </span>
                                <div>
                                    <h3 class="font-black text-gray-900 uppercase tracking-tight">{{ item.product_name }}</h3>
                                    <p class="text-xs text-gray-500 font-semibold">{{ item.category?.name }} • {{ item.category_model?.name || 'Standard' }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Line Total</p>
                                <p class="text-lg font-black text-gray-900">{{ formatCurrency(item.total) }}</p>
                            </div>
                        </div>

                        <!-- Item Content -->
                        <div class="p-6">
                            <MeasurementView :item="item" />

                            <!-- Product Info -->
                            <div class="mt-8 pt-6 border-t grid grid-cols-2 md:grid-cols-4 gap-6">
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Quantity</p>
                                    <p class="text-sm font-bold text-gray-800">{{ item.quantity }} {{ item.unit?.name || 'Nos' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Color</p>
                                    <p class="text-sm font-bold text-gray-800">{{ item.product_color || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Stitch Rate</p>
                                    <p class="text-sm font-bold text-gray-800">{{ formatCurrency(item.stitch_rate) }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Status</p>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-700 uppercase">
                                        {{ item.status || 'Pending' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payments Log -->
                    <div v-if="order.payments && order.payments.length > 0" class="mt-12">
                         <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Payment History</h2>
                            <div class="h-px bg-gray-200 grow mx-4"></div>
                        </div>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Date</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Method</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="payment in order.payments" :key="payment.id" class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-600">{{ formatDate(payment.date) }}</td>
                                        <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ payment.payment_method?.name || 'Cash' }}</td>
                                        <td class="px-6 py-4 text-sm font-black text-emerald-600 text-right">{{ formatCurrency(payment.amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import MeasurementView from '@/components/Tailoring/MeasurementView.vue'

const props = defineProps({
    order: {
        type: Object,
        required: true
    }
})

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'AED',
    }).format(amount || 0)
}

const getStatusClass = (status) => {
    const classes = {
        'Pending': 'bg-amber-100 text-amber-700',
        'Confirmed': 'bg-blue-100 text-blue-700',
        'Completed': 'bg-emerald-100 text-emerald-700',
        'Cancelled': 'bg-red-100 text-red-700'
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getCompletionStatusClass = (status) => {
    const classes = {
        'Pending': 'bg-gray-100 text-gray-500',
        'In Progress': 'bg-blue-50 text-blue-600',
        'Partially Completed': 'bg-amber-50 text-amber-600',
        'Completed': 'bg-emerald-50 text-emerald-600'
    }
    return classes[status] || 'bg-gray-100 text-gray-500'
}

const printOrder = () => {
    window.print()
}
</script>

<style scoped>
@media print {
    .bg-gray-50 {
        background-color: white !important;
    }
    .no-print, nav, .sticky {
        display: none !important;
    }
    .shadow-sm, .shadow-md, .shadow-lg {
        box-shadow: none !important;
    }
    .rounded-2xl {
        border-radius: 0 !important;
    }
}
</style>
