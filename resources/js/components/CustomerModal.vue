<template>
    <div v-if="show"
        class="posx-modal-backdrop"
        @click.self="closeModal">
        <div class="posx-modal" style="max-width: 56rem" @click.stop>
            <div class="posx-modal-head">
                <h4 class="posx-modal-title">
                    <i class="fa fa-user"></i>
                    <span>
                        Customer Details
                        <span class="posx-modal-sub">
                            {{ mode === 'view' ? 'View customer information and history' : 'Add or edit customer information' }}
                        </span>
                    </span>
                </h4>
                <button type="button" class="posx-modal-close" @click="closeModal" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="posx-modal-body">
                <!-- Loading State -->
                <div v-if="loading && mode === 'view'" class="flex items-center justify-center py-6">
                    <div class="posx-spinner animate-spin h-8 w-8"></div>
                    <span class="ml-2 text-sm posx-ink-2 font-medium">Loading customer details...</span>
                </div>

                <!-- VIEW MODE -->
                <div v-else-if="mode === 'view' && customer" class="space-y-2 sm:space-y-3">
                    <!-- Customer Basic Info -->
                    <div
                        class="posx-surface rounded-lg p-2.5 sm:p-3 border posx-hairline shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-2 gap-2">
                            <h4 class="text-sm sm:text-base font-bold posx-ink flex items-center">
                                <i class="fa fa-user mr-2 posx-pri-ink"></i>
                                Basic Information
                            </h4>
                            <button type="button" @click="switchToEditMode"
                                class="posx-btn posx-btn-primary w-full sm:w-auto">
                                <i class="fa fa-edit mr-1"></i>Edit Customer
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                            <!-- Full Name -->
                            <div class="break-words pb-2 border-b posx-hairline last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold posx-muted uppercase tracking-wide block mb-1">Full Name</label>
                                <p class="text-sm font-semibold posx-ink break-words">{{ customer.name }}</p>
                            </div>
                            <!-- Mobile -->
                            <div class="break-words pb-2 border-b posx-hairline last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold posx-muted uppercase tracking-wide block mb-1">Mobile</label>
                                <p class="text-sm font-semibold posx-ink break-words">
                                    <i class="fa fa-phone mr-1.5 posx-ok-ink"></i>
                                    <a :href="`tel:${customer.mobile}`" class="posx-pri-ink hover:posx-pri-ink hover:underline transition-colors">{{ customer.mobile }}</a>
                                </p>
                            </div>
                            <!-- Credit Period -->
                            <div class="break-words pb-2 border-b posx-hairline last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold posx-muted uppercase tracking-wide block mb-1">Credit Period</label>
                                <p class="text-sm font-semibold posx-ink break-words">
                                    <i class="fa fa-calendar mr-1.5 posx-pri-ink"></i>
                                    <span v-if="customer.credit_period_days">{{ customer.credit_period_days }} {{ customer.credit_period_days === 1 ? 'Day' : 'Days' }}</span>
                                    <span v-else class="posx-muted font-normal">Not set</span>
                                </p>
                            </div>
                            <!-- Email -->
                            <div v-if="customer.email" class="break-words pb-2 border-b posx-hairline last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold posx-muted uppercase tracking-wide block mb-1">Email</label>
                                <p class="text-sm font-semibold posx-ink break-words">
                                    <i class="fa fa-envelope mr-1.5 posx-pri-ink"></i>
                                    <a :href="`mailto:${customer.email}`" class="posx-pri-ink hover:posx-pri-ink hover:underline break-all transition-colors">{{ customer.email }}</a>
                                </p>
                            </div>
                            <!-- WhatsApp -->
                            <div v-if="customer.whatsapp_mobile" class="break-words pb-2 border-b posx-hairline last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold posx-muted uppercase tracking-wide block mb-1">WhatsApp</label>
                                <p class="text-sm font-semibold posx-ink break-words">
                                    <i class="fa fa-whatsapp mr-1.5 posx-ok-ink"></i>
                                    <a :href="`https://wa.me/${customer.whatsapp_mobile.replace(/[^0-9]/g, '')}`" target="_blank" class="posx-pri-ink hover:posx-pri-ink hover:underline transition-colors">{{ customer.whatsapp_mobile }}</a>
                                </p>
                            </div>
                            <!-- Company -->
                            <div v-if="customer.company" class="break-words pb-2 border-b posx-hairline last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold posx-muted uppercase tracking-wide block mb-1">Company</label>
                                <p class="text-sm font-semibold posx-ink break-words">
                                    <i class="fa fa-building mr-1.5 posx-acc-ink"></i>
                                    {{ customer.company }}
                                </p>
                            </div>
                            <!-- Nationality -->
                            <div v-if="customer.nationality" class="break-words pb-2 border-b posx-hairline last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold posx-muted uppercase tracking-wide block mb-1">Nationality</label>
                                <p class="text-sm font-semibold posx-ink break-words">
                                    <i class="fa fa-flag mr-1.5 posx-danger-ink"></i>
                                    {{ customer.nationality }}
                                </p>
                            </div>
                            <!-- Customer Type -->
                            <div v-if="customer.customer_type && customer.customer_type.name" class="break-words pb-2 border-b posx-hairline last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold posx-muted uppercase tracking-wide block mb-1">Customer Type</label>
                                <p class="text-sm font-semibold posx-ink break-words">
                                    <i class="fa fa-tag mr-1.5 posx-pri-ink"></i>
                                    {{ customer.customer_type.name }}
                                </p>
                            </div>
                            <!-- Date of Birth -->
                            <div v-if="customer.dob" class="break-words pb-2 border-b posx-hairline last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold posx-muted uppercase tracking-wide block mb-1">Date of Birth</label>
                                <p class="text-sm font-semibold posx-ink break-words">
                                    <i class="fa fa-birthday-cake mr-1.5 posx-acc-ink"></i>
                                    {{ formatDate(customer.dob) }}
                                </p>
                            </div>
                            <!-- ID Number -->
                            <div v-if="customer.id_no" class="break-words pb-2 border-b posx-hairline last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold posx-muted uppercase tracking-wide block mb-1">ID Number</label>
                                <p class="text-sm font-semibold posx-ink break-words">
                                    <i class="fa fa-credit-card mr-1.5 posx-acc-ink"></i>
                                    {{ customer.id_no }}
                                </p>
                            </div>
                            <!-- Tax Number -->
                            <div v-if="customer.tax_no" class="break-words pb-2 border-b posx-hairline last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label class="text-xs font-semibold posx-muted uppercase tracking-wide block mb-1">Tax Number</label>
                                <p class="text-sm font-semibold posx-ink break-words">
                                    <i class="fa fa-file-text-o mr-1.5 posx-pri-ink"></i>
                                    {{ customer.tax_no }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Summary -->
                    <div class="posx-surface rounded-lg p-2.5 sm:p-3 border posx-hairline shadow-sm hover:shadow-md transition-shadow">
                        <h4 class="text-sm sm:text-base font-bold posx-ink mb-2 flex items-center">
                            <i class="fa fa-line-chart mr-2 posx-ok-ink"></i>
                            Sales Summary
                        </h4>

                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                            <div class="text-center p-2 posx-surface-2 rounded-lg border posx-hairline shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-lg sm:text-xl font-bold posx-ok-ink break-words mb-0.5">{{ totalSales || 0 }}</div>
                                <div class="text-xs font-medium posx-ink-2 uppercase tracking-wide">Total Sales</div>
                            </div>
                            <div class="text-center p-2 posx-surface-2 rounded-lg border posx-hairline shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-lg sm:text-xl font-bold posx-pri-ink break-words mb-0.5">{{ formatCurrency(totalAmount) }}</div>
                                <div class="text-xs font-medium posx-ink-2 uppercase tracking-wide">Total Amount</div>
                            </div>
                            <div class="text-center p-2 posx-surface-2 rounded-lg border posx-hairline shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-lg sm:text-xl font-bold posx-pri-ink break-words mb-0.5">{{ formatCurrency(totalPaid) }}</div>
                                <div class="text-xs font-medium posx-ink-2 uppercase tracking-wide">Total Paid</div>
                            </div>
                            <div class="text-center p-2 posx-surface-2 rounded-lg border posx-hairline shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-lg sm:text-xl font-bold posx-danger-ink break-words mb-0.5">{{ formatCurrency(totalBalance) }}</div>
                                <div class="text-xs font-medium posx-ink-2 uppercase tracking-wide">Outstanding</div>
                            </div>
                        </div>
                        <div v-if="lastPurchase" class="mt-2 pt-2 border-t posx-hairline">
                            <div class="text-center">
                                <div class="text-xs font-semibold posx-ink-2 inline-flex items-center px-2 py-1 posx-surface-2 rounded-lg">
                                    <i class="fa fa-calendar mr-1.5 posx-pri-ink"></i>
                                    Last Purchase: {{ formatDate(lastPurchase) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Sales & Feedbacks Tabs -->
                    <div class="posx-surface rounded-lg p-2.5 sm:p-3 border posx-hairline shadow-sm hover:shadow-md transition-shadow">
                        <!-- Tab Headers -->
                        <div class="flex border-b posx-hairline mb-2 posx-surface-2 rounded-t-lg -mx-2.5 sm:-mx-3 px-2.5 sm:px-3">
                            <button @click="activeTab = 'sales'" :class="[
                                'flex-1 px-3 sm:px-4 py-2 text-xs font-semibold transition-all duration-200 rounded-t-lg',
                                activeTab === 'sales' ? 'posx-tab is-active' : 'posx-tab'
                            ]">
                                <i class="fa fa-history mr-1.5"></i>
                                Recent Sales
                            </button>
                            <button @click="activeTab = 'feedbacks'" :class="[
                                'flex-1 px-3 sm:px-4 py-2 text-xs font-semibold transition-all duration-200 rounded-t-lg',
                                activeTab === 'feedbacks' ? 'posx-tab is-active' : 'posx-tab'
                            ]">
                                <i class="fa fa-comments mr-1.5"></i>
                                Customer Feedbacks
                            </button>
                        </div>

                        <!-- Tab Content: Recent Sales -->
                        <div v-if="activeTab === 'sales'">
                            <div v-if="recentSales.length > 0" class="space-y-1.5">
                                <div v-for="sale in recentSales" :key="sale.id" class="posx-surface-2 rounded-lg p-2 border posx-hairline hover:posx-hairline hover:shadow-md transition-all duration-200">
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="min-w-0">
                                            <div class="font-bold text-sm posx-ink break-words mb-0.5">
                                                <a :href="`/sale/view/${sale.id}`" target="_blank" class="posx-pri-ink hover:posx-pri-ink hover:underline transition-colors">#{{ sale.invoice_no }}</a>
                                            </div>
                                            <div class="text-xs posx-muted flex items-center">
                                                <i class="fa fa-calendar mr-1 posx-muted"></i>
                                                {{ new Date(sale.date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-center">
                                            <div v-if="sale.rating" class="flex items-center gap-0.5 posx-surface-2 px-1.5 py-0.5 rounded border posx-hairline">
                                                <i v-for="i in 5" :key="i" :class="['fa fa-star text-xs', sale.rating >= i ? 'posx-acc-ink' : 'posx-muted']"></i>
                                                <span class="text-xs font-semibold posx-ink-2 ml-0.5">{{ sale.rating }}/5</span>
                                            </div>
                                            <span v-else class="text-xs posx-muted italic">No rating</span>
                                        </div>
                                        <div class="text-right min-w-0">
                                            <div class="font-bold text-sm posx-ok-ink mb-0.5">{{ formatCurrency(sale.total) }}</div>
                                            <div v-if="sale.balance > 0" class="text-xs font-medium posx-muted">Balance: <span class="posx-danger-ink">{{ formatCurrency(sale.balance) }}</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-4">
                                <i class="fa fa-shopping-cart text-3xl posx-muted mb-2"></i>
                                <h4 class="text-sm font-semibold posx-ink-2 mb-1">No Recent Sales</h4>
                                <p class="text-xs posx-muted">This customer hasn't made any purchases yet.</p>
                            </div>
                        </div>

                        <!-- Tab Content: Customer Feedbacks -->
                        <div v-if="activeTab === 'feedbacks'">
                            <div v-if="customerFeedbacks.length > 0" class="space-y-1.5">
                                <div v-for="feedback in customerFeedbacks" :key="feedback.id" class="posx-surface-2 rounded-lg p-2 border posx-hairline hover:posx-hairline hover:shadow-md transition-all duration-200">
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between flex-wrap gap-1.5 pb-1.5 border-b posx-hairline">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <a :href="`/sale/view/${feedback.id}`" target="_blank" class="font-bold text-sm posx-pri-ink hover:posx-pri-ink hover:underline transition-colors">#{{ feedback.invoice_no }}</a>
                                                <span class="text-xs posx-muted flex items-center">
                                                    <i class="fa fa-calendar mr-1 posx-muted"></i>
                                                    {{ new Date(feedback.date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}
                                                </span>
                                                <div v-if="feedback.rating" class="flex items-center gap-0.5 posx-surface-2 px-1.5 py-0.5 rounded border posx-hairline">
                                                    <i v-for="i in 5" :key="i" :class="['fa fa-star text-xs', feedback.rating >= i ? 'posx-acc-ink' : 'posx-muted']"></i>
                                                    <span class="text-xs font-semibold posx-ink-2 ml-0.5">{{ feedback.rating }}/5</span>
                                                </div>
                                            </div>
                                            <span v-if="feedback.feedback_type" :class="[
                                                'px-2 py-0.5 rounded-full text-xs font-semibold shadow-sm',
                                                feedback.feedback_type === 'compliment' ? 'posx-surface-2 posx-ok-ink border posx-hairline' :
                                                feedback.feedback_type === 'suggestion' ? 'posx-surface-2 posx-pri-ink border posx-hairline' :
                                                feedback.feedback_type === 'complaint' ? 'posx-surface-2 posx-danger-ink border posx-hairline' :
                                                'posx-surface-2 posx-ink-2 border posx-hairline'
                                            ]">
                                                {{ formatFeedbackType(feedback.feedback_type) }}
                                            </span>
                                        </div>
                                        <div v-if="feedback.feedback" class="text-xs posx-ink-2 posx-surface rounded-lg p-2 border posx-hairline">
                                            <div class="flex items-start">
                                                <i class="fa fa-comment mr-1.5 posx-pri-ink mt-0.5"></i>
                                                <span class="flex-1">{{ feedback.feedback }}</span>
                                            </div>
                                        </div>
                                        <div v-else class="text-xs posx-muted italic posx-surface rounded-lg p-2 border posx-hairline">No comment provided</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-4">
                                <i class="fa fa-comments text-3xl posx-muted mb-2"></i>
                                <h4 class="text-sm font-semibold posx-ink-2 mb-1">No Feedbacks</h4>
                                <p class="text-xs posx-muted">This customer hasn't provided any feedback yet.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- EDIT MODE -->
                <div v-else-if="mode === 'edit'" class="p-2 sm:p-3 md:p-4 overflow-y-auto flex-1 min-h-0">
                    <!-- Errors -->
                    <div v-if="errors.length > 0"
                        class="mb-2 sm:mb-3 p-2 sm:p-2.5 posx-surface-2 border posx-hairline rounded-md">
                        <div class="flex items-center mb-1.5">
                            <i class="fa fa-exclamation-triangle posx-danger-ink mr-1.5 text-sm"></i>
                            <span class="text-xs sm:text-sm font-medium posx-danger-ink">Please correct the errors:</span>
                        </div>
                        <ul class="text-xs sm:text-sm posx-danger-ink list-disc list-inside space-y-0.5">
                            <li v-for="error in errors" :key="error">{{ error }}</li>
                        </ul>
                    </div>

                    <form @submit.prevent="saveCustomer" class="space-y-2 sm:space-y-3">
                    <!-- Main Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                        <div>
                            <label class="flex items-center text-xs sm:text-sm font-medium posx-ink-2 mb-1">
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full mr-1.5">
                                    <i class="fa fa-user posx-pri-ink text-xs"></i>
                                </div>
                                Full Name <span class="posx-danger-ink ml-0.5">*</span>
                            </label>
                            <input v-model="customer.name" type="text" required
                                class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border posx-hairline rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm transition-all duration-200"
                                placeholder="Enter full name">
                        </div>

                        <div>
                            <label class="flex items-center text-xs sm:text-sm font-medium posx-ink-2 mb-1">
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full mr-1.5">
                                    <i class="fa fa-phone posx-ok-ink text-xs"></i>
                                </div>
                                Mobile Number <span class="posx-danger-ink ml-0.5">*</span>
                            </label>
                            <input v-model="customer.mobile" type="tel" required @input="checkExistingCustomers"
                                class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border posx-hairline rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm transition-all duration-200"
                                placeholder="Enter mobile number">
                        </div>

                        <div>
                            <label class="flex items-center text-xs sm:text-sm font-medium posx-ink-2 mb-1">
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full mr-1.5">
                                    <i class="fa fa-envelope posx-pri-ink text-xs"></i>
                                </div>
                                Email
                            </label>
                            <input v-model="customer.email" type="email"
                                class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border posx-hairline rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all duration-200"
                                placeholder="Enter email">
                        </div>

                        <div v-if="hasCountries">
                            <label class="flex items-center text-xs sm:text-sm font-medium posx-ink-2 mb-1">
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full mr-1.5">
                                    <i class="fa fa-flag posx-danger-ink text-xs"></i>
                                </div>
                                Nationality
                            </label>
                            <SearchSelect v-model="customer.nationality" :options="countries"
                                placeholder="Search and select nationality..."
                                :input-class="'w-full px-2 sm:px-3 py-1.5 sm:py-2 border posx-hairline rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm transition-all duration-200'" />
                        </div>
                        <div v-if="hasCustomerTypes">
                            <label class="flex items-center text-xs sm:text-sm font-medium posx-ink-2 mb-1">
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full mr-1.5">
                                    <i class="fa fa-tags posx-pri-ink text-xs"></i>
                                </div>
                                Customer Type
                            </label>
                            <select v-model="customer.customer_type_id"
                                class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border posx-hairline rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all duration-200">
                                <option value="">Select type</option>
                                <option v-for="(type, id) in customerTypes" :key="id" :value="id">{{ type }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="flex items-center text-xs sm:text-sm font-medium posx-ink-2 mb-1">
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full mr-1.5">
                                    <i class="fa fa-whatsapp posx-ok-ink text-xs"></i>
                                </div>
                                WhatsApp
                            </label>
                            <input v-model="customer.whatsapp_mobile" type="tel"
                                class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border posx-hairline rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition-all duration-200"
                                placeholder="WhatsApp number">
                        </div>
                    </div>

                    <!-- Additional Details -->
                    <div class="space-y-2 sm:space-y-3">
                        <div class="border-t posx-hairline pt-2 sm:pt-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                                <div>
                                    <label class="flex items-center text-xs sm:text-sm font-medium posx-ink-2 mb-1">
                                        <div
                                            class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full mr-1.5">
                                            <i class="fa fa-birthday-cake posx-acc-ink text-xs"></i>
                                        </div>
                                        Date of Birth
                                    </label>
                                    <input v-model="customer.dob" type="date"
                                        class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border posx-hairline rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent text-sm transition-all duration-200">
                                </div>

                                <div>
                                    <label class="flex items-center text-xs sm:text-sm font-medium posx-ink-2 mb-1">
                                        <div
                                            class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full mr-1.5">
                                            <i class="fa fa-credit-card posx-acc-ink text-xs"></i>
                                        </div>
                                        ID Number
                                    </label>
                                    <input v-model="customer.id_no" type="text"
                                        class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border posx-hairline rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent text-sm transition-all duration-200"
                                        placeholder="ID/Passport">
                                </div>

                                <div>
                                    <label class="flex items-center text-xs sm:text-sm font-medium posx-ink-2 mb-1">
                                        <div
                                            class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full mr-1.5">
                                            <i class="fa fa-building posx-acc-ink text-xs"></i>
                                        </div>
                                        Company
                                    </label>
                                    <input v-model="customer.company" type="text"
                                        class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border posx-hairline rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent text-sm transition-all duration-200"
                                        placeholder="Company name">
                                </div>

                                <div>
                                    <label class="flex items-center text-xs sm:text-sm font-medium posx-ink-2 mb-1">
                                        <div
                                            class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full mr-1.5">
                                            <i class="fa fa-file-text-o posx-pri-ink text-xs"></i>
                                        </div>
                                        Tax Number
                                    </label>
                                    <input v-model="customer.tax_no" type="text" maxlength="30"
                                        class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border posx-hairline rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm transition-all duration-200"
                                        placeholder="Tax number">
                                </div>

                            </div>
                        </div>

                        <!-- Credit Information -->
                        <div class="border-t posx-hairline pt-2 sm:pt-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                                <div>
                                    <label class="flex items-center text-xs sm:text-sm font-medium posx-ink-2 mb-1">
                                        <div
                                            class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full mr-1.5">
                                            <i class="fa fa-calendar posx-pri-ink text-xs"></i>
                                        </div>
                                        Credit Period (Days)
                                    </label>
                                    <input v-model.number="customer.credit_period_days" type="number" min="0" step="1"
                                        class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border posx-hairline rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent text-sm transition-all duration-200"
                                        placeholder="e.g., 30, 60, 90">
                                    <p class="text-xs posx-muted mt-0.5 sm:mt-1">
                                        <i class="fa fa-info-circle mr-1 text-xs"></i>
                                        Number of days allowed for credit payment
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Customers -->
                    <div v-if="existingCustomers.length > 0"
                        class="p-2 sm:p-3 posx-surface-2 border posx-hairline rounded-lg shadow-sm">
                        <div class="flex items-center mb-2">
                            <div
                                class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full mr-1.5">
                                <i class="fa fa-exclamation-triangle posx-acc-ink text-xs"></i>
                            </div>
                            <span class="text-xs sm:text-sm font-semibold posx-acc-ink">Similar customers found</span>
                        </div>
                        <div class="space-y-1.5 sm:space-y-2">
                            <div v-for="existing in existingCustomers" :key="existing.id"
                                @click="selectExistingCustomer(existing)"
                                class="flex justify-between items-center p-2 sm:p-2.5 posx-surface rounded-lg border posx-hairline hover:posx-hairline cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md">
                                <div class="flex items-center space-x-2 sm:space-x-2.5">
                                    <div
                                        class="flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 posx-surface-2 rounded-full">
                                        <i class="fa fa-user posx-pri-ink text-xs"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-xs sm:text-sm posx-ink truncate">{{
                                            existing.name }}</div>
                                        <div class="text-xs posx-ink-2 truncate">
                                            <i class="fa fa-phone posx-ok-ink mr-1"></i>{{ existing.mobile }}
                                            <span class="mx-1 sm:mx-2">•</span>
                                            <i class="fa fa-envelope posx-pri-ink mr-1"></i>
                                            {{ existing.email || 'No email' }}
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 posx-surface-2 rounded-full flex-shrink-0 ml-1.5">
                                    <i class="fa fa-chevron-right posx-muted text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="posx-modal-foot">
                <button type="button" @click="closeModal"
                    class="posx-btn posx-btn-ghost">
                    <i class="fa fa-times"></i>
                    {{ mode === 'view' ? 'Close' : 'Cancel' }}
                </button>
                <button v-if="mode === 'edit'" type="button" @click="saveAndAddNew" :disabled="loading"
                    class="posx-btn posx-btn-accent">
                    <i class="fa fa-plus"></i>
                    Save & Add New
                </button>
                <button v-if="mode === 'edit'" type="button" @click="saveCustomer" :disabled="loading"
                    class="posx-btn posx-btn-primary">
                    <i v-if="loading" class="fa fa-spinner fa-spin"></i>
                    <i v-else class="fa fa-check"></i>
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
            tax_no: '',
            dob: null,
            id_no: '',
            nationality: null,
            customer_type_id: '',
            credit_period_days: null,
            type: 'customer',
            status: 'active'
        })

        const resetCustomer = () => {
            customer.value = {
                id: null,
                name: '',
                mobile: '',
                whatsapp_mobile: '',
                email: '',
                company: '',
                tax_no: '',
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

        // Watch for initial customer changes
        watch(() => props.initialCustomer, (newCustomer) => {
            if (newCustomer && Object.keys(newCustomer).length > 0) {
                Object.assign(customer.value, newCustomer)
            } else {
                resetCustomer()
            }
        }, { immediate: true, deep: true })

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
                    company: customer.value.company ? customer.value.company.trim() : null,
                    tax_no: customer.value.tax_no ? customer.value.tax_no.trim() : null
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
