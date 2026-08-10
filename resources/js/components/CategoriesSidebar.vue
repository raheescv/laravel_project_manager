<template>
    <!-- Categories Sidebar - Mobile: top bar, Desktop: left sidebar -->
    <div class="flex flex-col h-full min-h-0 categories-container">
        <div class="posx-panel posx-panel-flush h-full flex flex-col min-h-0">
            <!-- Categories Header -->
            <div class="posx-head px-2.5 py-2 flex-shrink-0">
                <div class="flex items-center justify-between gap-2">
                    <h6 class="font-bold text-xs sm:text-sm flex items-center gap-2 mb-0">
                        <i class="fa fa-th-large text-xs"></i>
                        <span class="posx-head-label">Categories</span>
                    </h6>
                    <div class="flex items-center gap-1">
                        <a href="/sale" class="posx-htool d-print-none" aria-label="Open sale list" title="Sale list">
                            <i class="fa fa-list-ul"></i>
                        </a>
                        <button type="button" @click="toggleFullscreen" class="posx-htool d-print-none"
                            aria-label="Toggle fullscreen" title="Toggle fullscreen">
                            <i class="fa fa-arrows"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Fixed Categories (Favorites & All Products) -->
            <div class="fixed-categories posx-side-pin posx-panel-2 flex-shrink-0 border-b posx-divide flex flex-col w-full">
                <!-- Favorites -->
                <button type="button" @click="handleCategorySelect('favorite')" title="Favorites" :class="[
                    'category-btn fixed-category-btn posx-cat fav',
                    selectedCategory === 'favorite' ? 'is-active' : ''
                ]">
                    <span class="posx-cat-ico"><i class="fa fa-star"></i></span>
                        <span class="posx-cat-name">Favorites</span>
                </button>

                <!-- All Products -->
                <button type="button" @click="handleCategorySelect('')" title="All Products" :class="[
                    'category-btn fixed-category-btn posx-cat',
                    selectedCategory === '' ? 'is-active' : ''
                ]">
                    <span class="posx-cat-ico"><i class="fa fa-th-large"></i></span>
                        <span class="posx-cat-name">All Products</span>
                </button>
            </div>

            <!-- Scrollable Categories List -->
            <div ref="scrollContainer"
                class="posx-side-scroll flex flex-col custom-scrollbar flex-1 min-h-0 overflow-y-auto scroll-smooth categories-scroll-container"
                style="scroll-behavior:smooth;-webkit-overflow-scrolling:touch">
                <div class="posx-side-track flex flex-col w-full">
                <!-- Category Items -->
                <button v-for="category in categories" :key="category.id" type="button"
                    @click="handleCategorySelect(category.id)" :class="[
                        'category-btn posx-cat',
                        selectedCategory === category.id ? 'is-active' : ''
                    ]">
                    <span class="posx-cat-ico"><i class="fa fa-tag"></i></span>
                    <span class="posx-cat-name text-left" :title="category.name">{{ category.name }}</span>
                    <span class="posx-cat-count">{{ category.products_count || 0 }}</span>
                </button>
                </div>
            </div>

            <!-- closes the column so it never ends in dead white space -->
            <div class="posx-side-foot">
                <span>Catalogue</span>
                <b>{{ totalProductCount }} items</b>
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
        const scrollContainer = ref(null)

        // The sidebar becomes a horizontal strip on a CONTAINER query, not a
        // viewport one, so orientation is read off the element itself — a
        // window-width test would be wrong whenever the shell is narrower
        // than the window (collapsed nav, split view).
        const isHorizontal = () => {
            const el = scrollContainer.value
            if (!el) return false
            return getComputedStyle(el).flexDirection.startsWith('row')
        }

        const scrollToActiveCategory = () => {
            nextTick(() => {
                if (!scrollContainer.value) return

                // Active state is a stable class now, not a gradient utility
                const activeButton = scrollContainer.value.querySelector('.category-btn.is-active')
                if (activeButton) {
                    if (!isHorizontal()) {
                        // Column: scroll vertically
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

        const toggleFullscreen = () => {
            if (typeof window !== 'undefined' && typeof window.toggleFullscreen === 'function') {
                window.toggleFullscreen()
                return
            }

            const doc = document
            const elem = document.documentElement
            const isNotFullscreen = !doc.fullscreenElement && !doc.mozFullScreenElement && !doc.webkitFullscreenElement && !doc.msFullscreenElement

            if (isNotFullscreen) {
                if (elem.requestFullscreen) {
                    elem.requestFullscreen()
                } else if (elem.msRequestFullscreen) {
                    elem.msRequestFullscreen()
                } else if (elem.mozRequestFullScreen) {
                    elem.mozRequestFullScreen()
                } else if (elem.webkitRequestFullscreen) {
                    elem.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT)
                }
            } else {
                if (doc.exitFullscreen) {
                    doc.exitFullscreen()
                } else if (doc.msExitFullscreen) {
                    doc.msExitFullscreen()
                } else if (doc.mozCancelFullScreen) {
                    doc.mozCancelFullScreen()
                } else if (doc.webkitExitFullscreen) {
                    doc.webkitExitFullscreen()
                }
            }
        }

        // Watch for selected category changes to auto-scroll
        watch(() => props.selectedCategory, () => {
            scrollToActiveCategory()
        }, { flush: 'post' })

        // Height is CSS now (flex:1 + own scroll), so the only thing left to do
        // on a resize is keep the active chip in view when the strip flips
        // between row and column.
        const onResize = () => scrollToActiveCategory()

        onMounted(() => {
            scrollToActiveCategory()
            window.addEventListener('resize', onResize)
            window.addEventListener('orientationchange', onResize)
        })

        onUnmounted(() => {
            window.removeEventListener('resize', onResize)
            window.removeEventListener('orientationchange', onResize)
        })

        const totalProductCount = computed(() =>
            props.categories.reduce((n, c) => n + (Number(c.products_count) || 0), 0).toLocaleString()
        )

        return {
            totalProductCount,
            scrollContainer,
            handleCategorySelect,
            toggleFullscreen
        }
    }
}
</script>

<style scoped>
@import '../../css/pos-common.css';
</style>
