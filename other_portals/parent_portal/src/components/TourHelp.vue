<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import { tourLabels } from '@/tours'
import { hasTour, startTour } from '@/utils/tour'

/** The "?" in the header: plays this page's tour again. Hidden on pages without one. */
const route = useRoute()
const available = computed(() => hasTour(route.name))
</script>

<template>
  <button v-if="available" class="pp-themebtn pp-helpbtn" type="button" data-tour="help" :title="tourLabels.help" :aria-label="tourLabels.help" @click="startTour(route)">
    <i class="fa fa-question-circle" aria-hidden="true"></i>
  </button>
</template>
