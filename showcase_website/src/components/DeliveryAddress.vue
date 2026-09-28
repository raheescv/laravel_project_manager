<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'

import { fetchBuildings, fetchStreets, fetchZones } from '@/api/resources'
import { i18n, t } from '@/i18n'
import { useBagStore } from '@/stores/bag'
import AddressCombo from './AddressCombo.vue'

/**
 * Delivery address as Qatar writes it: the zone / street / building numbers
 * from the blue plate by the door, plus the city. Lists come from the Qatar
 * National Address Service through the API; when it is unavailable the same
 * fields simply take typed numbers.
 */
const bag = useBagStore()
const c = bag.customer

/** Lists survive the drawer closing — the geography doesn't change mid-visit. */
const lists = reactive({ zones: [], streets: {}, buildings: {} })
const state = reactive({ zones: 'idle', streets: false, buildings: false })

const CITIES = [
  ['Doha', 'الدوحة'],
  ['Al Rayyan', 'الريان'],
  ['Al Wakrah', 'الوكرة'],
  ['Lusail', 'لوسيل'],
  ['Al Khor', 'الخور'],
  ['Umm Salal', 'أم صلال'],
  ['Al Daayen', 'الضعاين'],
  ['Al Shamal', 'الشمال'],
  ['Al Shahaniya', 'الشحانية'],
  ['Mesaieed', 'مسيعيد'],
  ['Dukhan', 'دخان'],
]

const zoneRef = ref(null)
const streetRef = ref(null)
const buildingRef = ref(null)

const localName = (row) => (i18n.lang === 'ar' ? row.name_ar || row.name_en : row.name_en || row.name_ar) || ''
const clean = (v) => String(v ?? '').trim()

const lookupOn = computed(() => state.zones === 'ready')
const zone = computed(() => lists.zones.find((z) => String(z.number) === clean(c.zoneNumber)) || null)
const streetKey = computed(() => `${clean(c.zoneNumber)}/${clean(c.streetNumber)}`)
const streets = computed(() => lists.streets[clean(c.zoneNumber)] || [])
const buildings = computed(() => lists.buildings[streetKey.value] || [])
const pin = computed(() => {
  const b = buildings.value.find((x) => x.number.toLowerCase() === clean(c.buildingNumber).toLowerCase())
  return b && b.lat != null && b.lng != null ? b : null
})
const mapUrl = computed(() => (pin.value ? `https://www.google.com/maps?q=${pin.value.lat},${pin.value.lng}` : null))

const zoneOptions = computed(() => lists.zones.map((z) => ({ value: z.number, primary: localName(z) })))
const streetOptions = computed(() =>
  streets.value.map((s) => ({ value: s.number, primary: localName(s) || t('addrStreetNo', { n: s.number }) })),
)
const buildingOptions = computed(() =>
  buildings.value.map((b) => ({ value: b.number, primary: t('addrBuildingNo', { n: b.number }) })),
)
const cityOptions = computed(() =>
  CITIES.map(([en, ar]) => (i18n.lang === 'ar' ? { value: ar, primary: en } : { value: en, primary: ar })),
)

async function loadZones() {
  if (state.zones === 'ready' || state.zones === 'loading') return
  state.zones = 'loading'
  try {
    lists.zones = (await fetchZones()) || []
    state.zones = lists.zones.length ? 'ready' : 'off'
  } catch {
    state.zones = 'off'
  }
}

async function loadStreets(z) {
  if (!lookupOn.value || !/^\d+$/.test(z) || lists.streets[z]) return
  state.streets = true
  try {
    lists.streets[z] = (await fetchStreets(z)) || []
  } catch {
    /* typed street numbers still work */
  } finally {
    state.streets = false
  }
}

async function loadBuildings(z, s) {
  const key = `${z}/${s}`
  if (!lookupOn.value || !/^\d+$/.test(z) || !/^\d+$/.test(s) || lists.buildings[key]) return
  state.buildings = true
  try {
    lists.buildings[key] = (await fetchBuildings(z, s)) || []
  } catch {
    /* typed building numbers still work */
  } finally {
    state.buildings = false
  }
}

