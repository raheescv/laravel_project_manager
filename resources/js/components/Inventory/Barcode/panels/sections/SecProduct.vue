<template>
  <div>
    <div class="bcx-drawer__title">Preview Product</div>

    <label class="bcx-field bcx-field--stack">
      <span>Sample product</span>
      <select :value="modelValue" @change="$emit('update:modelValue', $event.target.value)">
        <option v-for="product in products" :key="product.id" :value="String(product.id)">
          {{ product.name }}{{ product.size ? ` (${product.size})` : '' }}
        </option>
      </select>
    </label>

    <div v-if="product" class="bcx-note" style="line-height:1.8">
      <div><b style="color:var(--bcx-ink)">{{ product.name }}</b></div>
      <div v-if="product.name_arabic" dir="rtl">{{ product.name_arabic }}</div>
      <div>Barcode <span class="bcx-num">{{ product.barcode || '—' }}</span></div>
      <div>Size <span class="bcx-num">{{ product.size || '—' }}</span></div>
      <div>Price <span class="bcx-num">QR {{ price }}</span></div>
    </div>

    <p class="bcx-note">The proof always renders with real data, so you can see how long names actually behave.</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  products: { type: Array, default: () => [] },
  product: { type: Object, default: null },
  modelValue: { type: String, default: '' },
})

defineEmits(['update:modelValue'])

const price = computed(() => Number(props.product?.mrp || 0).toFixed(2))
</script>
