/**
 * Composable for interacting with Livewire components
 */
export function useLivewire(componentName = null) {
    const getLivewire = () => {
        return window.Livewire || window.livewire || (window.Alpine && window.Alpine.$wire)
    }

    const getComponent = () => {
        const livewire = getLivewire()
        if (!livewire) {
            return null
        }

        if (componentName) {
            return livewire.find ? livewire.find(componentName) : null
        } else {
            // Try to find component using @this or first available
            if (window.Livewire && window.Livewire.all) {
                const components = window.Livewire.all()
                return components.length > 0 ? components[0] : null
            }
            // Fallback: try to find by component name or use first available
            if (window.Livewire?.find) {
                // Try common component name patterns
                const names = ['purchase.page', 'purchase-page', 'purchasePage']
                for (const name of names) {
                    const comp = window.Livewire.find(name)
                    if (comp) return comp
                }
            }
            return null
        }
    }

    const call = async (method, ...params) => {
        const livewire = getLivewire()
        if (!livewire) {
            console.error('Livewire is not available')
            return Promise.reject(new Error('Livewire is not available'))
        }

        let component = getComponent()
        if (!component) {
            // Try using @this syntax for Livewire v3 - try different component names
            if (window.Livewire && window.Livewire.find) {
                const names = ['purchase.page', 'purchase-page', 'purchasePage']
                for (const name of names) {
                    const comp = window.Livewire.find(name)
                    if (comp && comp.call) {
                        component = comp
                        break
                    }
                }
            }
            if (!component) {
                return Promise.reject(new Error('No Livewire component found'))
            }
        }

        if (component.call) {
            return component.call(method, ...params)
        } else if (component.$wire && component.$wire.call) {
            return component.$wire.call(method, ...params)
        } else {
            // Fallback: use Livewire.dispatch to trigger method
            return new Promise((resolve, reject) => {
                const eventName = `livewire-call-${method}-${Date.now()}`
                const handler = (event) => {
                    window.removeEventListener(eventName, handler)
                    if (event.detail.success) {
                        resolve(event.detail.result)
                    } else {
                        reject(new Error(event.detail.error))
                    }
                }
                window.addEventListener(eventName, handler)
                livewire.dispatch(`call-${method}`, { params, eventName })
            })
        }
    }

    const set = (property, value) => {
        const livewire = getLivewire()
        if (!livewire) {
            console.error('Livewire is not available')
            return
        }

        const component = getComponent()
        if (component) {
            if (component.set) {
                component.set(property, value)
            } else if (component.$wire && component.$wire.set) {
                component.$wire.set(property, value)
            }
        } else {
            // Fallback: try to find by component name
            if (window.Livewire && window.Livewire.find) {
                const comp = window.Livewire.find('purchase.page')
                if (comp && comp.set) {
                    comp.set(property, value)
                }
            }
        }
    }

    const get = (property) => {
        const livewire = getLivewire()
        if (!livewire) {
            return null
        }

        const component = getComponent()
        if (component) {
            if (component.get) {
                return component.get(property)
            } else if (component.$wire && component.$wire.get) {
                return component.$wire.get(property)
            } else if (component[property] !== undefined) {
                return component[property]
            }
        }
        
        // Fallback: try to get from window.purchasePageData
        if (window.purchasePageData && window.purchasePageData[property] !== undefined) {
            return window.purchasePageData[property]
        }
        
        return null
    }

    const dispatch = (event, data = {}) => {
        const livewire = getLivewire()
        if (!livewire) {
            console.error('Livewire is not available')
            return
        }
        
        if (livewire.dispatch) {
            livewire.dispatch(event, data)
        } else {
            window.dispatchEvent(new CustomEvent(event, { detail: data }))
        }
    }

    const on = (event, callback) => {
        const handler = (e) => {
            callback(e.detail || e)
        }
        
        window.addEventListener(event, handler)
        return () => {
            window.removeEventListener(event, handler)
        }
    }

    return {
        call,
        set,
        get,
        dispatch,
        on
    }
}
