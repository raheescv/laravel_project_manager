<template>
  <div class="barcode-template-app" v-if="ready">
    <div class="barcode-topbar">
      <div>
        <div class="barcode-topbar__eyebrow">Barcode Template Designer</div>
        <div class="barcode-topbar__title-row">
          <input
            v-model="templateName"
            class="barcode-topbar__title"
            type="text"
            placeholder="Template name"
            @input="scheduleAutoSave"
          />
          <span v-if="templateKey === defaultTemplateKey" class="barcode-topbar__badge">Default Print</span>
        </div>
      </div>
      <div class="barcode-topbar__actions">
        <a :href="listUrl" class="barcode-btn barcode-btn--ghost">Back</a>
        <button class="barcode-btn barcode-btn--ghost" :disabled="saving" @click="resetTemplate">Reset</button>
        <button class="barcode-btn barcode-btn--primary" :disabled="saving" @click="saveTemplate">
          {{ saving ? 'Saving...' : 'Save' }}
        </button>
      </div>
    </div>

    <div class="barcode-layout">
      <aside class="barcode-sidebar barcode-sidebar--left">
        <div class="barcode-panel">
          <div class="barcode-panel__title">Elements</div>
          <button
            v-for="item in elementItems"
            :key="item.key"
            class="barcode-element-row"
            :class="{ 'is-active': selectedElementKey === item.key }"
            @click="selectedElementKey = item.key"
          >
            <div>
              <div class="barcode-element-row__label">{{ item.label }}</div>
              <div class="barcode-element-row__meta">{{ item.key }}</div>
            </div>
            <label class="barcode-switch" @click.stop>
              <input type="checkbox" v-model="settings[item.key].visible" @change="scheduleAutoSave" />
              <span />
            </label>
          </button>
        </div>

        <div class="barcode-panel">
          <div class="barcode-panel__title">Template Size</div>
          <div class="barcode-dimension-card">
            <label class="barcode-field barcode-field--dimension">
              <span>Width</span>
              <div class="barcode-input-wrap">
                <input v-model.number="settings.width" type="number" min="10" step="0.5" @input="scheduleAutoSave" />
                <em>mm</em>
              </div>
            </label>
            <label class="barcode-field barcode-field--dimension">
              <span>Height</span>
              <div class="barcode-input-wrap">
                <input v-model.number="settings.height" type="number" min="10" step="0.5" @input="scheduleAutoSave" />
                <em>mm</em>
              </div>
            </label>
          </div>
          <div class="barcode-template-note">Template size still controls the actual barcode label size.</div>
          <div class="barcode-canvas-meta">{{ settings.width }} x {{ settings.height }} mm</div>
        </div>

        <div class="barcode-panel">
          <div class="barcode-panel__title">Preview Product</div>
          <label class="barcode-field">
            <span>Select Product</span>
            <select v-model="selectedProductId" @change="applySelectedProduct">
              <option v-for="product in productOptions" :key="product.id" :value="String(product.id)">
                {{ product.name }}{{ product.size ? ` (${product.size})` : '' }}
              </option>
            </select>
          </label>
          <div v-if="selectedProduct" class="barcode-product-card">
            <div class="barcode-product-card__name">{{ selectedProduct.name }}</div>
            <div class="barcode-product-card__meta">{{ selectedProduct.name_arabic || '-' }}</div>
            <div class="barcode-product-card__meta">Barcode: {{ selectedProduct.barcode || '-' }}</div>
            <div class="barcode-product-card__meta">Size: {{ selectedProduct.size || '-' }}</div>
            <div class="barcode-product-card__meta">Price: QR {{ formatPrice(selectedProduct.mrp) }}</div>
          </div>
        </div>
      </aside>

      <section class="barcode-preview-wrap">
        <div class="barcode-preview-wrap__header">
          <div>
            <div class="barcode-panel__title barcode-panel__title--inline">Large Preview</div>
            <div class="barcode-preview-wrap__note">Preview only. Use the controls on the right to adjust the selected element.</div>
          </div>
          <a :href="printUrl" target="_blank" class="barcode-btn barcode-btn--ghost">Print</a>
        </div>

        <div class="barcode-preview-chips">
          <button
            v-for="item in visibleElementItems"
            :key="`chip-${item.key}`"
            type="button"
            class="barcode-chip"
            :class="{ 'is-active': selectedElementKey === item.key }"
            @click="selectedElementKey = item.key"
          >
            {{ item.label }}
          </button>
        </div>

        <div class="barcode-preview-stage">
          <iframe :src="previewUrl" class="barcode-preview-stage__frame" scrolling="no"></iframe>
        </div>
      </section>

      <aside class="barcode-sidebar barcode-sidebar--right">
        <div class="barcode-panel" v-if="selectedElement">
          <div class="barcode-panel__title">{{ selectedElement.label }}</div>

          <div class="barcode-field-grid">
            <label class="barcode-field">
              <span>Top</span>
              <input v-model.number="selectedElementBox.top" type="number" @input="scheduleAutoSave" />
            </label>
            <label class="barcode-field">
              <span>Left</span>
              <input v-model.number="selectedElementBox.left" type="number" @input="scheduleAutoSave" />
            </label>
            <label class="barcode-field">
              <span>Width</span>
              <input v-model.number="selectedElementBox.width" type="number" min="20" @input="scheduleAutoSave" />
            </label>
            <label class="barcode-field">
              <span>Height</span>
              <input v-model.number="selectedElementBox.height" type="number" min="12" @input="scheduleAutoSave" />
            </label>
          </div>

          <div v-if="selectedElement.key !== 'logo'" class="barcode-field-grid">
            <label class="barcode-field">
              <span>Font Size</span>
              <input v-model.number="settings[selectedElement.key].font_size" type="number" min="6" @input="scheduleAutoSave" />
            </label>
            <label class="barcode-field">
              <span>Align</span>
              <select v-model="settings[selectedElement.key].align" @change="scheduleAutoSave">
                <option value="left">Left</option>
                <option value="center">Center</option>
                <option value="right">Right</option>
              </select>
            </label>
          </div>

          <div v-if="['product_name', 'product_name_arabic', 'company_name'].includes(selectedElement.key)" class="barcode-field-grid">
            <label class="barcode-field">
              <span>Character Limit</span>
              <input v-model.number="settings[selectedElement.key].char_limit" type="number" min="5" @input="scheduleAutoSave" />
            </label>
          </div>

          <div v-if="selectedElement.key === 'barcode'" class="barcode-field-grid">
            <label class="barcode-field">
              <span>Barcode Type</span>
              <select v-model="settings.barcode.type" @change="scheduleAutoSave">
                <option v-for="(label, key) in barcodeTypes" :key="key" :value="key">{{ label }}</option>
              </select>
            </label>
            <label class="barcode-field">
              <span>Scale</span>
              <input v-model.number="settings.barcode.scale" type="number" min="1" step="0.1" @input="scheduleAutoSave" />
            </label>
          </div>

          <div class="barcode-panel__actions">
            <a :href="printUrl" target="_blank" class="barcode-btn barcode-btn--ghost">Print</a>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  templateKey: { type: String, required: true },
  listUrl: { type: String, required: true },
  dataUrl: { type: String, required: true },
  saveUrl: { type: String, required: true },
  resetUrl: { type: String, required: true },
  csrf: { type: String, required: true },
})

