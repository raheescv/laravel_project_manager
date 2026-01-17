/**
 * Route helper function for Vue components
 * Uses Ziggy if available, otherwise falls back to manual URL construction
 */
export function getRoute(name, params = {}) {
    // Try to use Ziggy route if available
    if (window.route && typeof window.route === 'function') {
        try {
            return window.route(name, params)
        } catch (e) {
            // Fallback if route doesn't exist
        }
    }

    // Fallback to manual URL construction for common routes
    const routes = {
        'account::list': '/account/list',
        'product::list': '/product/list',
        'purchase::print': (id) => `/purchase/print/${id}`,
        'purchase::barcode-print': (id) => `/purchase/barcode-print/${id}`,
    }

    if (routes[name]) {
        if (typeof routes[name] === 'function') {
            return routes[name](params.id || params)
        }
        return routes[name]
    }

    // If no route found, try to construct from name
    const routeName = name.replace('::', '/').replace(/::/g, '/')
    return `/${routeName}`
}
