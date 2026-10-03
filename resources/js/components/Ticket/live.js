import { onBeforeUnmount, onMounted, ref } from 'vue'

/**
 * Live link to the tenant's ticket channel. Any change made on another device
 * or by another user arrives as `ticket.activity` ({ action, ticket_id }) and is
 * handed to `onActivity`. After a dropped connection or a tab coming back to
 * the front, `onResync` runs once, because events sent meanwhile were missed.
 */
export function useTicketLive({ onActivity, onResync }) {
    const channelName = document.getElementById('ticket-console')?.dataset.liveChannel
    const connected = ref(false)
    let missedEvents = false

    const pusher = () => window.Echo?.connector?.pusher

    function onState({ previous, current }) {
        connected.value = current === 'connected'
        if (previous === 'connected' && !connected.value) missedEvents = true
        if (connected.value && missedEvents) {
            missedEvents = false
            onResync()
        }
    }

    function onVisible() {
        if (document.visibilityState === 'visible') onResync()
    }

    onMounted(() => {
        if (!channelName || !window.Echo) return
        window.Echo.private(channelName).listen('.ticket.activity', onActivity)
        pusher()?.connection.bind('state_change', onState)
        connected.value = pusher()?.connection.state === 'connected'
        document.addEventListener('visibilitychange', onVisible)
    })

    onBeforeUnmount(() => {
        if (!channelName || !window.Echo) return
        window.Echo.leave(channelName)
        pusher()?.connection.unbind('state_change', onState)
        document.removeEventListener('visibilitychange', onVisible)
    })

    return { connected }
}