const ready = ref(false)
const saving = ref(false)
const settings = ref({})
const barcodeTypes = ref({})
const templateName = ref('')
const defaultTemplateKey = ref('')
const previewUrl = ref('')
const printUrl = ref('')
const previewBaseUrl = ref('')
const selectedElementKey = ref('product_name')
const productSearchUrl = ref('')
const productOptions = ref([])
const selectedProductId = ref('')
const selectedProduct = ref(null)
const suppressAutoSave = ref(true)
let autoSaveTimer = null

const elementItems = [
  { key: 'product_name', label: 'Product Name' },
  { key: 'product_name_arabic', label: 'Product Name Arabic' },
  { key: 'barcode', label: 'Barcode' },
  { key: 'company_name', label: 'Company Name' },
  { key: 'logo', label: 'Logo' },
  { key: 'price', label: 'Price' },
  { key: 'price_arabic', label: 'Price Arabic' },
  { key: 'size', label: 'Size' },
]

const visibleElementItems = computed(() => elementItems.filter((item) => settings.value[item.key]?.visible))
const selectedElement = computed(() => elementItems.find((item) => item.key === selectedElementKey.value) || null)
const selectedElementBox = computed(() => {
  const key = selectedElementKey.value
  if (!key) {
    return { top: 0, left: 0, width: 0, height: 0 }
  }

  if (!settings.value.elements) {
    settings.value.elements = {}
  }

  if (!settings.value.elements[key]) {
    settings.value.elements[key] = { top: 0, left: 0, width: 120, height: 32 }
  }

  return settings.value.elements[key]
})

