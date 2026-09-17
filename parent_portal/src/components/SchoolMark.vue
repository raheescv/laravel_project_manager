<script setup>
import { school } from '@/school'

/** The school's name as it is printed on a card face. */
</script>

<template>
  <span class="pp-card__school"><i class="fa fa-graduation-cap pp-mark"></i>{{ school.name || 'Parent Portal' }}</span>
</template>
