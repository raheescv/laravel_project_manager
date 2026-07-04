import { defineStore } from 'pinia'

import { fetchBranches } from '@/api/resources'

const STORAGE_KEY = 'showcase.branch_id'

/** Branch name with fallbacks — real data sometimes has an empty name. */
export function displayName(branch) {
  if (!branch) return ''
  return branch.name || branch.location || branch.code || `Branch ${branch.id}`
}

export const useBranchStore = defineStore('branch', {
  state: () => ({
    branches: [],
    currentId: Number(localStorage.getItem(STORAGE_KEY)) || null,
    loading: false,
    error: null,
    modalOpen: false,
  }),

  getters: {
    current(state) {
      return state.branches.find((b) => b.id === state.currentId) || null
    },
    currentName() {
      return displayName(this.current) || 'Choose a branch'
    },
  },

  actions: {
    async load() {
      this.loading = true
      this.error = null
      try {
        this.branches = (await fetchBranches()) || []
        // Saved branch no longer exists → force re-pick.
        if (this.currentId && !this.branches.some((b) => b.id === this.currentId)) {
          this.currentId = null
          localStorage.removeItem(STORAGE_KEY)
        }
        if (!this.currentId) this.modalOpen = true
      } catch (e) {
        this.error = e.message
      } finally {
        this.loading = false
      }
    },

    select(id) {
      this.currentId = id
      localStorage.setItem(STORAGE_KEY, String(id))
      this.modalOpen = false
    },

    openModal() {
      this.modalOpen = true
    },
  },
})
