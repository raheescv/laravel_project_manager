<template>
    <!-- Categories Sidebar - Mobile: top bar, Desktop: left sidebar -->
    <div class="w-full lg:w-60 flex flex-col order-1 lg:order-1 h-auto lg:h-full categories-container"
        :style="{ height: containerHeight }">
        <div class="bg-white/95 backdrop-blur-lg rounded-xl shadow-xl border border-emerald-100/60 h-full flex flex-col overflow-hidden min-h-0"
            style="background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.98) 50%, rgba(255,255,255,0.95) 100%);">
            <!-- Categories Header -->
            <div
                class="bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 p-3 relative overflow-hidden flex-shrink-0">
                <div class="absolute inset-0 bg-gradient-to-r from-white/15 to-transparent"></div>
                <div class="absolute inset-0 opacity-20 bg-gradient-to-r from-yellow-300 via-transparent to-pink-300">
                </div>
                <div class="relative flex items-center justify-between z-10">
                    <h6 class="text-white font-bold text-sm flex items-center drop-shadow-sm">
                        <div class="bg-white/25 p-1.5 rounded-lg mr-2 border border-white/20 backdrop-blur-sm">
                            <i class="fa fa-th-large text-white text-sm drop-shadow-sm"></i>
                        </div>
                        Categories
                    </h6>
                    <span
                        class="bg-white/25 backdrop-blur text-white px-2 py-1 rounded-full text-xs font-semibold shadow-lg border border-white/20">
                        {{ categories.length }}
                    </span>
                </div>
                <!-- Subtle decorative elements -->
                <div class="absolute -right-2 -top-2 w-16 h-16 bg-white/5 rounded-full"></div>
                <div class="absolute -left-4 -bottom-4 w-20 h-20 bg-white/5 rounded-full"></div>
            </div>

            <!-- Fixed Categories (Favorites & All Products) -->
            <div
                class="fixed-categories flex-shrink-0 bg-gradient-to-b from-emerald-50/40 via-white/60 to-rose-50/40 backdrop-blur-sm border-b border-emerald-200/60">
                <!-- Favorites -->
                <button type="button" @click="handleCategorySelect('favorite')" :class="[
                    'category-btn fixed-category-btn flex-shrink-0 lg:w-full flex items-center px-3 py-2.5 border-b border-white/20 transition-all duration-300 group relative overflow-hidden whitespace-nowrap lg:whitespace-normal',
                    selectedCategory === 'favorite'
                        ? 'bg-gradient-to-r from-rose-400 via-pink-500 to-purple-500 text-white shadow-lg transform scale-[1.02] ring-2 ring-white/30'
                        : 'bg-white/70 hover:bg-gradient-to-r hover:from-rose-50 hover:via-pink-50 hover:to-purple-50 text-slate-700 hover:text-rose-600 hover:shadow-md backdrop-blur-sm border border-rose-100/50'
                ]">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        v-if="selectedCategory !== 'favorite'"></div>
                    <div :class="[
                        'p-1.5 rounded-lg mr-3 transition-all duration-300 relative z-10',
                        selectedCategory === 'favorite' ? 'bg-white/25 shadow-md backdrop-blur-sm border border-white/20' : 'bg-rose-100 group-hover:bg-rose-200 group-hover:shadow-sm'
                    ]">
                        <i :class="[
                            'fa fa-star text-sm drop-shadow-sm',
                            selectedCategory === 'favorite' ? 'text-white' : 'text-rose-600'
                        ]"></i>
                    </div>
                    <span class="flex-1 text-left font-semibold relative z-10 text-sm">Favorites</span>
                    <div v-if="selectedCategory === 'favorite'"
                        class="w-1.5 h-1.5 bg-white rounded-full animate-pulse shadow-sm"></div>
                </button>

                <!-- All Products -->
                <button type="button" @click="handleCategorySelect('')" :class="[
                    'category-btn fixed-category-btn flex-shrink-0 lg:w-full flex items-center px-3 py-2.5 border-b border-white/20 transition-all duration-300 group relative overflow-hidden whitespace-nowrap lg:whitespace-normal',
                    selectedCategory === ''
                        ? 'bg-gradient-to-r from-emerald-400 via-teal-500 to-cyan-500 text-white shadow-lg transform scale-[1.02] ring-2 ring-white/30'
                        : 'bg-white/70 hover:bg-gradient-to-r hover:from-emerald-50 hover:via-teal-50 hover:to-cyan-50 text-slate-700 hover:text-emerald-600 hover:shadow-md backdrop-blur-sm border border-emerald-100/50'
                ]">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        v-if="selectedCategory !== ''"></div>
                    <div :class="[
                        'p-1.5 rounded-lg mr-3 transition-all duration-300 relative z-10',
                        selectedCategory === '' ? 'bg-white/25 shadow-md backdrop-blur-sm border border-white/20' : 'bg-emerald-100 group-hover:bg-emerald-200 group-hover:shadow-sm'
                    ]">
                        <i :class="[
                            'fa fa-th-large text-sm drop-shadow-sm',
                            selectedCategory === '' ? 'text-white' : 'text-emerald-600'
                        ]"></i>
                    </div>
                    <span class="flex-1 text-left font-semibold relative z-10 text-sm">All Products</span>
                    <div v-if="selectedCategory === ''"
                        class="w-1.5 h-1.5 bg-white rounded-full animate-pulse shadow-sm"></div>
                </button>
            </div>

            <!-- Scrollable Categories List -->
            <div ref="scrollContainer"
                class="flex lg:flex-col overflow-x-auto lg:overflow-x-hidden lg:overflow-y-auto bg-gradient-to-b from-violet-50/30 via-white/60 to-indigo-50/40 backdrop-blur-sm custom-scrollbar flex-1 min-h-0 scroll-smooth categories-scroll-container"
                :style="{
                    scrollBehavior: 'smooth',
                    WebkitOverflowScrolling: 'touch',
                    maxHeight: windowWidth >= 1024 ? categoriesHeight : '120px',
                    height: windowWidth >= 1024 ? categoriesHeight : 'auto'
                }">

                <!-- Category Items -->
                <button v-for="category in categories" :key="category.id" type="button"
                    @click="handleCategorySelect(category.id)" :class="[
                        'category-btn flex-shrink-0 lg:w-full flex items-center px-3 py-2.5 border-b border-white/20 transition-all duration-300 group relative overflow-hidden whitespace-nowrap lg:whitespace-normal',
                        selectedCategory === category.id
                            ? 'bg-gradient-to-r from-violet-400 via-purple-500 to-indigo-500 text-white shadow-lg transform scale-[1.02] ring-2 ring-white/30'
                            : 'bg-white/60 hover:bg-gradient-to-r hover:from-violet-50 hover:via-purple-50 hover:to-indigo-50 text-slate-700 hover:text-violet-600 hover:shadow-md backdrop-blur-sm border border-violet-100/50'
                    ]">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        v-if="selectedCategory !== category.id"></div>
                    <div :class="[
                        'p-1.5 rounded-lg mr-3 transition-all duration-300 relative z-10',
                        selectedCategory === category.id ? 'bg-white/25 shadow-md backdrop-blur-sm border border-white/20' : 'bg-violet-100/80 group-hover:bg-violet-200 group-hover:shadow-sm'
                    ]">
                        <i :class="[
                            'fa fa-tag text-sm drop-shadow-sm',
                            selectedCategory === category.id ? 'text-white' : 'text-violet-600'
                        ]"></i>
                    </div>
                    <div class="flex-1 text-left relative z-10 min-w-0">
                        <span class="font-semibold block text-sm truncate" :title="category.name">{{
                            category.name }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 relative z-10">
                        <span :class="[
                            'px-1.5 py-0.5 rounded-full text-xs font-bold shadow-sm',
                            selectedCategory === category.id
                                ? 'bg-white/20 text-white'
                                : 'bg-slate-100/80 text-slate-600 group-hover:bg-cyan-100 group-hover:text-cyan-800'
                        ]">
                            {{ category.products_count || 0 }}
                        </span>
                        <div v-if="selectedCategory === category.id"
                            class="w-1.5 h-1.5 bg-white rounded-full animate-pulse shadow-sm"></div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

