<template>
  <div class="bcx bcx-shell bcx-designer" v-if="ready">
    <!-- command bar -->
    <div class="bcx-bar">
      <a :href="listUrl" class="bcx-btn bcx-back" title="Back to templates">
        <i class="fa fa-angle-left"></i><span class="bcx-back__label">Templates</span>
      </a>
      <input v-model="templateName" class="bcx-name" type="text" placeholder="Template name" @input="scheduleAutoSave" />
      <span class="bcx-chip bcx-chip--brand" :title="typeDescription">{{ typeLabel }}</span>
      <span v-if="templateKey === defaultTemplateKey" class="bcx-chip">Default</span>

      <div class="bcx-spacer"></div>

      <button class="bcx-btn" :disabled="saving" @click="resetTemplate">
        <i class="fa fa-refresh"></i> Reset
      </button>
      <a :href="printUrl" target="_blank" class="bcx-btn"><i class="fa fa-print"></i> Print</a>
      <button class="bcx-btn bcx-btn--primary" :disabled="saving" @click="saveTemplate">
        <i class="fa fa-check"></i> {{ saving ? 'Saving…' : 'Save' }}
      </button>
    </div>

    <div class="bcx-designer__body">
      <!-- icon rail: one control group at a time -->
      <nav class="bcx-rail">
        <button
          v-for="section in sections"
          :key="section.key"
          type="button"
          class="bcx-rail__btn"
          :class="{ 'is-active': activeSection === section.key }"
          :title="section.label"
          @click="activeSection = section.key"
        >
          <i class="fa" :class="section.icon"></i>
        </button>
      </nav>

      <!-- the drawer the rail swaps -->
      <aside class="bcx-drawer">
        <component
          :is="activeSectionComponent"
          :settings="settings"
          :barcode-types="barcodeTypes"
          :qty-sources="qtySources"
          :fonts="fonts"
          :font-weights="fontWeights"
          :products="productOptions"
          :product="selectedProduct"
          :model-value="selectedProductId"
          v-model:selected="selectedElementKey"
          @update:modelValue="applySelectedProduct"
          @change="scheduleAutoSave"
        />
      </aside>

      <!-- stage -->
      <section class="bcx-stage" ref="stageEl">
        <div class="bcx-stage__sheet" :style="sheetStyle">
          <iframe :src="previewUrl" class="bcx-stage__frame" :style="frameStyle" scrolling="no"></iframe>
        </div>
      </section>

      <!-- inspector for whatever is selected -->
      <aside class="bcx-drawer bcx-drawer--end">
        <component
          :is="typeUi.inspector"
          :settings="settings"
          :barcode-types="barcodeTypes"
          :qty-sources="qtySources"
          :fonts="fonts"
          :font-weights="fontWeights"
          :selected="selectedElementKey"
          @change="scheduleAutoSave"
        />
      </aside>
    </div>

    <!-- status bar -->
    <div class="bcx-status">
      <span>LABEL <b>{{ settings.width }} × {{ settings.height }} mm</b></span>
      <span v-if="isJewellery">WINGS <b>{{ settings.wing_width }} / {{ settings.neck_width }} / {{ settings.wing_width }}</b></span>
      <span>SYMBOLOGY <b>{{ settings.barcode?.type }}</b></span>
      <span class="bcx-status__zoom">
        ZOOM
        <button class="bcx-ord" title="Zoom out" @click="nudgeZoom(-0.15)"><i class="fa fa-search-minus"></i></button>
        <b>{{ Math.round(preview.scale * 100) }}%</b>
        <button class="bcx-ord" title="Zoom in" @click="nudgeZoom(0.15)"><i class="fa fa-search-plus"></i></button>
        <button class="bcx-ord" title="Fit to stage" @click="zoom = 1"><i class="fa fa-expand"></i></button>
      </span>
      <span class="bcx-spacer"></span>
      <span>
        <i class="bcx-dot" :class="{ 'bcx-dot--idle': saving }"></i>
        {{ saving ? 'SAVING…' : 'SAVED' }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import InspJewellery from './panels/inspectors/InspJewellery.vue'
import InspStandard from './panels/inspectors/InspStandard.vue'
import SecJewelleryBarcode from './panels/sections/SecJewelleryBarcode.vue'
import SecJewelleryFields from './panels/sections/SecJewelleryFields.vue'
import SecJewellerySize from './panels/sections/SecJewellerySize.vue'
import SecProduct from './panels/sections/SecProduct.vue'
import SecStandardElements from './panels/sections/SecStandardElements.vue'
import SecStandardSize from './panels/sections/SecStandardSize.vue'
import SecTypography from './panels/sections/SecTypography.vue'

// One entry per label type: the groups its rail offers and the inspector that
// edits whatever is selected. A new type is a new entry here plus its defaults
// in config/barcode_default_configuration.php - nothing else.
const TYPE_UI = {
  standard: {
    sections: [
      { key: 'elements', icon: 'fa-th-large', label: 'Elements', comp: SecStandardElements },
      { key: 'size', icon: 'fa-arrows-alt', label: 'Label size', comp: SecStandardSize },
      { key: 'typography', icon: 'fa-font', label: 'Typography', comp: SecTypography },
      { key: 'product', icon: 'fa-cube', label: 'Preview product', comp: SecProduct },
    ],
    inspector: InspStandard,
    fallbackSelection: 'product_name',
  },
  jewellery_tag: {
    sections: [
      { key: 'size', icon: 'fa-arrows-h', label: 'Tag size', comp: SecJewellerySize },
      { key: 'fields', icon: 'fa-list-ul', label: 'Text wing', comp: SecJewelleryFields },
      { key: 'barcode', icon: 'fa-barcode', label: 'Barcode wing', comp: SecJewelleryBarcode },
      { key: 'typography', icon: 'fa-font', label: 'Typography', comp: SecTypography },
      { key: 'product', icon: 'fa-cube', label: 'Preview product', comp: SecProduct },
    ],
    inspector: InspJewellery,
    fallbackSelection: 'product_name',
  },
}

// Only used until the stage has been measured for the first time.
const STAGE_FALLBACK = { width: 640, height: 420 }

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
const qtySources = ref({})
const fonts = ref({})
const fontWeights = ref({})
const templateType = ref('standard')
const types = ref({})
const templateName = ref('')
const defaultTemplateKey = ref('')
const previewUrl = ref('')
const printUrl = ref('')
const previewBaseUrl = ref('')
const selectedElementKey = ref('product_name')
const activeSection = ref('')
const zoom = ref(1)
const productSearchUrl = ref('')
const productOptions = ref([])
const selectedProductId = ref('')
const selectedProduct = ref(null)
const suppressAutoSave = ref(true)
const stageEl = ref(null)
const stageBox = ref({ ...STAGE_FALLBACK })
let autoSaveTimer = null
let stageObserver = null

const typeUi = computed(() => TYPE_UI[templateType.value] || TYPE_UI.standard)
const sections = computed(() => typeUi.value.sections)
const isJewellery = computed(() => templateType.value === 'jewellery_tag')
const typeLabel = computed(() => types.value[templateType.value]?.label || templateType.value)
const typeDescription = computed(() => types.value[templateType.value]?.description || '')

const activeSectionComponent = computed(
  () => sections.value.find((s) => s.key === activeSection.value)?.comp || sections.value[0].comp
)

// The preview renders the label at its true millimetre size, so the iframe is
// sized to that natural pixel size and the whole frame is scaled to fill the
// stage. Fit both axes: a jewellery strip is far wider than it is tall, and
// scaling on the longer side alone leaves it a hairline.
const MM_PER_PX = 25.4 / 96

const preview = computed(() => {
  const naturalWidth = Math.max(1, Number(settings.value.width || 50) / MM_PER_PX)
  const naturalHeight = Math.max(1, Number(settings.value.height || 30) / MM_PER_PX)
  const fit = Math.min(stageBox.value.width / naturalWidth, stageBox.value.height / naturalHeight)
  const scale = Math.max(0.15, Math.min(12, fit * zoom.value))

  return { naturalWidth, naturalHeight, scale }
})

// Sheet takes the scaled footprint; the frame keeps its natural size and is
// scaled into it, so the label fills the paper instead of floating in it.
const sheetStyle = computed(() => ({
  width: `${Math.round(preview.value.naturalWidth * preview.value.scale)}px`,
  height: `${Math.round(preview.value.naturalHeight * preview.value.scale)}px`,
}))

const frameStyle = computed(() => ({
  width: `${preview.value.naturalWidth}px`,
  height: `${preview.value.naturalHeight}px`,
  transform: `scale(${preview.value.scale})`,
  transformOrigin: 'top left',
}))

function nudgeZoom(step) {
  zoom.value = Math.min(3, Math.max(0.35, Math.round((zoom.value + step) * 100) / 100))
}

async function loadData() {
  const response = await fetch(props.dataUrl, { headers: { Accept: 'application/json' } })
  const data = await response.json()
  suppressAutoSave.value = true
  settings.value = data.settings
  barcodeTypes.value = data.barcodeTypes
  qtySources.value = data.qtySources || {}
  fonts.value = data.fonts || {}
  fontWeights.value = data.fontWeights || {}
  installFontFaces(data.fontFaceCss)
  types.value = data.types || {}
  templateType.value = data.type || 'standard'
  templateName.value = data.templateName
  defaultTemplateKey.value = data.defaultTemplateKey
  previewBaseUrl.value = data.previewUrl
  printUrl.value = data.printUrl
  productSearchUrl.value = data.productSearchUrl
  productOptions.value = data.sampleProduct ? [data.sampleProduct] : []
  selectedProduct.value = data.sampleProduct
  selectedProductId.value = data.sampleProduct ? String(data.sampleProduct.id) : ''
  activeSection.value = sections.value[0].key
  selectedElementKey.value = firstSelectableField()
  ready.value = true
  await loadProducts()
  suppressAutoSave.value = false
  refreshPreview()
}

// The designer draws every font option in its own face, which means the faces
// have to exist on this page too, not only inside the print view.
function installFontFaces(css) {
  if (!css) return
  const id = 'bcx-font-faces'
  const style = document.getElementById(id) || document.createElement('style')
  style.id = id
  style.textContent = css
  if (!style.parentNode) document.head.appendChild(style)
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
  selectedElementKey.value = firstSelectableField()
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

function applySelectedProduct(id) {
  selectedProductId.value = String(id)
  selectedProduct.value = productOptions.value.find((item) => String(item.id) === String(id)) || null
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

// The right hand inspector edits whatever is selected on the left, and the two
// types keep their selectable things in different places.
function firstSelectableField() {
  if (templateType.value === 'standard') {
    const visible = ['product_name', 'product_name_arabic', 'barcode', 'company_name', 'logo', 'price', 'price_arabic', 'size']
      .filter((key) => settings.value[key]?.visible)

    return visible[0] || typeUi.value.fallbackSelection
  }

  const fields = Object.entries(settings.value.fields || {})
    .filter(([, field]) => field?.visible)
    .sort((a, b) => Number(a[1].order ?? 99) - Number(b[1].order ?? 99))

  return fields[0]?.[0] || typeUi.value.fallbackSelection
}

// The proof is scaled to whatever room the stage actually has. Measuring beats
// assuming: a fixed guess overflows the moment the window, the sidebar or a
// drawer takes the space away, and drags the whole grid off the page with it.
watch(ready, async (isReady) => {
  if (!isReady) return
  await nextTick()
  if (!stageEl.value) return

  stageObserver = new ResizeObserver(([entry]) => {
    const box = entry?.contentRect
    if (box?.width > 0 && box?.height > 0) {
      stageBox.value = { width: box.width, height: box.height }
    }
  })
  stageObserver.observe(stageEl.value)
})

onMounted(loadData)
onBeforeUnmount(() => {
  clearAutoSaveTimer()
  stageObserver?.disconnect()
})
</script>

<style>
/*
  Layout only. Every visual token lives in the shared .bcx system
  (resources/views/components/barcode/premium.blade.php) so the designer, the
  template list and the print cart stay one design.
*/
.bcx-designer {
    display: flex;
    flex-direction: column;
    min-height: 560px;
    max-width: 100%;
    /* Containment is the point: children can no longer widen the shell, and the
       breakpoints below measure this box instead of the window. */
    container: bcx-designer / inline-size;
}

.bcx-designer__body {
    display: grid;
    /* Container query units so the columns react to the space the page actually
       gives the shell, not to the window - the app sidebar can collapse or
       expand underneath us and the window width never changes. */
    grid-template-columns: 54px clamp(230px, 22cqw, 300px) minmax(0, 1fr) clamp(230px, 22cqw, 304px);
    flex: 1;
    min-height: 0;
}

/* Every track is allowed to shrink; nothing inside may push the grid wider. */
.bcx-designer__body>* {
    min-width: 0;
    min-height: 0;
}

.bcx-designer .bcx-drawer {
    max-height: 72vh;
}

/* A proof zoomed past the fit scale scrolls inside the stage rather than
   dragging the whole layout sideways. */
.bcx-designer .bcx-stage {
    overflow: auto;
    padding: 20px;
}

/* The label is the affordance; on a cramped bar the arrow alone still reads. */
@container bcx-designer (max-width: 560px) {
    .bcx-back__label {
        display: none;
    }

    .bcx-back {
        padding-inline: 9px;
    }
}

.bcx-status__zoom {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Breakpoints are on the shell, not the viewport, for the same reason.
   A drawer that has moved under the stage is far too wide for one field per
   row, so its contents flow into columns instead of stretching. Each section
   renders a single wrapper div, which is the element that becomes the grid. */
@container bcx-designer (max-width: 1180px) {
    .bcx-designer__body {
        grid-template-columns: 54px minmax(0, 1fr) clamp(230px, 26cqw, 290px);
    }

    .bcx-designer__body>.bcx-drawer:not(.bcx-drawer--end) {
        grid-row: 2;
        grid-column: 1 / -1;
        border-inline-end: 0;
        border-top: 1px solid var(--bcx-line);
        max-height: 40vh;
    }

    .bcx-designer__body>.bcx-drawer:not(.bcx-drawer--end)>div {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 0 22px;
        align-content: start;
    }

    .bcx-designer__body>.bcx-drawer:not(.bcx-drawer--end)>div>.bcx-drawer__title,
    .bcx-designer__body>.bcx-drawer:not(.bcx-drawer--end)>div>.bcx-note {
        grid-column: 1 / -1;
    }
}

@container bcx-designer (max-width: 820px) {
    .bcx-designer__body {
        grid-template-columns: 54px minmax(0, 1fr);
    }

    .bcx-designer__body>.bcx-drawer--end {
        grid-row: 3;
        grid-column: 1 / -1;
        border-inline-start: 0;
        border-top: 1px solid var(--bcx-line);
        max-height: 40vh;
    }

    .bcx-designer__body>.bcx-drawer--end>div {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 0 22px;
        align-content: start;
    }

    .bcx-designer__body>.bcx-drawer--end>div>.bcx-drawer__title,
    .bcx-designer__body>.bcx-drawer--end>div>.bcx-note {
        grid-column: 1 / -1;
    }
}

@container bcx-designer (max-width: 560px) {
    .bcx-designer__body {
        grid-template-columns: 1fr;
    }

    /* Single column: drop the row pinning so the panes fall in source order —
       rail, the group being edited, the proof, then its inspector. Selectors
       mirror the wider breakpoints so they win on order, not specificity. */
    .bcx-designer__body>.bcx-drawer:not(.bcx-drawer--end),
    .bcx-designer__body>.bcx-drawer--end,
    .bcx-designer__body>.bcx-rail,
    .bcx-designer__body>.bcx-stage {
        grid-row: auto;
        grid-column: 1;
    }

    /* One field per row again at this width. */
    .bcx-designer__body>.bcx-drawer>div {
        display: block;
    }

    .bcx-designer .bcx-rail {
        flex-direction: row;
        justify-content: center;
        border-inline-end: 0;
        border-bottom: 1px solid var(--bcx-line);
    }

    .bcx-designer .bcx-rail__btn.is-active::before {
        inset-inline-start: 9px;
        inset-inline-end: 9px;
        top: auto;
        bottom: -8px;
        width: auto;
        height: 3px;
        border-radius: 3px 3px 0 0;
    }

    .bcx-designer .bcx-drawer,
    .bcx-designer .bcx-drawer--end {
        grid-column: 1;
        border-inline: 0;
        border-bottom: 1px solid var(--bcx-line);
    }
}
</style>