watch(
  () => [lookupOn.value, clean(c.zoneNumber)],
  ([on, z]) => on && zone.value && loadStreets(z),
  { immediate: true },
)
watch(
  () => [lookupOn.value, clean(c.zoneNumber), clean(c.streetNumber)],
  ([on, z, s]) => on && streets.value.some((x) => String(x.number) === s) && loadBuildings(z, s),
  { immediate: true },
)

/** A different zone or street makes the numbers after it meaningless. */
function setZone(v) {
  if (clean(v) !== clean(c.zoneNumber)) {
    c.streetNumber = ''
    c.buildingNumber = ''
  }
  c.zoneNumber = v
}

function setStreet(v) {
  if (clean(v) !== clean(c.streetNumber)) c.buildingNumber = ''
  c.streetNumber = v
}

/** Template refs arrive unwrapped, so this takes the component itself. */
const next = (combo) => requestAnimationFrame(() => combo?.focus())

onMounted(loadZones)
</script>

<template>
  <div class="dlv">
    <!-- the blue plate, filled in as they type -->
    <div class="plate" :class="{ 'is-pinned': pin }" dir="ltr" aria-hidden="true">
      <button type="button" class="plate__cell" tabindex="-1" @click="zoneRef?.focus()">
        <span class="plate__lbl"><span>Zone</span><span>منطقة</span></span>
        <span class="plate__num" :class="{ 'is-empty': !clean(c.zoneNumber) }">{{ clean(c.zoneNumber) || '—' }}</span>
      </button>
      <button type="button" class="plate__cell" tabindex="-1" @click="streetRef?.focus()">
        <span class="plate__lbl"><span>Street</span><span>شارع</span></span>
        <span class="plate__num" :class="{ 'is-empty': !clean(c.streetNumber) }">{{ clean(c.streetNumber) || '—' }}</span>
      </button>
      <button type="button" class="plate__cell" tabindex="-1" @click="buildingRef?.focus()">
        <span class="plate__lbl"><span>Building</span><span>مبنى</span></span>
        <span class="plate__num" :class="{ 'is-empty': !clean(c.buildingNumber) }">{{ clean(c.buildingNumber) || '—' }}</span>
      </button>
    </div>

    <div class="dlv__meta">
      <span v-if="zone" class="dlv__area">{{ localName(zone) }}</span>
      <span v-else class="dlv__hint">{{ t('addrPlateHint') }}</span>
      <a v-if="mapUrl" :href="mapUrl" target="_blank" rel="noopener" class="dlv__pin">
        <svg viewBox="0 0 16 16" aria-hidden="true">
          <path d="M8 1.5a4.5 4.5 0 0 0-4.5 4.5c0 3.2 4.5 8.5 4.5 8.5s4.5-5.3 4.5-8.5A4.5 4.5 0 0 0 8 1.5Zm0 6.2a1.7 1.7 0 1 1 0-3.4 1.7 1.7 0 0 1 0 3.4Z" fill="currentColor" />
        </svg>
        {{ t('addrPinned') }}
      </a>
    </div>

    <div class="addr">
      <AddressCombo
        id="addr-zone"
        ref="zoneRef"
        :label="t('zoneNumber')"
        :model-value="c.zoneNumber"
        :options="zoneOptions"
        :loading="state.zones === 'loading'"
        :placeholder="lookupOn ? t('addrZonePh') : ''"
        inputmode="numeric"
        :maxlength="3"
        numeric
        @update:model-value="setZone"
        @pick="next(streetRef)"
      />
      <AddressCombo
        id="addr-street"
        ref="streetRef"
        :label="t('streetNumber')"
        :model-value="c.streetNumber"
        :options="streetOptions"
        :loading="state.streets"
        inputmode="numeric"
        :maxlength="4"
        numeric
        @update:model-value="setStreet"
        @pick="next(buildingRef)"
      />
      <AddressCombo
        id="addr-building"
        ref="buildingRef"
        v-model="c.buildingNumber"
        :label="t('buildingNumber')"
        :options="buildingOptions"
        :loading="state.buildings"
        :maxlength="10"
        numeric
      />
      <AddressCombo
        id="addr-city"
        class="addr__city"
        v-model="c.city"
        :label="t('city')"
        :options="cityOptions"
        :maxlength="100"
        :placeholder="t('addrCityPh')"
      />
    </div>
  </div>
</template>
