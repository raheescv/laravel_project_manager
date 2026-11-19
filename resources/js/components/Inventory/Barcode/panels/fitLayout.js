import { STANDARD_ELEMENTS } from './labels'

// Element boxes are CSS pixels inside the label, which prints at its true mm
// size (96 px per inch). The label's 2px border sits inside that size, so the
// elements only get what is left inside it.
const PX_PER_MM = 96 / 25.4
const LABEL_BORDER = 2
const EDGE_MARGIN = 2
const ROW_GAP = 1.5
const LINE_HEIGHT = 1.15
const MIN_FONT_SIZE = 5

const round = (value, step) => Number((Math.round(value / step) * step).toFixed(1))

const overlapsHorizontally = (a, b) => a.left < b.left + b.width && b.left < a.left + a.width

/**
 * Re-flows the visible elements of a standard sticker so none overlap and
 * together they fill the label's current width and height.
 *
 * Elements are read top to bottom into rows: one joins the row above when it
 * shares its height band without colliding with anything already in it (the two
 * prices side by side), otherwise it starts a row of its own. Rows are then
 * stacked from the top edge and stretched to the label height, the columns to
 * its width, and font sizes follow the smaller stretch so text never outgrows
 * its box. Hidden elements are left where they are.
 *
 * Returns false when there is nothing visible to arrange.
 */
export function fitStandardLayout(settings) {
  const elements = settings.elements || {}
  const items = STANDARD_ELEMENTS.map(({ key }) => key)
    .filter((key) => settings[key]?.visible && elements[key])
    .map((key) => {
      const box = elements[key]
      const fontSize = key === 'barcode' ? 0 : Number(settings[key]?.font_size || 0)

      return {
        key,
        top: Number(box.top || 0),
        left: Number(box.left || 0),
        width: Math.max(1, Number(box.width || 0)),
        height: Math.max(1, Number(box.height || 0), fontSize * LINE_HEIGHT),
      }
    })
    .sort((a, b) => a.top - b.top || a.left - b.left)

  if (!items.length) return false

  const rows = []
  items.forEach((item) => {
    const row = rows[rows.length - 1]
    if (row && item.top < row.bottom && !row.items.some((other) => overlapsHorizontally(other, item))) {
      row.items.push(item)
      row.bottom = Math.max(row.bottom, item.top + item.height)
      row.height = Math.max(row.height, item.height)
    } else {
      rows.push({ items: [item], bottom: item.top + item.height, height: item.height })
    }
  })

  const minLeft = Math.min(...items.map((i) => i.left))
  const maxRight = Math.max(...items.map((i) => i.left + i.width))
  const innerWidth = Number(settings.width || 0) * PX_PER_MM - LABEL_BORDER * 2 - EDGE_MARGIN * 2
  const innerHeight = Number(settings.height || 0) * PX_PER_MM - LABEL_BORDER * 2 - EDGE_MARGIN * 2
  const rowSpace = innerHeight - ROW_GAP * (rows.length - 1)
  if (innerWidth <= 0 || rowSpace <= 0) return false

  const scaleX = innerWidth / (maxRight - minLeft)
  const scaleY = rowSpace / rows.reduce((sum, row) => sum + row.height, 0)
  const fontScale = Math.min(scaleX, scaleY)

  let top = EDGE_MARGIN
  rows.forEach((row) => {
    const rowHeight = row.height * scaleY
    row.items.forEach((item) => {
      Object.assign(elements[item.key], {
        top: round(top, 0.1),
        left: round(EDGE_MARGIN + (item.left - minLeft) * scaleX, 0.1),
        width: round(item.width * scaleX, 0.1),
        height: round(rowHeight, 0.1),
      })

      const field = settings[item.key]
      if (field?.font_size !== undefined) {
        field.font_size = Math.max(MIN_FONT_SIZE, round(Number(field.font_size || 0) * fontScale, 0.5))
      }
    })
    top += rowHeight + ROW_GAP
  })

  return true
}
