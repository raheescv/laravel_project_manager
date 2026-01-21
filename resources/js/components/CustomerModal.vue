<template>
    <div v-if="show"
        class="fixed inset-0 bg-gray-900 bg-opacity-40 backdrop-blur-sm flex items-center justify-center z-50 p-1 sm:p-2"
        @click.self="closeModal">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[95vh] sm:max-h-[90vh] overflow-hidden flex flex-col"
            @click.stop>
            <!-- Header -->
            <div
                class="flex items-center justify-between p-2 sm:p-3 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-blue-50 flex-shrink-0">
                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                    <div class="p-1.5 sm:p-2 bg-blue-100 rounded-lg flex-shrink-0 shadow-sm">
                        <i class="fa fa-user text-blue-600 text-base sm:text-lg"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base sm:text-lg font-bold text-slate-800 truncate">Customer Details</h3>
                        <p class="text-xs text-slate-600 hidden sm:block">
                            {{ mode === 'view' ? 'View customer information and history' : 'Add or edit customer information' }}
                        </p>
                    </div>
                </div>
                <button @click="closeModal"
                    class="p-1.5 sm:p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-all duration-200 flex-shrink-0 ml-2">
                    <i class="fa fa-times text-base sm:text-lg"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="p-2 sm:p-3 overflow-y-auto flex-1 bg-gray-50">
                <!-- Loading State -->
                <div v-if="loading && mode === 'view'" class="flex items-center justify-center py-6">
                    <div class="animate-spin rounded-full h-8 w-8 border-4 border-blue-200 border-t-blue-600"></div>
                    <span class="ml-2 text-sm text-slate-600 font-medium">Loading customer details...</span>
                </div>

                <!-- VIEW MODE -->
                <div v-else-if="mode === 'view' && customer" class="space-y-2 sm:space-y-3">
                    <!-- Customer Basic Info -->
                    <div
                        class="bg-white rounded-lg p-2.5 sm:p-3 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-2 gap-2">
                            <h4 class="text-sm sm:text-base font-bold text-slate-800 flex items-center">
                                <i class="fa fa-user-circle mr-2 text-blue-500"></i>
                                Basic Information
                            </h4>
                            <button type="button" @click="switchToEditMode"
                                class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md w-full sm:w-auto flex items-center justify-center">
                                <i class="fa fa-edit mr-1"></i>Edit Customer
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                            <!-- Full Name -->
                            <div class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Full Name</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">{{ customer.name }}</p>
                            </div>
                            <!-- Mobile -->
                            <div class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Mobile</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-phone mr-1.5 text-emerald-500"></i>
                                    <a :href="`tel:${customer.mobile}`" class="text-blue-600 hover:text-blue-700 hover:underline transition-colors">{{ customer.mobile }}</a>
                                </p>
                            </div>
                            <!-- Credit Period -->
                            <div class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Credit Period</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-calendar mr-1.5 text-teal-500"></i>
                                    <span v-if="customer.credit_period_days">{{ customer.credit_period_days }} {{ customer.credit_period_days === 1 ? 'Day' : 'Days' }}</span>
                                    <span v-else class="text-slate-400 font-normal">Not set</span>
                                </p>
                            </div>
                            <!-- Email -->
                            <div v-if="customer.email" class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Email</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-envelope mr-1.5 text-blue-500"></i>
                                    <a :href="`mailto:${customer.email}`" class="text-blue-600 hover:text-blue-700 hover:underline break-all transition-colors">{{ customer.email }}</a>
                                </p>
                            </div>
                            <!-- WhatsApp -->
                            <div v-if="customer.whatsapp_mobile" class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">WhatsApp</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-whatsapp mr-1.5 text-emerald-500"></i>
                                    <a :href="`https://wa.me/${customer.whatsapp_mobile.replace(/[^0-9]/g, '')}`" target="_blank" class="text-blue-600 hover:text-blue-700 hover:underline transition-colors">{{ customer.whatsapp_mobile }}</a>
                                </p>
                            </div>
                            <!-- Company -->
                            <div v-if="customer.company" class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Company</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-building mr-1.5 text-amber-500"></i>
                                    {{ customer.company }}
                                </p>
                            </div>
                            <!-- Nationality -->
                            <div v-if="customer.nationality" class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Nationality</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-flag mr-1.5 text-rose-500"></i>
                                    {{ customer.nationality }}
                                </p>
                            </div>
                            <!-- Customer Type -->
                            <div v-if="customer.customer_type && customer.customer_type.name" class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Customer Type</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-tag mr-1.5 text-indigo-500"></i>
                                    {{ customer.customer_type.name }}
                                </p>
                            </div>
                            <!-- Date of Birth -->
                            <div v-if="customer.dob" class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Date of Birth</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-birthday-cake mr-1.5 text-pink-500"></i>
                                    {{ formatDate(customer.dob) }}
                                </p>
                            </div>
                            <!-- ID Number -->
                            <div v-if="customer.id_no" class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">ID Number</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-id-badge mr-1.5 text-amber-500"></i>
                                    {{ customer.id_no }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Summary -->
                    <div class="bg-white rounded-lg p-2.5 sm:p-3 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <h4 class="text-sm sm:text-base font-bold text-slate-800 mb-2 flex items-center">
                            <i class="fa fa-chart-line mr-2 text-emerald-500"></i>
                            Sales Summary
                        </h4>

                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                            <div class="text-center p-2 bg-gradient-to-br from-emerald-50 to-teal-50 rounded-lg border border-emerald-100 shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-lg sm:text-xl font-bold text-emerald-700 break-words mb-0.5">{{ totalSales || 0 }}</div>
                                <div class="text-xs font-medium text-slate-600 uppercase tracking-wide">Total Sales</div>
                            </div>
                            <div class="text-center p-2 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg border border-blue-100 shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-lg sm:text-xl font-bold text-blue-700 break-words mb-0.5">{{ formatCurrency(totalAmount) }}</div>
                                <div class="text-xs font-medium text-slate-600 uppercase tracking-wide">Total Amount</div>
                            </div>
                            <div class="text-center p-2 bg-gradient-to-br from-teal-50 to-cyan-50 rounded-lg border border-teal-100 shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-lg sm:text-xl font-bold text-teal-700 break-words mb-0.5">{{ formatCurrency(totalPaid) }}</div>
                                <div class="text-xs font-medium text-slate-600 uppercase tracking-wide">Total Paid</div>
                            </div>
                            <div class="text-center p-2 bg-gradient-to-br from-rose-50 to-pink-50 rounded-lg border border-rose-100 shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-lg sm:text-xl font-bold text-rose-700 break-words mb-0.5">{{ formatCurrency(totalBalance) }}</div>
                                <div class="text-xs font-medium text-slate-600 uppercase tracking-wide">Outstanding</div>
                            </div>
                        </div>
                        <div v-if="lastPurchase" class="mt-2 pt-2 border-t border-slate-200">
                            <div class="text-center">
                                <div class="text-xs font-semibold text-slate-700 inline-flex items-center px-2 py-1 bg-slate-100 rounded-lg">
                                    <i class="fa fa-calendar mr-1.5 text-indigo-500"></i>
                                    Last Purchase: {{ formatDate(lastPurchase) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Sales & Feedbacks Tabs -->
                    <div class="bg-white rounded-lg p-2.5 sm:p-3 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <!-- Tab Headers -->
                        <div class="flex border-b border-slate-200 mb-2 bg-slate-50 rounded-t-lg -mx-2.5 sm:-mx-3 px-2.5 sm:px-3">
                            <button @click="activeTab = 'sales'" :class="[
                                'flex-1 px-3 sm:px-4 py-2 text-xs font-semibold transition-all duration-200 rounded-t-lg',
                                activeTab === 'sales' ? 'text-blue-700 border-b-2 border-blue-600 bg-white shadow-sm' : 'text-slate-600 hover:text-blue-600 hover:bg-white/50'
                            ]">
                                <i class="fa fa-history mr-1.5"></i>
                                Recent Sales
                            </button>
                            <button @click="activeTab = 'feedbacks'" :class="[
                                'flex-1 px-3 sm:px-4 py-2 text-xs font-semibold transition-all duration-200 rounded-t-lg',
                                activeTab === 'feedbacks' ? 'text-blue-700 border-b-2 border-blue-600 bg-white shadow-sm' : 'text-slate-600 hover:text-blue-600 hover:bg-white/50'
                            ]">
                                <i class="fa fa-comments mr-1.5"></i>
                                Customer Feedbacks
                            </button>
                        </div>

                        <!-- Tab Content: Recent Sales -->
                        <div v-if="activeTab === 'sales'">
                            <div v-if="recentSales.length > 0" class="space-y-1.5">
                                <div v-for="sale in recentSales" :key="sale.id" class="bg-slate-50 rounded-lg p-2 border border-slate-200 hover:border-blue-300 hover:shadow-md transition-all duration-200">
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="min-w-0">
                                            <div class="font-bold text-sm text-slate-800 break-words mb-0.5">
                                                <a :href="`/sale/view/${sale.id}`" target="_blank" class="text-blue-600 hover:text-blue-700 hover:underline transition-colors">#{{ sale.invoice_no }}</a>
                                            </div>
                                            <div class="text-xs text-slate-500 flex items-center">
                                                <i class="fa fa-calendar mr-1 text-slate-400"></i>
                                                {{ new Date(sale.date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-center">
                                            <div v-if="sale.rating" class="flex items-center gap-0.5 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                                <i v-for="i in 5" :key="i" :class="['fa fa-star text-xs', sale.rating >= i ? 'text-amber-400' : 'text-slate-300']"></i>
                                                <span class="text-xs font-semibold text-slate-700 ml-0.5">{{ sale.rating }}/5</span>
                                            </div>
                                            <span v-else class="text-xs text-slate-400 italic">No rating</span>
                                        </div>
                                        <div class="text-right min-w-0">
                                            <div class="font-bold text-sm text-emerald-600 mb-0.5">{{ formatCurrency(sale.total) }}</div>
                                            <div v-if="sale.balance > 0" class="text-xs font-medium text-slate-500">Balance: <span class="text-rose-600">{{ formatCurrency(sale.balance) }}</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-4">
                                <i class="fa fa-shopping-cart text-3xl text-slate-300 mb-2"></i>
                                <h4 class="text-sm font-semibold text-slate-600 mb-1">No Recent Sales</h4>
                                <p class="text-xs text-slate-500">This customer hasn't made any purchases yet.</p>
                            </div>
                        </div>

                        <!-- Tab Content: Customer Feedbacks -->
                        <div v-if="activeTab === 'feedbacks'">
                            <div v-if="customerFeedbacks.length > 0" class="space-y-1.5">
                                <div v-for="feedback in customerFeedbacks" :key="feedback.id" class="bg-slate-50 rounded-lg p-2 border border-slate-200 hover:border-blue-300 hover:shadow-md transition-all duration-200">
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between flex-wrap gap-1.5 pb-1.5 border-b border-slate-200">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <a :href="`/sale/view/${feedback.id}`" target="_blank" class="font-bold text-sm text-blue-600 hover:text-blue-700 hover:underline transition-colors">#{{ feedback.invoice_no }}</a>
                                                <span class="text-xs text-slate-500 flex items-center">
                                                    <i class="fa fa-calendar mr-1 text-slate-400"></i>
                                                    {{ new Date(feedback.date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}
                                                </span>
                                                <div v-if="feedback.rating" class="flex items-center gap-0.5 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                                    <i v-for="i in 5" :key="i" :class="['fa fa-star text-xs', feedback.rating >= i ? 'text-amber-400' : 'text-slate-300']"></i>
                                                    <span class="text-xs font-semibold text-slate-700 ml-0.5">{{ feedback.rating }}/5</span>
                                                </div>
                                            </div>
                                            <span v-if="feedback.feedback_type" :class="[
                                                'px-2 py-0.5 rounded-full text-xs font-semibold shadow-sm',
                                                feedback.feedback_type === 'compliment' ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' :
                                                feedback.feedback_type === 'suggestion' ? 'bg-blue-100 text-blue-700 border border-blue-200' :
                                                feedback.feedback_type === 'complaint' ? 'bg-rose-100 text-rose-700 border border-rose-200' :
                                                'bg-slate-100 text-slate-700 border border-slate-200'
                                            ]">
                                                {{ formatFeedbackType(feedback.feedback_type) }}
                                            </span>
                                        </div>
                                        <div v-if="feedback.feedback" class="text-xs text-slate-700 bg-white rounded-lg p-2 border border-slate-200">
                                            <div class="flex items-start">
                                                <i class="fa fa-comment mr-1.5 text-blue-500 mt-0.5"></i>
                                                <span class="flex-1">{{ feedback.feedback }}</span>
                                            </div>
                                        </div>
                                        <div v-else class="text-xs text-slate-400 italic bg-white rounded-lg p-2 border border-slate-200">No comment provided</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-4">
                                <i class="fa fa-comments text-3xl text-slate-300 mb-2"></i>
                                <h4 class="text-sm font-semibold text-slate-600 mb-1">No Feedbacks</h4>
                                <p class="text-xs text-slate-500">This customer hasn't provided any feedback yet.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- EDIT MODE -->
                <div v-else-if="mode === 'edit'" class="p-2 sm:p-3 md:p-4 overflow-y-auto flex-1 min-h-0">
                    <!-- Errors -->
                    <div v-if="errors.length > 0"
                        class="mb-2 sm:mb-3 p-2 sm:p-2.5 bg-red-50 border border-red-200 rounded-md">
                        <div class="flex items-center mb-1.5">
                            <i class="fa fa-exclamation-triangle text-red-500 mr-1.5 text-sm"></i>
                            <span class="text-xs sm:text-sm font-medium text-red-800">Please correct the errors:</span>
                        </div>
                        <ul class="text-xs sm:text-sm text-red-700 list-disc list-inside space-y-0.5">
                            <li v-for="error in errors" :key="error">{{ error }}</li>
                        </ul>
                    </div>

                    <form @submit.prevent="saveCustomer" class="space-y-2 sm:space-y-3">
                    <!-- Main Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                        <div>
                            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-purple-100 rounded-full mr-1.5">
                                    <i class="fa fa-user text-purple-600 text-xs"></i>
                                </div>
                                Full Name <span class="text-red-500 ml-0.5">*</span>
                            </label>
                            <input v-model="customer.name" type="text" required
                                class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm transition-all duration-200"
                                placeholder="Enter full name">
                        </div>

                        <div>
                            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-green-100 rounded-full mr-1.5">
                                    <i class="fa fa-phone text-green-600 text-xs"></i>
                                </div>
                                Mobile Number <span class="text-red-500 ml-0.5">*</span>
                            </label>
                            <input v-model="customer.mobile" type="tel" required @input="checkExistingCustomers"
                                class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm transition-all duration-200"
                                placeholder="Enter mobile number">
                        </div>

                        <div>
                            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-blue-100 rounded-full mr-1.5">
                                    <i class="fa fa-envelope text-blue-600 text-xs"></i>
                                </div>
                                Email
                            </label>
                            <input v-model="customer.email" type="email"
                                class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all duration-200"
                                placeholder="Enter email">
                        </div>

                        <div v-if="hasCountries">
                            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-red-100 rounded-full mr-1.5">
                                    <i class="fa fa-flag text-red-600 text-xs"></i>
                                </div>
                                Nationality
                            </label>
                            <SearchSelect v-model="customer.nationality" :options="countries"
                                placeholder="Search and select nationality..."
                                :input-class="'w-full px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm transition-all duration-200'" />
                        </div>
                        <div v-if="hasCustomerTypes">
                            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-indigo-100 rounded-full mr-1.5">
                                    <i class="fa fa-tags text-indigo-600 text-xs"></i>
                                </div>
                                Customer Type
                            </label>
                            <select v-model="customer.customer_type_id"
                                class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all duration-200">
                                <option value="">Select type</option>
                                <option v-for="(type, id) in customerTypes" :key="id" :value="id">{{ type }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-emerald-100 rounded-full mr-1.5">
                                    <i class="fa fa-whatsapp text-emerald-600 text-xs"></i>
                                </div>
                                WhatsApp
                            </label>
                            <input v-model="customer.whatsapp_mobile" type="tel"
                                class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition-all duration-200"
                                placeholder="WhatsApp number">
                        </div>
                    </div>

                    <!-- Additional Details -->
                    <div class="space-y-2 sm:space-y-3">
                        <div class="border-t border-gray-200 pt-2 sm:pt-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                                <div>
                                    <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                        <div
                                            class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-pink-100 rounded-full mr-1.5">
                                            <i class="fa fa-birthday-cake text-pink-600 text-xs"></i>
                                        </div>
                                        Date of Birth
                                    </label>
                                    <input v-model="customer.dob" type="date"
                                        class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent text-sm transition-all duration-200">
                                </div>

                                <div>
                                    <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                        <div
                                            class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-yellow-100 rounded-full mr-1.5">
                                            <i class="fa fa-id-badge text-yellow-600 text-xs"></i>
                                        </div>
                                        ID Number
                                    </label>
                                    <input v-model="customer.id_no" type="text"
                                        class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm transition-all duration-200"
                                        placeholder="ID/Passport">
                                </div>

                                <div>
                                    <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                        <div
                                            class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-orange-100 rounded-full mr-1.5">
                                            <i class="fa fa-building text-orange-600 text-xs"></i>
                                        </div>
                                        Company
                                    </label>
                                    <input v-model="customer.company" type="text"
                                        class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent text-sm transition-all duration-200"
                                        placeholder="Company name">
                                </div>

                            </div>
                        </div>

                        <!-- Credit Information -->
                        <div class="border-t border-gray-200 pt-2 sm:pt-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                                <div>
                                    <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                        <div
                                            class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-teal-100 rounded-full mr-1.5">
                                            <i class="fa fa-calendar text-teal-600 text-xs"></i>
                                        </div>
                                        Credit Period (Days)
                                    </label>
                                    <input v-model.number="customer.credit_period_days" type="number" min="0" step="1"
                                        class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent text-sm transition-all duration-200"
                                        placeholder="e.g., 30, 60, 90">
                                    <p class="text-xs text-gray-500 mt-0.5 sm:mt-1">
                                        <i class="fa fa-info-circle mr-1 text-xs"></i>
                                        Number of days allowed for credit payment
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Customers -->
                    <div v-if="existingCustomers.length > 0"
                        class="p-2 sm:p-3 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-lg shadow-sm">
                        <div class="flex items-center mb-2">
                            <div
                                class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-amber-100 rounded-full mr-1.5">
                                <i class="fa fa-exclamation-triangle text-amber-600 text-xs"></i>
                            </div>
                            <span class="text-xs sm:text-sm font-semibold text-amber-800">Similar customers found</span>
                        </div>
                        <div class="space-y-1.5 sm:space-y-2">
                            <div v-for="existing in existingCustomers" :key="existing.id"
                                @click="selectExistingCustomer(existing)"
                                class="flex justify-between items-center p-2 sm:p-2.5 bg-white rounded-lg border border-amber-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 hover:border-blue-200 cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md">
                                <div class="flex items-center space-x-2 sm:space-x-2.5">
                                    <div
                                        class="flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-full">
                                        <i class="fa fa-user text-blue-600 text-xs"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-xs sm:text-sm text-gray-800 truncate">{{
                                            existing.name }}</div>
                                        <div class="text-xs text-gray-600 truncate">
                                            <i class="fa fa-phone text-green-500 mr-1"></i>{{ existing.mobile }}
                                            <span class="mx-1 sm:mx-2">•</span>
                                            <i class="fa fa-envelope text-blue-500 mr-1"></i>
                                            {{ existing.email || 'No email' }}
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 bg-gray-100 rounded-full flex-shrink-0 ml-1.5">
                                    <i class="fa fa-chevron-right text-gray-400 text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-end space-x-2 sm:space-x-3 p-2 sm:p-3 md:p-4 border-t border-slate-200 bg-gradient-to-r from-slate-50 to-blue-50 flex-shrink-0">
                <button type="button" @click="closeModal"
                    class="flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md w-full sm:w-auto">
                    <i class="fa fa-times mr-1.5 sm:mr-2 text-slate-500"></i>
                    {{ mode === 'view' ? 'Close' : 'Cancel' }}
                </button>
                <button v-if="mode === 'edit'" type="button" @click="saveAndAddNew" :disabled="loading"
                    class="flex items-center px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-green-500 to-emerald-600 border border-transparent rounded-lg hover:from-green-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 shadow-sm hover:shadow-md">
                    <i class="fa fa-plus mr-1.5 sm:mr-2"></i>
                    Save & Add New
                </button>
                <button v-if="mode === 'edit'" type="button" @click="saveCustomer" :disabled="loading"
                    class="flex items-center px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-indigo-600 border border-transparent rounded-lg hover:from-blue-600 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 shadow-sm hover:shadow-md">
                    <i v-if="loading" class="fa fa-spinner fa-spin mr-1.5 sm:mr-2"></i>
                    <i v-else class="fa fa-check mr-1.5 sm:mr-2"></i>
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
        mode: {
            type: String,
            default: 'edit', // 'edit' or 'view'
            validator: (value) => ['edit', 'view'].includes(value)
        },
        customerId: {
            type: [String, Number],
            default: null
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

        // View mode data
        const totalSales = ref(0)
        const totalAmount = ref(0)
        const totalPaid = ref(0)
        const totalBalance = ref(0)
        const lastPurchase = ref(null)
        const recentSales = ref([])
        const feedbacks = ref([])
        const activeTab = ref('sales')

        // Computed properties for conditional rendering
        const hasCustomerTypes = computed(() => {
            return props.customerTypes && Object.keys(props.customerTypes).length > 0
        })

        const hasCountries = computed(() => {
            return props.countries && Object.keys(props.countries).length > 0
        })

        const customerFeedbacks = computed(() => {
            return feedbacks.value
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

        const formatCurrency = (amount) => {
            if (amount === null || amount === undefined) return '0.00'
            return parseFloat(amount).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        }

        const formatDate = (date) => {
            if (!date) return ''
            const d = new Date(date)
            return d.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
        }

        const formatFeedbackType = (type) => {
            const types = {
                'compliment': 'Compliment',
                'suggestion': 'Suggestion',
                'complaint': 'Complaint'
            }
            return types[type] || type
        }

        const loadCustomerDetails = async () => {
            if (!props.customerId) return

            loading.value = true
            try {
                const response = await axios.get(`/account/customer/${props.customerId}/details`)
                customer.value = response.data.customer
                totalSales.value = response.data.total_sales || 0
                totalAmount.value = response.data.total_amount || 0
                totalPaid.value = response.data.total_paid || 0
                totalBalance.value = response.data.total_balance || 0
                lastPurchase.value = response.data.last_purchase || null
                recentSales.value = response.data.recent_sales || []
                feedbacks.value = response.data.feedbacks || []
            } catch (error) {
                toast.error('Failed to load customer details')
                console.error('Error loading customer details:', error)
            } finally {
                loading.value = false
            }
        }

        const switchToEditMode = () => {
            // The parent component will handle this by changing the mode prop
            // For now, we just emit an event
            emit('customerSaved', customer.value)
        }

        // Watch for changes in customerId and show prop for view mode
        watch(() => [props.customerId, props.show, props.mode], ([newCustomerId, newShow, newMode]) => {
            if (newShow && newMode === 'view' && newCustomerId) {
                loadCustomerDetails()
            }
        }, { immediate: true })

        return {
            customer,
            loading,
            errors,
            existingCustomers,
            hasCustomerTypes,
            hasCountries,
            totalSales,
            totalAmount,
            totalPaid,
            totalBalance,
            lastPurchase,
            recentSales,
            feedbacks,
            activeTab,
            customerFeedbacks,
            closeModal,
            saveCustomer,
            saveAndAddNew,
            checkExistingCustomers,
            selectExistingCustomer,
            formatCurrency,
            formatDate,
            formatFeedbackType,
            switchToEditMode
        }
    }
}
</script>