async function loadData() {
  const response = await fetch(props.dataUrl, { headers: { Accept: 'application/json' } })
  const data = await response.json()
  suppressAutoSave.value = true
  settings.value = data.settings
  barcodeTypes.value = data.barcodeTypes
  templateName.value = data.templateName
  defaultTemplateKey.value = data.defaultTemplateKey
  previewBaseUrl.value = data.previewUrl
  printUrl.value = data.printUrl
  productSearchUrl.value = data.productSearchUrl
  productOptions.value = data.sampleProduct ? [data.sampleProduct] : []
  selectedProduct.value = data.sampleProduct
  selectedProductId.value = data.sampleProduct ? String(data.sampleProduct.id) : ''
  selectedElementKey.value = visibleElementItems.value[0]?.key || 'product_name'
  ready.value = true
  await loadProducts()
  suppressAutoSave.value = false
  refreshPreview()
}

async function saveTemplate() {
  clearAutoSaveTimer()
  saving.value = true
  try {
    suppressAutoSave.value = true
    const response = await fetch(props.saveUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': props.csrf,
      },
      body: JSON.stringify({
        templateName: templateName.value,
        settings: settings.value,
      }),
    })

    const data = await response.json()
    settings.value = data.settings
    refreshPreview()
  } finally {
    suppressAutoSave.value = false
    saving.value = false
  }
}

async function resetTemplate() {
  clearAutoSaveTimer()
  suppressAutoSave.value = true
  const response = await fetch(props.resetUrl, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': props.csrf,
    },
  })
  const data = await response.json()
  settings.value = data.settings
  suppressAutoSave.value = false
  refreshPreview()
}

function refreshPreview() {
  const url = new URL(previewBaseUrl.value, window.location.origin)
  if (selectedProduct.value?.id) {
    url.searchParams.set('product_id', selectedProduct.value.id)
  }
  url.searchParams.set('t', Date.now())
  previewUrl.value = `${url.pathname}${url.search}`
}

async function loadProducts() {
  if (!productSearchUrl.value) return

  const response = await fetch(`${productSearchUrl.value}?query=`, { headers: { Accept: 'application/json' } })
  const data = await response.json()
  productOptions.value = data.items || []

  if (!selectedProduct.value && productOptions.value.length) {
    selectedProduct.value = productOptions.value[0]
    selectedProductId.value = String(productOptions.value[0].id)
  }
}

function applySelectedProduct() {
  selectedProduct.value = productOptions.value.find((item) => String(item.id) === String(selectedProductId.value)) || null
  refreshPreview()
}

function clearAutoSaveTimer() {
  if (autoSaveTimer) {
    window.clearTimeout(autoSaveTimer)
    autoSaveTimer = null
  }
}

function scheduleAutoSave() {
  if (!ready.value || suppressAutoSave.value) return
  clearAutoSaveTimer()
  autoSaveTimer = window.setTimeout(() => {
    saveTemplate()
  }, 400)
}

function formatPrice(value) {
  return Number(value || 0).toFixed(2)
}

function elementBox(key) {
  if (!settings.value.elements) {
    settings.value.elements = {}
  }

  if (!settings.value.elements[key]) {
    settings.value.elements[key] = { top: 0, left: 0, width: 120, height: 32 }
  }

  return settings.value.elements[key]
}

onMounted(loadData)
onBeforeUnmount(clearAutoSaveTimer)
</script>

<style scoped>
.barcode-template-app {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.barcode-topbar {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  padding: 18px 22px;
  background: linear-gradient(135deg, #f8fbff, #eef4fb);
  border: 1px solid #d8e3f0;
  border-radius: 20px;
}

.barcode-topbar__eyebrow {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: #6b7c93;
}

.barcode-topbar__title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 6px;
}

.barcode-topbar__title {
  border: 0;
  background: transparent;
  font-size: 28px;
  font-weight: 800;
  color: #172554;
  padding: 0;
  min-width: 320px;
}

.barcode-topbar__badge {
  border-radius: 999px;
  background: #dcfce7;
  color: #166534;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 700;
}

.barcode-topbar__actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.barcode-layout {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr) 340px;
  gap: 20px;
  min-height: 720px;
}

.barcode-sidebar,
.barcode-preview-wrap {
  min-width: 0;
}

.barcode-panel,
.barcode-preview-wrap {
  background: #fff;
  border: 1px solid #dbe4f0;
  border-radius: 20px;
  padding: 18px;
  box-shadow: 0 16px 32px rgba(15, 23, 42, 0.05);
}

.barcode-sidebar .barcode-panel {
  margin-bottom: 16px;
}

.barcode-panel__title {
  font-size: 13px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #64748b;
  margin-bottom: 14px;
}

.barcode-panel__title--inline {
  margin-bottom: 4px;
}

.barcode-panel__actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 8px;
}

.barcode-element-row {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  margin-bottom: 10px;
  border-radius: 16px;
  border: 1px solid #d9e5f4;
  background: #f8fbff;
  text-align: left;
}