export default {
    name: 'CategoriesSidebar',
    props: {
        categories: {
            type: Array,
            required: true,
            default: () => []
        },
        selectedCategory: {
            type: [String, Number],
            default: 'favorite'
        }
    },
    emits: ['category-selected'],
    setup(props, { emit }) {
        const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)
        const windowHeight = ref(typeof window !== 'undefined' ? window.innerHeight : 768)
        const containerHeight = ref('auto')
        const scrollContainer = ref(null)

        const categoriesHeight = computed(() => {
            if (windowWidth.value >= 1024) {
                // Desktop: Calculate height based on screen size minus header and padding
                const availableHeight = windowHeight.value - 200 // Reserve space for header, padding, etc.
                return `${Math.max(400, availableHeight)}px`
            } else {
                // Mobile: Fixed height for horizontal scroll
                return '120px'
            }
        })

        const updateDimensions = () => {
            windowWidth.value = window.innerWidth
            windowHeight.value = window.innerHeight

            // Update container height on next tick
            nextTick(() => {
                if (windowWidth.value >= 1024) {
                    // Desktop: Set max height based on screen size
                    const maxHeight = Math.max(400, windowHeight.value - 200)
                    containerHeight.value = `${maxHeight}px`
                } else {
                    // Mobile: Auto height for content
                    containerHeight.value = 'auto'
                }
            })
        }

        const scrollToActiveCategory = () => {
            nextTick(() => {
                if (!scrollContainer.value) return

                // Look for active category button with updated color selectors
                const activeButton = scrollContainer.value.querySelector('.category-btn[class*="from-purple-500"], .category-btn[class*="from-indigo-500"], .category-btn[class*="from-cyan-500"]')
                if (activeButton) {
                    if (windowWidth.value >= 1024) {
                        // Desktop: vertical scroll
                        activeButton.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                            inline: 'nearest'
                        })
                    } else {
                        // Mobile: horizontal scroll
                        activeButton.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest',
                            inline: 'center'
                        })
                    }
                }
            })
        }

        const handleCategorySelect = (categoryId) => {
            emit('category-selected', categoryId)
            scrollToActiveCategory()
        }

        // Watch for selected category changes to auto-scroll
        watch(() => props.selectedCategory, () => {
            scrollToActiveCategory()
        }, { flush: 'post' })

        onMounted(() => {
            updateDimensions()
            scrollToActiveCategory()

            window.addEventListener('resize', updateDimensions)
            window.addEventListener('orientationchange', () => {
                // Delay to account for orientation change
                setTimeout(() => {
                    updateDimensions()
                    scrollToActiveCategory()
                }, 100)
            })

            // Initial height setup
            nextTick(() => {
                if (windowWidth.value >= 1024) {
                    const maxHeight = Math.max(400, windowHeight.value - 200)
                    containerHeight.value = `${maxHeight}px`
                }
            })
        })

        onUnmounted(() => {
            window.removeEventListener('resize', updateDimensions)
            window.removeEventListener('orientationchange', updateDimensions)
        })

        return {
            windowWidth,
            windowHeight,
            categoriesHeight,
            containerHeight,
            scrollContainer,
            handleCategorySelect
        }
    }
}
</script>

<style scoped>
@import '../../css/pos-common.css';
</style>
