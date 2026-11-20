import { reactive } from 'vue'

/**
 * Size display system (UK / US / EU) — a global, per-device header setting.
 * Display only: stock is stored, filtered and put in URLs in its own sizes,
 * which are read as EU. A size the chart has no EU row for (40.5, 10C, M …)
 * shows as stored whatever the setting.
 *
 * Men's footwear chart; below EU 38.5 it continues with the women's chart's
 * UK sizes, and US there is UK + 0.5 like the men's rows, so the run stays in order.
 */
const CHART = {
  // EU: [UK, US]
  35: ['2.5', '3'],
  35.5: ['3', '3.5'],
  36: ['3.5', '4'],
  37: ['4', '4.5'],
  37.5: ['4.5', '5'],
  38: ['5', '5.5'],
  38.5: ['6', '6.5'],
  39: ['6.5', '7'],
  40: ['7', '7.5'],
  41: ['7.5', '8'],
  42: ['8', '8.5'],
  43: ['8.5', '9'],
  43.5: ['9', '9.5'],
  44: ['9.5', '10'],
  44.5: ['10', '10.5'],
  45: ['10.5', '11'],
  45.5: ['11', '11.5'],
  46: ['11.5', '12'],
  46.5: ['12', '12.5'],
  47: ['12.5', '13'],
}

export const SIZE_SYSTEMS = ['UK', 'US', 'EU']
const STORAGE_KEY = 'sr.sizing'

function stored() {
  try {
    const v = localStorage.getItem(STORAGE_KEY)
    return SIZE_SYSTEMS.includes(v) ? v : 'EU'
  } catch {
    return 'EU'
  }
}

/** Reactive — templates that call sizeLabel() re-render when the system changes. */
export const sizing = reactive({ system: stored() })

export function setSizeSystem(system) {
  if (!SIZE_SYSTEMS.includes(system)) return
  sizing.system = system
  try {
    localStorage.setItem(STORAGE_KEY, system)
  } catch {
    /* private mode */
  }
}

/** A stored (EU) size as the shopper reads it: "42" → "8" in UK, "8.5" in US. */
export function sizeLabel(size) {
  const raw = String(size ?? '').trim()
  if (sizing.system === 'EU' || !/^\d+(\.\d+)?$/.test(raw)) return raw
  const row = CHART[Number(raw)]
  if (!row) return raw
  return sizing.system === 'UK' ? row[0] : row[1]
}
