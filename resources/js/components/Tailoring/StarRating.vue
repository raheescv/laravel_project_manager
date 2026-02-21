<template>
    <div class="star-rating" :class="sizeClass">
        <button v-for="star in max" :key="`star-${star}`" type="button" class="star-rating__btn" :disabled="disabled"
            :aria-label="`Rate ${star} star${star > 1 ? 's' : ''}`" @click="onSelect(star)">
            <i class="fa fa-star"
                :class="star <= currentValue ? 'text-amber-500' : 'text-slate-300 hover:text-amber-400'"></i>
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: {
        type: [Number, String, null],
        default: null,
    },
    max: {
        type: Number,
        default: 5,
    },
    size: {
        type: String,
        default: 'md',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:modelValue'])

const currentValue = computed(() => {
    const parsed = Number(props.modelValue || 0)
    if (!Number.isFinite(parsed) || parsed <= 0) return 0

    return Math.min(Math.max(Math.round(parsed), 0), props.max)
})

const sizeClass = computed(() => `star-rating--${props.size === 'sm' ? 'sm' : 'md'}`)

const onSelect = (value) => {
    if (props.disabled) return
    emit('update:modelValue', Number(value))
}
</script>

<style scoped>
.star-rating {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    border: 1px solid #cbd5e1;
    background: linear-gradient(180deg, #eef2f7 0%, #e8edf4 100%);
}

.star-rating--md {
    height: 38px;
    padding: 0 10px;
    border-radius: 0.75rem;
}

.star-rating--sm {
    height: 34px;
    padding: 0 8px;
    border-radius: 0.7rem;
}

.star-rating__btn {
    flex: 1;
    border: none;
    background: transparent;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    padding: 0;
}

.star-rating--md .star-rating__btn {
    font-size: 1.65rem;
}

.star-rating--sm .star-rating__btn {
    font-size: 1.15rem;
}
</style>
