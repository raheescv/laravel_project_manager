<template>
    <div class="min-h-screen bg-gray-50 p-4 md:p-6">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Tailoring Orders</h1>
                        <p class="text-gray-600 mt-1">Manage all tailoring orders</p>
                    </div>
                    <a :href="route('tailoring::order::create')"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold">
                        Create Order
                    </a>
                </div>
            </div>

            <!-- Orders list will be implemented here -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <p class="text-gray-500 text-center py-8">Orders list will be displayed here</p>
            </div>
        </div>
    </div>
</template>

<script setup>
// route is available globally via ZiggyVue plugin
</script>