.barcode-element-row.is-active {
  border-color: #2563eb;
  background: #eff6ff;
}

.barcode-element-row__label {
  font-weight: 700;
  color: #1e293b;
}

.barcode-element-row__meta,
.barcode-canvas-meta,
.barcode-preview-wrap__note {
  color: #64748b;
  font-size: 12px;
}

.barcode-switch {
  position: relative;
  width: 42px;
  height: 24px;
}

.barcode-switch input { display: none; }
.barcode-switch span {
  position: absolute;
  inset: 0;
  background: #cbd5e1;
  border-radius: 999px;
}
.barcode-switch span::after {
  content: '';
  position: absolute;
  width: 18px;
  height: 18px;
  left: 3px;
  top: 3px;
  border-radius: 50%;
  background: #fff;
  transition: transform .2s ease;
}
.barcode-switch input:checked + span { background: #2563eb; }
.barcode-switch input:checked + span::after { transform: translateX(18px); }

.barcode-dimension-card {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  padding: 12px;
  border: 1px solid #d9e5f4;
  border-radius: 18px;
  background: linear-gradient(180deg, #f8fbff, #f1f6fd);
  margin-bottom: 12px;
}

.barcode-field--dimension {
  margin-bottom: 0;
}

.barcode-input-wrap {
  display: flex;
  align-items: center;
  border: 1px solid #cfd9e6;
  border-radius: 14px;
  background: #fff;
  overflow: hidden;
}

.barcode-input-wrap input {
  border: 0;
  border-radius: 0;
}

.barcode-input-wrap em {
  font-style: normal;
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  padding: 0 12px;
}

.barcode-template-note {
  font-size: 12px;
  line-height: 1.5;
  color: #64748b;
  margin-bottom: 12px;
}

.barcode-product-card {
  padding: 14px;
  border: 1px solid #d9e5f4;
  border-radius: 16px;
  background: linear-gradient(180deg, #f8fbff, #f2f7fd);
}

.barcode-product-card__name {
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 6px;
}

.barcode-product-card__meta {
  font-size: 12px;
  color: #64748b;
  margin-bottom: 4px;
}

.barcode-preview-wrap {
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: linear-gradient(180deg, #f8fbff, #edf4fb);
}

.barcode-preview-wrap__header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
}

.barcode-preview-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.barcode-chip {
  border: 1px solid #c9d7ea;
  background: #fff;
  color: #334155;
  border-radius: 999px;
  padding: 8px 14px;
  font-size: 12px;
  font-weight: 800;
  line-height: 1;
}

.barcode-chip.is-active {
  background: #1d4ed8;
  border-color: #1d4ed8;
  color: #fff;
  box-shadow: 0 10px 20px rgba(29, 78, 216, 0.22);
}

.barcode-preview-stage {
  flex: 1;
  min-height: 760px;
  padding: 28px;
  overflow: hidden;
  border-radius: 24px;
  border: 1px solid #dbe4f0;
  background:
    radial-gradient(circle at 1px 1px, rgba(37, 99, 235, 0.14) 1px, transparent 0),
    linear-gradient(180deg, #f7faff, #edf3fa);
  background-size: 20px 20px, auto;
  display: flex;
  align-items: flex-start;
  justify-content: center;
}

.barcode-preview-stage__frame {
  width: min(100%, 760px);
  height: 420px;
  max-height: 100%;
  overflow: hidden;
  border: 0;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 18px 34px rgba(15, 23, 42, 0.08);
  outline: 2px solid rgba(191, 219, 254, 0.9);
  outline-offset: -2px;
}

.barcode-field-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.barcode-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 12px;
}

.barcode-field span {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
}

.barcode-field input,
.barcode-field select {
  border: 1px solid #d1d9e6;
  border-radius: 12px;
  padding: 10px 12px;
  background: #fff;
}

.barcode-btn {
  border-radius: 14px;
  padding: 10px 16px;
  font-weight: 700;
  text-decoration: none;
  border: 1px solid #c9d7ea;
  background: #fff;
  color: #1e3a8a;
}

.barcode-btn--primary {
  background: #1d4ed8;
  border-color: #1d4ed8;
  color: #fff;
}

.barcode-btn:disabled {
  opacity: .6;
}

@media (max-width: 1400px) {
  .barcode-layout {
    grid-template-columns: 240px minmax(0, 1fr);
  }

  .barcode-sidebar--right {
    grid-column: 1 / -1;
  }
}

@media (max-width: 900px) {
  .barcode-layout,
  .barcode-dimension-card,
  .barcode-field-grid {
    grid-template-columns: 1fr;
  }
}
</style>
