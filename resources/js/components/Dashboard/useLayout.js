import { ref } from 'vue'

const clone = (value) => JSON.parse(JSON.stringify(value))

/** A layout item's stable id: the widget key, or the divider's own id. */
export const itemId = (item) => item.key ?? item.id

/**
 * The editable layout: every change goes through commit() so it lands on the
 * undo stack and is autosaved (debounced). A failed save keeps the local
 * layout and reports `failed`, so the user never loses an arrangement.
 */
export function useLayout(initial, { save, persist, onError }) {
    const layout = ref(clone(initial))
    const undoStack = ref([])
    const redoStack = ref([])
    const saveState = ref('saved')
    let timer = null

    function commit(mutate) {
        const before = JSON.stringify(layout.value)
        const next = clone(layout.value)
        mutate(next)
        if (JSON.stringify(next) === before) return false
        undoStack.value.push(before)
        if (undoStack.value.length > 100) undoStack.value.shift()
        redoStack.value = []
        layout.value = next
        queueSave()
        return true
    }

    function travel(from, to) {
        if (!from.value.length) return false
        to.value.push(JSON.stringify(layout.value))
        layout.value = JSON.parse(from.value.pop())
        queueSave()
        return true
    }

    const undo = () => travel(undoStack, redoStack)
    const redo = () => travel(redoStack, undoStack)

    function queueSave() {
        if (!persist()) return
        saveState.value = 'saving'
        clearTimeout(timer)
        timer = setTimeout(flush, 600)
    }

    async function flush() {
        clearTimeout(timer)
        if (!persist()) return
        const snapshot = clone(layout.value)
        saveState.value = 'saving'
        try {
            await save(snapshot)
            if (JSON.stringify(snapshot) === JSON.stringify(layout.value)) saveState.value = 'saved'
        } catch (error) {
            saveState.value = 'failed'
            onError?.(error)
        }
    }

    return { layout, undoStack, redoStack, saveState, commit, undo, redo, flush, queueSave }
}
