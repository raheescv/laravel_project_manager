<template>
    <template v-for="(part, i) in parts" :key="i">
        <a v-if="part.href" :href="part.href" class="tkx-link" :target="part.external ? '_blank' : null"
            :rel="part.external ? 'noopener noreferrer' : null">{{ part.text }}</a>
        <template v-else>{{ part.text }}</template>
    </template>
</template>

<script setup>
import { computed } from 'vue'
import { linkify } from './ticketMeta.js'

const props = defineProps({ text: { type: String, default: '' } })

const parts = computed(() => linkify(props.text))
</script>
