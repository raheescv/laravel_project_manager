import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const resolveMetaContent = (name) => document.querySelector(`meta[name="${name}"]`)?.content;

const pusherKey = import.meta.env.PUSHER_APP_KEY || resolveMetaContent('pusher-key');
const pusherCluster = import.meta.env.PUSHER_APP_CLUSTER || resolveMetaContent('pusher-cluster');

if (pusherKey) {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: true,
    });
} else {
    console.warn('Pusher key not found. Echo was not initialized.');
}
